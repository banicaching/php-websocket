<?php

	spl_autoload_register(function($class) {
		$parts = explode('\\', $class);
		require_once __DIR__.'/'. end($parts) .".php";
	});

?>
