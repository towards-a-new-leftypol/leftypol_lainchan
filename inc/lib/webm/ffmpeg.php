<?php
/*
 * ffmpeg.php
 * A barebones ffmpeg based webm implementation for vichan.
 */

function get_webm_info($filename) {
	global $board, $config;
	$filename = escapeshellarg($filename);
	$ffprobe = $config['webm']['ffprobe_path'];
	$ffprobe_out = array();
	$webminfo = array();

	exec("$ffprobe -v quiet -print_format json -show_format -show_streams $filename", $ffprobe_out);
	$ffprobe_out = json_decode(implode("\n", $ffprobe_out), 1);
	$validcheck = is_valid_webm($ffprobe_out);
	$webminfo['error'] = $validcheck['error'];
	if(empty($webminfo['error'])) {
		$trackmap = $validcheck['trackmap'];
		$videoidx = $trackmap['videoat'][0];
		$webminfo['width'] = $ffprobe_out['streams'][$videoidx]['width'];
		$webminfo['height'] = $ffprobe_out['streams'][$videoidx]['height'];
		$webminfo['duration'] = $ffprobe_out['format']['duration'];
	}
	return $webminfo;
}

function locate_webm_tracks($ffprobe_out) {
    $streams     = $ffprobe_out['streams'];
    $streamcount = count($streams);
    $videoat     = array();
    $audioat     = array();
    $others      = array();

    for ($k = 0; $k < $streamcount; $k++) {
        $stream     = $streams[$k];
        $codec_type = $stream['codec_type'];

        if ($codec_type === 'video') {
            $videoat[] = $k;
        } elseif($codec_type === 'audio') {
            $audioat[] = $k;
        } else {
            if (isset($others[$codec_type])) {
                $others[$codec_type][] = $k;
            } else {
                $others[$codec_type] = [$k];
            }
        }
    }

    return array('videoat' => $videoat, 'audioat' => $audioat, 'others' => $others);
/*
var_dump example:
array(3) {
  ["videoat"]=>  array(1) {
    [0]=>    int(0)
  }
  ["audioat"]=>  array(1) {
    [0]=>    int(1)
  }
  ["others"]=>  array(1) {
    ["data"]=>    array(2) {
      [0]=>      int(2)
      [1]=>      int(3)
    }
  }
}
 */
}

function is_valid_webm($ffprobe_out) {
	global $board, $config;
	if (empty($ffprobe_out))
		return array('error' => array('code' => 1, 'msg' => $config['error']['genwebmerror']));
	$trackmap = locate_webm_tracks($ffprobe_out);

	// one video track
	if (count($trackmap['videoat']) != 1)
		return array('error' => array('code' => 2, 'msg' => $config['error']['invalidwebm']." [video track count]"));
	$videoidx = $trackmap['videoat'][0];

	$extension = pathinfo($ffprobe_out['format']['filename'], PATHINFO_EXTENSION);
	if ($extension === 'webm' && !stristr($ffprobe_out['format']['format_name'], 'mp4')) {
		if ($ffprobe_out['format']['format_name'] != 'matroska,webm')
			return array('error' => array('code' => 2, 'msg' => $config['error']['invalidwebm']."error 1"));
	} elseif ($extension === 'mp4' || stristr($ffprobe_out['format']['format_name'], 'mp4')) {
		// if the video is not h264 or (there is audio but it's not aac)
		if (($ffprobe_out['streams'][$videoidx]['codec_name'] != 'h264') || ((count($trackmap['audioat']) > 0) && ($ffprobe_out['streams'][$trackmap['audioat'][0]]['codec_name'] != 'aac')))
			return array('error' => array('code' => 2, 'msg' => $config['error']['invalidwebm']." [h264/aac check]"));
	} else {
		return array('error' => array('code' => 1, 'msg' => $config['error']['genwebmerror']."error 3"));
	}
	if ((count($ffprobe_out['streams']) > 1) && (!$config['webm']['allow_audio']))
		return array('error' => array('code' => 3, 'msg' => $config['error']['webmhasaudio']."error 4"));
	if ((count($trackmap['audioat']) > 0) && !$config['webm']['allow_audio']) {
		return array('error' => array('code' => 3, 'msg' => $config['error']['webmhasaudio']."error 5"));
	}
	if ($ffprobe_out['format']['duration'] > $config['webm']['max_length'])
		return array('error' => array('code' => 4, 'msg' => sprintf($config['error']['webmtoolong'], $config['webm']['max_length'])."error 6"));
	return array('error' => array(), 'trackmap' => $trackmap);
}
function make_webm_thumbnail($filename, $thumbnail, $width, $height, $duration) {
	global $board, $config;
	$filename = escapeshellarg($filename);
	$thumbnailfc = escapeshellarg($thumbnail); // Should be safe by default but you
	// can never be too safe.
	$width = escapeshellarg($width);
	$height = escapeshellarg($height); // Same as above.
	$ffmpeg = $config['webm']['ffmpeg_path'];
	$ret = 0;
	$ffmpeg_out = array();
	exec("$ffmpeg -strict -2 -ss " . floor($duration / 2) . " -i $filename -v quiet -an -vframes 1 -f mjpeg -vf scale=$width:$height $thumbnailfc 2>&1", $ffmpeg_out, $ret);
	// Work around for https://trac.ffmpeg.org/ticket/4362
	if (filesize($thumbnail) === 0) {
		// try again with first frame
		exec("$ffmpeg -y -strict -2 -ss 0 -i $filename -v quiet -an -vframes 1 -f mjpeg -vf scale=$width:$height $thumbnailfc 2>&1", $ffmpeg_out, $ret);
		clearstatcache();
		// failed if no thumbnail size even if ret code 0, ffmpeg is buggy
		if (filesize($thumbnail) === 0) {
			$ret = 1;
		}
	}
	return $ret;
}