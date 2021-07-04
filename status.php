<?php

require_once 'inc/functions.php';
require_once 'templates/themes/overboards/overboards.php';

// List of regexes that blacklist boards
$blacklist = [
    '/^.*_archive$/', // b_archive, leftypol_archive, ...
    '/^b_.*$/', // b_anime, b_leftypol, ...
];

// Boards that are nsfw
$nsfw_boards = ['b', 'overboard'];
// Boards that use spoiler_alunya.png as their spoiler
$alunya_spoiler = ['leftypol', 'anime'];
// Boards where posts are not allowed to be created
$readonly_boards = ['overboard', 'sfw', 'alt'];

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
 * - alternate_spoilers<boolean>: Does this board use the alunya spoiler?
 * - posting_enabled<boolean>: Can new posts be created belonging to this board?
 */
$boards = [];
foreach ($board_list as $board) {
    // Skip blacklisted boards
    foreach ($blacklist as $regex) {
        if (preg_match($regex, $board['uri'])) {
            continue 2; // Skip to the next board
        }
    }

    $boards[] = [
        'code' => $board['uri'],
        'name' => $board['title'],
        'sfw' => !in_array($board['uri'], $nsfw_boards),
        'alternate_spoilers' => in_array($board['uri'], $alunya_spoiler),
        'posting_enabled' => !in_array($board['uri'], $readonly_boards),
    ];
}

function endsWith( $haystack, $needle ) {
    $length = strlen( $needle );
    if( !$length ) {
        return true;
    }
    return substr( $haystack, -$length ) === $needle;
}

// Boards that are nsfw
$nsfw_boards = ['b', 'overboard'];
// Boards that use spoiler_alunya.png as their spoiler
$alunya_spoiler = ['leftypol', 'anime'];
// Boards where posts are not allowed to be created
$readonly_boards = ['overboard', 'sfw', 'alt'];

$board_list = listBoards();

// Add objects that are not boards but are treated as such
$board_list[] = ['uri' => 'overboard', 'title' => 'Overboard'];
$board_list[] = ['uri' => 'sfw', 'title' => 'SFW Overboard'];
$board_list[] = ['uri' => 'alt', 'title' => 'Alternate Overboard'];

/**
 * Allowed fields for the board object:
 * - code<string>: The board code ('b', 'tech', ...)
 * - name<string>: The board user-readable name ('Siberia', ...)
 * - description<string>: The board description ('Leftist Politically Incorrect', ...)
 * - sfw<boolean>: Is this board sfw?
 * - alternate_spoilers<boolean>: Does this board use the alunya spoiler?
 */
$boards = [];
foreach ($board_list as $board) {
    // Skip archives
    if (endsWith($board['uri'], '_archive')) {
        continue;
    }

    $boards[] = [
        'code' => $board['uri'],
        'name' => $board['title'],
        'sfw' => !in_array($board['uri'], $nsfw_boards),
        'alternate_spoilers' => in_array($board['uri'], $alunya_spoiler),
        'posting_enabled' => !in_array($board['uri'], $readonly_boards),
    ];
}

header('Content-Type: application/json');
echo json_encode([
    'captcha' => $config['securimage'],
    'flags' => $config['user_flags'],
    'boards' => $boards,
]);