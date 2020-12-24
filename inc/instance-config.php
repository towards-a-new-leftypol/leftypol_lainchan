<?php

/*
*  Instance Configuration
*  ----------------------
*  Edit this file and not config.php for imageboard configuration.
*
*  You can copy values from config.php (defaults) and paste them here.
*/


	$config['db']['server'] = 'localhost';
	$config['db']['database'] = 'lainchan';
	$config['db']['prefix'] = '';
	$config['db']['user'] = 'lainchan';
	$config['db']['password'] = '';


	$config['cookies']['mod'] = 'mod';
	$config['cookies']['salt'] = 'MGYwNjhlNjU5Y2QxNWU3YjQ3MzQ1Yj';

	$config['flood_time'] = 0;
	$config['flood_time_ip'] = 0;
	$config['flood_time_same'] = 0;
	$config['max_body'] = 100000;
	$config['reply_limit'] = 250;
	$config['max_links'] = 40;
	$config['max_filesize'] = 52428800;
	$config['thumb_width'] = 255;
	$config['thumb_height'] = 255;
	$config['max_width'] = 10000;
	$config['max_height'] = 10000;
	$config['threads_per_page'] = 10;
	$config['max_pages'] = 36;
	$config['threads_preview'] = 5;
	$config['root'] = '/';
	$config['secure_trip_salt'] = 'ODQ2NDM0ODlmMmRhNzk2M2EyNjJlOW';

	$config['thumb_method'] = 'gm+gifsicle';
	$config['gnu_md5'] = '1';

	$config['stylesheets']['Dark'] = 'dark.css';
	$config['stylesheets']['Dark Red'] = 'dark_red.css';