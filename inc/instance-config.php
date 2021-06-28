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
    'sfw' => '/sfw/',
    'alt' => '/alt/',
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

$config['search']['enable'] = true;
$config['flood_cache'] = 60 * 15; // 15 minutes. The oldest a post can be in the flood table
$config['flood_time_any'] = 20; // time between thread creation
$config['flood_time'] = 5;
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

//Date format
$config['post_date'] = '%F (%a) %T';

$config['thread_subject_in_title'] = true;

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
 * Basic captcha. See also: captchaconfig.php
 */
$config['securimage'] = false;

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
$config['thumb_ext'] = 'webp';
$config['gnu_md5'] = '1';


// Strip EXIF metadata from JPEG files.
$config['strip_exif'] = true;
// Use the command-line `exiftool` tool to strip EXIF metadata without decompressing/recompressing JPEGs.
$config['use_exiftool'] = true;

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
$config['flag_style'] = 'width:auto;max-height:16px;';
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
    'armchair' => 'Armchair',
    'atheism' => 'Atheism',
    'bolshevik' => 'Bolshevik',
    'brocialism' => 'Brocialism',
    'burkina_faso' => 'Burkina Faso',
    'ca' => 'Canadien',
    'carlism' => 'Carlism',
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
    'directx' => 'Direct X',
    'dprk' => 'DPRK',
    'egalitarianism' => 'Egalitarianism',
    'egoism' => 'Egoism',
    'eristocracy' => 'Έριστοκρατία',
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
    'luck_o_the_irish' => 'Luck O\' The Irish',
    'luxemburg' => 'Luxemburg',
    'marx' => 'Marx',
    'mutualism' => 'Mutualism',
    'naxalite' => 'Naxalite',
    'nazbol' => 'Nazbol',
    'nazi' => 'Nazi',
    'ndfp' => 'NDFP',
    'palestine' => 'Palestine',
    'pan-africanism' => 'Pan-Africanism',
    'phrygian_cap' => 'Phrygian Cap',
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
    'snibeti_snab' => 'Snibeti Snab',
    'socialism' => 'Socialism',
    'soviet_union' => 'Soviet Union',
    'spurdo' => 'Spurdo',
    'ssnp' => 'SSNP',
    'stalin' => 'Stalin',
    'syndicalism' => 'Syndicalism',
    'tankie' => 'Tankie',
    'technocracy' => 'Technocracy',
    'think' => 'Think',
    'transhumanism' => 'Transhumanism',
    'united_farm_workers' => 'United Farm Workers',
    'viet_cong' => 'Viet Cong',
    'ypg' => 'YPG',
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
$config['stylesheets']['Post-Left'] = 'dead.css';

$config['default_stylesheet'] = array('Dark Red', $config['stylesheets']['Dark Red']);
/*
 * ====================
 *  Javascript
 * ====================
 */

$config['additional_javascript'][] = 'js/jquery-ui.custom.min.js';
$config['additional_javascript'][] = 'js/ajax.js';

$config['additional_javascript'][] = 'js/options.js';
$config['additional_javascript'][] = 'js/strftime.min.js';
$config['additional_javascript'][] = 'js/local-time.js';
$config['additional_javascript'][] = 'js/auto-reload.js';
$config['additional_javascript'][] = 'js/auto-scroll.js';
$config['additional_javascript'][] = 'js/thread-stats.js';
$config['additional_javascript'][] = 'js/post-hover.js';
$config['additional_javascript'][] = 'js/style-select.js';
$config['additional_javascript'][] = 'js/flag-preview.js';

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

$config['flag_preview'] = true;

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

/*
 * Original wordfilters (Obsolete, this is the basic form of the newer version below)
 */
// $config['wordfilters'][] = array('/trann(y|ie)?/i', 'transhumanist', true);
// $config['wordfilters'][] = array('/nigger/i', 'uyghur', true);
// $config['wordfilters'][] = array('/nigg/i', 'uygh', true);

/*
 * Traditional word filters. Expires 31-12-2021.
 *
 * So, there are three flags at the end of each regex pattern, the "imu" at the end:
 * Case Insensitive, Multiline and UTF-8 (to avoid breaking non-English posts)
 * Let's take the nigg filter as an example.
 *
 * n+ [^a-z]* i+ [^a-z]* g+ [^a-z]* g+  ( [$x_alias] is just a set of common lookalike characters for x)
 *
 * Basic regex syntax: * means the preceeding element will be matched if it repeats 0 or more times. + will match 1 or more times
 * so a+ matches cat or caaat
 *
 * [] denotes a set of possible matches, so c[au]t matches 'cat' and 'cut'
 * [a-z] means any character from a to z and [^a-z] means any character that isn't in the alphabet (the starting ^ inverts the set)
 * We have the case insensitive flag so captials are included.
 *
 * The [$n_alias]+ means that nnnnnnigg still matches due to repetition
 * The [^a-z]* means that if someone does 'n..i..g..g', then the 0 or more non-alphabet padding
 * characters between the n, i, g, g are still matching. Note that it's 0 or more, not 1 or more, so 'nigg' still matches.
 *
 * [\p{L}] is a pre-made class of unicode letters (so for example an a with an accent is included)
 *
 * Example:
 * https://regex101.com/r/31wYx0/2
 *
 */
$a_alias = 'a4@ÁÀȦÂÄǞǍĂĀÃÅǺǼǢáàȧâäǟǎăāãåǻǽǣĄĄ̊ąą̊æɑÆⱭАа';
$g_alias = 'gǵġĝǧğǥɠǤƓǴĠĜǦĞĢ';
$i_alias = 'i1L||ıɩįɨɨ̧ĮƗƗ̧íìîïǐĭīĩịÍÌİÎÏǏĬĪĨỊĺļľŀḷḽІії';
$n_alias = 'nŋŉńṅňñņṋŃṄŇÑŅṊ';

$config['wordfilters'][] = array('/TRANN(Y|IE)?/', 'TRANSHUMANIST', true);
$config['wordfilters'][] = array('/NIGGA/', 'UYGHA', true);
$config['wordfilters'][] = array('/NIGGER/', 'UYGHUR', true);
$config['wordfilters'][] = array("/t[^\p{L}0-9]*r+[^\p{L}0-9]*[$a_alias]+[^\p{L}0-9]*[$n_alias]+[^\p{L}0-9]*[$n_alias]+[^\p{L}0-9]*(y|[$i_alias]+[^\p{L}0-9]*[e3е]+)?/imu", 'transhumanist', true);
$config['wordfilters'][] = array("/[$n_alias][^\p{L}0-9]*[$i_alias]+[^\p{L}0-9]*[$g_alias]+[^\p{L}0-9]*[$g_alias]+[^\p{L}0-9]*[e3е]+[^\p{L}0-9]*r/imu", 'uyghur', true);
$config['wordfilters'][] = array("/[$n_alias][^\p{L}0-9]*[$i_alias]+[^\p{L}0-9]*[$g_alias]+[^\p{L}0-9]*[$g_alias]+/imu", 'uygh', true);
$config['wordfilters'][] = array('/ewish uyghur/i', 'ewish nigger', true);


/*
 * Filters for diverting anorectal violence spammer
 */
$fakereason_ano = 'Due to automated child pornography and gore spam by /pol/, all posting now requires a pass.<br>To receive a one-week pass, email a short explanation of the Labor Theory of Value to space@national.shitposting.agency .';
$config['filters'][] = array(
    'condition' => array(
        'subject'  => '/anorectal/i', // Typical thread subject used
    ),
    'action' => 'reject',
    'message' => "$fakereason_ano" 
);
$config['filters'][] = array(
    'condition' => array(
        'filename'  => '/(TAKE ACTION v|trends.*associations|anusporn|anal insanity|anorectal risks|TAv[0-9]+|arisks)/', // Typical opening filename format. Their usual evasion strategy is to post only the image.
    ),
    'action' => 'reject',
    'message' => "$fakereason_ano"
);

// Favorite names and buzzterms
$config['filters'][] = array(
    'condition' => array(
        'body'  => '/(Rocco Siff|Evil Angel|Xavier Becerra|AdultDVDTalk|painal|Roughanal|anoreceptive|ltimately this is not about me|Logically-fallacious diversionary tactics)/',
    ),
    'action' => 'reject',
    'message' => "$fakereason_ano"
);

/*
 * Filter TheThingN0ticer ban evader
 */
event_handler('post', function($post, $tor) {
    if($post->board == 'leftypol'){
    // note: just posting nazi flag with name doesn't trigger, on purpose
    $n = 0;
    // body is just a twitter account (or has ?lang=en or something)
    if(preg_match('/^(https:\/\/)?(www.|m(obile)?.)?twitter\.com\/[a-zA-Z0-9_-]+\/?[#?&a-zA-Z0-9=_-]*(<tinyboard[^>]*>[^<]*<\/tinyboard>|<[^>]*>|\s)*$/',
                    $post->body_nomarkup)){$n+=2;}
    if($post->has_file && preg_match('/^Untitled[0-9]*.png/', $post->files[0]->filename)){$n+=2;}
    if($post->name != 'Anonymous'){$n++; if($post->name == 'NasheedsSeedAndFeed'){$n+=2;}}
    if(strpos($post->body_nomarkup,'<tinyboard flag>nazi</tinyboard>')){$n++;}
    
    if($n > 2){
        if($tor){return 'Flood detected; Post discarded.';}
        return 'Your IP address is listed in multirbl or rbl.efnetrbl.org.';
    }
    }
});


// Changes made via web editor by "zul_admin" @ Fri, 19 Feb 2021 15:06:33 -0800:
$config['reply_limit'] = 800;


// Changes made via web editor by "zul_admin" @ Tue, 27 Apr 2021 15:37:26 -0700:
$config['reply_limit'] = 600;


// Changes made via web editor by "zul_admin" @ Tue, 27 Apr 2021 15:39:19 -0700:
$config['max_body'] = 80000;

