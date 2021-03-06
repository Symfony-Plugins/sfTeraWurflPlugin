<?php
/*
 * This file is part of the sfTeraWurflPlugin plugin.
 * (c) 2010 Skander Mabrouk <mabroukskander@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfTeraWurfl
 *
 * @package    sfTeraWurflPlugin
 * @author     Skander Mabrouk <mabroukskander@gmail.com>
 * @version    SVN: $Id: sfTeraWurfl.class.php 1 skonsoft $
 */
class sfTeraWurfl{
	
	public $errors;
	public $capabilities;
	public $db;
	public $rootdir;
	public $userAgent; 
	public $httpAccept;
	public $userAgentMatcher;
	public $tablename;
	public $foundInCache;
	
	public $release_branch = "Stable";
	public $release_version = "2.0.0";
	public static $required_php_version = "5.0.0";
	
	private $lookup_start;
	private $lookup_end;
	private $matchDataKey = "tera_wurfl";
	private $matchData;
	private $matcherHistory;
	/*
	 * This keeps the device fallback lookup from running away.
	 * The deepest device I've seen is sonyericsson_z520a_subr3c at 15
	 */
	private $maxDeviceDepth = 40;
	
	// Constructor
	public function __construct(){
		$this->errors = array();
		$this->capabilities = array();
		$this->matcherHistory = array();
		$this->rootdir = sfConfig::get("sf_plugins_dir").DIRECTORY_SEPARATOR."sfTeraWurflPlugin".DIRECTORY_SEPARATOR."lib/";
		$this->db = new TeraWurflDatabase_MySQL5();
		if(!$this->db->connect()){
			//throw new Exception("Cannot connect to database: ".$this->db->getLastError());
			return false;
		}
	}

	// Public Methods
	
	private function getDeviceIDFromUALoose(){
		$this->matcherHistory = array();
		// Return generic UA if userAgent is empty
		if(strlen($this->userAgent)==0){
			$this->matchData['matcher'] = "none"; 
			$this->matchData['match_type'] = "none";
			$this->matchData['match'] = false;
			$this->setMatcherHistory();
			return WurflConstants::$GENERIC;
		}
		// Set the table to be used for searching by the database
		$this->db->tablename = $this->fullTableName();
		
		// Check for exact match
		$deviceID = $this->db->getDeviceFromUA($this->userAgent);
		$this->matcherHistory[] = $this->userAgentMatcher->matcherName() . "(exact)";
		if($deviceID !== false){
			$this->matchData['matcher'] = $this->userAgentMatcher->matcherName();
			$this->matchData['match_type'] = "exact";
			$this->matchData['match'] = true;
			$this->setMatcherHistory();
			return $deviceID;
		}
		// Check for a conclusive match
		$deviceID = $this->userAgentMatcher->applyConclusiveMatch($this->userAgent);
		$this->matcherHistory[] = $this->userAgentMatcher->matcherName() . "(conclusive)";
		if($deviceID != WurflConstants::$GENERIC){
			$this->matchData['matcher'] = $this->userAgentMatcher->matcherName();
			$this->matchData['match_type'] = "conclusive";
			$this->matchData['match'] = true;
			$this->setMatcherHistory();
			return $deviceID;
		}
		// Check for Vodafone magic
		if($this->userAgentMatcher->matcherName()!="VodafoneUserAgentMatcher" && UserAgentMatcher::contains($this->userAgent,"Vodafone")){
			@require_once("UserAgentMatchers/VodafoneUserAgentMatcher.php");
			$vodafoneUserAgentMatcher = new VodafoneUserAgentMatcher($this);
			$this->matcherHistory[] = $vodafoneUserAgentMatcher->matcherName() . "(conclusive)";
			$deviceID = $vodafoneUserAgentMatcher->applyConclusiveMatch($this->userAgent);
			if($deviceID != WurflConstants::$GENERIC){
				$this->matchData['matcher'] = $vodafoneUserAgentMatcher->matcherName();
				$this->matchData['match_type'] = "conclusive";
				$this->matchData['match'] = true;
				$this->setMatcherHistory();
				return $deviceID;
			}
		}
		// Check for recovery match
		$deviceID = $this->userAgentMatcher->applyRecoveryMatch($this->userAgent);
		$this->matcherHistory[] = $this->userAgentMatcher->matcherName() . "(recovery)";
		if($deviceID != WurflConstants::$GENERIC){
			$this->matchData['matcher'] = $this->userAgentMatcher->matcherName();
			$this->matchData['match_type'] = "recovery";
			$this->matchData['match'] = false;
			$this->setMatcherHistory();
			return $deviceID;
		}
		// Check CatchAll if it's not already in use
		if($this->userAgentMatcher->matcherName()!="CatchAllUserAgentMatcher"){
			$catchAllUserAgentMatcher = new CatchAllUserAgentMatcher($this);
			$this->matcherHistory[] = $catchAllUserAgentMatcher->matcherName() . "(recovery)";
			$deviceID = $catchAllUserAgentMatcher->applyRecoveryMatch($this->userAgent);
			if($deviceID != WurflConstants::$GENERIC){
				// The CatchAll matcher is intelligent enough to determine the match properties
				$this->matchData['matcher'] = $catchAllUserAgentMatcher->matcher;
				$this->matchData['match_type'] = $catchAllUserAgentMatcher->match_type;
				$this->matchData['match'] = $catchAllUserAgentMatcher->match;
				$this->setMatcherHistory();
				return $deviceID;
			}
		}
		
		// A matching device still hasn't been found - check HTTP ACCEPT headers
		if(strlen($this->httpAccept) > 0){
			$this->matcherHistory[] = "http_accept";
			if(UserAgentMatcher::contains($this->httpAccept,array(
				WurflConstants::$ACCEPT_HEADER_VND_WAP_XHTML_XML,
				WurflConstants::$ACCEPT_HEADER_XHTML_XML,
				WurflConstants::$ACCEPT_HEADER_TEXT_HTML
			  ))){
				$this->matchData['matcher'] = "http_accept";
				$this->matchData['match_type'] = "recovery";
				// This isn't really a match, it's a suggestion
				$this->matchData['match'] = false;
				$this->setMatcherHistory();
				return WurflConstants::$GENERIC_XHTML;
			}
		}
		$this->matchData['matcher'] = "none";
		$this->matchData['match_type'] = "none";
		$this->matchData['match'] = false;
		$this->setMatcherHistory();
		return (UserAgentUtils::isMobileBrowser($this->userAgent))? WurflConstants::$GENERIC: WurflConstants::$GENERIC_WEB_BROWSER;
	}
	public function getDeviceCapabilitiesFromAgent($userAgent=null,$httpAccept=null){
		$this->db->numQueries = 0;
		$this->matchData = array(
			"num_queries" => 0,
			"actual_root_device" => '',
			"match_type" => '',
			"matcher" => '',
			"match"	=> false,
			"lookup_time" => 0,
			"fall_back_tree" => ''
		);
		$this->lookup_start = microtime(true);
		$this->foundInCache = false;
		$this->capabilities = array();
		// Define User Agent
		$this->userAgent = (is_null($userAgent))? WurflSupport::getUserAgent(): $userAgent;
		// Define HTTP ACCEPT header.  Default: DO NOT use HTTP_ACCEPT headers
		//$this->httpAccept= (is_null($httpAccept))? WurflSupport::getAcceptHeader(): $httpAccept;
		$this->tablename = sfTeraWurflConfig::getDeviceTableNamePrefix();

		// Remove the gateway signatures from UA (UP.Link/x.x.x)
		$this->userAgent = UserAgentUtils::removeUPLinkFromUA($this->userAgent);
		// Check cache for device
		if(sfTeraWurflConfig::isCacheEnabled()){
			$cacheData = $this->db->getDeviceFromCache($this->userAgent);
			// Found in cache
			if($cacheData !== false){
				$this->capabilities = $cacheData;
				$this->foundInCache = true;
				$deviceID = $cacheData['id'];
			}
		}
		if(!$this->foundInCache){
			// Find appropriate user agent matcher
			$this->userAgentMatcher = UserAgentFactory::createUserAgentMatcher($this,$this->userAgent);
			// Find the best matching WURFL ID
			$deviceID = $this->getDeviceIDFromUALoose($userAgent);
			// Get the capabilities of this device and all its ancestors
			$this->getFullCapabilities($deviceID);
			// Now add in the Tera-WURFL results array
			$this->lookup_end = microtime(true);
			$this->matchData['num_queries'] = $this->db->numQueries;
			$this->matchData['lookup_time'] = $this->lookup_end - $this->lookup_start;
			// Add the match data to the capabilities array so it gets cached
			$this->addCapabilities(array($this->matchDataKey => $this->matchData));
		}
		if(sfTeraWurflConfig::isCacheEnabled()==true && !$this->foundInCache){
			// Since this device was not cached, cache it now.
			$this->db->saveDeviceInCache($this->userAgent,$this->capabilities);
		}
		return $this->capabilities[$this->matchDataKey]['match'];
	}
	private function getFullCapabilities($deviceID){
		if(is_null($deviceID)){
			throw new Exception("Invalid Device ID: ".var_export($deviceID,true)."\nMatcher: {$this->userAgentMatcher->matcherName()}\nUser Agent: ".$this->userAgent);
			die();
		}
		$this->db->tablename = '';
		// Now get all the devices in the fallback tree
		$fallbackTree = array();
		$childDevice = $this->db->getDeviceFromID($deviceID);
		$fallbackTree[] = $childDevice;
		$fallbackIDs[] = $childDevice['id'];
		$currentDevice = $childDevice;
		$i=0;
		/**
		 * This loop starts with the best-matched device, and follows its fall_back until it reaches the GENERIC device
		 * Lets use "tmobile_shadow_ver1" for an example:
		 * 
		 * 'id' => 'tmobile_shadow_ver1', 'fall_back' => 'ms_mobile_browser_ver1'
		 * 'id' => 'ms_mobile_browser_ver1', 'fall_back' => 'generic_xhtml'
		 * 'id' => 'generic_xhtml', 'fall_back' => 'generic'
		 * 'id' => 'generic', 'fall_back' => 'root'
		 * 
		 * This fallback_tree in this example contains 4 elements in the order shown above.
		 * 
		 */
		while($currentDevice['fall_back'] != "root"){
			$currentDevice = $this->db->getDeviceFromID($currentDevice['fall_back']);
			if(in_array($currentDevice['id'],$fallbackIDs)){
				// The device we just looked up is already in the list, which means that
				// we are going to enter an infinate loop if we don't break from it.
				$this->toLog("The device we just looked up is already in the list, which means that we are going to enter an infinate loop if we don't break from it. DeviceID: $deviceID, FallbackIDs: [".implode(',',$fallbackIDs)."]",LOG_ERR);
				throw new Exception("Killed script to prevent infinate loop.  See log for details.");
				break;
			}
			if(!isset($currentDevice['fall_back']) || $currentDevice['fall_back'] == ''){
				$this->toLog("Empty fall_back detected. DeviceID: $deviceID, FallbackIDs: [".implode(',',$fallbackIDs)."]",LOG_ERR);
				throw new Exception("Empty fall_back detected.  See log for details.");
			}
			$fallbackTree[] = $currentDevice;
			$fallbackIDs[] = $currentDevice['id'];
			$i++;
			if($i > $this->maxDeviceDepth){
				$this->toLog("Exceeded maxDeviceDepth while trying to build capabilities for device. DeviceID: $deviceID, FallbackIDs: [".implode(',',$fallbackIDs)."]",LOG_ERR);
				throw new Exception("Killed script to prevent infinate loop.  See log for details.");
				break;
			}
		}
		$this->matchData['fall_back_tree'] = implode(',',$fallbackIDs);
		if($fallbackTree[count($fallbackTree)-1]['id'] != WurflConstants::$GENERIC){
			// The device we are looking up cannot be traced back to the GENERIC device
			// and will likely not contain the correct capabilities
			$this->toLog("The device we are looking up cannot be traced back to the GENERIC device and will likely not contain the correct capabilities. DeviceID: $deviceID, FallbackIDs: [".implode(',',$fallbackIDs)."]",LOG_ERR);
		}
		/**
		 * Merge the device capabilities from the parent (GENERIC) to the child (DeviceID)
		 * We merge in this order because the GENERIC device contains all the properties that can be set
		 * Then the next child modifies them, then the next child, and the next child, etc... 
		 */
		while(count($fallbackTree)>0){
			$dev = array_pop($fallbackTree);
			// actual_root_device is the most accurate device in the fallback tree that is a "real" device, not a sub version or generic
			if(isset($dev['actual_device_root']) && $dev['actual_device_root'])$this->matchData['actual_root_device'] = $dev['id'];
			$this->addCapabilities($dev);
		}
		$this->addTopLevelSettings($childDevice);
	}
	public function getDeviceCapability($capability) {
		// TODO: Optimize function
		$this->toLog('Searching for '.$capability.' as a capability', LOG_INFO);
		$deviceCapabilities = $this->capabilities;
		foreach ( $deviceCapabilities as $group ) {
			if ( !is_array($group) ) {
				continue;
			}
			while ( list($key, $value)=each($group) ) {
				if ($key==$capability) {
					$this->toLog('I found it, value is '.$value, LOG_INFO);
					return $value;
				}
			}
		}
		$this->toLog('I could not find the requested capability ('.$capability.'), returning NULL', LOG_WARNING);
		// since 1.5.2, I can't return "false" because that is a valid value.  Now I return NULL, use is_null() to check
		return NULL;
	}
	public function fullTableName(){
		return $this->tablename.'_'.$this->userAgentMatcher->tableSuffix();
	}
	public function toLog($text, $requestedLogLevel=LOG_NOTICE, $func="Tera-WURFL"){
		if($requestedLogLevel == LOG_ERR) $this->errors[] = $text;
		if (sfTeraWurflConfig::$LOG_LEVEL == 0 || ($requestedLogLevel-1) >= sfTeraWurflConfig::$LOG_LEVEL ) {
			return;
		}
		if ( $requestedLogLevel == LOG_ERR ) {
			$warn_banner = 'ERROR: ';
		} else if ( $requestedLogLevel == LOG_WARNING ) {
			$warn_banner = 'WARNING: ';
		} else {
			$warn_banner = '';
		}
		$_textToLog = date('r')." [".php_uname('n')." ".getmypid()."]"."[$func] ".$warn_banner . $text;
		$logfile = sfConfig::get('sf_root_dir').DIRECTORY_SEPARATOR."log".DIRECTORY_SEPARATOR.sfTeraWurflConfig::$LOG_FILE;
		if (!is_file($logfile))
		{
			$_logFP = fopen($logfile, "w");
			fputs($_logFP," \n");
			fclose($_logFP);
		}
		if(!is_writeable($logfile)){
			throw new Exception("Tera-WURFL Error: cannot write to log file ($logfile)");
		}
		$_logFP = fopen($logfile, "a+");
		fputs($_logFP, $_textToLog."\n");
		fclose($_logFP);
		return true;
	}
	public function addTopLevelSettings($newCapabilities){
		foreach($newCapabilities as $key => $val){
			if(is_array($val))continue;
			$this->capabilities[$key] = $val;
		}
	}
	public function addCapabilities($newCapabilities){
		self::mergeCapabilities($this->capabilities,$newCapabilities);
	}
	private function setMatcherHistory(){
		$this->matchData['matcher_history'] = implode(',',$this->matcherHistory);
	}
	public static function mergeCapabilities(&$baseDevice, $addedDevice){
		if(count($baseDevice) == 0){
			// Base device is empty
			$baseDevice = $addedDevice;
			return;
		}
		foreach($addedDevice as $levOneKey => $levOneVal){
			// Check if the base device has defined this value yet
			if(!is_array($levOneVal)){
				// This is top level setting, not a capability
				continue;
			}else{
				if(!isset($baseDevice[$levOneKey]))$baseDevice[$levOneKey]=array();
				// This is an array value, merge the contents
				foreach($levOneVal as $levTwoKey => $levTwoVal){
					// This is just a scalar value, apply it
					$baseDevice[$levOneKey][$levTwoKey] = $levTwoVal;
					continue;
				}
			}
		}
	}
	/**
	 * return the current user agent max width screen, it's a short link to $this->capabilities ['display'] ['max_image_width'];
	 * @return int
	 */
	public function getUserAgentWidthScreen(){
		$user = sfContext::getInstance()->getUser();
		if ($user->hasAttribute("user_agent_screen_max_width")){
			return $user->getAttribute("user_agent_screen_max_width");
		}
		if (count($this->capabilities)==0) {
			$this->getDeviceCapabilitiesFromAgent(NULL,TRUE);
			
		}
		$width =  $this->capabilities ['display'] ['max_image_width'];
		$user->setAttribute("user_agent_screen_max_width",$width);
		return $width;		
	}
	/**
	 * return the current user agent max height screen, it's a short link to $this->capabilities ['display'] ['max_image_height'];
	 * @return int
	 */
	public function getUserAgentHeightScreen(){
		$user = sfContext::getInstance()->getUser();
		if ($user->hasAttribute("user_agent_screen_max_height")){
			return $user->getAttribute("user_agent_screen_max_height");
		}
		if (count($this->capabilities)==0) {
			$this->getDeviceCapabilitiesFromAgent(NULL,TRUE);
			
		}
		$height =  $this->capabilities ['display'] ['max_image_height'];
		$user->setAttribute("user_agent_screen_max_height",$height);
		return $height;	
	}
	/**
	 * should be removed on next update !
	 * use sfTeraWurflConfig::getDataDir() method 
	 * @deprecated 
	 * @see sfTeraWurflConfig::getDataDir()
	 * @return string
	 */
	public static function getAbsoluteDataDir(){
		return sfTeraWurflConfig::getDataDir();
	}
}
?>