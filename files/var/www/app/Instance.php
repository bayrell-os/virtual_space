<?php

namespace App;

class Instance extends \TinyPHP\App
{
	
	/**
	 * Add modules
	 */
	function add_modules()
	{
		$this->addModule(\App\Module::class);
	}
	
}