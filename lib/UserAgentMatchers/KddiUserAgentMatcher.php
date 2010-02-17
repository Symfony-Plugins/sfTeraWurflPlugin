<?php
/*
 * Tera_WURFL - PHP MySQL driven WURFL
 * 
 * Tera-WURFL was written by Steve Kamerman, and is based on the
 * Java WURFL Evolution package by Luca Passani and WURFL PHP Tools by Andrea Trassati.
 * This version uses a MySQL database to store the entire WURFL file, multiple patch
 * files, and a persistent caching mechanism to provide extreme performance increases.
 * 
 * @package TeraWurfl
 * @author Steve Kamerman, stevekamerman AT gmail.com
 * @version Stable 2.0.0 $Date: 2009/11/13 23:59:59
 * @license http://www.mozilla.org/MPL/ MPL Vesion 1.1
 * $Id: KddiUserAgentMatcher.php,v 1.2 2008/03/01 00:05:25 kamermans Exp $
 * $RCSfile: KddiUserAgentMatcher.php,v $
 * 
 * Based On: Java WURFL Evolution by Luca Passani
 *
 */
class KddiUserAgentMatcher extends UserAgentMatcher {
	public function __construct(sfTeraWurfl $wurfl){
		parent::__construct($wurfl);
	}
	public function applyConclusiveMatch($ua) {
		if(self::startsWith($ua,"KDDI/")){
			$tolerance = UserAgentUtils::secondSlash($ua);
		}else{
			$tolerance = UserAgentUtils::firstSlash($ua);
		}
		$this->wurfl->toLog("Applying ".get_class($this)." Conclusive Match: RIS with threshold $tolerance",LOG_INFO);
		return $this->risMatch($ua, $tolerance);
	}
	public function recoveryMatch($ua){
		$this->wurfl->toLog("Applying ".get_class($this)." Recovery Match",LOG_INFO);
		return "opwv_v62_generic";
	}
}
?>