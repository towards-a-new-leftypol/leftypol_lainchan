<?php

/*
 * When adding a new board, rebuild this theme. If necessary, reconfigure the catalog theme.
 * Exclude list is space-separated (e.g. 'exclude' => 'b games music' )
 */
	$thread_limit = 30;

	// Define list of overboards
	$overboards_config = array(
		array(
			'title' => 'Overboard',
			'uri' => 'overboard',
			'subtitle' => '30 most recently bumped threads',
			'exclude' => '',
			'thread_limit' => $thread_limit,
		),
		array(
			'title' => 'SFW Overboard',
			'uri' => 'sfwoverboard',
			'subtitle' => '30 most recently bumped threads from work-safe boards',
			'exclude' => 'b',
			'thread_limit' => $thread_limit,
		),
	);

?>
