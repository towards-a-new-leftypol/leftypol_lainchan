<?php

	// Basic theme properties
	$theme = array(
		'name'        => 'Overboards',
		// Description (you can use Tinyboard markup here)
		'description' => 'Add one or more overboards, such as a normal overboard and a SFW overboard.',
		'version'     => 'v0.1',
		// Unique function name for building and installing whatever's necessary
		'build_function'   => 'overboards_build',
	);

	// Theme configuration
	$theme['config'] = array(
		array(
			'title' => 'Edit overboards.php manually to add, remove or modify overboards',
			'name' => 'instruct1',
			'type' => 'checkbox',
			'default' => false,
			'comment' => 'Located at templates/themes/overboards/overboards.php'
		),

	);
