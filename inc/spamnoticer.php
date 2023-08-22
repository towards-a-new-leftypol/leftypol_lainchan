<?php

require_once 'vendor/autoload.php';
require_once 'inc/anti-bot.php';

function _createClient($config) {
    return new GuzzleHttp\Client([
        'base_uri' => $config['spam_noticer']['base_url'],
        'http_errors' => false
    ]);
}

class SpamNoticerResult {
    public $result_json = NULL;
    public $noticed = false;
    public $succeeded = false;
    public $reason = NULL;
    public $client = NULL;
}

class SpamNoticerBanFileInfo {
    public $filename;
    public $is_illegal;
    public $is_spam;

    public function __construct($filename, $isIllegal, $isSpam) {
        $this->filename = $filename;
        $this->is_illegal = $isIllegal;
        $this->is_spam = $isSpam;
    }
}

class BanFormFieldsForSpamnoticer {
    public bool $ban;
    public bool $delete;
    public bool $ban_content;
    public bool $text_is_spam;
    public array $files_info;

    public function __construct(
            bool $ban,
            bool $delete,
            bool $ban_content,
            bool $text_is_spam,
            array $files_info, // Array of SpamNoticerBanFileInfo
            ) {
        $this->ban = $ban;
        $this->delete = $delete;
        $this->ban_content = $ban_content;
        $this->text_is_spam = $text_is_spam;
        $this->files_info = array();

        foreach ($files_info as $info) {
            $this->files_info[$info->filename] = $info;
        }
    }

}

function getUsername() {
    global $config;

	if (isset($_COOKIE[$config['cookies']['mod']])) {
		// Should be username:hash:salt
		$cookie = explode(':', $_COOKIE[$config['cookies']['mod']]);
        return $cookie[0];
    } else {
        return '__BOARD_MOD_USERNAME__';
    }
}

function addToSpamNoticer($config, $post, $boardname, BanFormFieldsForSpamnoticer $form_info) {
    global $board;

    $client = _createClient($config);

    $attachments = array();

    foreach ($post->files as $file) {
        $key = $file->file_id . '.' . $file->extension;
        $file_info = $form_info->files_info[$key];
        $thumb_uri
            = $config['spam_noticer']['imageboard_root']
            . $board['uri']
            . '/' . $config['dir']['thumb']
            . $file->file_id
            . '.png';

        $a = array(
            'filename' => $file->filename,
            'mimetype' => $file->type ? $file->type : mime_content_type($file->tmp_name),
            'md5_hash' => $file->hash,
            'thumbnail_url' => $thumb_uri,
            'is_spam'  => $file_info->is_spam
        );

        if ($file_info->is_illegal) {
            $a['is_illegal'] = true;
        }

        $attachments[] = $a;
    }

    $json_payload = [
        'attachments'   => $attachments,
        'body'          => $post->body_nomarkup,
        'body_is_spam'  => $form_info->text_is_spam,
        'time_stamp'    => $post->time,
        'website_name'  => $config['spam_noticer']['website_name'],
        'board_name'    => $boardname,
        'thread_id'     => isset($post->thread) ? $post->thread : NULL,
        'reporter_name' => getUsername()
    ];


    try {
        $multipart = array();
        $multipart[] =
            array(
                'name' => 'json',
                'contents' => json_encode($json_payload)
            );

        foreach ($post->files as $file) {
            $filename = $board['dir'] . $config['dir']['img'] . $file->file;

            $multipart[] = array(
                'name' => 'attachments',
                'contents' => GuzzleHttp\Psr7\Utils::tryFopen($filename, 'r')
            );
        }


        $response = $client->request('POST', '/add_post_to_known_spam', [
            'multipart' => $multipart
        ]);

        $status_code = $response->getStatusCode();
        $result_body = (string) $response->getBody();
        return "$status_code $result_body";

    } catch (GuzzleHttp\Exception\ConnectException $e) {
        return 'Spamnoticer Connection ERROR: ' . $e->getMessage();
    }
}

function checkWithSpamNoticer($config, $post, $boardname) {
    $client = _createClient($config);

    $attachments = [];

    foreach ($post['files'] as $key => $file) {
        $attachments[] = array(
            'filename'      => $file['filename'],
            'mimetype'      => $file['type'] ? $file['type'] : mime_content_type($file['tmp_name']),
            'md5_hash'      => $file['hash'],
        );
    }

    $json_payload = [
        'attachments' => $attachments,
        'body' => $post['raw_body'],
        'time_stamp' => time(),
        'website_name' => $config['spam_noticer']['website_name'],
        'board_name' => $boardname,
        'thread_id' => array_key_exists('thread', $post) ? $post['thread'] : NULL,
        'delete_token' => $post['delete_token'],
    ];

    $result = new SpamNoticerResult();

    try {
        $multipart = array();
        $multipart[] =
            [
                'name' => 'json',
                'contents' => json_encode($json_payload)
            ];

        foreach ($post['files'] as $key => $file) {
            $multipart[] = array(
                'name' => 'attachments',
                'contents' => GuzzleHttp\Psr7\Utils::tryFopen($file['tmp_name'], 'r')
            );
        }


        $response = $client->request('POST', '', [
            'multipart' => $multipart
        ]);

        $status_code = $response->getStatusCode();

        if ($status_code >= 200 && $status_code < 300) {
            $result->succeeded = true;
            $result->result_json = json_decode($response->getBody(), true);
            $result->noticed = $result->result_json['noticed'] == true;
            if ($result->noticed) {
                $result->reason = (string) $response->getBody();
            }
        } else {
            $result->reason = (string) $response->getBody();
        }

        $result->client = $client;

        return $result;
    } catch (GuzzleHttp\Exception\ConnectException $e) {
        $result->reason = $e->getMessage();
    }

    return $result;
}
