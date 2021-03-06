<?php

namespace TodoList;

use	Nette\Application\Routers\RouteList;
use	Nette\Application\Routers\Route;
use Nette;

class RouterFactory
{

	/**
	 * @return Nette\Application\IRouter
	 */
	public static function createRouter()
	{
		$router = new RouteList();
		$router[] = new Route('<presenter>/<action>[/<id \d+>]', [
            'presenter' => 'Homepage',
            'action' => 'default',
            'id' => null
        ]);
		return $router;
	}

}
