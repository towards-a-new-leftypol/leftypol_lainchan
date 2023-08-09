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
    public array $files_info;

    public function __construct(
            bool $ban,
            bool $delete,
            bool $ban_content,
            array $files_info, // Array of SpamNoticerBanFileInfo
            ) {
        $this->ban = $ban;
        $this->delete = $delete;
        $this->ban_content = $ban_content;
        $this->files_info = $files_info;
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
            'thumbnail_url' => $config['spam_noticer']['imageboard_root'] . $file['thumb']
        );
    }

    $json_payload = [
        'attachments' => $attachments,
        'body' => $post['raw_body'],
        'time_stamp' => time(),
        'website_name' => $config['spam_noticer']['website_name'],
        'board_name' => $boardname,
        'thread_id' => array_key_exists('thread', $post) ? $post['thread'] : NULL
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

        print_err("strbody:\n" . $result->reason);
        print_err("status_code: " . $response->getStatusCode());

        $result->client = $client;

        return $result;
    } catch (GuzzleHttp\Exception\ConnectException $e) {
        $result->reason = $e->getMessage();
    }

    return $result;
}
