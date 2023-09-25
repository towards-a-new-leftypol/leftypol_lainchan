<?php

require_once 'inc/lib/gmstrftime/php-8.1-strftime.php';
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use function PHP81_BC\gmstrftime;

class TinyboardExtension extends AbstractExtension
{
    /**
     * Returns a list of filters to add to the existing list.
     *
     * @return array An array of filters
     */
    public function getFilters()
    {
        return array(
            new TwigFilter('filesize', 'format_bytes'),
            new TwigFilter('truncate', 'twig_truncate_filter'),
            new TwigFilter('truncate_body', 'truncate'),
            new TwigFilter('truncate_filename', 'twig_filename_truncate_filter'),
            new TwigFilter('extension', 'twig_extension_filter'),
            new TwigFilter('sprintf', 'sprintf'),
            new TwigFilter('capcode', 'capcode'),
            new TwigFilter('remove_modifiers', 'remove_modifiers'),
            new TwigFilter('hasPermission', 'twig_hasPermission_filter'),
            new TwigFilter('date', 'twig_date_filter'),
            new TwigFilter('poster_id', 'poster_id'),
            new TwigFilter('remove_whitespace', 'twig_remove_whitespace_filter'),
            new TwigFilter('count', 'count'),
            new TwigFilter('ago', 'ago'),
            new TwigFilter('until', 'until'),
            new TwigFilter('push', 'twig_push_filter'),
            new TwigFilter('bidi_cleanup', 'bidi_cleanup'),
            new TwigFilter('addslashes', 'addslashes'),
        );
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of filters
     */
    public function getFunctions()
    {
        return array(
            new TwigFunction('time', 'time'),
            new TwigFunction('floor', 'floor'),
            new TwigFunction('timezone', 'twig_timezone_function'),
            new TwigFunction('hiddenInputs', 'hiddenInputs'),
            new TwigFunction('hiddenInputsHash', 'hiddenInputsHash'),
            new TwigFunction('ratio', 'twig_ratio_function'),
            new TwigFunction('secure_link_confirm', 'twig_secure_link_confirm'),
            new TwigFunction('secure_link', 'twig_secure_link'),
            new TwigFunction('link_for', 'link_for')
        );
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'tinyboard';
    }
}

function twig_timezone_function() {
    return 'Z';
}

function twig_push_filter($array, $value) {
    array_push($array, $value);
    return $array;
}

function twig_remove_whitespace_filter($data) {
    return preg_replace('/[\t\r\n]/', '', $data);
}

function twig_date_filter($date, $format) {
    return gmstrftime($format, $date, 'en_US');
}

function twig_hasPermission_filter($mod, $permission, $board = null) {
    return hasPermission($permission, $board, $mod);
}

function twig_extension_filter($value, $case_insensitive = true) {
    $ext = mb_substr($value, mb_strrpos($value, '.') + 1);
    if($case_insensitive)
        $ext = mb_strtolower($ext);		
    return $ext;
}

function twig_sprintf_filter( $value, $var) {
    return sprintf($value, $var);
}

function twig_truncate_filter($value, $length = 30, $preserve = false, $separator = '…') {
    if (mb_strlen($value) > $length) {
        if ($preserve) {
            if (false !== ($breakpoint = mb_strpos($value, ' ', $length))) {
                $length = $breakpoint;
            }
        }
        return mb_substr($value, 0, $length) . $separator;
    }
    return $value;
}

function twig_filename_truncate_filter($value, $length = 30, $separator = '…') {
    if (mb_strlen($value) > $length) {
        $value = strrev($value);
        $array = array_reverse(explode(".", $value, 2));
        $array = array_map("strrev", $array);

        $filename = &$array[0];
        $extension = isset($array[1]) ? $array[1] : false;

        $filename = mb_substr($filename, 0, $length - ($extension ? mb_strlen($extension) + 1 : 0)) . $separator;

        return implode(".", $array);
    }
    return $value;
}

function twig_ratio_function($w, $h) {
    return fraction($w, $h, ':');
}
function twig_secure_link_confirm($text, $title, $confirm_message, $href) {
    global $config;

    return '<a onclick="if (event.which==2) return true;if (confirm(\'' . htmlentities(addslashes($confirm_message)) . '\')) document.location=\'?/' . htmlspecialchars(addslashes($href . '/' . make_secure_link_token($href))) . '\';return false;" title="' . htmlentities($title) . '" href="?/' . $href . '">' . $text . '</a>';
}
function twig_secure_link($href) {
    return $href . '/' . make_secure_link_token($href);
}
