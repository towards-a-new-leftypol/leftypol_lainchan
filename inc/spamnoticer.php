<?php

require_once 'vendor/autoload.php';
require_once 'inc/anti-bot.php';

function _createClient($config) {
    return new GuzzleHttp\Client([
        'base_uri' => $config['spam_noticer']['base_url'],
        'http_errors' => false
    ]);
}

$REASONS = [
    (1 << 0) => [
        'reason' => 'RECENTLY_POSTED_ATTACHMENT_ABOVE_RATE',
        'explanation' => 'your file was posted too fast',
    ],
    (1 << 1) => [
        'reason' => 'LEXICAL_ANALYSIS_SHOWS_NONSENSE',
        'explanation' => 'your post is nonsense',
    ],
    (1 << 2) => [
        'reason' => 'SEGMENTED_AVG_ENTROPY_TOO_HIGH',
        'explanation' => 'this seems to be garbage input',
    ],
    (1 << 3) => [
        'reason' => 'TEXT_MATCHES_KNOWN_SPAM',
        'explanation' => 'your text is considered spam',
    ],
    (1 << 4) => [
        'reason' => 'RECENTLY_POSTED_TEXT_ABOVE_RATE',
        'explanation' => 'your text is being spammed',
    ],
    (1 << 5) => [
        'reason' => 'TEXT_IS_URL',
        'explanation' => 'this is just a url',
    ],
    (1 << 6) => [
        'reason' => 'TEXT_IS_ONE_LONG_WORD',
        'explanation' => "your post is illegible",
    ],
    (1 << 7) => [
        'reason' => 'TEXT_IS_RANDOM_WORDS',
        'explanation' => "what you're saying is nonsense",
    ],
    (1 << 8) => [
        'reason' => 'ATTACHMENT_MATCHES_KNOWN_SPAM',
        'explanation' => 'that file is considered spam',
    ],
    (1 << 9) => [
        'reason' => 'TEXT_MANUALLY_MARKED_AS_SPAM',
        'explanation' => "we said you can't post that",
    ],
    (1 << 10) => [
        'reason' => 'ATTACHMENT_MANUALLY_MARKED_AS_SPAM',
        'explanation' => 'that file is spam',
    ],
    (1 << 11) => [
        'reason' => 'ILLEGAL_ATTACHMENT',
        'explanation' => 'of illegal content',
    ],
];

function reasonsFromBitmap($n) {
    $reasons = [];

    foreach ($GLOBALS['REASONS'] as $bit => $reason) {
        if ($n & $bit) {
            $reasons[] = $reason['explanation'];
        }
    }

    return $reasons;
}

function renderReasons($reason) {
    $reasons = reasonsFromBitmap($reason);
    $result = '';

    if (!empty($reasons)) {
        $result = implode(' and ', $reasons);
    }

    return $result;
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

    if (isset($post->files) && is_array($post->files)) {
        foreach ($post->files as $file) {
            $key = $file->file_id . '.' . $file->extension;

            if (!isset($form_info->files_info[$key])) {
                continue;
            }

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
    }

    $json_payload = [
        'attachments'   => $attachments,
        'body'          => $post->body_nomarkup,
        'body_is_spam'  => $form_info->text_is_spam,
        'time_stamp'    => $post->time,
        'website_name'  => $config['spam_noticer']['website_name'],
        'board_name'    => $boardname,
        'reporter_name' => getUsername()
    ];

    if (isset($post->thread)) {
        $json_payload['thread_id'] = $post->thread;
    }

    try {
        $multipart = array();
        $multipart[] =
            array(
                'name' => 'json',
                'contents' => json_encode($json_payload)
            );

        if (isset($post->files) && is_array($post->files)) {
            foreach ($post->files as $file) {
                $key = $file->file_id . '.' . $file->extension;
                if (!isset($form_info->files_info[$key])) {
                    continue;
                }

                $filename = $board['dir'] . $config['dir']['img'] . $file->file;

                $multipart[] = array(
                    'name' => 'attachments',
                    'contents' => GuzzleHttp\Psr7\Utils::tryFopen($filename, 'r')
                );
            }
        }


        $response = $client->request('POST', '/add_post_to_known_spam', [
            'multipart' => $multipart
        ]);

        $status_code = $response->getStatusCode();
        $result_body = (string) $response->getBody();
        print_err($result_body);
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
        'delete_token' => $post['delete_token'],
    ];

    if (array_key_exists('thread', $post)) {
        $json_payload['thread_id'] = $post['thread'];
    }

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
                $body = (string) $response->getBody();
                print_err($body);
                $reasons_bitmap = json_decode($body)->reason;
                $result->reason = renderReasons($reasons_bitmap);
            }
        } else {
            print_err("spamnoticer status code: " . $status_code);
            print_err("spamnoticer response body: " . $response->getBody());

            $result->reason = (string) $response->getBody();
        }

        $result->client = $client;

        return $result;
    } catch (GuzzleHttp\Exception\ConnectException $e) {
        $result->reason = $e->getMessage();
    }

    return $result;
}

function removeRecentPostFromSpamnoticer($config, $delete_tokens, $files_only = false) {
    if (!$delete_tokens) {
        return;
    }

    $client = _createClient($config);

    $promise = $client->postAsync('/undo_recent_post', [
        'headers' => [
            'Content-Type' => 'application/json',
        ],
        'json' => [
            'delete_tokens' => $delete_tokens,
            'files_only' => $files_only,
        ]
    ]);

    $promise->then(
        function ($response) use ($config) {
            // This callback is executed when the request is successful
            if ($config['debug']) {
                print_err("POST to SpamNoticer /undo_recent_post sent successfully!");
            }
        },
        function (RequestException $exception) {
            print_err("ERROR sending POST to SpamNoticer /undo_recent_post:\n$exception");
        }
    );

    // This will initiate the asynchronous request, but we won't wait for the response.
    $promise->wait(false);  // Set to false for asynchronous behavior
}
