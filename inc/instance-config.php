<?php

/*
*  Instance Configuration
*  ----------------------
*  Edit this file and not config.php for imageboard configuration.
*
*  You can copy values from config.php (defaults) and paste them here.
*/

	 $config['boards'] = array(
	 	array('leftypol', 'b'),
	 	array('GET', 'ref'),
	 	array('gulag' => 'http://status.example.org/')
	 );
	 // Board categories. Only used in the "Categories" theme.
	 $config['categories'] = array(
	 	'Leftypol' => array('leftypol', 'b'),
	 	'GET' => array('GET', 'ref'),
	 	'Meta' => array('gulag')
	 );
	 // Optional for the Categories theme. This is an array of name => (title, url) groups for categories
	 // with non-board links.
	 $config['custom_categories'] = array(
	 	'Links' => array(
	 		'Leftypedia' => 'http://example.org',
	 		'Staff application' => 'staff_applications.html',
	 		'FAQ' => 'faq.html',
	 		'Donate' => 'donate.html'
	 	)
	 );


	$config['db']['server'] = 'localhost';
	$config['db']['database'] = 'lainchan';
	$config['db']['prefix'] = '';
	$config['db']['user'] = 'lainchan';
	$config['db']['password'] = 'oijrljqqwjr242kjn';


	$config['cookies']['mod'] = 'mod';
	$config['cookies']['salt'] = 'ZGZkYWU3NGUwZDNiYjU2MDEwZmRkMW';

	$config['flood_time'] = 10;
	$config['flood_time_ip'] = 120;
	$config['flood_time_same'] = 30;
	$config['max_body'] = 1800;
	$config['reply_limit'] = 250;
	$config['max_links'] = 20;
	$config['max_filesize'] = 10485760;
	$config['thumb_width'] = 255;
	$config['thumb_height'] = 255;
	$config['max_width'] = 10000;
	$config['max_height'] = 10000;
	$config['max_images'] = 4;
	$config['threads_per_page'] = 10;
	$config['max_pages'] = 10;
	$config['threads_preview'] = 5;
	$config['root'] = '/';
	$config['secure_trip_salt'] = 'MzdhNTJiMjNkMTM5Nzc5NDcwOGViMD';

	$config['thumb_method'] = 'gm+gifsicle';
	$config['gnu_md5'] = '1';

	// < Added by Zero >
	// Sun Aug 30 17:44:19 UTC 2020

	// Allowed image file extensions.
	$config['allowed_ext'][] = 'jpg';
	$config['allowed_ext'][] = 'jpeg';
	$config['allowed_ext'][] = 'bmp';
	$config['allowed_ext'][] = 'gif';
	$config['allowed_ext'][] = 'png';
	 
	 // TODO test section, please remove
	$config['allowed_ext'][] = 'mp4';
	$config['allowed_ext'][] = 'webm';
	$config['allowed_ext_files'][] = 'webm';
	$config['webm']['use_ffmpeg'] = true;
	$config['additional_javascript'][] = 'js/options.js';
	$config['additional_javascript'][] = 'js/webm-settings.js';
	$config['additional_javascript'][] = 'js/expand-video.js';
	$config['max_filesize'] = 50 * 1024 * 1024; // 50MB
	$config['webm']['ffmpeg_path'] = '/usr/bin/ffmpeg';
	$config['webm']['ffprobe_path'] = '/usr/bin/ffprobe';
	$config['mp4']['ffmpeg_path'] = '/usr/bin/ffmpeg';
	$config['mp4']['ffprobe_path'] = '/usr/bin/ffprobe';
	$config['webm']['allow_audio'] = true;
	$config['webm']['max_length'] = 620;
	 //end test section
	
	
	$config['allowed_ext_files'][] = 'mp4';
	$config['allowed_ext_files'][] = 'pdf';
	$config['allowed_ext_files'][] = 'txt';
	$config['allowed_ext_files'][] = 'zip';
	$config['allowed_ext_files'][] = 'epub';

	/*
	 * From Config:
		// Always regenerate markup. This isn't recommended and should only be used for debugging; by default,
		// Tinyboard only parses post markup when it needs to, and keeps post-markup HTML in the database. This
		// will significantly impact performance when enabled.
    *
	*/
	$config['markup_repair_tidy'] = false;


	$config['image_reject_repost'] = false;
	$config['flood_time'] = 0;
	// Minimum time between between each post with the exact same content AND same IP address.
	$config['flood_time_ip'] = 0;

	$config['filters'] = array();
	$config['always_noko'] = false; // the migration script now relies on this default behavior, we can turn this on later.
	// </ Added by Zero >

// Changes made via web editor by "admin" @ Tue, 02 Jun 2020 23:16:58 -0700:
$config['debug'] = true;


// Changes made via web editor by "admin" @ Tue, 02 Jun 2020 23:24:29 -0700:
$config['debug'] = false;
$config['verbose_errors'] = false;


/*
 * ====================
 *  Javascript
 * ====================
 */

	// Additional Javascript files to include on board index and thread pages. See js/ for available scripts.
	$config['additional_javascript'][] = 'js/jquery.min.js';
	$config['additional_javascript'][] = 'js/inline-expanding.js';
	$config['additional_javascript'][] = 'js/multi-image.js'; // required when using multi file upload
	$config['additional_javascript'][] = 'js/local-time.js';
	$config['additional_javascript'][] = 'js/auto-reload.js';
	$config['additional_javascript'][] = 'js/post-hover.js';
	$config['additional_javascript'][] = 'js/style-select.js';
	
	// Some scripts require jQuery. Check the comments in script files to see what's needed. When enabling
	// jQuery, you should first empty the array so that "js/query.min.js" can be the first, and then re-add
	// "js/inline-expanding.js" or else the inline-expanding script might not interact properly with other
	// scripts.
	// $config['additional_javascript'] = array();
	// $config['additional_javascript'][] = 'js/jquery.min.js';
	// $config['additional_javascript'][] = 'js/inline-expanding.js';
	// $config['additional_javascript'][] = 'js/auto-reload.js';
	// $config['additional_javascript'][] = 'js/post-hover.js';
	// $config['additional_javascript'][] = 'js/style-select.js';

	// Where these script files are located on the web. Defaults to $config['root'].
	// $config['additional_javascript_url'] = 'http://static.example.org/tinyboard-javascript-stuff/';

	// Compile all additional scripts into one file ($config['file_script']) instead of including them seperately.
	$config['additional_javascript_compile'] = false;

	// Minify Javascript using http://code.google.com/p/minify/.
	$config['minify_js'] = false;

	// Dispatch thumbnail loading and image configuration with JavaScript. It will need a certain javascript
	// code to work.
	$config['javascript_image_dispatch'] = false;

        $config['multiimage_method'] = 'each';


	$config['mod']['show_ip'] = DISABLED;
	$config['mod']['move'] = MOD;

// Changes made via web editor by "admin" @ Sun, 23 Aug 2020 16:45:14 -0700:
$config['force_image_op'] = false;


// Changes made via web editor by "admin" @ Sun, 23 Aug 2020 16:48:22 -0700:
$config['force_body_op'] = false;


// Changes made via web editor by "admin" @ Sun, 23 Aug 2020 17:38:52 -0700:
$config['flood_time'] = 0;
$config['flood_time_ip'] = 0;
$config['flood_time_same'] = 0;


// Changes made via web editor by "admin" @ Wed, 26 Aug 2020 20:15:11 -0700:
$config['min_body'] = 5;


// Changes made via web editor by "admin" @ Wed, 26 Aug 2020 20:15:44 -0700:
$config['force_image_op'] = true;
$config['min_body'] = 15;


// Changes made via web editor by "admin" @ Sat, 29 Aug 2020 13:26:51 -0700:
$config['force_image_op'] = false;


// Changes made via web editor by "admin" @ Sat, 29 Aug 2020 23:13:46 -0700:
$config['cookies']['salt'] = 'ZGZkYWU3NGUwZDNiYjU2MDEwZmRkMX';


// Changes made via web editor by "admin" @ Sat, 29 Aug 2020 23:21:09 -0700:
$config['cookies']['salt'] = 'ZGZkYWU3NGUwZDNiYjU2MDEwZmRkMA';


// Changes made via web editor by "admin" @ Sun, 30 Aug 2020 10:48:36 -0700:
$config['max_body'] = 100000;


// Changes made via web editor by "admin" @ Sun, 30 Aug 2020 15:25:41 -0700:
$config['max_links'] = 40;

