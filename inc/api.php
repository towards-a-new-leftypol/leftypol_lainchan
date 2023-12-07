<?php
/*
 *  Copyright (c) 2010-2013 Tinyboard Development Group
 */

defined('TINYBOARD') or exit;

/**
 * Class for generating json API compatible with 4chan API
 */
class Api {
    public $config;
    public $postFields;
    public $threadsPageFields;
    public $fileFields;

	function __construct(){
		global $config;
		/**
		 * Translation from local fields to fields in 4chan-style API
		 */
		$this->config = $config;

		$this->postFields = array(
			'id' => 'no',
			'thread' => 'resto',
			'subject' => 'sub',
			'body' => 'com',
			'email' => 'email',
			'name' => 'name',
			'trip' => 'trip',
			'capcode' => 'capcode',
			'time' => 'time',
			'omitted' => 'omitted_posts',
			'omitted_images' => 'omitted_images',
			'replies' => 'replies',
			'images' => 'images',
			'sticky' => 'sticky',
			'locked' => 'locked',
			'cycle' => 'cyclical',
			'bump' => 'last_modified',
			'embed' => 'embed',
			'board' => 'board',
		);

		$this->threadsPageFields = array(
			'id' => 'no',
			'bump' => 'last_modified',
			'board' => 'board',
		);

		$this->fileFields = array(
			'file_id' => 'id',
			'type' => 'mime',
			'extension' => 'ext',
			'height' => 'h',
			'width' => 'w',
			'size' => 'fsize',
		);

		if (isset($config['api']['extra_fields']) && gettype($config['api']['extra_fields']) == 'array'){
			$this->postFields = array_merge($this->postFields, $config['api']['extra_fields']);
		}
	}

	private static $ints = array(
		'no' => 1,
		'resto' => 1,
		'time' => 1,
		'tn_w' => 1,
		'tn_h' => 1,
		'w' => 1,
		'h' => 1,
		'fsize' => 1,
		'omitted_posts' => 1,
		'omitted_images' => 1,
		'replies' => 1,
		'images' => 1,
		'sticky' => 1,
		'locked' => 1,
		'last_modified' => 1
	);

	private function translateFields($fields, $object, &$apiPost) {
		foreach ($fields as $local => $translated) {
			if (!isset($object->$local))
				continue;

			$toInt = isset(self::$ints[$translated]);
			$val = $object->$local;
			if ($val !== null && $val !== '') {
				$apiPost[$translated] = $toInt ? (int) $val : $val;
			}

		}
	}

	private function translateFile($file, $post, &$apiPost) {
		global $config;

		$this->translateFields($this->fileFields, $file, $apiPost);
		$apiPost['filename'] = @substr($file->name, 0, strrpos($file->name, '.'));
		if (isset ($file->thumb) && $file->thumb) {
			$apiPost['spoiler'] = $file->thumb === 'spoiler';
 		}
		if (isset ($file->hash) && $file->hash) {
			$apiPost['md5'] = base64_encode(hex2bin($file->hash));
		}
		else if (isset ($post->filehash) && $post->filehash) {
			$apiPost['md5'] = base64_encode(hex2bin($post->filehash));
		}

		$apiPost['file_path'] = $config['uri_img'] . $file->file;

		// Pick the correct thumbnail
		if (isset($file->thumb) && $file->thumb === 'spoiler') {
			// Spoiler
			$apiPost['thumb_path'] = $config['root'] . $config['spoiler_image'];
		} else if (!isset($file->thumb) || $file->thumb === 'file') {
			// Default file format image
			$thumbFile = $config['file_icons']['default'];
			if (isset($file->extension) && isset($config['file_icons'][$file->extension])) {
				$thumbFile = $config['file_icons'][$file->extension];
			}

			$apiPost['thumb_path'] = $config['root'] . sprintf($config['file_thumb'], $thumbFile);
		} else {
			// The file's own thumbnail
			$apiPost['thumb_path'] = $config['uri_thumb'] . $file->thumb;
		}
	}

	private function translatePost($post, $threadsPage = false) {
		global $config, $board;
		$apiPost = array();
		$fields = $threadsPage ? $this->threadsPageFields : $this->postFields;
		$this->translateFields($fields, $post, $apiPost);

		if (isset($config['poster_ids']) && $config['poster_ids']) $apiPost['id'] = poster_id($post->ip, $post->thread, $board['uri']);
		if ($threadsPage) return $apiPost;

		// Load board info
		if (isset($post->board)) {
			openBoard($post->board);
		}

		// Handle special fields
		if (isset($post->body_nomarkup) && ($this->config['country_flags'] || $this->config['user_flag'])) {
			$modifiers = extract_modifiers($post->body_nomarkup);
			if (isset($modifiers['flag']) && isset($modifiers['flag alt']) && preg_match('/^[1-9a-z_-]{2,}$/', $modifiers['flag'])) {
				$country = strtolower($modifiers['flag']);
				if ($country) {
					$apiPost['country'] = $country;
					$apiPost['country_name'] = $modifiers['flag alt'];
				}
			}
			if (isset($modifiers['warning message'])) {
				$apiPost['warning_msg'] = $modifiers['warning message'];
			}
			if (isset($modifiers['ban message'])) {
				$apiPost['ban_msg'] = $modifiers['ban message'];
			}
		}

		if ($config['slugify'] && !$post->thread) {
			$apiPost['semantic_url'] = $post->slug;
		}

		// Handle files
		if (isset($post->files) && $post->files && !$threadsPage) {
			$apiPost['files'] = [];
			foreach ($post->files as $f) {
				$file = array();
				$this->translateFile($f, $post, $file);

				$apiPost['files'][] = $file;
			}
		}

		return $apiPost;
	}

	function translateThread(Thread $thread, $threadsPage = false) {
		$apiPosts = array();
		$op = $this->translatePost($thread, $threadsPage);
		if (!$threadsPage) $op['resto'] = 0;
		$apiPosts['posts'][] = $op;

		foreach ($thread->posts as $p) {
			$apiPosts['posts'][] = $this->translatePost($p, $threadsPage);
		}

		// Count unique IPs
		$ips = array($thread->ip);
		foreach ($thread->posts as $p) {
			$ips[] = $p->ip;
		}
		$apiPosts['posts'][0]['unique_ips'] = count(array_unique($ips));

		return $apiPosts;
	}

	function translatePage(array $threads) {
		$apiPage = array();
		foreach ($threads as $thread) {
			$apiPage['threads'][] = $this->translateThread($thread);
		}
		return $apiPage;
	}

	function translateCatalogPage(array $threads, $threadsPage = false) {
		$apiPage = array();
		foreach ($threads as $thread) {
			$ts = $this->translateThread($thread, $threadsPage);
			$apiPage['threads'][] = current($ts['posts']);
		}
		return $apiPage;
	}

	function translateCatalog($catalog, $threadsPage = false) {
		$apiCatalog = array();
		foreach ($catalog as $page => $threads) {
			$apiPage = $this->translateCatalogPage($threads, $threadsPage);
			$apiPage['page'] = $page;
			$apiCatalog[] = $apiPage;
		}

		return $apiCatalog;
	}
}
