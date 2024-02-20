<?php

/*
 * When adding a new board, rebuild this theme. If necessary, reconfigure the catalog theme.
 * Exclude list is a PHP array e.g. 'exclude' => array('b', 'games', 'music')
 */
	$thread_limit = 30;

	// Define list of overboards
	$overboards_config = array(
		array(
			'title' => 'Overboard',
			'uri' => 'overboard',
			'subtitle' => '30 most recently bumped threads',
			'exclude' => array('assembly', 'assembly_archive', 'gulag', 'i'),
			'thread_limit' => $thread_limit,
		),
		array(
			'title' => 'SFW Overboard',
			'uri' => 'sfw',
			'subtitle' => '30 most recently bumped threads from work-safe boards',
			'exclude' => array('assembly', 'assembly_archive', 'gulag', 'b', 'R9K', 'i'),
			'thread_limit' => $thread_limit,
		),
		array(
			'title' => 'Alternate Overboard',
			'uri' => 'alt',
			'subtitle' => '30 most recently bumped threads from smaller interest boards',
			'exclude' => array('assembly', 'assembly_archive', 'gulag', 'leftypol', 'b', 'meta', 'i'),
			'thread_limit' => $thread_limit,
		),
	);

?>
