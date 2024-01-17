<?php

namespace App\Console;

use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Nette\Mail\Message;
use Nette\Mail\SendException;
use Nette\Mail\Mailer;
use Nette\Database\Explorer;
use Latte\Engine;
use Nette\Database\Table\Selection;
use App\Model\VariablesStore;

final class EmailCronCommand extends Command
{

    protected static $defaultName = 'email:send';
	private $mailer;

    public function __construct(
			Mailer $mailer,
			private Explorer $database,
			private VariablesStore $variablesStore
	)  {
		parent::__construct();
        $this->mailer = $mailer;
    }
	

	protected function configure(): void
	{
		$this->setName('email:send')
			->setDescription('Send email by cron');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{

		$output->writeln(\sprintf('Trying to send email...'));

		try {
            $this->prepareEmail();
			
			return 0;

		} catch (SendException $e) {

			$output->writeln(\sprintf(
				'<error> Error occurred: </error>',
				$e->getMessage(),
			));
			return 1;
		}
	}

	public function prepareEmail() {

		$starting_schedules = $this->getStartingSchedules();
		$ending_schedules = $this->getEndingSchedules();
		$buy_reminders = $this->getBuyReminders();

		if ((bool) count($starting_schedules)) {
			$this->generateSchedulesEmail($starting_schedules, 'start');
		}
		if ((bool) count($ending_schedules)) {
			$this->generateSchedulesEmail($ending_schedules, 'stop');
		}
		if ((bool) count($starting_schedules)) {
			$this->generateBuyReminderEmail($buy_reminders, 'buy');
		}
	}

	public function generateSchedulesEmail(Selection|array $schedules_data, $action)
	{

        $admin_notice_params = [];
        $a_counter = 0;

        $action_template = match ($action) {
            'start' => 'starting_schedules_email.latte',
            'stop' => 'ending_schedules_email.latte',
            'buy' => 'buy_reminders_email.latte',
        };
        $admin_action_template = match ($action) {
            'start' => 'starting_schedules_email_admin.latte',
            'stop' => 'ending_schedules_email_admin.latte',
        };

		foreach ($schedules_data as $schedule_item) {


            $first_name = $schedule_item->user->first_name;
            $last_name = $schedule_item->user->last_name;
            $email = $schedule_item->user->email;
            $element_title = $schedule_item->element->title;

			$subject = 'Напоминание для ' . $first_name . ' ' . $last_name . ': ' . ucfirst($element_title);
			$template_params = [
				'date_from' => $schedule_item->date_from, 
				'date_to' => $schedule_item->date_to, 
				'receipt_time' => $schedule_item->receipt_time, 
				'element_title' => $schedule_item->element->title, 
				'element_dosage' => $schedule_item->dosage,
			];
			
			$this->sendMail($email, null, $subject, $action_template, $template_params);

            $admin_notice_params[$a_counter]['first_name'] = $first_name;
            $admin_notice_params[$a_counter]['last_name'] = $first_name;
            $admin_notice_params[$a_counter]['date_from'] = $schedule_item->date_from;
            $admin_notice_params[$a_counter]['date_to'] = $schedule_item->date_to;
            $admin_notice_params[$a_counter]['receipt_time'] = $schedule_item->receipt_time;
            $admin_notice_params[$a_counter]['element_title'] = $schedule_item->element->title;
            $admin_notice_params[$a_counter]['element_dosage'] = $schedule_item->dosage;
            $a_counter++;
        }

        if (count($admin_notice_params) && ($action_template != 'buy_reminders_email.latte')) {

            $params = [
                'customers' => $admin_notice_params,
            ];
            
            $this->sendMail($this->variablesStore->admin_email, null, 'Напоминание про пользователей', $admin_action_template, $params);
        }
	}

	public function generateBuyReminderEmail(Selection|array $buy_reminders, $email_template = 'buy_reminders_email.latte')
	{

		foreach ($buy_reminders as $item) {
			$email = $this->variablesStore->admin_email;

			$subject = 'Напоминание о закупке: ' . ucfirst($item['element_title']);
			$template_params = [
				'user_name' => $item['user_name'],
				'element_title' => $item['element_title'],
				'date_from' => $item['date_from'],
			];
			
			$this->sendMail($email, $admin_email = $this->variablesStore->admin_email, $subject, $email_template, $template_params);
        }
	}

	public function sendMail(string $email, ?string $admin_email, string $subject, string $email_template, array $template_params): void 
	{

		$latte = new Engine;
		$mail = new Message;

		if (($email !== $admin_email) && ($admin_email !== null)) {

			$mail->setFrom($this->variablesStore->server_sender_email)
				->addTo($email)
				->addCc($admin_email)
				->setSubject($subject)
				->setHtmlBody(
					$latte->renderToString(__DIR__ . '/emails/' . $email_template, $template_params),
				);

		} else {

			$mail->setFrom($this->variablesStore->server_sender_email)
				->addTo($email)
				->setSubject($subject)
				// ->setBody($text);
				->setHtmlBody(
					$latte->renderToString(__DIR__ . '/emails/' . $email_template, $template_params),
				);
		}

		try { 

			$this->mailer->send($mail);

		} catch (SendException $e) {

			echo " ERROR " . $e->getMessage(); 

		}		
    }

	public function getAllShedules()
	{
		return $this->database
			->table('shedule')
			->order('id');

	}

	public function getStartingSchedules()
	{
		$today = new DateTime();
		$day_after = $today->add(new \DateInterval('P1D'));

		$starting_schedules = $this->database->table('shedule')->where('date_from', $day_after->format('Y-m-d'));

		return $starting_schedules;       

	}

	public function getEndingSchedules()
	{
		$today = new DateTime();
		$day_after = $today->add(new \DateInterval('P1D'));

		$ending_schedules = $this->database->table('shedule')->where('date_to', $day_after->format('Y-m-d'));

		return $ending_schedules;       

	}

	public function getBuyReminders()
	{
		$today = new DateTime();
		$buy_reminders = [];

		$buy_reminders_elements = $this->database->table('elements')->where('buy_reminder != ?', 0);

		foreach($buy_reminders_elements as $element) {

			foreach ($element->related('shedule.element_id') as $schedule) {

				if ($schedule->date_from < $today) continue;

				$schedule_date_from = new DateTime($schedule->date_from);
				$interval = $today->diff($schedule_date_from);

				if ($interval->format('%d') == $element->buy_reminder) {

					$buy_reminders[$element->id]['element_title'] = $element->title;
					$buy_reminders[$element->id]['date_from'] = $schedule->date_from;
					$buy_reminders[$element->id]['user_name'] = $schedule->user->first_name . ' ' . $schedule->user->last_name;
				}
			}

		}
		return $buy_reminders;

	}



}
