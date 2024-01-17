<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;


final class NotepadPresenter extends Nette\Application\UI\Presenter
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
        
        $this->template->posts = $this->database
            ->table('posts')
            ->limit(50);
    }

    public function renderShow(int $postId): void
    {
        
        $this->template->post = $this->database
            ->table('posts')
            ->where('id', $postId);
    }


    public function renderEdit(int $postId): void
    {
        $post = $this->database
            ->table('posts')
			->where('id', $postId);

        if (!$post) {
            $this->error('Пост не найден');
        }

        $this->getComponent('postForm')
		    ->setDefaults($post->fetch());

    }

    protected function createComponentPostForm(): Form
    {        
        $form = new Form;
        
        $form->addText('title', 'Заголовок:')
            ->setRequired('Пожалуйста, введите название');

        $form->addTextArea('description', 'Текст:');

        $form->addSubmit('send', 'Сохранить')
            ->setHtmlAttribute('class', 'button_success custom_button');

        $form->onSuccess[] = $this->postFormSucceeded(...);

        return $form;
    }

   
    private function postFormSucceeded(array $data): void
    {
        $postId = $this->getParameter('postId');

        if ($postId) {
            $post = $this->database
                ->table('posts')
                ->get($postId);
            $post->update($data);

        } else {
            $post = $this->database
                ->table('posts')
                ->insert($data);
        }

        $this->flashMessage('Пост добавлен', 'success');
        $this->redirect('Notepad:index');

    }


    public function actionDelete(int $postId )
    {
        $form = new Form;
        try {
                $this->flashMessage("Пост удален", 'success');
                $this->database->table('posts')->where('id', $postId)->delete();
                $this->redirect('Notepad:index');

        } catch (Nette\Security\AuthenticationException $e) {
            $form->addError('Ошибка при удалении поста'.$e->getMessage() );
        }
        $this->terminate();
    }
}
