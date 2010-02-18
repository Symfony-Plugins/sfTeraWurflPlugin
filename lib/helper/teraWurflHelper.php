<?php
/*
 * This file is part of the sfTeraWurflPlugin plugin.
 * (c) 2010 Skander Mabrouk <mabroukskander@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * generates a thumb for current user agent, and return the web path to image,
 * 
 * @param string $file image file(absolute path !), ie: /var/www/myproject/images/logo.gif
 * @param float $width_ratio a valid float ratio, ie: 0.98 : the new image will have 98% width screen device as width
 * @param float $height_ratio a valid float ratio, ie: 0.2 : the new image will have 20% height screen device as height
 * @return string $path an absolute path to generated image
 *
 */
function tw_generateThumb($file, $width_ratio = NULL, $height_ratio=NULL){
	$thumb_dir = sfConfig::get("app_sfTeraWurflPlugin_thumb_dir");
	$tw = new sfTeraWurfl();
	$width = $tw->getUserAgentWidthScreen();
	if (!empty($width_ratio)) $width = $width*$width_ratio;
	$width = round($width);
	$height = $tw->getUserAgentHeightScreen();
	if (!empty($height_ratio)) $height = $height*$height_ratio;
	$height = round($height);
	if (!is_file($file))
		throw new Exception("source image file does not exist ! $file");
	if (!is_dir($thumb_dir))
		throw new Exception("Destination directory images thumb does not exist ! $thumb_dir");
	if (!is_writable($thumb_dir))
		throw new Exception("Permission denied ! could not write image on $thumb_dir");
		
	$filename = $width."x".$height."_".basename($file);
	
	$thumbnail = new sfThumbnail($width, $height, TRUE, TRUE, 100);
	$thumbnail->loadFile($file);
	if (file_exists($thumb_dir.$filename))
		return tw_getThumbPath().$filename;
		
	$thumbnail->save($thumb_dir.$filename);
	return tw_getThumbPath().$filename;
}

function tw_getThumbPath(){
	return str_replace(sfConfig::get("sf_web_dir"),' ',sfConfig::get("app_sfTeraWurflPlugin_thumb_dir"));
}
?>