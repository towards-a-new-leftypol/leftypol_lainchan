<?php
/*
 *  Copyright (c) 2010-2014 Tinyboard Development Group
 */

require_once 'inc/functions.php';
require_once 'inc/anti-bot.php';
require_once 'inc/bans.php';
require_once 'inc/anti-bot.php'; // DELETE ME FOR DEBUGGING ONLY

use Ramsey\Uuid\Uuid;

$dropped_post = false;

function handle_nntpchan() {
    global $config;
    if ($_SERVER['REMOTE_ADDR'] != $config['nntpchan']['trusted_peer']) {
        error("NNTPChan: Forbidden. $_SERVER[REMOTE_ADDR] is not a trusted peer");
    }

    $_POST = array();
    $_POST['json_response'] = true;

    $headers = json_encode($_GET);

    if (!isset ($_GET['Message-Id'])) {
        if (!isset ($_GET['Message-ID'])) {
            error("NNTPChan: No message ID");
        }
        else $msgid = $_GET['Message-ID'];
    }
    else $msgid = $_GET['Message-Id'];

    $groups = preg_split("/,\s*/", $_GET['Newsgroups']);
    if (count($groups) != 1) {
        error("NNTPChan: Messages can go to only one newsgroup");
    }
    $group = $groups[0];

    if (!isset($config['nntpchan']['dispatch'][$group])) {
        error("NNTPChan: We don't synchronize $group");
    }
    $xboard = $config['nntpchan']['dispatch'][$group];

    $ref = null;
    if (isset ($_GET['References'])) {
        $refs = preg_split("/,\s*/", $_GET['References']);

        if (count($refs) > 1) {
            error("NNTPChan: We don't support multiple references");
        }

        $ref = $refs[0];

        $query = prepare("SELECT `board`,`id` FROM ``nntp_references`` WHERE `message_id` = :ref");
        $query->bindValue(':ref', $ref);
        $query->execute() or error(db_error($query));

        $ary = $query->fetchAll(PDO::FETCH_ASSOC);

        if (count($ary) == 0) {
            error("NNTPChan: We don't have $ref that $msgid references");
        }

        $p_id = $ary[0]['id'];
        $p_board = $ary[0]['board'];

        if ($p_board != $xboard) {
            error("NNTPChan: Cross board references not allowed. Tried to reference $p_board on $xboard");
        }

        $_POST['thread'] = $p_id;
    }

    $date = isset($_GET['Date']) ? strtotime($_GET['Date']) : time();

    list($ct) = explode('; ', $_GET['Content-Type']);

    $query = prepare("SELECT COUNT(*) AS `c` FROM ``nntp_references`` WHERE `message_id` = :msgid");
    $query->bindValue(":msgid", $msgid);
    $query->execute() or error(db_error($query));

    $a = $query->fetch(PDO::FETCH_ASSOC);
    if ($a['c'] > 0) {
        error("NNTPChan: We already have this post. Post discarded.");
    }

    if ($ct == 'text/plain') {
        $content = file_get_contents("php://input");
    }
    elseif ($ct == 'multipart/mixed' || $ct == 'multipart/form-data') {
        _syslog(LOG_INFO, "MM: Files: ".print_r($GLOBALS, true)); // Debug

        $content = '';

        $newfiles = array();
        foreach ($_FILES['attachment']['error'] as $id => $error) {
            if ($_FILES['attachment']['type'][$id] == 'text/plain') {
                $content .= file_get_contents($_FILES['attachment']['tmp_name'][$id]);
            }
            elseif ($_FILES['attachment']['type'][$id] == 'message/rfc822') { // Signed message, ignore for now
            }
            else { // A real attachment :^)
                $file = array();
                $file['name']     = $_FILES['attachment']['name'][$id];
                $file['type']     = $_FILES['attachment']['type'][$id];
                $file['size']     = $_FILES['attachment']['size'][$id];
                $file['tmp_name'] = $_FILES['attachment']['tmp_name'][$id];
                $file['error']    = $_FILES['attachment']['error'][$id];

                $newfiles["file$id"] = $file;
            }
        }

        $_FILES = $newfiles;
    }
    else {
        error("NNTPChan: Wrong mime type: $ct");
    }

    $_POST['subject'] = isset($_GET['Subject']) ? ($_GET['Subject'] == 'None' ? '' : $_GET['Subject']) : '';
    $_POST['board'] = $xboard;

    if (isset ($_GET['From'])) {
        list($name, $mail) = explode(" <", $_GET['From'], 2);
        $mail = preg_replace('/>\s+$/', '', $mail);

        $_POST['name'] = $name;
        //$_POST['email'] = $mail;
        $_POST['email'] = '';
    }

    if (isset ($_GET['X_Sage'])) {
        $_POST['email'] = 'sage';
    }

    $content = preg_replace_callback('/>>([0-9a-fA-F]{6,})/', function($id) use ($xboard) {
        $id = $id[1];

        $query = prepare("SELECT `board`,`id` FROM ``nntp_references`` WHERE `message_id_digest` LIKE :rule");
        $idx = $id . "%";
        $query->bindValue(':rule', $idx);
        $query->execute() or error(db_error($query));
        
        $ary = $query->fetchAll(PDO::FETCH_ASSOC);
        if (count($ary) == 0) {
            return ">>>>$id";
        }
        else {
            $ret = array();
            foreach ($ary as $v) {
                if ($v['board'] != $xboard) {
                    $ret[] = ">>>/".$v['board']."/".$v['id'];
                }
                else {
                    $ret[] = ">>".$v['id'];
                }
            }
            return implode($ret, ", ");
        }
    }, $content);

    $_POST['body'] = $content;

    $dropped_post = array(
        'date' => $date,
        'board' => $xboard,
        'msgid' => $msgid,
        'headers' => $headers,
        'from_nntp' => true,
    );
     

}

function handle_delete() {
    // Delete
    global $config, $board, $mod;
    if (!isset($_POST['board'], $_POST['password']))
        error($config['error']['bot']);

    check_login(false);
    $is_mod = !!$mod;

    if (isset($_POST['mod']) && $_POST['mod'] && !$mod) {
        // Mismatched claims. (As stated below "Liar, you are not a mod.")
        error($config['error']['notamod']);
    }

    
    $password = &$_POST['password'];
    
    if ($password == '')
        error($config['error']['invalidpassword']);
    
    $delete = array();
    foreach ($_POST as $post => $value) {
        if (preg_match('/^delete_(\d+)$/', $post, $m)) {
            $delete[] = (int)$m[1];
        }
    }
    
    checkDNSBL();
        
    // Check if board exists
    if (!openBoard($_POST['board']))
        error($config['error']['noboard']);

    // Check if mod has permission to delete posts in this board
    if ($is_mod && !hasPermission($config['mod']['delete'], $board))
        error($config['error']['noaccess']);
    
    // Check if banned
    checkBan($board['uri']);

    // Check if deletion is enabled
    if (!$is_mod && !$config['allow_delete'])
        error(_('Post deletion is not allowed!'));
    
    if (empty($delete))
        error($config['error']['nodelete']);
        
    foreach ($delete as &$id) {
        $query = prepare(sprintf("SELECT `thread`, `time`,`password` FROM ``posts_%s`` WHERE `id` = :id", $board['uri']));
        $query->bindValue(':id', $id, PDO::PARAM_INT);
        $query->execute() or error(db_error($query));
        
        if ($post = $query->fetch(PDO::FETCH_ASSOC)) {
            $thread = false;
            if ($config['user_moderation'] && $post['thread']) {
                $thread_query = prepare(sprintf("SELECT `time`,`password` FROM ``posts_%s`` WHERE `id` = :id", $board['uri']));
                $thread_query->bindValue(':id', $post['thread'], PDO::PARAM_INT);
                $thread_query->execute() or error(db_error($query));

                $thread = $thread_query->fetch(PDO::FETCH_ASSOC);   
            }

            if (isset($config['allow_thread_deletion']) && !$config['allow_thread_deletion'] && !$post['thread']) {
                error($config['error']['nodeletethread']);
            }

            if ($password != '' 
                && $post['password'] != $password 
                && (!$thread || $thread['password'] != $password)
                && !$is_mod) {
                error($config['error']['invalidpassword']);
            }
            
            if ($post['time'] > time() - $config['delete_time']) {
                error(sprintf($config['error']['delete_too_soon'], until($post['time'] + $config['delete_time'])));
            }
            
            if (isset($_POST['file'])) {
                // Delete just the file
                deleteFile($id, true, null, true);
                modLog("User deleted file from his own post #$id");
            } else {
                // Delete entire post
                deletePost($id, true, true, true);
                modLog("User deleted his own post #$id");
            }
            
            _syslog(LOG_INFO, 'Deleted post: ' .
            '/' . $board['dir'] . $config['dir']['res'] . sprintf($config['file_page'], $post['thread'] ? $post['thread'] : $id) . ($post['thread'] ? '#' . $id : '')   
            );
        }
    }
    
    buildIndex();

    
    $root = $is_mod ? $config['root'] . $config['file_mod'] . '?/' : $config['root'];

    if (!isset($_POST['json_response'])) {
        // If only deleting a post in a thread, redirect to the current thread
        header('Location: ' . $root . $board['dir'] . ($post['thread'] ? $config['dir']['res'] . sprintf($config['file_page'], $post['thread']) : $config['file_index']), true, $config['redirect_http']);
    } else {
        header('Content-Type: text/json');
        echo json_encode(array('success' => true));
    }

    // We are already done, let's continue our heavy-lifting work in the background (if we run off FastCGI)
    if (function_exists('fastcgi_finish_request')) {
        @fastcgi_finish_request();
    }

    rebuildThemes('post-delete', $board['uri']);

}

function handle_report(){
    global $config,$board;
    if (!isset($_POST['board'], $_POST['reason']))
        error($config['error']['bot']);
    
    $report = array();
    foreach ($_POST as $post => $value) {
        if (preg_match('/^delete_(\d+)$/', $post, $m)) {
            $report[] = (int)$m[1];
        }
    }
    
    checkDNSBL();
        
    // Check if board exists
    if (!openBoard($_POST['board']))
        error($config['error']['noboard']);
    
    // Check if banned
    checkBan($board['uri']);
    
    if (empty($report))
        error($config['error']['noreport']);
    
    if (strlen($_POST['reason']) > $config['report_max_length'])
        error($config['error']['toolongreport']);
    
    if (count($report) > $config['report_limit'])
        error($config['error']['toomanyreports']);

    if ($config['report_captcha'] && !isset($_POST['captcha_text'], $_POST['captcha_cookie'])) {
        error($config['error']['bot']);
    }

    if ($config['report_captcha']) {
        $resp = file_get_contents($config['captcha']['provider_check'] . "?" . http_build_query([
            'mode' => 'check',
            'text' => $_POST['captcha_text'],
            'extra' => $config['captcha']['extra'],
            'cookie' => $_POST['captcha_cookie']
        ]));

        if ($resp !== '1') {
                        error($config['error']['captcha']);
        }
    }
    
    $reason = escape_markup_modifiers($_POST['reason']);
    markup($reason);
    
    foreach ($report as $id) {
        $query = prepare(sprintf("SELECT `id`,`thread` , `body_nomarkup` FROM ``posts_%s`` WHERE `id` = :id", $board['uri']));
        $query->bindValue(':id', $id, PDO::PARAM_INT);
        $query->execute() or error(db_error($query));
        
        $thread = $query->fetch(PDO::FETCH_ASSOC);

        $error = event('report', array('ip' => $_SERVER['REMOTE_ADDR'], 'board' => $board['uri'], 'post' => $post, 'reason' => $reason, 'link' => link_for($thread)));
        if ($error) {
            error($error);
        }

        if ($config['syslog'])
            _syslog(LOG_INFO, 'Reported post: ' .
                '/' . $board['dir'] . $config['dir']['res'] . link_for($thread) . ($thread['thread'] ? '#' . $id : '') .
                ' for "' . $reason . '"'
            );
        $query = prepare("INSERT INTO ``reports`` VALUES (NULL, :time, :ip, :board, :post, :reason)");
        $query->bindValue(':time', time(), PDO::PARAM_INT);
        $query->bindValue(':ip', $_SERVER['REMOTE_ADDR'], PDO::PARAM_STR);
        $query->bindValue(':board', $board['uri'], PDO::PARAM_STR);
        $query->bindValue(':post', $id, PDO::PARAM_INT);
        $query->bindValue(':reason', $reason, PDO::PARAM_STR);
        $query->execute() or error(db_error($query));
        
        if ($config['slack'])
        {
            
        function slack($message, $room = "reports", $icon = ":no_entry_sign:") 
        {
        $room = ($room) ? $room : "reports";
        $data = "payload=" . json_encode(array(
        "channel"       =>  "#{$room}",
        "text"          =>  urlencode($message),
        "icon_emoji"    =>  $icon
        ));

        // You can get your webhook endpoint from your Slack settings
        // For some reason using the configuration key doesn't work 
        $ch = curl_init($config['slack_incoming_webhook_endpoint']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
        }
        
        $postcontent = mb_substr($thread['body_nomarkup'], 0, 120) . '...  _*(POST TRIMMED)*_';
        $slackmessage = '<'  .$config['domain']  . "/mod.php?/" . $board['dir'] . $config['dir']['res'] .  ( $thread['thread'] ? $thread['thread'] : $id ) . ".html"  .  ($thread['thread'] ? '#' . $id : '') . '> \n ' . $reason . '\n ' . $postcontent . '\n';

        $slackresult = slack($slackmessage, $config['slack_channel']); 
        
        }


    }
    
    $is_mod = isset($_POST['mod']) && $_POST['mod'];
    $root = $is_mod ? $config['root'] . $config['file_mod'] . '?/' : $config['root'];
    
    if (!isset($_POST['json_response'])) {
        $index = $root . $board['dir'] . $config['file_index'];
        $reported_post = $root . $board['dir']  . $config['dir']['res'] .  ( $thread['thread'] ? $thread['thread'] : $id ) . ".html"  .  ($thread['thread'] ? '#' . $id : '') ;
        //header('Location: ' . $reported_post);
  
        echo Element('page.html', array('config' => $config, 'body' => '<div style="text-align:center"><a href="javascript:window.close()">[ ' . _('Close window') ." ]</a> <a href='$index'>[ " . _('Return') . ' ]</a></div>', 'title' => _('Report submitted!')));
    } else {
        header('Content-Type: text/json');
        echo json_encode(array('success' => true));
    }

}

function handle_post(){
    global $config,$dropped_post,$board, $mod,$pdo;

    print_err('handle_post BEGIN');

    if (!isset($_POST['body'], $_POST['board']) && !$dropped_post) {
        print_err('handle_post bot error 1');
        error($config['error']['bot']);
    }

    print_err('handle_post NO SUCH bot error 1');

    $post = array('board' => $_POST['board'], 'files' => array());

    // Check if board exists
    if (!openBoard($post['board']))
        error($config['error']['noboard']);

    $board_locked_check = (!isset($_POST['mod']) || !$_POST['mod'])
        && ($config['board_locked']===true
        || (is_array($config['board_locked']) && in_array(strtolower($_POST['board']), $config['board_locked'])));

    if ($board_locked_check){
        error("Board is locked");
    }

    if (!isset($_POST['name']))
        $_POST['name'] = $config['anonymous'];
    
    if (!isset($_POST['email']))
        $_POST['email'] = '';
    
    if (!isset($_POST['subject']))
        $_POST['subject'] = '';
    
    if (!isset($_POST['password']))
        $_POST['password'] = '';    
    
    if (isset($_POST['thread'])) {
        $post['op'] = false;
        $post['thread'] = round($_POST['thread']);
    } else {
        $post['op'] = true;
    }


    if (!$dropped_post) {
        // Check for CAPTCHA right after opening the board so the "return" link is in there
        if ($config['recaptcha']) {
            if (!isset($_POST['g-recaptcha-response']))
                error($config['error']['bot']); 
            // Check what reCAPTCHA has to say...
            $resp = json_decode(file_get_contents(sprintf('https://www.google.com/recaptcha/api/siteverify?secret=%s&response=%s&remoteip=%s',
                $config['recaptcha_private'],
                urlencode($_POST['g-recaptcha-response']),
                $_SERVER['REMOTE_ADDR'])), true);

            if (!$resp['success']) {
                error($config['error']['captcha']);
            }
        }

        if(isset($config['securimage']) && $config['securimage']){

            if(!isset($_POST['captcha'])){
                error($config['error']['securimage']['missing']);
            }

            if(empty($_POST['captcha'])){
                error($config['error']['securimage']['empty']);
            }

            $query=prepare('DELETE FROM captchas WHERE time<DATE_SUB(NOW(), INTERVAL 30 MINUTE)');
            $query=prepare('DELETE FROM captchas WHERE ip=:ip AND code=:code LIMIT 1');
            $query->bindValue(':ip', $_SERVER['REMOTE_ADDR']);
            $query->bindValue(':code', $_POST['captcha']);
            $query->execute();

            if($query->rowCount()==0){
                error($config['error']['securimage']['bad']);
            }
        }

        if (!(($post['op'] && $_POST['post'] == $config['button_newtopic']) ||
            (!$post['op'] && $_POST['post'] == $config['button_reply']))) {

            error($config['error']['bot']);
        }

        // Check the referrer
        if ($config['referer_match'] !== false &&
            (!isset($_SERVER['HTTP_REFERER']) || !preg_match($config['referer_match'], rawurldecode($_SERVER['HTTP_REFERER'])))) {
            error($config['error']['referer']);
        }

        checkDNSBL();
        
        // Check if banned
        checkBan($board['uri']);

        if ($post['mod'] = isset($_POST['mod']) && $_POST['mod']) {
            check_login(false);
            if (!$mod) {
                // Liar. You're not a mod.
                error($config['error']['notamod']);
            }
        
            $post['sticky'] = $post['op'] && isset($_POST['sticky']);
            $post['locked'] = $post['op'] && isset($_POST['lock']);
            $post['raw'] = isset($_POST['raw']);
        
            if ($post['sticky'] && !hasPermission($config['mod']['sticky'], $board['uri']))
                error($config['error']['noaccess']);
            if ($post['locked'] && !hasPermission($config['mod']['lock'], $board['uri']))
                error($config['error']['noaccess']);
            if ($post['raw'] && !hasPermission($config['mod']['rawhtml'], $board['uri']))
                error($config['error']['noaccess']);
        }

        if (!$post['mod'] && $config['spam']['enabled'] == true) {
            $post['antispam_hash'] = checkSpam(
                array($board['uri'],
                isset($post['thread']) ? $post['thread'] : ($config['try_smarter'] && isset($_POST['page']) ? 0 - (int)$_POST['page'] : null))
            );
            //$post['antispam_hash'] = checkSpam();

            if ($post['antispam_hash'] === true) {
                error($config['error']['spam']);
            }
        }
    
        if ($config['robot_enable'] && $config['robot_mute']) {
            checkMute();
        }
    }
    else {
        $mod = $post['mod'] = false;
    }
    
    //Check if thread exists
    if (!$post['op']) {
        $query = prepare(sprintf("SELECT `sticky`,`locked`,`cycle`,`sage`,`slug` FROM ``posts_%s`` WHERE `id` = :id AND `thread` IS NULL LIMIT 1", $board['uri']));
        $query->bindValue(':id', $post['thread'], PDO::PARAM_INT);
        $query->execute() or error(db_error());
        
        if (!$thread = $query->fetch(PDO::FETCH_ASSOC)) {
            // Non-existant
            error($config['error']['nonexistant']);
        }
    }
    else {
        $thread = false;
    }

    
    // Check for an embed field
    if ($config['enable_embedding'] && isset($_POST['embed']) && !empty($_POST['embed'])) {
        // yep; validate it
        $value = $_POST['embed'];
        foreach ($config['embedding'] as &$embed) {
            if (preg_match($embed[0], $value)) {
                // Valid link
                $post['embed'] = $value;
                // This is bad, lol.
                $post['no_longer_require_an_image_for_op'] = true;
                break;
            }
        }
        if (!isset($post['embed'])) {
            error($config['error']['invalid_embed']);
        }
    }

    if (!hasPermission($config['mod']['bypass_field_disable'], $board['uri'])) {
        if ($config['field_disable_name'])
            $_POST['name'] = $config['anonymous']; // "forced anonymous"
    
        if ($config['field_disable_email'])
            $_POST['email'] = '';
    
        if ($config['field_disable_password'])
            $_POST['password'] = '';
    
        if ($config['field_disable_subject'] || (!$post['op'] && $config['field_disable_reply_subject']))
            $_POST['subject'] = '';
    }
    
    if ($config['allow_upload_by_url'] && isset($_POST['file_url1']) && !empty($_POST['file_url1'])) {
        function unlink_tmp_file($file) {
            @unlink($file);
            fatal_error_handler();
        }

        function upload_by_url($config,$post,$url) {
        $post['file_url'] = $url;
        if (!preg_match('@^https?://@', $post['file_url']))
            error($config['error']['invalidimg']);
        
        if (mb_strpos($post['file_url'], '?') !== false)
            $url_without_params = mb_substr($post['file_url'], 0, mb_strpos($post['file_url'], '?'));
        else
            $url_without_params = $post['file_url'];

        $post['extension'] = strtolower(mb_substr($url_without_params, mb_strrpos($url_without_params, '.') + 1));

        if ($post['op'] && $config['allowed_ext_op']) {
            if (!in_array($post['extension'], $config['allowed_ext_op']))
                error($config['error']['unknownext']);
        }
        else if (!in_array($post['extension'], $config['allowed_ext']) && !in_array($post['extension'], $config['allowed_ext_files']))
            error($config['error']['unknownext']);

        $post['file_tmp'] = tempnam($config['tmp'], 'url');
        register_shutdown_function('unlink_tmp_file', $post['file_tmp']);
        
        $fp = fopen($post['file_tmp'], 'w');
        
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $post['file_url']);
        curl_setopt($curl, CURLOPT_FAILONERROR, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curl, CURLOPT_TIMEOUT, $config['upload_by_url_timeout']);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Tinyboard');
        curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($curl, CURLOPT_FILE, $fp);
        curl_setopt($curl, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
            curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );  

        if (curl_exec($curl) === false)
            error($config['error']['nomove'] . '<br/>Curl says: ' . curl_error($curl));
        
        curl_close($curl);
        
        fclose($fp);

        $_FILES[$post['file_tmp']] = array(
            'name' => basename($url_without_params),
            'tmp_name' => $post['file_tmp'],
            'file_tmp' => true,
            'error' => 0,
            'size' => filesize($post['file_tmp'])
        );
        }
            
        for( $counter = 1; $counter <= $config['max_images']; $counter++ ) { 
            $varname = "file_url". $counter;
            if (isset($_POST[$varname]) && !empty($_POST[$varname])){
                upload_by_url($config,$post,$_POST[$varname]);
            }
        }
            
    }

    $post['name'] = $_POST['name'] != '' ? $_POST['name'] : $config['anonymous'];
    $post['subject'] = $_POST['subject'];
    $post['email'] = str_replace(' ', '%20', htmlspecialchars($_POST['email']));
    $post['body'] = $_POST['body'];
    $post['raw_body'] = $_POST['body'];
    $post['password'] = $_POST['password'];
    $post['has_file'] = (!isset($post['embed']) && (($post['op'] && !isset($post['no_longer_require_an_image_for_op']) && $config['force_image_op']) || count($_FILES) > 0));
    
    if (!$dropped_post) {
        if (!($post['has_file'] || isset($post['embed'])) || (($post['op'] && $config['force_body_op']) || (!$post['op'] && $config['force_body']))) {
            $stripped_whitespace = preg_replace('/[\s]/u', '', $post['body']);
            if ($stripped_whitespace == '') {
                error($config['error']['tooshort_body']);
            }
        }
    
        if (!$post['op']) {
            // Check if thread is locked
            // but allow mods to post
            if ($thread['locked'] && !hasPermission($config['mod']['postinlocked'], $board['uri'])) {
                error($config['error']['locked']);
            }
        
            $numposts = numPosts($post['thread']);
            
            $replythreshold = isset($thread['cycle']) && $thread['cycle'] ? $numposts['replies'] - 1 : $numposts['replies'];    
            $imagethreshold = isset($thread['cycle']) && $thread['cycle'] ? $numposts['images'] - 1 : $numposts['images'];  

            if ($config['reply_hard_limit'] != 0 && $config['reply_hard_limit'] <= $replythreshold) {
                error($config['error']['reply_hard_limit']);
            }
        
            if ($post['has_file'] && $config['image_hard_limit'] != 0 && $config['image_hard_limit'] <= $imagethreshold) {
                error($config['error']['image_hard_limit']);
            }
        }
    }
    else {
        if (!$post['op']) {
      $numposts = numPosts($post['thread']);
        }
    }

    if ($post['has_file']) {
        // Determine size sanity
        $size = 0;
        if ($config['multiimage_method'] == 'split') {
            foreach ($_FILES as $key => $file) {
                $size += $file['size'];
            }
        } elseif ($config['multiimage_method'] == 'each') {
            foreach ($_FILES as $key => $file) {
                if ($file['size'] > $size) {
                    $size = $file['size'];
                }
            }
        } else {
            error(_('Unrecognized file size determination method.'));
        }
        $max_size = $config['max_filesize'];

        if (array_key_exists('board_specific',$config)){
            if (array_key_exists($board['uri'],$config['board_specific'])){
                if (array_key_exists('max_filesize',$config['board_specific'][$board['uri']])){
                    $max_size = $config['board_specific'][$board['uri']]['max_filesize'];
                }
            }
        }

        if ($size > $max_size)
            error(sprintf3($config['error']['filesize'], array(
                'sz' => number_format($size),
                'filesz' => number_format($size),
                'maxsz' => number_format($config['max_filesize'])
            )));
        $post['filesize'] = $size;
    }

    $post['capcode'] = false;
    
    if ($mod && preg_match('/^((.+) )?## (.+)$/', $post['name'], $matches)) {
        $name = $matches[2] != '' ? $matches[2] : $config['anonymous'];
        $cap = $matches[3];
        
        if (isset($config['mod']['capcode'][$mod['type']])) {
            if (    $config['mod']['capcode'][$mod['type']] === true ||
                (is_array($config['mod']['capcode'][$mod['type']]) &&
                    in_array($cap, $config['mod']['capcode'][$mod['type']])
                )) {
                
                $post['capcode'] = utf8tohtml($cap);
                $post['name'] = $name;
            }
        }
    }
    else if ($config['joke_capcode']) {
        if (strtolower($post['email']) == 'joke') {
            if (isset($config['joke_capcode_default'])){
                $cap = $config['joke_capcode_default'];
            }
            else {
                $cap = "joke";
            }
            $post['capcode'] = utf8tohtml($cap);
            $post['email'] = '';
        }
    }
    
    $trip = generate_tripcode($post['name']);
    $post['name'] = $trip[0];
    $post['trip'] = isset($trip[1]) ? $trip[1] : ''; // XX: Dropped posts and tripcodes
    
    $noko = false;
    if (strtolower($post['email']) == 'noko') {
        $noko = true;
        $post['email'] = '';
    } elseif (strtolower($post['email']) == 'nonoko'){
        $noko = false;
        $post['email'] = '';
    } else $noko = $config['always_noko'];
    
    if ($post['has_file']) {
        $i = 0;
        foreach ($_FILES as $key => $file) {
            if ($file['size'] && $file['tmp_name']) {
                $file['filename'] = urldecode($file['name']);
                $file['extension'] = strtolower(mb_substr($file['filename'], mb_strrpos($file['filename'], '.') + 1));
                if (isset($config['filename_func']))
                    $file['file_id'] = $config['filename_func']($file);
                else
                    $file['file_id'] = time() . substr(microtime(), 2, 3);

                if (sizeof($_FILES) > 1)
                    $file['file_id'] .= "-$i";
                
                $file['file'] = $board['dir'] . $config['dir']['img'] . $file['file_id'] . '.' . $file['extension'];
                $file['thumb'] = $board['dir'] . $config['dir']['thumb'] . $file['file_id'] . '.' . ($config['thumb_ext'] ? $config['thumb_ext'] : $file['extension']);
                $post['files'][] = $file;
                $i++;
            }
        }
    }

    if (empty($post['files'])) $post['has_file'] = false;

    if (!$dropped_post) {
        // Check for a file
        if ($post['op'] && !isset($post['no_longer_require_an_image_for_op'])) {
            if (!$post['has_file'] && $config['force_image_op']) {
                error($config['error']['noimage']);
            }
        }

        // Check for too many files
        if (sizeof($post['files']) > $config['max_images']) {
            error($config['error']['toomanyimages']);
        }
    }

    if ($config['strip_combining_chars']) {
        $post['name'] = strip_combining_chars($post['name']);
        $post['email'] = strip_combining_chars($post['email']);
        $post['subject'] = strip_combining_chars($post['subject']);
        $post['body'] = strip_combining_chars($post['body']);
    }

    if (!$dropped_post) {
        // Check string lengths
        if (mb_strlen($post['name']) > 35) {
            error(sprintf($config['error']['toolong'], 'name'));    
        }
        if (mb_strlen($post['email']) > 40) {
            error(sprintf($config['error']['toolong'], 'email'));
        }
        if (mb_strlen($post['subject']) > 100) {
            error(sprintf($config['error']['toolong'], 'subject'));
        }
        if (!$mod && mb_strlen($post['body']) > $config['max_body']) {
            error($config['error']['toolong_body']);
        }
        if (!$mod && mb_strlen($post['body']) > 0 && (mb_strlen($post['body']) < $config['min_body'])) {
            error($config['error']['tooshort_body']);
        }
        if (mb_strlen($post['password']) > 20) {
            error(sprintf($config['error']['toolong'], 'password'));
        }
    }

    wordfilters($post['body']);
    
    $post['body'] = escape_markup_modifiers($post['body']);

    if ($mod && isset($post['raw']) && $post['raw']) {
        $post['body'] .= "\n<tinyboard raw html>1</tinyboard>";
    }
    
    if (!$dropped_post)
    if (($config['country_flags'] && !$config['allow_no_country']) || ($config['country_flags'] && $config['allow_no_country'] && !isset($_POST['no_country']))) {
        require 'inc/lib/geoip/geoip.inc';
        $gi=geoip\geoip_open('inc/lib/geoip/GeoIPv6.dat', GEOIP_STANDARD);
    
        function ipv4to6($ip) {
            if (strpos($ip, ':') !== false) {
                if (strpos($ip, '.') > 0)
                    $ip = substr($ip, strrpos($ip, ':')+1);
                else return $ip;  //native ipv6
            }
            $iparr = array_pad(explode('.', $ip), 4, 0);
            $part7 = base_convert(($iparr[0] * 256) + $iparr[1], 10, 16);
            $part8 = base_convert(($iparr[2] * 256) + $iparr[3], 10, 16);
            return '::ffff:'.$part7.':'.$part8;
        }
    
        if ($country_code = geoip\geoip_country_code_by_addr_v6($gi, ipv4to6($_SERVER['REMOTE_ADDR']))) {
            if (!in_array(strtolower($country_code), array('eu', 'ap', 'o1', 'a1', 'a2')))
                $post['body'] .= "\n<tinyboard flag>".strtolower($country_code)."</tinyboard>".
                "\n<tinyboard flag alt>".geoip\geoip_country_name_by_addr_v6($gi, ipv4to6($_SERVER['REMOTE_ADDR']))."</tinyboard>";
        }
    }

    if ($config['user_flag'] && isset($_POST['user_flag']))
    if (!empty($_POST['user_flag']) ){
        
        $user_flag = $_POST['user_flag'];

        if (!isset($config['user_flags'][$user_flag]))
            error(_('Invalid flag selection!'));

        $flag_alt = isset($user_flag_alt) ? $user_flag_alt : $config['user_flags'][$user_flag];

        $post['body'] .= "\n<tinyboard flag>" . strtolower($user_flag) . "</tinyboard>" .
        "\n<tinyboard flag alt>" . $flag_alt . "</tinyboard>";
    }

    if ($config['allowed_tags'] && $post['op'] && isset($_POST['tag']) && isset($config['allowed_tags'][$_POST['tag']])) {
        $post['body'] .= "\n<tinyboard tag>" . $_POST['tag'] . "</tinyboard>";
    }

    if (!$dropped_post)
        if ($config['proxy_save'] && isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $proxy = preg_replace("/[^0-9a-fA-F.,: ]/", '', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $post['body'] .= "\n<tinyboard proxy>".$proxy."</tinyboard>";
    }

    if (mysql_version() >= 50503) {
        $post['body_nomarkup'] = $post['body']; // Assume we're using the utf8mb4 charset
    } else {
        // MySQL's `utf8` charset only supports up to 3-byte symbols
        // Remove anything >= 0x010000
        
        $chars = preg_split('//u', $post['body'], -1, PREG_SPLIT_NO_EMPTY);
        $post['body_nomarkup'] = '';
        foreach ($chars as $char) {
            $o = 0;
            $ord = ordutf8($char, $o);
            if ($ord >= 0x010000)
                continue;
            $post['body_nomarkup'] .= $char;
        }
    }

    $post['tracked_cites'] = markup($post['body'], true);

    if ($post['has_file']) {
        $md5cmd = false;
        if ($config['bsd_md5'])  $md5cmd = '/sbin/md5 -r';
        if ($config['gnu_md5'])  $md5cmd = 'md5sum';

        $allhashes = '';

        foreach ($post['files'] as $key => &$file) {
            if ($post['op'] && $config['allowed_ext_op']) {
                if (!in_array($file['extension'], $config['allowed_ext_op'])) {
                    error($config['error']['unknownext']);
                }
            } elseif (!in_array($file['extension'], $config['allowed_ext']) && !in_array($file['extension'], $config['allowed_ext_files'])) {
                error($config['error']['unknownext']);
            }
            
            $file['is_an_image'] = !in_array($file['extension'], $config['allowed_ext_files']);
            
            // Truncate filename if it is too long
            $file['filename'] = mb_substr($file['filename'], 0, $config['max_filename_len']);
            
            $upload = $file['tmp_name'];
            
            if (!is_readable($upload))
                error($config['error']['nomove']);

            if ($md5cmd) {
                $output = shell_exec_error($md5cmd . " " . escapeshellarg($upload));
                $output = explode(' ', $output);
                $hash = $output[0];
            }
            else {
                $hash = md5_file($upload);
            }

            $file['hash'] = $hash;
            $allhashes .= $hash;
        }

        if (count ($post['files']) == 1) {
            $post['filehash'] = $hash;
        }
        else {
            $post['filehash'] = md5($allhashes);
        }
    }

    if ($config['spam_noticer']['enabled']) {
        require_once 'inc/spamnoticer.php';

        $delete_token_uuid = Uuid::uuid4(); // Generate a version 4 (random) UUID
        $delete_token = $delete_token_uuid->toString();
        $post['delete_token'] = $delete_token;

        $spam_noticer_result = checkWithSpamNoticer($config, $post, $board['uri']);

        if ($spam_noticer_result->succeeded) {
            if ($spam_noticer_result->noticed) {
                error($config['error']['spam_noticer'] . $spam_noticer_result->reason);
            }
        } else {
            print_err($spam_noticer_result->reason);
        }
    }

    if (!hasPermission($config['mod']['bypass_filters'], $board['uri']) && !$dropped_post) {
        require_once 'inc/filters.php';

        do_filters($post);
    }

    if ($post['has_file']) {
        foreach ($post['files'] as $key => &$file) {
        if ($file['is_an_image']) {
            if ($config['ie_mime_type_detection'] !== false) {
                // Check IE MIME type detection XSS exploit
                $buffer = file_get_contents($upload, false, null, 0, 255);
                if (preg_match($config['ie_mime_type_detection'], $buffer)) {
                    undoImage($post);
                    error($config['error']['mime_exploit']);
                }
            }
            
            require_once 'inc/image.php';
            
            // find dimensions of an image using GD
            if (!$size = @getimagesize($file['tmp_name'])) {
                error($config['error']['invalidimg']);
            }
            if (!in_array($size[2], array(IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_BMP))) {
                error($config['error']['invalidimg']);
            }
            if ($size[0] > $config['max_width'] || $size[1] > $config['max_height']) {
                error($config['error']['maxsize']);
            }
            
            if ($config['convert_auto_orient'] && ($file['extension'] == 'jpg' || $file['extension'] == 'jpeg')) {
                // The following code corrects the image orientation.
                // Currently only works with the 'convert' option selected but it could easily be expanded to work with the rest if you can be bothered.
                if (!($config['redraw_image'] || (($config['strip_exif'] && !$config['use_exiftool']) && ($file['extension'] == 'jpg' || $file['extension'] == 'jpeg')))) {
                    if (in_array($config['thumb_method'], array('convert', 'convert+gifsicle', 'gm', 'gm+gifsicle'))) {
                        $exif = @exif_read_data($file['tmp_name']);
                        $gm = in_array($config['thumb_method'], array('gm', 'gm+gifsicle'));
                        if (isset($exif['Orientation']) && $exif['Orientation'] != 1) {
                            if ($config['convert_manual_orient']) {
                                $error = shell_exec_error(($gm ? 'gm ' : '') . 'convert ' .
                                    escapeshellarg($file['tmp_name']) . ' ' .
                                    ImageConvert::jpeg_exif_orientation(false, $exif) . ' ' .
                                    ($config['strip_exif'] ? '+profile "*"' :
                                        ($config['use_exiftool'] ? '' : '+profile "*"')
                                    ) . ' ' .
                                    escapeshellarg($file['tmp_name']));
                                if ($config['use_exiftool'] && !$config['strip_exif']) {
                                    if ($exiftool_error = shell_exec_error(
                                        'exiftool -overwrite_original -q -q -orientation=1 -n ' .
                                            escapeshellarg($file['tmp_name'])))
                                        error(_('exiftool failed!'), null, $exiftool_error);
                                } else {
                                    // TODO: Find another way to remove the Orientation tag from the EXIF profile
                                    // without needing `exiftool`.
                                }
                            } else {
                                $error = shell_exec_error(($gm ? 'gm ' : '') . 'convert ' .
                                        escapeshellarg($file['tmp_name']) . ' -auto-orient ' . escapeshellarg($upload));
                            }
                            if ($error) {
                                error(_('Could not auto-orient image!'), null, $error);
                            }
                            $size = @getimagesize($file['tmp_name']);
                            if ($config['strip_exif'])
                                $file['exif_stripped'] = true;
                        }
                    }
                }
            }

            // create image object
            $image = new Image($file['tmp_name'], $file['extension'], $size);
            if ($image->size->width > $config['max_width'] || $image->size->height > $config['max_height']) {
                $image->delete();

                error($config['error']['maxsize']);
            }
            
            $file['width'] = $image->size->width;
            $file['height'] = $image->size->height;
            
            if ($config['spoiler_images'] && isset($_POST['spoiler'])) {
                $file['thumb'] = 'spoiler';
                
                $size = @getimagesize($config['spoiler_image']);
                $file['thumbwidth'] = $size[0];
                $file['thumbheight'] = $size[1];
            } elseif ($config['minimum_copy_resize'] &&
                $image->size->width <= $config['thumb_width'] &&
                $image->size->height <= $config['thumb_height'] &&
                $file['extension'] == ($config['thumb_ext'] ? $config['thumb_ext'] : $file['extension'])) {
            
                // Copy, because there's nothing to resize
                copy($file['tmp_name'], $file['thumb']);
            
                $file['thumbwidth'] = $image->size->width;
                $file['thumbheight'] = $image->size->height;
            } else {
                $thumb = $image->resize(
                    $config['thumb_ext'] ? $config['thumb_ext'] : $file['extension'],
                    $post['op'] ? $config['thumb_op_width'] : $config['thumb_width'],
                    $post['op'] ? $config['thumb_op_height'] : $config['thumb_height']
                );
                
                $thumb->to($file['thumb']);

                $file['thumbwidth'] = $thumb->width;
                $file['thumbheight'] = $thumb->height;
            
                $thumb->_destroy();
            }

            if ($config['redraw_image'] || (!@$file['exif_stripped'] && $config['strip_exif'] && ($file['extension'] == 'jpg' || $file['extension'] == 'jpeg'))) {
                if (!$config['redraw_image'] && $config['use_exiftool']) {
                    if($error = shell_exec_error('exiftool -overwrite_original -ignoreMinorErrors -q -q -all= ' .
                        escapeshellarg($file['tmp_name']))) {
                        error(_('Could not strip EXIF metadata!'), null, $error);
                    } else {
                        clearstatcache(true, $file['tmp_name']);
                        if (($newfilesize = filesize($file['tmp_name'])) !== false)
                            $file['size'] = $newfilesize;
                    }
                } else {
                    $image->to($file['file']);
                    $dont_copy_file = true;
                }
            }
            $image->destroy();
        } else {
            if (($file['extension'] == "pdf" && $config['pdf_file_thumbnail']) || 
                ($file['extension'] == "djvu" && $config['djvu_file_thumbnail']) ){
                $path = $file['thumb'];
                $error = shell_exec_error( 'convert -thumbnail x300 -background white -alpha remove ' .
                escapeshellarg($file['tmp_name']. '[0]') . ' ' .
                escapeshellarg($file['thumb']));

                if ($error){
                    $path = sprintf($config['file_thumb'],isset($config['file_icons'][$file['extension']]) ? $config['file_icons'][$file['extension']] : $config['file_icons']['default']); 
                }

                $file['thumb'] = basename($file['thumb']);
                $size = @getimagesize($path);
                $file['thumbwidth'] = $size[0];
                $file['thumbheight'] = $size[1];
                $file['width'] = $size[0];
                $file['height'] = $size[1];
            }
            /*if (($file['extension'] == "epub" && $config['epub_file_thumbnail'])){ 
                $path = $file['thumb'];
                // Open epub
                // Get file list
                // Check if cover file exists according to regex if it does use it
                // Otherwise check if metadata file exists, and if does get rootfile and search for manifest for cover file name 
                // Otherwise Check if other image files exist and use them, based on criteria to pick the best one.
                // Once we have filename extract said file from epub to file['thumb'] location. 
                $zip = new ZipArchive();
                if(@$zip->open($path)){
                    $filename = "";
                    // Go looking for a file name, current implementation just uses regex but should fallback to
                    // getting all images and then choosing one.
                    for( $i = 0; $i < $zip->numFiles; $i++ ){ 
                        $stat = $zip->statIndex( $i ); 
                        $matches = array();
                        if (preg_match('/.*cover.*\.(jpg|jpeg|png)/', $stat['name'], $matches)) {
                            $filename = $matches[0];
                                break;      
                        }
                    } 
                    // We have a cover filename to extract.         
                    if (strlen($filename) > 0){
                        //$zip->extractTo(dirname($file['thumb']), array($filename));
                    }
                    else {
                    $error = 1;
                    }

                }
                else {
                    $error = 1;
                }
                
                if ($error){
                    $path = sprintf($config['file_thumb'],isset($config['file_icons'][$file['extension']]) ? $config['file_icons'][$file['extension']] : $config['file_icons']['default']); 
                }

                $file['thumb'] = basename($file['thumb']);
                $size = @getimagesize($path);
                $file['thumbwidth'] = $size[0];
                $file['thumbheight'] = $size[1];
                $file['width'] = $size[0];
                $file['height'] = $size[1];
            }*/
            else if ($file['extension'] == "txt" && $config['txt_file_thumbnail']){
                $path = $file['thumb'];
                $error = shell_exec_error( 'convert -thumbnail x300 xc:white -pointsize 12 -fill black -annotate +15+15 ' .
                escapeshellarg( '@' . $file['tmp_name']) . ' ' .
                escapeshellarg($file['thumb']));

                if ($error){
                    $path = sprintf($config['file_thumb'],isset($config['file_icons'][$file['extension']]) ? $config['file_icons'][$file['extension']] : $config['file_icons']['default']); 
                }

                $file['thumb'] = basename($file['thumb']);
                $size = @getimagesize($path);
                $file['thumbwidth'] = $size[0];
                $file['thumbheight'] = $size[1];
                $file['width'] = $size[0];
                $file['height'] = $size[1];
            }
            else if ($file['extension'] == "svg"){
                // Copy, because there's nothing to resize
                $file['thumb'] = substr_replace($file['thumb'] , $file['extension'], strrpos($file['thumb'] , '.') +1);
                copy($file['tmp_name'], $file['thumb']);
                $file['thumbwidth'] = $config['thumb_width'];
                $file['thumbheight'] = $config['thumb_height'];
                $file['thumb'] = basename($file['thumb']);
            
            }
            else {
            // not an image
            //copy($config['file_thumb'], $post['thumb']);
            $file['thumb'] = 'file';

            $size = @getimagesize(sprintf($config['file_thumb'],
                isset($config['file_icons'][$file['extension']]) ?
                    $config['file_icons'][$file['extension']] : $config['file_icons']['default']));
            $file['thumbwidth'] = $size[0];
            $file['thumbheight'] = $size[1];
            }
        }

        if ($config['tesseract_ocr'] && $file['thumb'] != 'file') { // Let's OCR it!
            $fname = $file['tmp_name'];

            if ($file['height'] > 500 || $file['width'] > 500) {
                $fname = $file['thumb'];
            }

            if ($fname == 'spoiler') { // We don't have that much CPU time, do we?
            }
            else {
                $tmpname = __DIR__ . "/tmp/tesseract/".rand(0,10000000);

                // Preprocess command is an ImageMagick b/w quantization
                $error = shell_exec_error(sprintf($config['tesseract_preprocess_command'], escapeshellarg($fname)) . " | " .
                                                          'tesseract stdin '.escapeshellarg($tmpname).' '.$config['tesseract_params']);
                $tmpname .= ".txt";

                $value = @file_get_contents($tmpname);
                @unlink($tmpname);

                if ($value && trim($value)) {
                    // This one has an effect, that the body is appended to a post body. So you can write a correct
                    // spamfilter.
                    $post['body_nomarkup'] .= "<tinyboard ocr image $key>".htmlspecialchars($value)."</tinyboard>";
                }
            }
        }

        if (!isset($dont_copy_file) || !$dont_copy_file) {
            if (isset($file['file_tmp'])) {
                if (!@rename($file['tmp_name'], $file['file']))
                    error($config['error']['nomove']);
                chmod($file['file'], 0644);
            } elseif (!@move_uploaded_file($file['tmp_name'], $file['file']))
                error($config['error']['nomove']);
            }
        }

        if ($config['image_reject_repost']) {
            if ($p = getPostByHash($post['filehash'])) {
                undoImage($post);
                error(sprintf($config['error']['fileexists'], 
                    ($post['mod'] ? $config['root'] . $config['file_mod'] . '?/' : $config['root']) .
                    ($board['dir'] . $config['dir']['res'] .
                        ($p['thread'] ?
                            $p['thread'] . '.html#' . $p['id']
                        :
                            $p['id'] . '.html'
                        ))
                ));
            }
        } else if (!$post['op'] && $config['image_reject_repost_in_thread']) {
            if ($p = getPostByHashInThread($post['filehash'], $post['thread'])) {
                undoImage($post);
                error(sprintf($config['error']['fileexistsinthread'], 
                    ($post['mod'] ? $config['root'] . $config['file_mod'] . '?/' : $config['root']) .
                    ($board['dir'] . $config['dir']['res'] .
                        ($p['thread'] ?
                            $p['thread'] . '.html#' . $p['id']
                        :
                            $p['id'] . '.html'
                        ))
                ));
            }
        }

    }

    // Do filters again if OCRing
    if ($config['tesseract_ocr'] && !hasPermission($config['mod']['bypass_filters'], $board['uri']) && !$dropped_post) {
        do_filters($post);
    }

    if (!hasPermission($config['mod']['postunoriginal'], $board['uri']) && $config['robot_enable'] && checkRobot($post['body_nomarkup']) && !$dropped_post) {
        undoImage($post);
        if ($config['robot_mute']) {
            error(sprintf($config['error']['muted'], mute()));
        } else {
            error($config['error']['unoriginal']);
        }
    }
    
    // Remove board directories before inserting them into the database.
    if ($post['has_file']) {
        foreach ($post['files'] as $key => &$file) {
            $file['file_path'] = $file['file'];
            $file['thumb_path'] = $file['thumb'];
            $file['file'] = mb_substr($file['file'], mb_strlen($board['dir'] . $config['dir']['img']));
            if ($file['is_an_image'] && $file['thumb'] != 'spoiler')
                $file['thumb'] = mb_substr($file['thumb'], mb_strlen($board['dir'] . $config['dir']['thumb']));
        }
    }

    // Custom /leftypol/ var to check if Tor
    $tor = ($_SERVER['REMOTE_ADDR'] == '127.0.0.1');

    $post = (object)$post;
    $post->files = array_map(function($a) { return (object)$a; }, $post->files);

    $error = event('post', $post, $tor);
    $post->files = array_map(function($a) { return (array)$a; }, $post->files);

    if ($error) {
        undoImage((array)$post);
        error($error);
    }

    $post = (array)$post;

    $post['num_files'] = sizeof($post['files']);
    
    $post['id'] = $id = post($post);
    $post['slug'] = slugify($post);
    
    if ($dropped_post && $dropped_post['from_nntp']) {
            $query = prepare("INSERT INTO ``nntp_references`` (`board`, `id`, `message_id`, `message_id_digest`, `own`, `headers`) VALUES ".
                                                             "(:board , :id , :message_id , :message_id_digest , false, :headers)");

        $query->bindValue(':board', $dropped_post['board']);
        $query->bindValue(':id', $id);
        $query->bindValue(':message_id', $dropped_post['msgid']);
        $query->bindValue(':message_id_digest', sha1($dropped_post['msgid']));
        $query->bindValue(':headers', $dropped_post['headers']);
        $query->execute() or error(db_error($query));
    }   // ^^^^^ For inbound posts  ^^^^^
    elseif ($config['nntpchan']['enabled'] && $config['nntpchan']['group']) {
        // vvvvv For outbound posts vvvvv

        require_once('inc/nntpchan/nntpchan.php');
        $msgid = gen_msgid($post['board'], $post['id']);

        list($headers, $files) = post2nntp($post, $msgid);

        $message = gen_nntp($headers, $files);

            $query = prepare("INSERT INTO ``nntp_references`` (`board`, `id`, `message_id`, `message_id_digest`, `own`, `headers`) VALUES ".
                                                             "(:board , :id , :message_id , :message_id_digest , true , :headers)");

        $query->bindValue(':board', $post['board']);
                $query->bindValue(':id', $post['id']);
                $query->bindValue(':message_id', $msgid);
                $query->bindValue(':message_id_digest', sha1($msgid));
                $query->bindValue(':headers', json_encode($headers));
                $query->execute() or error(db_error($query));

        // Let's broadcast it!
        nntp_publish($message, $msgid);
    }

    insertFloodPost($post);

    // Handle cyclical threads
    if (!$post['op'] && isset($thread['cycle']) && $thread['cycle']) {
        // Query is a bit weird due to "This version of MariaDB doesn't yet support 'LIMIT & IN/ALL/ANY/SOME subquery'" (MariaDB Ver 15.1 Distrib 10.0.17-MariaDB, for Linux (x86_64))
        $query = prepare(sprintf('DELETE FROM ``posts_%s`` WHERE `thread` = :thread AND `id` NOT IN (SELECT `id` FROM (SELECT `id` FROM ``posts_%s`` WHERE `thread` = :thread ORDER BY `id` DESC LIMIT :limit) i)', $board['uri'], $board['uri']));
        $query->bindValue(':thread', $post['thread']);
        $query->bindValue(':limit', $config['cycle_limit'], PDO::PARAM_INT);
        $query->execute() or error(db_error($query));
    }
    
    if (isset($post['antispam_hash'])) {
        incrementSpamHash($post['antispam_hash']);
    }
    
    if (isset($post['tracked_cites']) && !empty($post['tracked_cites'])) {
        $insert_rows = array();
        foreach ($post['tracked_cites'] as $cite) {
            $insert_rows[] = '(' .
                $pdo->quote($board['uri']) . ', ' . (int)$id . ', ' .
                $pdo->quote($cite[0]) . ', ' . (int)$cite[1] . ')';
        }
        query('INSERT INTO ``cites`` VALUES ' . implode(', ', $insert_rows)) or error(db_error());
    }

    if (!$post['op'] && strtolower($post['email']) != 'sage' && !$thread['sage'] && ($config['reply_limit'] == 0 || $numposts['replies'] < $config['reply_limit'])) {
        bumpThread($post['thread']);
    }

    if (isset($_SERVER['HTTP_REFERER'])) {
        // Tell Javascript that we posted successfully
        if (isset($_COOKIE[$config['cookies']['js']]))
            $js = json_decode($_COOKIE[$config['cookies']['js']]);
        else
            $js = (object) array();
        // Tell it to delete the cached post for referer
        $js->{$_SERVER['HTTP_REFERER']} = true;

        // Encode and set cookie
        setcookie(
            $config['cookies']['js'],
            json_encode($js),
            0,
            $config['cookies']['jail'] ? $config['cookies']['path'] : '/',
            '',
            false,
            false);
    }
    
    $root = $post['mod'] ? $config['root'] . $config['file_mod'] . '?/' : $config['root'];
    
    if ($noko) {
        $redirect = $root . $board['dir'] . $config['dir']['res'] .
            link_for($post, false, false, $thread) . (!$post['op'] ? '#' . $id : '');
        
        if (!$post['op'] && isset($_SERVER['HTTP_REFERER'])) {
            $regex = array(
                'board' => str_replace('%s', '(\w{1,8})', preg_quote($config['board_path'], '/')),
                'page' => str_replace('%d', '(\d+)', preg_quote($config['file_page'], '/')),
                'page50' => '(' . str_replace('%d', '(\d+)', preg_quote($config['file_page50'], '/')) . '|' .
                          str_replace(array('%d', '%s'), array('(\d+)', '[a-z0-9-]+'), preg_quote($config['file_page50_slug'], '/')) . ')',
                'res' => preg_quote($config['dir']['res'], '/'),
            );

            if (preg_match('/\/' . $regex['board'] . $regex['res'] . $regex['page50'] . '([?&].*)?$/', $_SERVER['HTTP_REFERER'])) {
                $redirect = $root . $board['dir'] . $config['dir']['res'] .
                    link_for($post, true, false, $thread) . (!$post['op'] ? '#' . $id : '');
            }
        }
    } else {
        $redirect = $root . $board['dir'] . $config['file_index'];
        
    }

    buildThread($post['op'] ? $id : $post['thread']);
    
    if ($config['syslog'])
        _syslog(LOG_INFO, 'New post: /' . $board['dir'] . $config['dir']['res'] .
            link_for($post) . (!$post['op'] ? '#' . $id : ''));
    
    if (!$post['mod']) header('X-Associated-Content: "' . $redirect . '"');

    if (!isset($_POST['json_response'])) {
        header('Location: ' . $redirect, true, $config['redirect_http']);
    } else {
        header('Content-Type: text/json; charset=utf-8');
        echo json_encode(array(
            'redirect' => $redirect,
            'noko' => $noko,
            'id' => $id
        ));
    }
    
    if ($config['try_smarter'] && $post['op'])
        $build_pages = range(1, $config['max_pages']);
    
    if ($post['op'])
        clean($id);
    
    event('post-after', $post);
    
    buildIndex();

    // We are already done, let's continue our heavy-lifting work in the background (if we run off FastCGI)
    if (function_exists('fastcgi_finish_request')) {
        @fastcgi_finish_request();
    }

    if ($post['op']) {
        rebuildThemes('post-thread', $board['uri']);
    } else {
        rebuildThemes('post', $board['uri']);
    }
}

function handle_appeal(){
    global $config;
    if (!isset($_POST['ban_id']))
        error($config['error']['bot']);
    
    $ban_id = (int)$_POST['ban_id'];
    
    $bans = Bans::find($_SERVER['REMOTE_ADDR']);
    foreach ($bans as $_ban) {
        if ($_ban['id'] == $ban_id) {
            $ban = $_ban;
            break;
        }
    }
    
    if (!isset($ban)) {
        error(_("That ban doesn't exist or is not for you."));
    }
    
    if ($ban['expires'] && $ban['expires'] - $ban['created'] <= $config['ban_appeals_min_length']) {
        error(_("You cannot appeal a ban of this length."));
    }
    
    $query = query("SELECT `denied` FROM ``ban_appeals`` WHERE `ban_id` = $ban_id") or error(db_error());
    $ban_appeals = $query->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($ban_appeals) >= $config['ban_appeals_max']) {
        error(_("You cannot appeal this ban again."));
    }
    
    foreach ($ban_appeals as $is_denied) {
        if (!$is_denied)
            error(_("There is already a pending appeal for this ban."));
    }
    
    $query = prepare("INSERT INTO ``ban_appeals`` VALUES (NULL, :ban_id, :time, :message, 0)");
    $query->bindValue(':ban_id', $ban_id, PDO::PARAM_INT);
    $query->bindValue(':time', time(), PDO::PARAM_INT);
    $query->bindValue(':message', $_POST['appeal']);
    $query->execute() or error(db_error($query));
    
    displayBan($ban);

}

// Is it a post coming from NNTP? Let's extract it and pretend it's a normal post.
if (isset($_GET['Newsgroups'])) {
     if($config['nntpchan']['enabled']){
        handle_nntpchan();
     } 
     else {
        error("NNTPChan: NNTPChan support is disabled");
     }

}

if (isset($_POST['delete'])) {
    handle_delete();
} elseif (isset($_POST['report'])) {
    handle_report();
} elseif (isset($_POST['post']) || $dropped_post) {
    handle_post();
} elseif (isset($_POST['appeal'])) {
    handle_appeal();
} else {
    if (!file_exists($config['has_installed'])) {
        header('Location: install.php', true, $config['redirect_http']);
    } else {
        // They opened post.php in their browser manually.
        error($config['error']['nopost']);
    }
}
