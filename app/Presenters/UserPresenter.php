<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;


final class UserPresenter extends Nette\Application\UI\Presenter
{
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
		// if ($this->getUser()->isLoggedIn() && !$this->getUser()->isInRole('admin')) {
		// 	$this->redirect('User:index');
		// }

		
	}


    public function renderIndex(): void
    {
        $user = $this->getUser();

        if (!$this->getUser()->isInRole('admin')) {

            $this->template->users = $this->database
                ->table('users')
                ->where('id', $user->getId());
        } else {

            $this->template->users = $this->database
                ->table('users');
        }
        
    }

    public function renderShow(int $memberId): void
    {
        if ($this->getUser()->isLoggedIn() && !$this->getUser()->isInRole('admin')) {
			$this->redirect('User:index');
		}

        $this->template->shedules = $this->database
            ->table('shedule')
            ->where('user_id', $memberId)
            ->order('date_from');
        
        $this->template->member = $this->database
            ->table('users')->get($memberId);
    }


    public function renderEdit(int $sheduleId): void
    {
        if ($this->getUser()->isLoggedIn() && !$this->getUser()->isInRole('admin')) {
			$this->redirect('User:index');
		}

        $shedule = $this->database
            ->table('shedule')
			->where('id', $sheduleId);

        if (!$shedule) {
            $this->error('Запись не найдена');
        }

        $this->getComponent('sheduleForm')
		    ->setDefaults($shedule->fetch());

    }

    protected function createComponentSheduleForm(): Form
    {
        if ($this->getUser()->isLoggedIn() && !$this->getUser()->isInRole('admin')) {
			$this->redirect('User:index');
		}

        $user = $this->getUser();
        $uid = $user->getId();

        $form = new Form;

        $elements = $this->database
            ->table('elements')
            ->fetchPairs('id', 'title');
        
        
        $form->addSelect('element_id', 'Element:', $elements);


        $form->addDate('date_from', 'Date from:')
            ->setFormat('Y-m-d')
            ->setRequired('Пожалуйста, выберите дату от:');

        $form->addDate('date_to', 'Date to:')
            ->setFormat('Y-m-d')
            ->setRequired('Пожалуйста, выберите дату до:');

        $form->addHidden('user_id', $uid);

        $form->addSubmit('send', 'Save and public');
        $form->onSuccess[] = $this->sheduleFormSucceeded(...);

        return $form;
    }

   
    private function sheduleFormSucceeded(array $data): void
    {
        $sheduleId = $this->getParameter('sheduleId');

        if ($sheduleId) {
            $shedule = $this->database
                ->table('shedule')
                ->get($sheduleId);
            $shedule->update($data);

        } else {
            $shedule = $this->database
                ->table('shedule')
                ->insert($data);
        }

        $this->flashMessage('Запись добавлена', 'success');
        $this->redirect('User:index');

    }


    public function actionDelete(int $sheduleId )
    {
        if ($this->getUser()->isLoggedIn() && !$this->getUser()->isInRole('admin')) {
			$this->redirect('User:index');
		}
        
        $form = new Form;
        try {
                $this->flashMessage("Recipe Category Deleted", 'success');
                $this->database->table('shedule')->where('id', $sheduleId)->delete();
                $this->redirect('User:index');

        } catch (Nette\Security\AuthenticationException $e) {
            $form->addError('Delete Recipe Category Failed'.$e->getMessage() );
        }
        $this->terminate();
    }
}
