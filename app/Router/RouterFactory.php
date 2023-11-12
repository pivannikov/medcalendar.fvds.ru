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
		$router->addRoute('panel/edit/[/<id \d+>]', 'Panel:edit');
		$router->addRoute('panel/delete/[/<id \d+>]', 'Panel:delete');
		
		$router->addRoute('admin/index', 'Admin:index');
		$router->addRoute('admin/users', 'Admin:users');
		$router->addRoute('admin/elements', 'Admin:elements');
		// $router->withPath('admin')
		// 	->addRoute('users', 'Home:users')
		// 	->addRoute('elements', 'Home:elements')
		// 	->addRoute('', 'Home:shedule');
		return $router;
	}
}
