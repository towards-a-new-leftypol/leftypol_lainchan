<?php

require_once 'inc/functions.php';
require_once 'templates/themes/overboards/overboards.php';

// Boards that are nsfw
$nsfw_boards = ['b', 'R9K', 'overboard'];
// Boards where posts are not allowed to be created
$readonly_boards = [];

// Allowed boards
$whitelist = ['dead'];
foreach ($config['boards'] as $boards) {
    foreach ($boards as $board) {
        $whitelist[] = $board;
    }
}
foreach ($overboards_config as $board) {
    $whitelist[] = $board['uri'];
    $readonly_boards[] = $board['uri'];
}

$board_list = listBoards();

// Add objects that are not boards but are treated as such
foreach ($overboards_config as $overboard) {
    $board_list[] = $overboard;
}

/**
 * Allowed fields for the board object:
 * - code<string>: The board code ('b', 'tech', ...)
 * - name<string>: The board user-readable name ('Siberia', ...)
 * - sfw<boolean>: Is this board sfw?
 * - posting_enabled<boolean>: Can new posts be created belonging to this board?
 */
$boards = [];
foreach ($board_list as $board) {
    // Skip non-whitelisted boards
    if (!in_array($board['uri'], $whitelist)) {
        continue;
    }

    $boards[] = [
        'code' => $board['uri'],
        'name' => $board['title'],
        'sfw' => !in_array($board['uri'], $nsfw_boards),
        'posting_enabled' => !in_array($board['uri'], $readonly_boards),
    ];
}

header('Content-Type: application/json');
echo json_encode([
    'captcha' => $config['securimage'],
    'flags' => $config['user_flags'],
    'boards' => $boards,
]);
