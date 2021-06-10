<?php

	require 'info.php';

	/**
	 * Generate the board's HTML and move it and its JavaScript in place, whence
	 * it's served
	 */
	function overboards_build($action, $settings) {
		global $config;

		if ($action !== 'all' && $action !== 'post' && $action !== 'post-thread' &&
			$action !== 'post-delete')
		{
			return;
		}

		if ($config['smart_build']) {
			file_unlink($settings['uri'] . '/index.html');
		} else {
			require 'overboards.php';

			$overboards = new overboards($overboards_config);

			foreach ($overboards_config as &$overboard) {
				if (!file_exists($overboard['uri'])) {
					@mkdir($overboard['uri'], 0777) or error("Couldn't create {$overboard['uri']}. Check permissions.", true);
				}
				// Copy the generated board HTML to its place
				file_write($overboard['uri'] . '/index.html', $overboards->build($overboard));
				file_write($overboard['uri'] . '/overboard.js',
					Element('themes/overboards/overboard.js', array()));
			}
		}
	}

	/**
	 * Encapsulation of the theme's internals
	 */
	class overboards {
		private $settings;

		function __construct($settings) {
			$this->settings = $this->parseSettings($settings);
		}

		/**
		 * Parse and validate configuration parameters passed from the UI
		 */
		private function parseSettings(&$settings) {
			foreach ($settings as &$overboard) {
				if (!is_int($overboard['thread_limit']) || $overboard['thread_limit'] < 1)
				{
					error('thread_limit must be an integer above 1.', true);
				}
				if (!is_array($overboard['exclude'])) 
				{
					error('Exclude list must be array of strings.', true);
				}
				foreach ($overboard['exclude'] as &$board) {
					if (!is_string($board)){
						error('Exclude list must be array of strings.', true);
					}
				}
					
			}

			return $settings;
		}

		/**
		 * Obtain list of all threads from all non-excluded boards
		 */
		private function fetchThreads($overboard) {
			$query   = '';
			$boards  = listBoards(true);

			foreach ($boards as $b) {
				if (in_array($b, $overboard['exclude']))
					continue;
				// Threads are those posts that have no parent thread
				$query .= "SELECT *, '$b' AS `board` FROM ``posts_$b`` " .
					"WHERE `thread` IS NULL UNION ALL ";
			}

			$query  = preg_replace('/UNION ALL $/', 'ORDER BY `bump` DESC', $query);
			$result = query($query) or error(db_error());

			return $result->fetchAll(PDO::FETCH_ASSOC);
		}

		/**
		 * Retrieve all replies to a given thread
		 */
		private function fetchReplies($board, $thread_id, $preview_count) {
			$query = prepare("SELECT * FROM (SELECT * FROM ``posts_$board`` WHERE `thread` = :id ORDER BY `time` DESC LIMIT :limit) as
 t ORDER BY t.time ASC");
			$query->bindValue(':id', $thread_id, PDO::PARAM_INT);
			$query->bindValue(':limit', $preview_count, PDO::PARAM_INT);
			$query->execute() or error(db_error($query));

			return $query->fetchAll(PDO::FETCH_ASSOC);
		}

		/**
		 * Retrieve count of images and posts in a thread
		 */
		private function fetchThreadCount($board, $thread_id, $preview_count) {
			$query = prepare("SELECT SUM(t.num_files) as file_count, COUNT(t.id) as post_count FROM (SELECT *  FROM ``posts_$board`` WHERE `thread` = :id ORDER BY `time` DESC LIMIT :offset , 18446744073709551615) as t;");
			$query->bindValue(':id', $thread_id, PDO::PARAM_INT);
			$query->bindValue(':offset', $preview_count, PDO::PARAM_INT);
			$query->execute() or error(db_error($query));

			return $query->fetch(PDO::FETCH_ASSOC);
		}

		/**
		 * Build the HTML of a single thread in the catalog
		 */
		private function buildOne($post, $mod = false) {
			global $config;

			openBoard($post['board']);
			$thread  = new Thread($post, $mod ? '?/' : $config['root'], $mod);
			// Number of replies to a thread that are displayed beneath it
			$preview_count = $post['sticky'] ? $config['threads_preview_sticky'] :
				$config['threads_preview'];
			$replies = $this->fetchReplies($post['board'], $post['id'], $preview_count);

			$disp_replies   = $replies;
			foreach ($disp_replies as $reply) {
				// Append the reply to the thread as it's being built
				$thread->add(new Post($reply, $mod ? '?/' : $config['root'], $mod));
			}

			$threadCount = $this->fetchThreadCount($post['board'], $post['id'], $preview_count);
			$thread->omitted = $threadCount['post_count'];
			$thread->omitted_images = $threadCount['file_count'];

			// Board name and link
			$html  = '<h2><a href="' . $config['root'] . $post['board'] . '/">/' .
				$post['board'] . '/</a></h2>';
			// The thread itself
			$html .= $thread->build(true);

			return $html;
		}

		/**
		 * Query the required information and generate the HTML
		 */
		public function build($overboard, $mod = false) {
			if (!isset($this->settings)) {
				error('Theme is not configured properly.');
			}

			global $config;

			$html     = '';
			$overflow = array();

			// Fetch threads from all boards and chomp the first 'n' posts, depending
			// on the setting
			$threads     = $this->fetchThreads($overboard);
			$total_count = count($threads);
			// Top threads displayed on load
			$top_threads = array_splice($threads, 0, $overboard['thread_limit']);
			// Number of processed threads by board
			$counts      = array();

			// Output threads up to the specified limit
			foreach ($top_threads as $post) {
				if (array_key_exists($post['board'], $counts)) {
					++$counts[$post['board']];
				} else {
					$counts[$post['board']] = 1;
				}

				$html .= $this->buildOne($post, $mod);
			}

			foreach ($threads as $post) {
				if (array_key_exists($post['board'], $counts)) {
					++$counts[$post['board']];
				} else {
					$counts[$post['board']] = 1;
				}

				$page       = 'index';
				$board_page = floor($counts[$post['board']] / $config['threads_per_page']);
				if ($board_page > 0) {
					$page = $board_page + 1;
				}
				$overflow[] = array(
					'id'    => $post['id'],
					'board' => $post['board'],
					'page'  => $page . '.html'
				);
			}

			$html .= '<script>var ukko_overflow = ' . json_encode($overflow) . '</script>';
			$html .= '<script type="text/javascript" src="/'.$overboard['uri'].'/overboard.js"></script>';

			return Element('index.html', array(
				'config'       => $config,
				'board'        => array(
					'dir' => $overboard['uri'] . "/",
					'url'      => $overboard['uri'],
					'title'    => $overboard['title'],
					'subtitle' => str_replace('%s', $overboard['thread_limit'],
						strval(min($overboard['subtitle'], $total_count))),
				),
				'no_post_form' => true,
				'body'         => $html,
				'mod'          => $mod,
				'boardlist'    => createBoardlist($mod),
			));
		}

	};

	if (!function_exists('array_column')) {
		/**
		 * Pick out values from subarrays by given key
		 */
		function array_column($array, $key) {
			$result = [];
			foreach ($array as $val) {
				$result[] = $val[$key];
			}
			return $result;
		}
	}


