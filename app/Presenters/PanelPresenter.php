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


    // public function renderIndex(): void
    // {        
    //     $user = $this->getUser();

    //     $this->template->user_name = $this->database
    //         ->table('users')
    //         ->get($user->getId());

    //     if (!$this->getUser()->isInRole('admin')) {

    //         $this->template->shedule = $this->database
    //             ->table('shedule')
    //             ->where('user_id', $user->getId());
	// 	} else {

    //         $this->template->shedule = $this->database
    //             ->table('shedule');
    //     }

    // }


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

        $this->getComponent('sheduleForm')
		    ->setDefaults($shedule->fetch());

    }

    protected function createComponentSheduleForm(): Form
    {
        if (!$this->getUser()->isInRole('admin')) {
			$this->redirect('User:index');
		}

        $form = new Form;

        $members_raw = $this->database
            ->table('users')
            ->fetchPairs('id');

        foreach ($members_raw as $key => $member) {
            $members[$member->id] = $member->first_name . ' ' . $member->last_name;
        }
        $form->addSelect('user_id', 'User:', $members);

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
        if (!$this->getUser()->isInRole('admin')) {
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
