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
		$route_container->addRoute([
			"url" => "/logout",
			"name" => "site:logout",
			"method" => [$this, "actionLogout"],
		]);
	}
	
	
	
	/**
	 * Action index
	 */
	function actionIndex()
	{
		$domain_name = $this->container->server("HTTP_HOST");
		$space_uid = $this->container->header("x-space-uid");
		$cloud_jwt = $this->container->cookie("cloud_jwt");
		
		/* Call api */
		$res = Bus::call
		(
			"/cloud_os/space/get_routes/",
			[
				"space_uid" => $space_uid,
				"domain_name" => $domain_name,
			]
		);
		
		//$res->debug();
		
		$this->setContext("routes", $res->result);
		
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
		if ($this->container->isPost())
		{
			$login = $this->container->post("login");
			$password = $this->container->post("password");
			$space_uid = $this->container->header("x-space-uid");
			
			/* Call api */
			$res = Bus::call
			(
				"/cloud_os/login/",
				[
					"login" => $login,
					"password" => $password,
					"space_uid" => $space_uid,
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
					$this->container->setCookie([
						"name" => "cloud_jwt",
						"value" => $jwt_string,
						"expires" => $expires,
					]);
				}
			}
		}
		
		/* If success redirect to main page */
		if ($form["error_code"] == 1)
		{
			$redirect = trim( urldecode( $this->container->get("r", "") ) ); 
			
			if ($redirect != "")
			{
				$arr = parse_url($redirect);
				$path = isset($arr["path"]) ? $arr["path"] : "";
				$query = isset($arr["query"]) ? $arr["query"] : "";
				$fragment = isset($arr["fragment"]) ? $arr["fragment"] : "";
				
				$redirect_url = $path;
				if ($query != "") $redirect_url .= "?" . $query;
				if ($fragment != "") $redirect_url .= "#" . $fragment;
				
				$this->redirect($redirect_url);
			}
			else
			{
				$this->redirect("/");
			}
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
	
	
	
	/**
	 * Action logout
	 */
	function actionLogout()
	{
		$auth = auth();
		
		if ($auth->isAuth())
		{
			$jwt = $auth->getJWTString();
			
			/* Call api */
			$res = Bus::call
			(
				"/cloud_os/logout/",
				[
					"jwt" => $jwt,
				]
			);
			
			// $res->debug();
			
			if ($res->isSuccess())
			{
				$this->container->setCookie([
					"name" => "cloud_jwt",
					"value" => "",
					"expires" => 0,
				]);
				$this->redirect("/");
			}
			
		}
	}
	
}