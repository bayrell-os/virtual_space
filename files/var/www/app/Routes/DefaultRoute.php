<?php

namespace App\Routes;

use TinyPHP\RenderContainer;
use TinyPHP\Route;
use TinyPHP\RouteContainer;


class DefaultRoute extends Route
{
	
	/**
	 * Declare routes
	 */
	function routes(RouteContainer $route_container)
	{
		$route_container->addRoute([
			"url" => "/",
			"name" => "site:index",
			"method" => [$this, "actionIndex"],
		]);
		$route_container->addRoute([
			"url" => "/login",
			"name" => "site:login",
			"method" => [$this, "actionLogin"],
		]);
	}
	
	
	
	/**
	 * Action index
	 */
	function actionIndex()
	{
		/* Set result */
		$this->render("@app/index.twig");
	}
	
	
	
	/**
	 * Action login
	 */
	function actionLogin()
	{
		/* Set result */
		$this->render("@app/login.twig");
	}
	
}