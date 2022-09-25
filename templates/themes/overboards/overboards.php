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
			'exclude' => array('assembly', 'assembly_archive', 'gulag'),
			'thread_limit' => $thread_limit,
		)
	);

?>
