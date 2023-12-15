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

		$router->addRoute('panel/index', 'Panel:index');
		$router->addRoute('panel/create', 'Panel:create');
		$router->addRoute('panel/edit[/<id \d+>]', 'Panel:edit');
		$router->addRoute('panel/delete/[/<id \d+>]', 'Panel:delete');
		
		$router->withPath('admin')
			->addRoute('panel/index', 'Admin:index')
			->addRoute('users/index', 'User:index')
			->addRoute('user/show[/<id \d+>]', 'User:show')
			->addRoute('elements/index', 'Element:index')
			->addRoute('element/create', 'Element:create')
			->addRoute('element/edit[/<id \d+>]', 'Element:edit')
			->addRoute('element/delete[/<id \d+>]', 'Element:delete');
		
		return $router;
	}
}
