<?php

namespace App\Routes;

use TinyPHP\RenderContainer;
use TinyPHP\Route;
use TinyPHP\RouteContainer;
use TinyPHP\Bus;


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
		$login = "";
		$password = "";
		
		$form = [
			"login" => "",
			"password" => "",
			"result" => "",
			"error_code" => 0,
		];
		
		/* Is post ? */
		if ($this->isPost())
		{
			$login = $this->container->post("login");
			$password = $this->container->post("password");
			
			/* Call api */
			$res = Bus::call
			(
				"/cloud_os/bus/login/",
				[
					"login" => $login,
					"password" => $password,
				]
			);
			
			$form["login"] = $login;
			// $form["password"] = $password;
			$form["result"] = $res->error_str;
			$form["error_code"] = $res->error_code;
			
			/* If success then setup JWT */
			if ($res->isSuccess())
			{
				$jwt_string = isset($res->result["jwt"]) ? $res->result["jwt"] : "";
				$jwt_data = isset($res->result["data"]) ? $res->result["data"] : [];
				$expires = isset($jwt_data["expires"]) ? $jwt_data["expires"] : 0;
				if ($jwt_string)
				{
					$this->setCookie([
						"name" => "cloud_jwt",
						"value" => $jwt_string,
						"expires" => $expires,
						"path" => "/",
					]);
				}
			}
		}
		
		/* If success redirect to main page */
		if ($form["error_code"] == 1)
		{
			$this->redirect("/");
		}
		
		/* Else render form */
		else
		{
			/* Set form context */
			$this->setContext("form", $form);
			
			/* Set result */
			$this->render("@app/login.twig");
		}
	}
	
}