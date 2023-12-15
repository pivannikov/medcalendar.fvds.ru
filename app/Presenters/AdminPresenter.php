<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;


final class AdminPresenter extends Nette\Application\UI\Presenter
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
        if (!$this->getUser()->isInRole('admin')) {
			$this->redirect('Panel:index');
		}
		
	}


    public function renderIndex(): void
    {

        $this->template->shedule = $this->database
            ->table('shedule')
            ->limit(5);
        
    }


    public function renderEdit(int $sheduleId): void
    {

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
        $form = new Form;

        $members = $this->database
            ->table('users')
            ->fetchPairs('id', 'first_name');
        $form->addSelect('user_id', 'User:', $members);

        $elements = $this->database
            ->table('elements')
            ->fetchPairs('id', 'title');        
        $form->addSelect('element_id', 'Element:', $elements);

        $form->addText('dosage', 'Дозировка:')
            ->setRequired('Пожалуйста, введите дозировку');

        $form->addDate('date_from', 'Date from:')
            ->setFormat('Y-m-d')
            ->setRequired('Пожалуйста, выберите дату от:');

        $form->addDate('date_to', 'Date to:')
            ->setFormat('Y-m-d')
            ->setRequired('Пожалуйста, выберите дату до:');

        $form->addTime('receipt_time', 'Время приема:')
            ->setFormat('H:i')
            ->setDefaultValue('08:00');

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

        $this->flashMessage('Пост опубликован', 'success');
        $this->redirect('Panel:index');

    }


    public function actionDelete(int $sheduleId )
    {
        
        $form = new Form;
        try {
                $this->flashMessage("Recipe Category Deleted", 'success');
                $this->database->table('shedule')->where('id', $sheduleId)->delete();
                $this->redirect('Panel:index');

        } catch (Nette\Security\AuthenticationException $e) {
            $form->addError('Delete Recipe Category Failed'.$e->getMessage() );
        }
        $this->terminate();
    }
}
