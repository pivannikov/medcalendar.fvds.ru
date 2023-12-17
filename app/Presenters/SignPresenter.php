<?php
namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;
use App\Model\LocalAuthenticator;
use App\Model\VariablesStore;
use Nette\Mail\Message;
use Nette\Mail\Mailer;
use Nette\Neon\Neon;



final class SignPresenter extends Nette\Application\UI\Presenter
{
    private $mailer;


    public function __construct(
        private LocalAuthenticator $authentificator, 
        private VariablesStore $variablesStore
    )
    {
    }

	protected function createComponentSignInForm(): Form
	{
		$form = new Form;
		$form->addEmail('email', 'Имя пользователя:')
			->setRequired('Пожалуйста, введите ваше имя.');

		$form->addPassword('password', 'Пароль:')
			->setRequired('Пожалуйста, введите ваш пароль.');

		$form->addSubmit('send', 'Войти');

		$form->onSuccess[] = $this->signInFormSucceeded(...);
		return $form;
	}

    private function signInFormSucceeded(Form $form, \stdClass $data): void
    {
        try {
            $this->getUser()->login($data->email, $data->password);
            $this->flashMessage('Вы успешно авторизованы!');
            $this->redirect('Panel:index');

        } catch (Nette\Security\AuthenticationException $e) {
            $form->addError('Неправильные логин или пароль.');
        }
    }

    protected function createComponentSignUpForm(): Form
	{
		$form = new Form;

        $form->addText('first_name', 'Имя')
            ->setRequired('Пожалуйста, введите ваше имя.');

        $form->addText('last_name', 'Name')
            ->setRequired('Пожалуйста, введите вашу фамилию.');
        
        $form->addEmail('email', 'Email:')
			->setRequired('Пожалуйста, введите ваше email.');

        $form->addText('phone', 'Телефон:')
            ->setHtmlType('tel');

        $form->addDate('birth_date', 'Date:')
            ->setFormat('Y-m-d')
            ->setRequired('Пожалуйста, выберите дату рождения.');

        $gender = [
            'm' => 'мужской',
            'f' => 'женский',
        ];
        $form->addRadioList('gender', 'Пол:', $gender)
            ->setDefaultValue('m')
            ->setRequired('Пожалуйста, введите ваше email.');

		$form->addPassword('password', 'Password:')
			->setRequired('Пожалуйста, введите ваш пароль.');

        $form->addInteger('signup_token', 'Secret key:')
            ->setRequired('Пожалуйста, введите "secret key" для успешной регистрации.');

		$form->addSubmit('send', 'Submit');

		$form->onSuccess[] = [$this, 'signUpFormSucceeded'];

        return $form;
	}

    public function signUpFormSucceeded(array $data): void
    {
        if ($data['signup_token'] == $this->variablesStore->signup_token) {

            $this->authentificator->createUser($data['first_name'], $data['last_name'], $data['email'], $data['phone'], $data['birth_date'], $data['role'] = 'member', $data['gender'], $data['password']);

            $mail = new Message;
            $mail->setFrom($this->variablesStore->server_sender_email)
                ->addTo($data['email'])
                ->setSubject('Подтверждение регистрации')
                ->setBody("Здравствуйте, вы успешно зарегистрированы на портале");
            $this->mailer->send($mail);
            
            $system_mail = new Message;
            $system_mail->setFrom($this->variablesStore->server_sender_email)
                ->addTo($this->variablesStore->system_email)
                ->setSubject('Новый пользователь')
                ->setBody("На портале новый пользователь: " . $data['email']);
            $this->mailer->send($system_mail);
    
            $this->flashMessage('Здравствуйте, вы успешно зарегистрированы!');
            $this->redirect('Panel:index');

        } else {

            $this->flashMessage('Secret key error...');
            $this->redirect('Panel:index');
        }

        
    }

	public function injectMailer(Mailer $mailer)
	{
		$this->mailer = $mailer;
	}


    public function actionOut(): void
    {
        $this->getUser()->logout();
        $this->flashMessage('Вы вышли.');
        $this->redirect('Sign:in');
    }


}
