<?php
namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;
use App\Model\LocalAuthenticator;
use Nette\Mail\Message;
use Nette\Mail\Mailer;
use Nette\Neon\Neon;



final class SignPresenter extends Nette\Application\UI\Presenter
{
    private $authentificator;
    private $mailer;

    public function __construct(LocalAuthenticator $authentificator)
    {
        $this->authentificator = $authentificator;
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
            $this->flashMessage('Wecome! You are all signed in!');
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

		$form->addSubmit('send', 'Submit');

		$form->onSuccess[] = [$this, 'signUpFormSucceeded'];

        return $form;
	}

    public function signUpFormSucceeded(array $data): void
    {
        if (true) {
            $this->authentificator->createUser($data['first_name'], $data['last_name'], $data['email'], $data['phone'], $data['birth_date'], $data['role'] = 'member', $data['gender'], $data['password']);

            $mail = new Message;
            $mail->setFrom('vds.email.sender@yandex.ru')
                ->addTo('pivannikov@yandex.ru')
                ->setSubject('Подтверждение регистрации')
                ->setBody("Здравствуйте. ВЫ успешно зарегистрированы на портале.");
            $this->mailer->send($mail);
    
            $this->flashMessage('Wecome! You are all signed up!');
            $this->redirect('Panel:index');
        } else {
            $this->flashMessage('token error');
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
