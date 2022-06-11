<?php

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
	
}