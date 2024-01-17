<?php

declare(strict_types=1);

namespace App\Router;

use Nette;
use Nette\Application\Routers\RouteList;


final class RouterFactory
{
	use Nette\StaticClass;

	public static function createRouter(): RouteList
	{
		$router = new RouteList;
		$router->addRoute('', 'Sign:in');
		$router->addRoute('sign-up', 'Sign:up');
		$router->addRoute('sign-out', 'Sign:out');

		$router->addRoute('panel/create/[<id \d+>]', 'Panel:create')
			->addRoute('panel/edit[/<id \d+>]', 'Panel:edit')
			->addRoute('panel/delete/[/<id \d+>]', 'Panel:delete');
		
		$router->addRoute('user/index', 'User:index')
			->addRoute('user/show[/<id \d+>]', 'User:show');

		$router->addRoute('element/index', 'Element:index')
			->addRoute('element/create', 'Element:create')
			->addRoute('element/edit[/<id \d+>]', 'Element:edit')
			->addRoute('element/delete[/<id \d+>]', 'Element:delete');

		$router->addRoute('notepad/index', 'Notepad:index')
			->addRoute('notepad/create', 'Notepad:create')
			->addRoute('notepad/edit[/<id \d+>]', 'Notepad:edit')
			->addRoute('notepad/delete[/<id \d+>]', 'Notepad:delete');
		
		return $router;
	}
}
