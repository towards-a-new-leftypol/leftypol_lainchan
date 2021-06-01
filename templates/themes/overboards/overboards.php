<?php

/* When adding a new board, rebuild this theme. If necessary, reconfigure the catalog theme.
 *
 */
	$thread_limit = 15;

	// Define list of overboards
	$overboards_config = array(
		array(
			'title' => 'Overboard',
			'uri' => 'overboard',
			'subtitle' => 'something something overboard',
			'exclude' => '',
			'thread_limit' => $thread_limit,
		),
		array(
			'title' => 'SFW Overboard',
			'uri' => 'sfwoverboard',
			'subtitle' => 'something something sfw overboard',
			'exclude' => 'b',
			'thread_limit' => $thread_limit,
		),
	);

?>
