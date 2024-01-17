<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;
use Nette\Neon\Neon;



final class PanelPresenter extends Nette\Application\UI\Presenter
{
    // private Nette\DI\Container $context;

	// public function injectContext(Nette\DI\Container $context)
	// {
	// 	$this->context = $context;
	// }

	// public function getContext(): Nette\DI\Container
	// {
	// 	return $this->context;
	// }



    public function __construct(
		private Nette\Database\Explorer $database,
	) {
	}

    protected function startup()
	{
		parent::startup();
		
		if (!$this->getUser()->isLoggedIn()) {
			$this->redirect('Sign:in');
		}
	}

    public function renderEdit(int $sheduleId): void
    {
        if (!$this->getUser()->isInRole('admin')) {
			$this->redirect('User:index');
		}

        $shedule = $this->database
            ->table('shedule')
			->where('id', $sheduleId);

        if (!$shedule) {
            $this->error('Пост не найден');
        }

        $this->getComponent('sheduleEditForm')
		    ->setDefaults($shedule->fetch());

    }
    public function renderCreate(int $customer): void
    {
        if (!$this->getUser()->isInRole('admin')) {
			$this->redirect('User:index');
		}

        if (!$customer) {
            $this->error('User не найден');
        }

        $this->getComponent('sheduleForm')
		    ->setDefaults([$customer]);

    }

    protected function createComponentSheduleForm(): Form
    {
        if (!$this->getUser()->isInRole('admin')) {
			$this->redirect('User:index');
		}

        $customer_id = $this->getHttpRequest()->getQuery('customer');

        $form = new Form;

        $members_raw = $this->database
            ->table('users')
            ->fetchPairs('id');

        foreach ($members_raw as $key => $member) {
            $members[$member->id] = $member->first_name . ' ' . $member->last_name;
        }
        $form->addMultiSelect('user_id', 'User:', $members)
            ->setDefaultValue($customer_id);

        
        $elements = $this->database
            ->table('elements')
            ->fetchPairs('id', 'title');        
        $form->addSelect('element_id', 'Element:', $elements);

        $form->addText('dosage', 'Дозировка:');

        $form->addDate('date_from', 'Date from:')
            ->setFormat('Y-m-d')
            ->setRequired('Пожалуйста, выберите дату от:');

        $form->addDate('date_to', 'Date to:')
            ->setFormat('Y-m-d')
            ->setRequired('Пожалуйста, выберите дату до:');

        $start_reminder_options = [
            '1' => 'за день',
            '0' => 'в день приема',
        ];
        $form->addSelect('start_reminder', 'Напомнить о приеме:', $start_reminder_options)
            ->setDefaultValue('1');


        $form->addText('receipt_time', 'Время приема:');

        $form->addCheckbox('published', 'Опубликовать:')
            ->setDefaultValue(true);


        $form->addSubmit('send', 'Save and public');
        $form->onSuccess[] = $this->sheduleFormSucceeded(...);

        return $form;
    }

   
    private function sheduleFormSucceeded(array $data): void
    {
        
        $sheduleId = $this->getParameter('sheduleId');
        $customer_id = $this->getHttpRequest()->getQuery('customer');

        if ($sheduleId) {
            $shedule = $this->database
                ->table('shedule')
                ->get($sheduleId);

            $shedule->update($data);

        } else {

            foreach($data['user_id'] as $key => $user_id) {
                $new_data = $data;
                $new_data['user_id'] = $user_id;
                
                $this->database
                ->table('shedule')
                ->insert($new_data);
            }


        }

        $this->flashMessage('Запись добавлена', 'success');
        $this->redirect('User:show', ['memberId' => $customer_id]);

    }

    protected function createComponentSheduleEditForm(): Form
    {
        if (!$this->getUser()->isInRole('admin')) {
			$this->redirect('User:index');
		}

        $customer_id = $this->getHttpRequest()->getQuery('customer');

        $form = new Form;

        $members_raw = $this->database
            ->table('users')
            ->fetchPairs('id');

        foreach ($members_raw as $key => $member) {
            $members[$member->id] = $member->first_name . ' ' . $member->last_name;
        }
        $form->addSelect('user_id', 'User:', $members)
            ->setDefaultValue($customer_id);

        
        $elements = $this->database
            ->table('elements')
            ->fetchPairs('id', 'title');        
        $form->addSelect('element_id', 'Element:', $elements);

        $form->addText('dosage', 'Дозировка:');

        $form->addDate('date_from', 'Date from:')
            ->setFormat('Y-m-d')
            ->setRequired('Пожалуйста, выберите дату от:');

        $form->addDate('date_to', 'Date to:')
            ->setFormat('Y-m-d')
            ->setRequired('Пожалуйста, выберите дату до:');

        $start_reminder_options = [
            '1' => 'за день',
            '0' => 'в день приема',
        ];
        $form->addSelect('start_reminder', 'Напомнить о приеме:', $start_reminder_options)
            ->setDefaultValue('1');


        $form->addText('receipt_time', 'Время приема:');

        $form->addCheckbox('published', 'Опубликовать:')
            ->setDefaultValue(true);


        $form->addSubmit('send', 'Save and public');
        $form->onSuccess[] = $this->sheduleEditFormSucceeded(...);

        return $form;
    }

    private function sheduleEditFormSucceeded(array $data): void
    {
        
        $sheduleId = $this->getParameter('sheduleId');

        $customer_id = $data['user_id'];

        $shedule = $this->database
            ->table('shedule')
            ->get($sheduleId);

        $shedule->update($data);

        $this->flashMessage('Запись обновлена', 'success');
        $this->redirect('User:show', ['memberId' => $customer_id]);

    }


    public function actionDelete(int $sheduleId )
    {
        if (!$this->getUser()->isInRole('admin')) {
			$this->redirect('User:index');
		}

        $customer_id = $this->database->fetchField('SELECT user_id FROM shedule WHERE id = ?', $sheduleId);

        $form = new Form;
        try {
                $this->flashMessage("Запись успешно удалена", 'success');
                $this->database->table('shedule')->where('id', $sheduleId)->delete();
                $this->redirect('User:show', ['memberId' => $customer_id]);

        } catch (Nette\Security\AuthenticationException $e) {
            $form->addError('При удалении возникла ошибка. Обратитесь к разработчику портала.'.$e->getMessage() );
        }
        $this->terminate();
    }
}
