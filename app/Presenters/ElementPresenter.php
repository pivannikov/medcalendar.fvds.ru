<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;


final class ElementPresenter extends Nette\Application\UI\Presenter
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
		if ($this->getUser()->isLoggedIn() && !$this->getUser()->isInRole('admin')) {
			$this->redirect('User:index');
		}
		
	}


    public function renderIndex(): void
    {
        
        $this->template->elements = $this->database
            ->table('elements')
            ->limit(50);
    }

    public function renderShow(int $elementId): void
    {
        
        $this->template->element = $this->database
            ->table('elements')
            ->where('id', $elementId);
    }


    public function renderEdit(int $elementId): void
    {
        $element = $this->database
            ->table('elements')
			->where('id', $elementId);

        if (!$element) {
            $this->error('Пост не найден');
        }

        $this->getComponent('elementForm')
		    ->setDefaults($element->fetch());

    }

    protected function createComponentElementForm(): Form
    {        
        $form = new Form;
        
        $form->addText('title', 'Название:')
            ->setRequired('Пожалуйста, введите название элемента');

        $form->addText('title_en', 'Название англ.:');

        $form->addTextArea('description', 'Описание:');

        $buy_reminder_options = [
            '14' => 'за две недели',
            '7' => 'за неделю',
            '0' => 'есть в наличии',
        ];
        $form->addSelect('buy_reminder', 'Напомнить о покупке:', $buy_reminder_options)
            ->setDefaultValue('0');

        $form->addSubmit('send', 'Сохранить')
            ->setHtmlAttribute('class', 'button_success custom_button');

        $form->onSuccess[] = $this->elementFormSucceeded(...);

        return $form;
    }

   
    private function elementFormSucceeded(array $data): void
    {
        $elementId = $this->getParameter('elementId');

        if ($elementId) {
            $element = $this->database
                ->table('elements')
                ->get($elementId);
            $element->update($data);

        } else {
            $element = $this->database
                ->table('elements')
                ->insert($data);
        }

        $this->flashMessage('Элемент добавлен', 'success');
        $this->redirect('Element:index');

    }


    public function actionDelete(int $elementId )
    {
        $form = new Form;
        try {
                $this->flashMessage("Элемент удален", 'success');
                $this->database->table('elements')->where('id', $elementId)->delete();
                $this->redirect('Element:index');

        } catch (Nette\Security\AuthenticationException $e) {
            $form->addError('ОШибка при удалении элемента'.$e->getMessage() );
        }
        $this->terminate();
    }
}
