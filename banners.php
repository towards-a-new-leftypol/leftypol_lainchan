<?php
function getBannerSrc(){
	$files = scandir(__dir__.'/banners/');
	$files = array_diff($files, array('.', '..'));
	return $files[array_rand($files)];
}

$filename = getBannerSrc();
$filename = "banners/" . $filename;
$fp = fopen($filename, 'rb');

header("Content-Type: image/png");
header("Content-Length: " . filesize($filename)); 

fpassthru($fp);
?>