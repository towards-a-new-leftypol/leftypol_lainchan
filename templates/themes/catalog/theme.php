<?php
    require 'info.php';

    function catalog_build($action, $settings, $board) {
        global $config;

        $b = new Catalog($settings);
        $boards = explode(' ', $settings['boards']);

        if (isset($settings['has_overboard']) && $settings['has_overboard']) {
            // Include overboard settings so that we can find them all and process exclusions
            require "templates/themes/overboards/overboards.php";
        }

        // Possible values for $action:
        //  - all (rebuild everything, initialization)
        //  - news (news has been updated)
        //  - boards (board list changed)
        //  - post (a reply has been made)
        //  - post-thread (a thread has been made)
        if ($action === 'all') {
            foreach ($boards as $board_name) {
                $action = generation_strategy("sb_catalog", array($board_name));
                if ($action == 'delete') {
                    file_unlink($config['dir']['home'] . $board_name . '/catalog.html');
                    file_unlink($config['dir']['home'] . $board_name . '/index.rss');
                }
                elseif ($action == 'rebuild') {
                    $b->build($settings, $board_name);
                }
            }
            if(isset($settings['has_overboard']) && $settings['has_overboard']) {
                foreach ($overboards_config as &$overboard) {
                    $included_boards = array_diff(listBoards(true), $overboard['exclude']);
                    $action = generation_strategy("sb_catalog", array($overboard));
                    if ($action == 'delete') {
                        file_unlink($config['dir']['home'] . $overboard . '/catalog.html');
                        file_unlink($config['dir']['home'] . $overboard . '/index.rss');
                    }
                    elseif ($action == 'rebuild') {
                        $b->buildOverboardCatalog($overboard['uri'], $settings, $included_boards);
                    }
                }
            }
        } elseif ($action == 'post-thread' || ($settings['update_on_posts'] && $action == 'post') || ($settings['update_on_posts'] && $action == 'post-delete')
        || $action == 'sticky' || ($action == 'lock' && in_array($board, $boards))) {
            $b = new Catalog($settings);

            $action = generation_strategy("sb_catalog", array($board));
            if ($action == 'delete') {
                file_unlink($config['dir']['home'] . $board . '/catalog.html');
                file_unlink($config['dir']['home'] . $board . '/index.rss');
            }
            elseif ($action == 'rebuild') {
                $b->build($settings, $board);
                if(isset($settings['has_overboard']) && $settings['has_overboard']) {
                    foreach ($overboards_config as &$overboard) {
                        $included_boards = array_diff(listBoards(true), $overboard['exclude']);
                        $b->buildOverboardCatalog($overboard['uri'], $settings, $included_boards);
                    }
                }
            }
        }
        // FIXME: Check that Ukko is actually enabled
        if ($settings['enable_ukko'] && (
            $action === 'all' || $action === 'post' ||
            $action === 'post-thread' || $action === 'post-delete' || $action === 'rebuild'))
        {
            $b->buildUkko();
        }
        
        // FIXME: Check that Ukko2 is actually enabled
        if ($settings['enable_ukko2'] && (
            $action === 'all' || $action === 'post' ||
            $action === 'post-thread' || $action === 'post-delete' || $action === 'rebuild'))
        {
            $b->buildUkko2();
        }
        
        // FIXME: Check that Ukko3 is actually enabled
        if ($settings['enable_ukko3'] && (
            $action === 'all' || $action === 'post' ||
            $action === 'post-thread' || $action === 'post-delete' || $action === 'rebuild'))
        {
            $b->buildUkko3();
        }
        // FIXME: Check that Ukko3 is actually enabled
        if ($settings['enable_ukko4'] && (
            $action === 'all' || $action === 'post' ||
            $action === 'post-thread' || $action === 'post-delete' || $action === 'rebuild'))
        {
            $b->buildUkko4();
        }
        // FIXME: Check that Rand is actually enabled
        if ($settings['enable_rand'] && (
            $action === 'all' || $action === 'post' ||
            $action === 'post-thread' || $action === 'post-delete' || $action === 'rebuild'))
        {
            $b->buildRand();
        }
    }

    // Wrap functions in a class so they don't interfere with normal Tinyboard operations
    class Catalog {
        private $settings;
        // Cache for threads from boards that have already been processed
        private $threadsCache = array();

        public function __construct($settings) {
            $this->settings = $settings;
        }

        /**
         * Build and save the HTML of the catalog for the Ukko theme
         */
        public function buildUkko() {
            global $config;

            $ukkoSettings = themeSettings('ukko');
            $queries = array();
            $threads = array();

            $exclusions = explode(' ', $ukkoSettings['exclude']);
            $boards = array_diff(listBoards(true), $exclusions);

            foreach ($boards as $b) {
                if (array_key_exists($b, $this->threadsCache)) {
                    $threads = array_merge($threads, $this->threadsCache[$b]);
                } else {
                    $queries[] = $this->buildThreadsQuery($b);
                }
            }

            // Fetch threads from boards that haven't beenp processed yet
            if (!empty($queries)) {
                $sql = implode(' UNION ALL ', $queries);
                $res = query($sql) or error(db_error());
                $threads = array_merge($threads, $res->fetchAll(PDO::FETCH_ASSOC));
            }

            // Sort in bump order
            usort($threads, function($a, $b) {
                return strcmp($b['bump'], $a['bump']);
            });
            // Generate data for the template
            $recent_posts = $this->generateRecentPosts($threads);

            $this->saveForBoard($ukkoSettings['uri'], $recent_posts,
                $config['root'] . $ukkoSettings['uri']);
        }
        /**
         * Build and save the HTML of the catalog for the Ukko2 theme
         */
        public function buildUkko2() {
            global $config;
            $ukkoSettings = themeSettings('ukko2');
            $queries = array();
            $threads = array();

            $inclusions = explode(' ', $ukkoSettings['include']);
            $boards = array_intersect(listBoards(true), $inclusions);

            foreach ($boards as $b) {
                if (array_key_exists($b, $this->threadsCache)) {
                    $threads = array_merge($threads, $this->threadsCache[$b]);
                } else {
                    $queries[] = $this->buildThreadsQuery($b);
                }
            }

            // Fetch threads from boards that haven't beenp processed yet
            if (!empty($queries)) {
                $sql = implode(' UNION ALL ', $queries);
                $res = query($sql) or error(db_error());
                $threads = array_merge($threads, $res->fetchAll(PDO::FETCH_ASSOC));
            }

            // Sort in bump order
            usort($threads, function($a, $b) {
                return strcmp($b['bump'], $a['bump']);
            });
            // Generate data for the template
            $recent_posts = $this->generateRecentPosts($threads);

            $this->saveForBoard($ukkoSettings['uri'], $recent_posts,
                $config['root'] . $ukkoSettings['uri']);
        }
        
        /**
         * Build and save the HTML of the catalog for the Ukko3 theme
         */
        public function buildUkko3() {
            global $config;

            $ukkoSettings = themeSettings('ukko3');
            $queries = array();
            $threads = array();

            $inclusions = explode(' ', $ukkoSettings['include']);
            $boards = array_intersect(listBoards(true), $inclusions);

            foreach ($boards as $b) {
                if (array_key_exists($b, $this->threadsCache)) {
                    $threads = array_merge($threads, $this->threadsCache[$b]);
                } else {
                    $queries[] = $this->buildThreadsQuery($b);
                }
            }

            // Fetch threads from boards that haven't beenp processed yet
            if (!empty($queries)) {
                $sql = implode(' UNION ALL ', $queries);
                $res = query($sql) or error(db_error());
                $threads = array_merge($threads, $res->fetchAll(PDO::FETCH_ASSOC));
            }

            // Sort in bump order
            usort($threads, function($a, $b) {
                return strcmp($b['bump'], $a['bump']);
            });
            // Generate data for the template
            $recent_posts = $this->generateRecentPosts($threads);

            $this->saveForBoard($ukkoSettings['uri'], $recent_posts,
                $config['root'] . $ukkoSettings['uri']);
        }
        
        /**
         * Build and save the HTML of the catalog for the Ukko theme
         */
        public function buildUkko4() {
            global $config;

            $ukkoSettings = themeSettings('ukko4');
            $queries = array();
            $threads = array();

            $exclusions = explode(' ', $ukkoSettings['exclude']);
            $boards = array_diff(listBoards(true), $exclusions);

            foreach ($boards as $b) {
                if (array_key_exists($b, $this->threadsCache)) {
                    $threads = array_merge($threads, $this->threadsCache[$b]);
                } else {
                    $queries[] = $this->buildThreadsQuery($b);
                }
            }

            // Fetch threads from boards that haven't beenp processed yet
            if (!empty($queries)) {
                $sql = implode(' UNION ALL ', $queries);
                $res = query($sql) or error(db_error());
                $threads = array_merge($threads, $res->fetchAll(PDO::FETCH_ASSOC));
            }

            // Sort in bump order
            usort($threads, function($a, $b) {
                return strcmp($b['bump'], $a['bump']);
            });
            // Generate data for the template
            $recent_posts = $this->generateRecentPosts($threads);

            $this->saveForBoard($ukkoSettings['uri'], $recent_posts,
                $config['root'] . $ukkoSettings['uri']);
        }
        /**
         * Build and save the HTML of the catalog for the Rand theme
         */
        public function buildRand() {
            global $config;

            $randSettings = themeSettings('rand');
            $queries = array();
            $threads = array();

            $exclusions = explode(' ', $randSettings['exclude']);
            $boards = array_diff(listBoards(true), $exclusions);

            foreach ($boards as $b) {
                if (array_key_exists($b, $this->threadsCache)) {
                    $threads = array_merge($threads, $this->threadsCache[$b]);
                } else {
                    $queries[] = $this->buildThreadsQuery($b);
                }
            }

            // Fetch threads from boards that haven't beenp processed yet
            if (!empty($queries)) {
                $sql = implode(' UNION ALL ', $queries);
                $res = query($sql) or error(db_error());
                $threads = array_merge($threads, $res->fetchAll(PDO::FETCH_ASSOC));
            }

            // Sort in bump order
            usort($threads, function($a, $b) {
                return strcmp($b['bump'], $a['bump']);
            });
            // Generate data for the template
            $recent_posts = $this->generateRecentPosts($threads);

            $this->saveForBoard($randSettings['uri'], $recent_posts,
                $config['root'] . $randSettings['uri'], true);
        }

        /**
         * Build and save the HTML of the catalog for the given board
         */
        public function build($settings, $board_name) {
            global $config, $board;

            if (!openBoard($board_name)) {
                error(sprintf(_("Board %s doesn't exist"), $board_name));
            }

            if (array_key_exists($board_name, $this->threadsCache)) {
                $threads = $this->threadsCache[$board_name];
            } else {
                $sql = $this->buildThreadsQuery($board_name);
                $query = query($sql . ' ORDER BY `sticky` DESC,`bump` DESC') or error(db_error());
                $threads = $query->fetchAll(PDO::FETCH_ASSOC);
                // Save for posterity
                $this->threadsCache[$board_name] = $threads;
            }

            // Generate data for the template
            $recent_posts = $this->generateRecentPosts($threads);

            $this->saveForBoard($board_name, $recent_posts);
        }

        private function buildThreadsQuery($board) {
            $sql  = "SELECT *, `id` AS `thread_id`, " .
                "(SELECT COUNT(`id`) FROM ``posts_$board`` WHERE `thread` = `thread_id`) AS `replies`, " .
                "(SELECT SUM(`num_files`) FROM ``posts_$board`` WHERE `thread` = `thread_id` AND `num_files` IS NOT NULL) AS `images`, " .
                "'$board' AS `board` FROM ``posts_$board`` WHERE `thread` IS NULL";

            return $sql;
        }

       /**
         * Build and save the HTML of the catalog for the overboard
         */
        public function buildOverboardCatalog($board_name, $settings, $boards) {
            global $config;

            if (array_key_exists($board_name, $this->threadsCache)) {
                $threads = $this->threadsCache[$board_name];
            } else {
                $sql = '';
                foreach ($boards as $board) {
                    $sql .= '('. $this->buildThreadsQuery($board) . ')';
                    $sql .= " UNION ALL ";
                }
                $sql  = preg_replace('/UNION ALL $/', 'ORDER BY `bump` DESC LIMIT :limit', $sql);
                $query = prepare($sql);
                $query->bindValue(':limit', $settings['overboard_limit'], PDO::PARAM_INT);
                $query->execute() or error(db_error($query));
                
                $threads = $query->fetchAll(PDO::FETCH_ASSOC);
                // Save for posterity
                $this->threadsCache[$board_name] = $threads;
            }
            // Generate data for the template
            $recent_posts = $this->generateRecentPosts($threads);

            $this->saveForBoard($board_name, $recent_posts,  '/' . $board_name, true);

            // Build the overboard JSON outputs
            if ($config['api']['enabled']) {
                $api = new Api();

                // Separate the threads into pages
                $pages = array(array());
                $totalThreads = count($recent_posts);
                $page = 0;
                for ($i = 1; $i <= $totalThreads; $i++) {
                    $pages[$page][] = new Thread($recent_posts[$i-1]);
                    
                    // If we have not yet visited all threads,
                    // and we hit the limit on the current page,
                    // skip to the next page
                    if ($i < $totalThreads && ($i % $config['threads_per_page'] == 0)) {
                        $page++;
                        $pages[$page] = array();
                    }
                }
                
                $json = json_encode($api->translateCatalog($pages));
                file_write($config['dir']['home'] . $board_name . '/catalog.json', $json);
    
                $json = json_encode($api->translateCatalog($pages, true));
                file_write($config['dir']['home'] . $board_name . '/threads.json', $json);
            }
        }

        private function generateRecentPosts($threads) {
            global $config, $board;

            $posts = array();
            foreach ($threads as $post) {
                if ($board['uri'] !== $post['board']) {
                    openBoard($post['board']);
                }

                $post['link'] = $config['root'] . $board['dir'] . $config['dir']['res'] . link_for($post);
                $post['board_name'] = $board['name'];

                if ($post['embed'] && preg_match('/^https?:\/\/(\w+\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9\-_]{10,11})(&.+)?$/i', $post['embed'], $matches)) {
                    $post['youtube'] = $matches[2];
                }

                if (isset($post['files']) && $post['files']) {
                    $files = json_decode($post['files']);

                    if ($files[0]) {
                        if ($files[0]->file == 'deleted') {
                            if (count($files) > 1) {
                                foreach ($files as $file) {
                                    if (($file == $files[0]) || ($file->file == 'deleted'))
                                        continue;
                                    $post['file'] = $config['uri_thumb'] . $file->thumb;
                                }

                                if (empty($post['file']))
                                    $post['file'] = $config['root'] . $config['image_deleted'];
                            } else {
                                $post['file'] = $config['root'] . $config['image_deleted'];
                            }
                        } else if($files[0]->thumb == 'spoiler') {
                            $post['file'] = $config['root'] . $config['spoiler_image'];
                        } else {
                            $post['file'] = $config['uri_thumb'] . $files[0]->thumb;
                        }
                    }
                } else {
                    $post['file'] = $config['root'] . $config['image_deleted'];
                }

                if (empty($post['images']))
                    $post['images'] = 0;
                $post['pubdate'] = date('r', $post['time']);

                $posts[] = $post;
            }

            return $posts;
        }

        private function saveForBoard($board_name, $recent_posts, $board_link = null, $is_overboard = false) {
            global $board, $config;

            if ($board_link === null) {
                $board_link = $config['root'] . $board['dir'];
            }

            $template_config = Array(
                'settings' => $this->settings,
                'config' => $config,
                'boardlist' => createBoardlist(),
                'recent_images' => array(),
                'recent_posts' => $recent_posts,
                'stats' => array(),
                'board' => $board,
                'link' => $board_link,
                'no_post_form' => false,
            );

            if ($is_overboard) {
                // fake board, I vomit
                $template_config['board'] = Array(
                    'uri' => $board_name,
                    'title' => $board_name,
                    'name' => $board_name,
                    'dir' => $board_name . '/',
                    'url' => '/' . $board_name . '/'
                );
                $template_config['no_post_form'] = true;
            }

            file_write($config['dir']['home'] . $board_name . '/catalog.html', Element('themes/catalog/catalog.html', $template_config));

            file_write($config['dir']['home'] . $board_name . '/index.rss', Element('themes/catalog/index.rss', Array(
                'config' => $config,
                'recent_posts' => $recent_posts,
                'board' => $board
            )));        
        }
    }
