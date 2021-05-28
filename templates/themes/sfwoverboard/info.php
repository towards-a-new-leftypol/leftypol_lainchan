<?php

	// Basic theme properties
	$theme = array(
		'name'        => 'SFW Overboard',
		// Description (you can use Tinyboard markup here)
		'description' => 'An additional overboard, intended to list a different set of boards.',
		'version'     => 'v0.1',
		// Unique function name for building and installing whatever's necessary
		'build_function'   => 'sfwoverboard_build',
		'install_callback' => 'sfwoverboard_install',
	);

	// Theme configuration
	$theme['config'] = array(
		array(
			'title'   => 'Board name',
			'name'    => 'title',
			'type'    => 'text',
			'default' => 'SFW Overboard',
		),
		array(
			'title'   => 'Board URI',
			'name'    => 'uri',
			'type'    => 'text',
			'default' => '.',
			'comment' => '("mixed", for example)',
		),
		array(
			'title'   => 'Subtitle',
			'name'    => 'subtitle',
			'type'    => 'text',
			'comment' => '(%s = thread limit, for example "%s coolest threads")',
		),
		array(
			'title'   => 'Excluded boards',
			'name'    => 'exclude',
			'type'    => 'text',
			'comment' => '(space seperated)',
		),
		array(
			'title'   => 'Number of threads',
			'name'    => 'thread_limit',
			'type'    => 'text',
			'default' => '15',
		),
	);

	if (!function_exists('sfwoverboard_install')) {
		function sfwoverboard_install($settings) {
			if (!file_exists($settings['uri'])) {
				@mkdir($settings['uri'], 0777) or error("Couldn't create {$settings['uri']}. Check permissions.", true);
			}
		}
	}

