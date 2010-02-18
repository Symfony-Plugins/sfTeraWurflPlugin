<?php
/*
 * This file is part of the sfTeraWurflPlugin plugin.
 * (c) 2010 Skander Mabrouk <mabroukskander@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfTeraWurflConfig
 *
 * @package    sfTeraWurflPlugin
 * @author     Skander Mabrouk <mabroukskander@gmail.com>
 * @version    SVN: $Id: sfTeraWurflConfig.class.php 1 skonsoft $
 */

class sfTeraWurflConfig{
	/**
	 * Database Hostname
	 * @var String
	 * @deprecated 
	 */
	public static $DB_HOST = "localhost";
	/**
	 * Database User
	 * @var String
	 * @deprecated 
	 */
	public static $DB_USER = "terawurfl";
	/**
	 * Database Password
	 * @var String
	 * @deprecated 
	 */
	public static $DB_PASS = 'terawurfladmin';
	/**
	 * Database Name / Schema Name
	 * @var String
	 * @deprecated 
	 */
	public static $DB_SCHEMA = "terawurfl";
	/**
	 * Database Connector (MySQL4 and MySQL5 are implemented at this time)
	 * @var String
	 */
	public static $DB_CONNECTOR = "MySQL5";
	/**
	 * Device Table Name
	 * @var String
	 */
	public static $DEVICES = "TeraWurfl";
	/**
	 * Device Cache Table Name
	 * @var String
	 */
	public static $CACHE = "TeraWurflCache";
	/**
	 * Device Index Table Name
	 * @var String
	 */
	public static $INDEX = "TeraWurflIndex";
	/**
	 * Merged Device Table
	 * @var String
	 */
	public static $MERGE = "TeraWurflMerge";
	/**
	 * URL of WURFL File
	 * @var String
	 */
	public static $WURFL_DL_URL = "http://downloads.sourceforge.net/project/wurfl/WURFL/latest/wurfl-latest.zip";
	/**
	 * URL of CVS WURFL File
	 * @var String
	 */
	public static $WURFL_CVS_URL = "http://wurfl.cvs.sourceforge.net/%2Acheckout%2A/wurfl/xml/wurfl.xml";
	/**
	 * Data Directory
	 * @var String
	 */
	public static $DATADIR = '../data/';
	/**
	 * Enable Caching System
	 * @var Bool
	 */
	public static $CACHE_ENABLE = true;
	/**
	 * Enable Patches (must reload WURFL after changing)
	 * @var Bool
	 */
	public static $PATCH_ENABLE = true;
	/**
	 * Filename of patch file.  If you want to use more than one, seperate them with semicolons.  They are loaded in order.
	 * ex: $PATCH_FILE = 'web_browsers_patch.xml;custom_patch_ver2.3.xml';
	 * @var String
	 */
	public static $PATCH_FILE = 'custom_web_patch.xml;web_browsers_patch.xml';
	/**
	 * Filename of main WURFL file (found in DATADIR; default: wurfl.xml)
	 * @var String
	 */
	public static $WURFL_FILE = 'wurfl.xml';
	/**
	 * Filename of Log File (found in DATADIR; default: wurfl.log)
	 * @var String
	 */
	public static $LOG_FILE = 'wurfl.log';
	/**
	 * Log Level as defined by PHP Constants LOG_ERR, LOG_WARNING and LOG_NOTICE.
	 * Should be changed to LOG_WARNING or LOG_ERR for production sites
	 * @var Int
	 */
	public static $LOG_LEVEL = LOG_WARNING;
	/**
	 * Enable to override PHP's memory limit if you are having problems loading the WURFL data like this:
	 * Fatal error: Allowed memory size of 67108864 bytes exhausted (tried to allocate 24 bytes) in TeraWurflLoader.php on line 287
	 * @var Bool
	 */
	public static $OVERRIDE_MEMORY_LIMIT = true;
	/**
	 * PHP Memory Limit.  See OVERRIDE_MEMORY_LIMIT for more info
	 * @var String
	 */
	public static $MEMORY_LIMIT = "256M";
	
	/**
	 * return the host of database
	 * @return string
	 */
	public static function getDataBaseHost(){
		return sfConfig::get("app_sfTeraWurflPlugin_database_host","localhost");
	}
	/**
	 * return the schema name
	 * @return string
	 */
	public static function getDataBaseName(){
		return sfConfig::get("app_sfTeraWurflPlugin_database_name","terawurfl");
	}
	/**
	 * return the database username
	 * @return string
	 */
	public static function getDataBaseUserName(){
		return sfConfig::get("app_sfTeraWurflPlugin_database_username","terawurfl");
	}
	/**
	 * return the user database password
	 * @return string
	 */
	public static function getDataBasePassword(){
		return sfConfig::get("app_sfTeraWurflPlugin_database_password","terawurfladmin");
	}
	/**
	 * return the index table on Db
	 * @return string
	 */
	public static function getIndexTable(){
		return sfConfig::get("app_sfTeraWurflPlugin_database_table_index","TeraWurflIndex");
	}
	/**
	 * return the merge table on Db
	 * @return string
	 */
	public static function getMergeTable(){
		return sfConfig::get("app_sfTeraWurflPlugin_database_table_merge","TeraWurflMerge");
	}
	/**
	 * return the cache table on Db
	 * @return string
	 */
	public static function getCacheTable(){
		return sfConfig::get("app_sfTeraWurflPlugin_database_table_cache","TeraWurflCache");
	}
	
	/**
	 * return the device prefix tables name
	 * @return string
	 */
	public static function getDeviceTableNamePrefix(){
		return sfConfig::get("app_sfTeraWurflPlugin_database_table_prefix","TeraWurfl");
	}
	/**
	 * return the connector used to connect to database, current, we just implemented Mysql5
	 * @return string
	 */
	public static  function getDataBaseConnector(){
		 return sfConfig::get("app_sfTeraWurflPlugin_database_connector","MySQL5");
	}
	/**
	 * check if cahce system is enabled. 
	 * @return bool
	 */
	public static  function isCacheEnabled(){
		 return (bool)sfConfig::get("app_sfTeraWurflPlugin_database_is_cache_enabled",TRUE);
	}
	/**
	 * return the absolute data directory path (where wurfl.xml exists),
	 * @return string 
	 */
	public static function getDataDir(){
		return sfConfig::get("app_sfTeraWurflPlugin_data_dir");
	}
	/**
	 * returns the patch file
	 * @return string
	 */
	public static function getPatchFile(){
		return sfConfig::get("app_sfTeraWurflPlugin_patch_file","custom_web_patch.xml;web_browsers_patch.xml");
	}
	/**
	 * returns the data file (default: wurfl.xml)
	 * @return string
	 */
	public static function getDataFile(){
		return sfConfig::get("app_sfTeraWurflPlugin_data_file","wurfl.xml");
	}
	/**
	 * returns the download url (zipped file)
	 * @return string
	 */
	public static function getDownloadUrl(){
		 return sfConfig::get("app_sfTeraWurflPlugin_download_url");
	}
	/**
	 * returns the cvs download url
	 * @return string
	 */
	public static function getCvsDownloadUrl(){
		 return sfConfig::get("app_sfTeraWurflPlugin_cvs_download_url");
	}
	/**
	 * check if override memory limit is enabled
	 * @return bool
	 */
	public static function isOverrideMemoryLimitEnabled(){
		return sfConfig::get("app_sfTeraWurflPlugin_override_memory_limit");
	}
	/**
	 * PHP Memory Limit.  
	 * @See isOverrideMemoryLimitEnabled for more info
	 * @return string
	 */
	public static function getMemoryLimit(){
		return sfConfig::get("app_sfTeraWurflPlugin_memory_limit");
	}
}
?>