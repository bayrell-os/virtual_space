<?php

/*!
 *  Bayrell Cloud OS
 *
 *  (c) Copyright 2020 - 2022 "Ildar Bikmamatov" <support@bayrell.org>
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *      https://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */


namespace App;


class Module
{
	
	/**
	 * Register hooks
	 */
	static function register_hooks()
	{
		add_chain("init_app", static::class, "init_app");
		add_chain("init_di_defs", static::class, "init_di_defs", CHAIN_LAST);
		add_chain("register_entities", static::class, "register_entities", CHAIN_LAST);
		add_chain("request_before", static::class, "request_before");
		add_chain("method_not_found", static::class, "method_not_found");
		add_chain("routes", static::class, "routes");
		add_chain("base_url", static::class, "base_url");
		add_chain("twig_opt", static::class, "twig_opt");
		add_chain("bus_gateway", static::class, "bus_gateway");
	}
	
	
	
	/**
	 * Init app
	 */
	static function init_app()
	{
	}
	
	
	
	/**
	 * Init defs
	 */
	static function init_di_defs($res)
	{
		$defs = $res->defs;
		
		/* Setup bus key */
		$defs["settings"]["bus_env_key"] = "CLOUD_OS_KEY";
		
		/* Setup jwt cookie key */
		$defs["settings"]["jwt_cookie_key"] = "cloud_jwt";
		
		$res->defs = $defs;
	}
	
	
	
	/**
	 * Register entities
	 */
	static function register_entities()
	{
		$app = app();
		
		/* Add routes */
		$app->addEntity(\App\Routes\DefaultRoute::class);		
	}
	
	
	
	/**
	 * Request before
	 */
	static function request_before($res)
	{
		$res->container->add_breadcrumb(
			$res->container->base_url . "/",
			"Main"
		);
	}
	
	
	
	/**
	 * Method not found
	 */
	static function method_not_found($res)
	{
		$container = $res->container;
	}
	
	
	
	/**
	 * Routes
	 */
	static function routes($res)
	{
		// var_dump( $res->route_container->routes );
	}
	
	
	
	/**
	 * Base url
	 */
	static function base_url($res)
	{
		$res["base_url"] = $res->request->server->get('HTTP_X_ROUTE_PREFIX', '');
	}
	
	
	
	/**
	 * Twig opt
	 */
	static function twig_opt($res)
	{
		$twig_opt = $res["twig_opt"];
		$twig_opt["cache"] = "/data/php/cache/twig";
		$res["twig_opt"] = $twig_opt;
	}
	
	
	
	/**
	 * Bus gateway
	 */
	static function bus_gateway($res)
	{
		$gateway = $res["project"];
		if ($gateway == "cloud_os")
		{
			$res["gateway"] = "http://" . env("CLOUD_OS_GATEWAY") . "/api/bus/";
		}
	}
	
	
	
	/**
	 * Create App
	 */
	static function createApp()
	{
		/* Create app */
		$app = create_app_instance();
		
		/* Add modules */
		$app->addModule(static::class);
		
		/* Run app */
		$app->init();
		return $app;
	}
	
}