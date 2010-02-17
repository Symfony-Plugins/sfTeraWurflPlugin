<?php

//error_reporting ( E_ALL );

//ini_set ( "display_errors", 1 );
 
/*$urlroot = $_SERVER ['SERVER_NAME'];
$pathroot = sfConfig::get ( "sf_root_dir" );

$pathwurfl = '/lib/vendor/wurfl/Tera-Wurfl/';

$ua = WurflSupport::getUserAgent ();
$wurfl = new sfTeraWurfl ();
$wurfl->getDeviceCapabilitiesFromAgent ( $ua, TRUE );
$cap = $wurfl->capabilities;
$width_screen =  $cap ['display'] ['max_image_width'];
$height_screen = $cap ['display'] ['max_image_height'];

//require_once ($pathroot . 'common/class/txt_function.php');

//params


$ip = $_SERVER ["REMOTE_ADDR"];

$default_max_width = 80; // Needed if server does not respond


//USED TO KNOW THE CURRENT PAGE
$root = getenv ( "SCRIPT_NAME" );

$str = explode ( "/", $root );

$page = $str [sizeof ( $str ) - 1];
*/

/**
 * return the current user agent max width screen
 * @return int
 *
 */
function tw_getUserAgentWidth(){
	$user = sfContext::getInstance()->getUser();
	if ($user->hasAttribute("user_agent_screen_max_width")){
		return $user->getAttribute("user_agent_screen_max_width");
	}
	$ua = WurflSupport::getUserAgent ();
	$wurfl = new sfTeraWurfl ();
	$wurfl->getDeviceCapabilitiesFromAgent ( $ua, TRUE );
	$cap = $wurfl->capabilities;
	$width_screen =  $cap ['display'] ['max_image_height'];
	$user->setAttribute("user_agent_screen_max_width",$width_screen);
	return $width_screen;
}
/**
 * return the current user agent max height screen
 *
 * @return int
 */
function tw_getUserAgentHeight(){
	$user = sfContext::getInstance()->getUser();
	if ($user->hasAttribute("user_agent_screen_max_height")){
		return $user->getAttribute("user_agent_screen_max_height");
	}
	$ua = WurflSupport::getUserAgent ();
	$wurfl = new sfTeraWurfl ();
	$wurfl->getDeviceCapabilitiesFromAgent ( $ua, TRUE );
	$cap = $wurfl->capabilities;
	$height_screen =  $cap ['display'] ['max_image_width'];
	$user->setAttribute("user_agent_screen_max_height",$height_screen);
	return $width_screen;
}
/**
 * generates a thumb for current user agent, and return the logic path to image
 * @param string $file image file(should be a valid file under web dir), ie: /images/logo.gif
 * 
 * @return string $path 
 *
 */
function tw_generateThumb($file, $width_ratio = NULL, $height_ratio=NULL){
	$thumb_dir = sfConfig::get("sf_web_dir")."/images/thumbs/";
	$width = tw_getUserAgentWidth();
	if (!empty($width_ratio)) $width = $width*$width_ratio;
	$width = round($width);
	$height = tw_getUserAgentHeight();
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
		return "/images/thumbs/".$filename;
	$thumbnail->save(sfConfig::get('sf_web_dir')."/images/thumbs/".$filename);
	return "/images/thumbs/".$filename;
}
/**
 * Resize and save image if this wasn't already done
 * 
 * @param string $imgid image name
 * @param string $subpath chemin d'accès à l'image !ATTENTION! il ne doit PAS contenir le dossier SOURCE mais s'arreter au repertoire parent
 * @param string $ext: extension/type d'image
 * @param int $width: largeur max de l'image souhaitée, largeur de l'écran
 * @param int $height: hauteur max de l'image souhaitée, hauteur de l'écran
 * @param string color_choice: [optionnel] permet de définir une couleur remplacant la transparence d'une image 
 * @param string output_type: permet de définir le type d'image en sortie, si output == jpg ou jpeg alors se sera un jpg sinon un gif
 * 
 * @return array witch contain url to resized image, with is size
 * $array['url']      resized picture's URL
 * $array['largeur']  resized picture's width
 * $array['hauteur']  resized picture's height
 **/
function tw_gen_img($imgid, $subpath, $ext, $width, $height, $color_choice, $output_type) {

	// limit executing time in case of overloading
	set_time_limit ( 10 );
	
	global $urlroot;
	
	
	
	$pathroot=sfConfig::get ( "sf_root_dir" );
	//$subpath=str_replace('/','\\',$subpath);
	
	if ($output_type == 'jpeg' or $output_type == 'jpg') {
		
		$output_type = 'jpg';
	
	} else {
		
		$output_type = 'gif';
	}
	
	//Round value
	$width = round ( $width );
	
	$height = round ( $height );
	
	//Get Original dimensions through file name
	$regex_dim = "_xy_";
	
	if (strpos ( $imgid, $regex_dim )) {
		
		$rootimgid = preg_replace ( '`_xy_[[:digit:]]+x[[:digit:]]+.`', '', $imgid );
		
		$tabxy = explode ( 'x', str_replace ( $rootimgid . $regex_dim, '', $imgid ) );
		
		$largeur_orig = $tabxy [0];
		
		$hauteur_orig = $tabxy [1];
		
		//Set right dimensions according original ratio
		if ($largeur_orig > $width || $hauteur_orig > $height) {
			
			// if the picture is too large
			$xrate = $width / $largeur_orig;
			
			$yrate = $height / $hauteur_orig;
			
			$rate = min ( $xrate, $yrate );
			
			$hauteur = round ( $hauteur_orig * $rate );
			
			$largeur = round ( $largeur_orig * $rate );
		
		} else {
			
			$largeur = $largeur_orig;
			
			$hauteur = $hauteur_orig;
		
		}
		
		$width = $largeur;
		
		$height = $hauteur;
	
	}
	
	//////////////////////
	$regex_http = "`^http://`";
	
	if (preg_match ( $regex_http, $subpath )) {
		
		//the picture isn't in local serveur, we have to download it before resize it
		

		$img = $subpath . $imgid . '.' . $ext; //external path
		$destination = $subpath . 'source/' . $imgid . '.' . $ext;
		
		if (! is_file ( $destination )) //the picture has already been download
		{
			$source_file = file_get_contents ( $img ); //read image content
			$destination_file = fopen ( $destination, 'w+' ); //create new file with read and write access
			fwrite ( $destination_file, $source_file ); // copy image in new file
		}
		
		$img = $urlroot . $destination;
		
		$imglocal = $pathroot . $destination;
	
	} else {
		
		//get image path
		$img = $urlroot . $subpath . 'source/' . $imgid . '.' . $ext;
		
		$imglocal = $pathroot . $subpath . 'source/' . $imgid . '.' . $ext;
	
	}
	
	// picture's folder :
	$output = $pathroot . $subpath . 'resize/';
	
	$outurl = $urlroot . $subpath . 'resize/';
	
	/*
	* if empty dimension :
	* we take defaults one (120*90)
	*/
	
	if (! isset ( $width ) || empty ( $width )) {
		
		$width = 120;
	
	}
	
	if (! isset ( $height ) || empty ( $height )) {
		
		$height = 90;
	
	}
	
	//if picture already exist
	//work is done
	

	if (is_file ( $output . $width . 'x' . $height . '_' . $imgid . '.' . $output_type )) {
		
		$tab_img ['url'] = $outurl . $width . 'x' . $height . '_' . $imgid . '.' . $output_type;
		
		$tab_img ['largeur'] = $width;
		
		$tab_img ['hauteur'] = $height;
	
	} else {
		
		//else we are looking for best values
		

		// opening file
		$file = @fopen ( $imglocal, 'r' );
		
		// no source
		if (! $file) {
			
			$src = imagecreatetruecolor ( $width, $height );
			
			$bgcolor = imagecolorallocate ( $src, 255, 255, 255 );
			
			$tcolor = imagecolorallocate ( $src, 0, 0, 0 );
			
			imagefill ( $src, 0, 0, $bgcolor );
			
			imagestring ( $src, 10, ($width - 2) / 2, ($height - 2) / 2, "-", $tcolor );
			
		//imagestring($src,10,2,($height+20)/2,"INTROUVABLE",$tcolor);
		

		} else {
			
			// is open
			

			if (! list ( $largeur_orig, $hauteur_orig, $ext ) = getimagesize ( $imglocal )) {
				
				$src = imagecreatetruecolor ( $width, $height );
				
				$bgcolor = imagecolorallocate ( $src, 255, 0, 0 );
				
				$tcolor = imagecolorallocate ( $src, 255, 0, 0 );
				
				imagestring ( $src, 10, 2, ($height - 20) / 2, "--", $tcolor );
				
			//imagestring($src,10,2,($height+20)/2,"-INCORRECT-",$tcolor);
			} else {
				
				// checking size
				if ($largeur_orig > $width || $hauteur_orig > $height) {
					
					// to large
					$xrate = $width / $largeur_orig;
					
					$yrate = $height / $hauteur_orig;
					
					$rate = min ( $xrate, $yrate );
					
					$hauteur = round ( $hauteur_orig * $rate );
					
					$largeur = round ( $largeur_orig * $rate );
				
				} else {
					
					$largeur = $largeur_orig;
					
					$hauteur = $hauteur_orig;
				
				}
				
				// we check if the picture with the new dimension already exist or not
				if (is_file ( $output . $largeur . 'x' . $hauteur . '_' . $imgid . '.' . $output_type )) {
					
					$tab_img ['url'] = $outurl . $largeur . 'x' . $hauteur . '_' . $imgid . '.' . $output_type;
					
					$tab_img ['largeur'] = $largeur;
					
					$tab_img ['hauteur'] = $hauteur;
				
				} else {
					
					// checking format jpg, gif ou png :
					if (preg_match ( '`(jpeg|jpg|gif|png)$`', $imglocal )) {
						
						// begin switch
						switch ($ext) {
							
							case 1 : // GIF
								$im = imagecreatefromgif ( $imglocal );
								
								break;
							
							case 2 : //JPEG
								$im = imagecreatefromjpeg ( $imglocal );
								
								break;
							
							case 3 : // PNG
								$im = imagecreatefrompng ( $imglocal );
								
								break;
						
						}
						
						// end switch
						

						// on calcule position de l'image
						//$py=($height-$hauteur)/2;
						//$px=($width-$largeur)/2;
						$py = 0;
						
						$px = 0;
						
						$src = imagecreatetruecolor ( $largeur, $hauteur );
						
						$bgcolor = imagecolorallocate ( $src, substr ( $color_choice, 0, - 6 ), substr ( $color_choice, 3, - 3 ), substr ( $color_choice, 6 ) ); // recuperatoin des donnees RGB
						imagefill ( $src, 0, 0, $bgcolor );
						
						imagecopyresampled ( $src, $im, $px, $py, 0, 0, $largeur, $hauteur, $largeur_orig, $hauteur_orig );
					
					} else {
						
						// wrong format
						$src = imagecreatetruecolor ( $width, $height );
						
						$bgcolor = imagecolorallocate ( $src, 255, 255, 255 );
						
						$tcolor = imagecolorallocate ( $src, 0, 0, 0 );
						
						imagestring ( $src, 10, 2, ($height - 20) / 2, "-", $tcolor );
						
					//imagestring($src,10,2,($height+20)/2,"INCORRECT",$tcolor);
					}
				
				}
				
			// end second check existing file
			}
		
		}
		
		$tab_img = array ();
		
		// Cache
		//image existing
		//works's done
		if (is_file ( $output . $largeur . 'x' . $hauteur . '_' . $imgid . '.' . $output_type )) {
			
			$url = $urlroot . $subpath . 'resize/' . $largeur . 'x' . $hauteur . '_' . $imgid . '.' . $output_type;
			
			$tab_img ['url'] = $url;
			
			//list($tab_img['largeur'],$tab_img['hauteur'],$exttmp) = getimagesize($url);
			$tab_img ['largeur'] = $largeur;
			
			$tab_img ['hauteur'] = $hauteur;
		
		} else {
			
			//else, picture doesn't exist
			//we create picture
			//free resource
			

			if ($output_type == 'jpg') {
				
				imagejpeg ( $src, $output . $largeur . 'x' . $hauteur . '_' . $imgid . '.jpg', 80 );
			
			} else {
				
				imagegif ( $src, $output . $largeur . 'x' . $hauteur . '_' . $imgid . '.gif', 80 );
			
			}
			
			imagedestroy ( $src );
			
			$url = $urlroot . $subpath . 'resize/' . $largeur . 'x' . $hauteur . '_' . $imgid . '.' . $output_type;
			
			$tab_img ['url'] = $url;
			
			$tab_img ['largeur'] = $largeur; // width
			$tab_img ['hauteur'] = $hauteur; //height
		}
	
	}
	
	return $tab_img;

}

//need to be call after session start/*
if (isset ( $_GET ['lang'] ) and ($_GET ['lang'] == 'fr' or $_GET ['l'] == 'en' or $_GET ['l'] == 'pt' or $_GET ['sw'])) {
	$lg = $_GET ['l'];
	$_SESSION['lg']=$lg;
} else {
	if (isset ( $_SESSION ['lg'] ) and $_SESSION['lg']!='') {
		$lg=$_SESSION['lg'];
	} else {
		$lg = 'en';
	}
	
}*/

?>