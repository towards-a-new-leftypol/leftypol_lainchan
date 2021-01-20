<?php
	require 'info.php';

	function categories_build($action, $settings, $board) {
		// Possible values for $action:
		//	- all (rebuild everything, initialization)
		//	- news (news has been updated)
		//	- boards (board list changed)

		Categories::build($action, $settings);
	}

	// Wrap functions in a class so they don't interfere with normal Tinyboard operations
	class Categories {
		public static function build($action, $settings) {
			global $config;
			
			if ($action == 'all' ||
				$action == 'boards' ||
				$action == 'news' ||
				$action == 'post' ||
				$action == 'post-thread' ||
	 			$action == 'post-delete'){
				file_write($config['dir']['home'] . $settings['file_main'], Categories::homepage($settings));
				file_write($config['dir']['home'] . $settings['file_news'], Categories::news($settings));
			}

			if ($action == 'all'){
				file_write($config['dir']['home'] . $settings['file_sidebar'], Categories::sidebar($settings));
			}
		}


		// Build homepage
		public static function homepage($settings) {
			global $config;
			$query = query("SELECT * FROM ``news`` ORDER BY `time` DESC") or error(db_error());
			$news = $query->fetchAll(PDO::FETCH_ASSOC);
			$stats = Categories::getPostStatistics($settings);
			return Element(
				'themes/categories/frames.html',
				Array(
					'config' => $config,
					'settings' => $settings,
					'categories' => Categories::getCategories($config),
					'news' => $news,
					'stats' => $stats,
					'boardlist' => createBoardlist(false)

				)
			);
		}

		// Build news page
		public static function news($settings) {
			global $config;

			$query = query("SELECT * FROM ``news`` ORDER BY `time` DESC") or error(db_error());
			$news = $query->fetchAll(PDO::FETCH_ASSOC);
			$stats = Categories::getPostStatistics($settings);
			return Element('themes/categories/news.html', Array(
				'settings' => $settings,
				'config' => $config,
				'news' => $news,
				'stats' => $stats,
				'boardlist' => createBoardlist(false)
			));
		}

		// Build sidebar
		public static function sidebar($settings) {
			global $config, $board;

			return Element('themes/categories/sidebar.html', Array(
				'settings' => $settings,
				'config' => $config,
				'categories' => Categories::getCategories($config)
			));
		}

		private static function getCategories($config) {
			$categories = $config['categories'];

			foreach ($categories as &$boards) {
				foreach ($boards as &$board) {
					$title = boardTitle($board);
					if (!$title)
						$title = $board; // board doesn't exist, but for some reason you want to display it anyway
					$board = Array('title' => $title, 'uri' => sprintf($config['board_path'], $board));
				}
			}

			return $categories;
		}

        private static function getPostStatistics($settings) {
        	global $config;

        	if (!isset($config['boards'])) {
        		return null;
        	}

        	$stats = [];

            foreach (array_merge(... $config['boards']) as $uri) {
            	$_board = getBoardInfo($uri);
            	if (!$_board) {
            		// board doesn't exist. 
            		continue;
            	}

            	$boardStat['title'] = $_board['title'];

                $pph_query = query(
                    sprintf("SELECT COUNT(*) AS count FROM ``posts_%s`` WHERE time > %d",
                            $_board['uri'],
                            time()-3600)
                ) or error(db_error());

                $boardStat['pph'] = $pph_query->fetch()['count'];

                $unique_query = query(
                    sprintf("SELECT DISTINCT ip FROM ``posts_%s`` WHERE time > %d",
                            $_board['uri'],
                            time()-3600)
                ) or error(db_error());

                $unique_ips = $unique_query->fetchAll();
                $boardStat['recent_ips'] = count($unique_ips);

                foreach ($unique_ips as $_k => $row) {
                    $unique[$row['ip']] = true;
                }

                $stats['boards'][] = $boardStat;
            }

            $stats['recent_ips'] = count($unique);
            $stats['pph'] = count(array_column($stats['boards'], 'pph'));

            return $stats;
        }


	};

?>
