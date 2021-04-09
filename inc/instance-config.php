<?php

/*
*  Instance Configuration
*  ----------------------
*  Edit this file and not config.php for imageboard configuration.
*
*  You can copy values from config.php (defaults) and paste them here.
 */

/*
 * Front page configurations.
 */

$config['boards'] = array(
   array('leftypol',
         'b',
         'hobby',
         'tech',
         'edu',
         'games',
         'anime',
         'music'
   ) ,
  array('meta')
);

$config['prepended_foreign_boards'] = array(
    'overboard' => '/overboard/',
    'cytube' => 'https://tv.leftypol.org/'
);

$config['foreign_boards'] = array(
    'GET' => 'https://getchan.net/GET/',
    'ref' => 'https://getchan.net/ref/'
);
// Board categories. Only used in the "Categories" theme.
$config['categories'] = array(
    'Leftypol' => array('leftypol',
        'b',
        'hobby',
        'tech',
        'edu',
        'games',
        'anime',
        'music'
    ),
    'Meta' => array('meta')
);

// Optional for the Categories theme. This is an array of name => (title, url) groups for categories
// with non-board links.
$config['custom_categories'] = array(
    'Our Friends' => array(
        'GET' => 'https://getchan.net/GET/',
        'ref' => 'https://getchan.net/ref/'
    ),
    'Links' => array(
        'New Multitude' => 'https://newmultitude.org/',
        'Booru image repository' => 'https://lefty.booru.org/',
        'Leftypedia' => 'https://leftypedia.org/',
        'Official chat room' => 'https://app.element.io/#/room/!RQxdjfGouwsFHwUzwL:matrix.org',
        'Rules' => 'rules.html'
    ),
    'Learning resources and blogs' => array(
        'Michael Roberts\' blog' => 'https://thenextrecession.wordpress.com/',
        'A Critique Of Crisis Theory blog' => 'https://critiqueofcrisistheory.wordpress.com/',
        'Leftypedia' => 'https://leftypedia.org/',
        'Marxist Internet Archive' => 'https://www.marxists.org/'
    ),
);

/*
 * Database and site wide configurations
 */

$config['db']['server'] = 'localhost';
$config['db']['database'] = 'lainchan';
$config['db']['prefix'] = '';
$config['db']['user'] = 'lainchan';
$config['db']['password'] = '';


$config['cookies']['mod'] = 'mod';
$config['cookies']['salt'] = 'MGYwNjhlNjU5Y2QxNWU3YjQ3MzQ1Yj';


$config['flood_cache'] = 60 * 15; // 15 minutes. The oldest a post can be in the flood table
$config['flood_time_any'] = 40; // time between thread creation
$config['flood_time'] = 30;
$config['flood_time_ip'] = 60;
$config['flood_time_same'] = 60;
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

//Banners
$config['url_banner'] = '/banners.php';

/*
 * Some users are having trouble posting when this is on,
 * with the message 'Your request looks automated; Post discarded.'
 *
 * This did not affect all users, and for some users only for some posts.
 *
 * If we are getting spammed hard, try turning this on.
 */
$config['spam']['enabled'] = false;

/*
 * Permissions
 */
$config['mod']['move'] = MOD;
$config['mod']['editpost'] = MOD;
// Raw HTML posting
$config['mod']['rawhtml'] = MOD;
$config['mod']['mod_board_log'] = MOD;
$config['mod']['ip_recentposts'] = 350;

// Post news entries
$config['mod']['news'] = MOD;
// Custom name when posting news
$config['mod']['news_custom'] = MOD;
// Delete news entries
$config['mod']['news_delete'] = MOD;
// Number of news entries to display per page.
$config['mod']['news_page'] = 5;

// Allow everyone to see bumplocks
$config['mod']['view_bumplock'] = -1;

$config['allow_thread_deletion'] = false;

// Max attachments per post
$config['max_images'] = 5;
$config['image_reject_repost'] = false;

$config['thumb_method'] = 'gm+gifsicle';
$config['gnu_md5'] = '1';
// $config['update_on_posts'] = true;
$config['referer_match'] = false;

$config['allowed_ext'][] = 'mp4';
$config['allowed_ext'][] = 'webm';
$config['allowed_ext_files'][] = 'webm';
$config['webm']['use_ffmpeg'] = true;
$config['max_filesize'] = 80 * 1024 * 1024; // 50MB
$config['webm']['allow_audio'] = true;
$config['webm']['max_length'] = 7200;

$config['pdf_file_thumbnail'] = true;
$config['djvu_file_thumbnail'] = true;

// Allowed image file extensions.
$config['allowed_ext'][] = 'jpg';
$config['allowed_ext'][] = 'jpeg';
$config['allowed_ext'][] = 'bmp';
$config['allowed_ext'][] = 'gif';
$config['allowed_ext'][] = 'png';

$config['allowed_ext_files'][] = 'mp3';
$config['allowed_ext_files'][] = 'mp4';
$config['allowed_ext_files'][] = 'pdf';
$config['allowed_ext_files'][] = 'txt';
$config['allowed_ext_files'][] = 'epub';
// Compressed files
$config['allowed_ext_files'][] = 'zip';
$config['allowed_ext_files'][] = 'gz';
$config['allowed_ext_files'][] = 'bz2';

/*
 * Flags
 */
$config['country_flags_condensed'] = false;
$config['user_flag'] = true;
$config['flag_style'] = 'width:auto;height:11px;';
$config['user_flags'] = array (
    '4th_international' => '4th International',
    'acceleration' => 'Acceleration',
    'ak-47' => 'AK-47',
    'albania' => 'Albania',
    'allende' => 'Allende',
    'anarcha-feminism' => 'Anarcha-Feminism',
    'anarchism' => 'Anarchism',
    'anarcho-capitalism' => 'Anarcho-Capitalism',
    'anarcho-communism' => 'Anarcho-Communism',
    'anarcho-nihilism' => 'Anarcho-Nihilism',
    'anarcho-primitivism' => 'Anarcho-Primitivism',
    'antifa' => 'Antifa',
    'athiesm' => 'Athiesm',
    'bolshevik' => 'Bolshevik',
    'brocialism' => 'Brocialism',
    'burkina_faso' => 'Burkina Faso',
    'ca' => 'Canadien',
    'chavismo' => 'Chavismo',
    'che' => 'Che',
    'china' => 'China',
    'christian_anarchism' => 'Christian Anarchism',
    'christian_communism' => 'Christian Communism',
    'cockshott' => 'Cockshott',
    'council_communism' => 'Council Communism',
    'cuba' => 'Cuba',
    'ddr' => 'DDR',
    'democrap' => 'Democrap',
    'democratic_socialism' => 'Democratic Socialism',
    'dprk' => 'DPRK',
    'egoism' => 'Egoism',
    'eureka' => 'Eureka',
    'eurocommunism' => 'Eurocommunism',
    'farc' => 'Las FARC',
    'fed' => 'Fed',
    'flq' => 'Front de libération du Québec',
    'freud' => 'Freud',
    'gadsden' => 'Gadsden',
    'gay_nazi' => 'Gay Nazi',
    'gentoo' => 'Gentoo',
    'gorro' => 'Gorro',
    'groucho_marxism' => 'Groucho Marxism',
    'hammer_&_sickle' => 'Hammer & Sickle',
    'international_brigade' => 'International Brigade',
    'ira' => 'IRA',
    'islamic_communism' => 'Islamic Communism',
    'iww' => 'IWW',
    'juche' => 'Juche',
    'kampuchea' => 'Kampuchea',
    'left_communism' => 'Left Communism',
    'lenin_cap' => 'Lenin Cap',
    'luxemburg' => 'Luxemburg',
    'marx' => 'Marx',
    'mutualism' => 'Mutualism',
    'naxalite' => 'Naxalite',
    'nazbol' => 'Nazbol',
    'nazi' => 'Nazi',
    'ndfp' => 'NDFP',
    'palestine' => 'Palestine',
    'pan-africanism' => 'Pan-Africanism',
    'pirate' => 'Pirate',
    'porky' => 'Porky',
    'posadas' => 'Posadas',
    'punk' => 'Punk',
    'raised_fist' => 'Raised Fist',
    'read_a_fucking_book' => 'Read a Fucking Book',
    'rethuglican' => 'Rethuglican',
    'sabo-tabby' => 'Sabo-Tabby',
    'sandinista' => 'Sandinista',
    'sendero_luminoso' => 'Sendero Luminoso',
    'slavoj' => 'Slavoj',
    'socialism' => 'Socialism',
    'soviet_union' => 'Soviet Union',
    'spurdo' => 'Spurdo',
    'ssnp' => 'SSNP',
    'stalin' => 'Stalin',
    'syndicalism' => 'Syndicalism',
    'tankie' => 'Tankie',
    'think' => 'Think',
    'united_farm_workers' => 'United Farm Workers',
    'viet_cong' => 'Viet Cong',
    'yugoslavia' => 'Yugoslavia',
    'zapatista' => 'Zapatista'
);


// Changes made via web editor by "krates" @ Tue, 22 Dec 2020 16:28:45 -0800:
$config['robot_mute'] = false;


// Changes made via web editor by "krates" @ Tue, 22 Dec 2020 16:29:57 -0800:
$config['max_links'] = 100;
$config['reply_limit'] = 750;

// Changes made via web editor by "krates" @ Tue, 22 Dec 2020 16:34:13 -0800:
$config['min_body'] = 0;


// Changes made via web editor by "krates" @ Tue, 22 Dec 2020 16:37:13 -0800:
$config['anti_bump_flood'] = true;


// Changes made via web editor by "krates" @ Tue, 22 Dec 2020 16:38:21 -0800:
$config['delete_time'] = 5;


// Changes made via web editor by "krates" @ Tue, 22 Dec 2020 17:20:14 -0800:
$config['reply_hard_limit'] = 1000;

//Changes by Barbara_Pitt
$config['stylesheets']['Dark'] = 'dark.css';
$config['stylesheets']['Dark Red'] = 'dark_red.css';
$config['always_noko'] = true;
$config['spoiler_images'] = true;

//Changes made by Coma
$config['markup_code'] = ("/\[code\](.*?)\[\/code\]/is");

//more themes (issue#26)
$config['stylesheets']['Burichan'] = 'burichan.css';
$config['stylesheets']['Futaba'] = 'futaba.css';
$config['stylesheets']['Gentoochan'] = 'gentoochan.css';
$config['stylesheets']['Gurochan'] = 'gurochan.css';
$config['stylesheets']['Jungle'] = 'jungle.css';
$config['stylesheets']['LainchanJP'] = 'lainchanjp.css';
$config['stylesheets']['Miku'] = 'miku.css';
$config['stylesheets']['Notsuba'] = 'notsuba.css';
$config['stylesheets']['Photon'] = 'photon.css';
$config['stylesheets']['Szalet'] = 'szalet.css';
$config['stylesheets']['Tsuki'] = 'tsuki.css';
$config['stylesheets']['DemainLight'] = 'demain_light.css';
$config['stylesheets']['DemainDark'] = 'demain_dark.css';
$config['stylesheets']['TempDark'] = 'temp_dark.css';
$config['stylesheets']['TempDarkRed'] = 'temp_dark_red.css';
$config['stylesheets']['AnonsDarkRed'] = 'anons_dark_red.css';
$config['stylesheets']['BunkerLike'] = 'bunker_like.css';

$config['default_stylesheet'] = array('Dark Red', $config['stylesheets']['Dark Red']);
/*
 * ====================
 *  Javascript
 * ====================
 */

$config['additional_javascript'][] = 'js/jquery-ui.custom.min.js';
$config['additional_javascript'][] = 'js/ajax.js';

$config['additional_javascript'][] = 'js/options.js';
$config['additional_javascript'][] = 'js/local-time.js';
$config['additional_javascript'][] = 'js/auto-reload.js';
$config['additional_javascript'][] = 'js/auto-scroll.js';
$config['additional_javascript'][] = 'js/thread-stats.js';
$config['additional_javascript'][] = 'js/post-hover.js';
$config['additional_javascript'][] = 'js/style-select.js';

$config['additional_javascript'][] = 'js/hide-threads.js';
$config['additional_javascript'][] = 'js/hide-images.js';
$config['additional_javascript'][] = 'js/show-backlinks.js';
$config['additional_javascript'][] = 'js/show-op.js';
$config['additional_javascript'][] = 'js/show-own-posts.js';

$config['additional_javascript'][] = 'js/quick-reply.js';
$config['additional_javascript'][] = 'js/post-menu.js';
$config['additional_javascript'][] = 'js/post-filter.js';

$config['additional_javascript'][] = 'js/options/general.js';
$config['additional_javascript'][] = 'js/options/user-css.js';
$config['additional_javascript'][] = 'js/options/user-js.js';
$config['additional_javascript'][] = 'js/thread-watcher.js';
$config['additional_javascript'][] = 'js/catalog-search.js';
$config['additional_javascript'][] = 'js/gallery-view.js';
$config['additional_javascript'][] = 'js/expand.js';
$config['additional_javascript'][] = 'js/file-selector.js';
$config['additional_javascript'][] = 'js/save-user_flag.js';
$config['additional_javascript'][] = 'js/webm-settings.js';
$config['additional_javascript'][] = 'js/expand-video.js';
$config['additional_javascript'][] = 'js/download-original.js';

$config['enable_embedding'] = true;

$config['youtube_js_html']
    = '<div class="video-container" data-video="$2">'
    . '<a href="https://youtu.be/$2" target="_blank" class="file">'
    . '<img style="width:255px;height:190px;" src="/vi/$2/0.jpg" class="post-image"/>'
    . '</a></div>';

$config['embedding'] = array();
$config['embedding'][0] =
    array(
        '/^https?:\/\/(\w+\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9\-_]{10,11})(&.+)?$/i',
        $config['youtube_js_html']
    );
$config['additional_javascript'][] = 'js/youtube.js';

/*
 * ====================
 *  Markup
 * ====================
 */

$config['markup'][] = array("/^\s*&lt;.*$/m", '<span class="orangeQuote">$0</span>');
$config['markup'][] = array("/__(.+?)__/", "<span class=\"underline\">\$1</span>");
$config['markup'][] = array("/~~(.+?)~~/", "<span class=\"strikethrough\">\$1</span>");

// 2021 april fools:
// $config['wordfilters'][] = array('/Stalin/i', 'Stalin (he/him)', true);
// $config['wordfilters'][] = array('/Capitalism/i', 'Crony Capitalism', true);
// $config['wordfilters'][] = array('/April fool\'s/i', 'Marx\'s birthday', true);
// $config['wordfilters'][] = array('/April fools/i', 'Marx\'s birthday', true);
// $config['wordfilters'][] = array('/China/i', 'Taiwan', true);
// $config['wordfilters'][] = array('/Retard/i', 'smart', true);
// $config['wordfilters'][] = array('/Communism/i', 'social democracy', true);
// $config['wordfilters'][] = array('/revolution /i', 'reforms ', true);
// $config['wordfilters'][] = array('/revolutionary/i', 'reformist', true);
// $config['wordfilters'][] = array('/Karl Marx/i', 'Ferdinand Lassalle', true);
// $config['wordfilters'][] = array('/jewish nigger/i', 'dear friend', true);
// $config['wordfilters'][] = array('/Lenin/i', 'Kautsky', true);
// $config['wordfilters'][] = array('/Bourgeois /i', 'Patrician ', true);
// $config['wordfilters'][] = array('/vaush /i', 'Haz ', true);
// $config['wordfilters'][] = array('/anarchists/i', 'vaushists', true);
// $config['wordfilters'][] = array('/anarchy|anarchism/i', 'vaushism', true);
// $config['wordfilters'][] = array('/Bourgeoisie/i', 'job creators', true);
// $config['wordfilters'][] = array('/kulak|koulak/i', 'martyr', true);
// $config['wordfilters'][] = array('/breadtube/i', 'basedtube', true);
// $config['wordfilters'][] = array('/tranny jannies/i', 'insanely based mods', true);
// $config['wordfilters'][] = array('/tranny janny/i', 'a respectable member of the jannguard', true);
// $config['wordfilters'][] = array('/anglo/i', 'black', true);
// $config['wordfilters'][] = array('/Trotksy /i', 'Trotsky, Lenin\'s rightful heir, ', true);
// $config['wordfilters'][] = array('/Bookchin/i', 'Postchin', true);
// $config['wordfilters'][] = array('/infrared/i', 'the cult', true);
// $config['wordfilters'][] = array('/ pill/i', ' vaccine', true);
// $config['wordfilters'][] = array('/crisis/i', 'christmas', true);
// $config['wordfilters'][] = array('/trannies/i', 'those that are advancing the dialectic of the human body and it\'s limitation', true);
// $config['wordfilters'][] = array('/dialectical materialism/i', 'magic', true);
// $config['wordfilters'][] = array('/dialectical/i', 'magic', true);
// $config['wordfilters'][] = array('/cope/i', 'i agreeing', true);
// $config['wordfilters'][] = array('/seethe/i', 'i agreeing', true);
// $config['wordfilters'][] = array('/liberal/i', 'leninist', true);

// Changes made via web editor by "zul_admin" @ Fri, 19 Feb 2021 15:06:33 -0800:
$config['reply_limit'] = 800;

