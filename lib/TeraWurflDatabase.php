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
 * $Id: TeraWurflDatabase.php,v 1.10 2008/03/01 00:05:25 kamermans Exp $
 * $RCSfile: TeraWurflDatabase.php,v $
 * 
 * Based On: Java WURFL Evolution by Luca Passani
 *
 */
abstract class TeraWurflDatabase{
	
	// Properties
	
	/**
	 * Errors
	 * @var array
	 */
	public $errors;
	/**
	 * Database connector implements the RIS search function directly
	 * @var bool
	 */
	public $db_implements_ris = false;
	/**
	 * Database connector implements the LD search function directly
	 * @var unknown_type
	 */
	public $db_implements_ld = false;
	/**
	 * Number of queries to database
	 * @var int
	 */
	public $numQueries = 0;
	/**
	 * Full table name to use for the search functions
	 * @var string
	 */
	public $tablename;
	/**
	 * True if connection to database is active
	 * @var bool
	 */
	public $connected = false;
	/**
	 * Raw database connection
	 * @var database_object
	 */
	private $dbcon;
	/**
	 * Database table name extension for temporary tables
	 * @var string
	 */
	public static $DB_TEMP_EXT = "_TEMP";
	

	public function __construct(){
		$this->errors = array();
	}
	
	// Device Table Functions
	
	/**
	 * Returns the capabilities array from a given WURFL Device ID
	 * @param $wurflID WURFL ID
	 * @return array Device capabilities
	 */
	abstract public function getDeviceFromID($wurflID);
	/**
	 * Returns the WURFL ID for the Actual Device Root in the given device's fall back tree.  This can be null if it does not exist.
	 * @param $wurflID WURFL ID
	 * @return string WURFL ID
	 */
	abstract public function getActualDeviceAncestor($wurflID);
	/**
	 * Returns an associative array of all the data from the given table in the form [WURFL ID] => [User Agent] 
	 * @param $tablename
	 * @return array
	 */
	abstract public function getFullDeviceList($tablename);
	/**
	 * Returns the WURFL ID from a raw User Agent if an exact match is found
	 * @param $userAgent
	 * @return string WURFL ID
	 */
	abstract public function getDeviceFromUA($userAgent);
	/**
	 * Find the matching Device ID for a given User Agent using RIS (Reduction in String)
	 * @param string $ua User Agent
	 * @param int $tolerance How short the strings are allowed to get before a match is abandoned
	 * @param UserAgentMatcher $matcher The UserAgentMatcherInstance that is matching the User Agent
	 * @return string WURFL IDe
	 */
	public function getDeviceFromUA_RIS($userAgent,$tolerance,UserAgentMatcher &$matcher){}
	/**
	 * Find the matching Device ID for a given User Agent using LD (Leveshtein Distance)
	 * @param string $ua User Agent
	 * @param int $tolerance Tolerance that is still considered a match
	 * @param UserAgentMatcher $matcher The UserAgentMatcherInstance that is matching the User Agent
	 * @return string WURFL IDe
	 */
	public function getDeviceFromUA_LD($userAgent,$tolerance,UserAgentMatcher &$matcher){}
	/**
	 * Loads the pre-processed WURFL tables into the database
	 * @param string Device tables
	 * @return boolean success
	 */
	abstract public function loadDevices(&$tables);
	/**
	 * Creates a table capable of holding devices (WURFL ID, User Agent and Capabilities)
	 * @param $tablename Name of the table
	 * @return bool Success
	 */
	abstract public function createGenericDeviceTable($tablename);
	
	// Cache Table Functions
	
	// should return (bool)false or the device array
	/**
	 * Return capabilities array for the given User Agent, or null if not found
	 * @param $userAgent
	 * @return array Capabilities
	 */
	abstract public function getDeviceFromCache($userAgent);
	/**
	 * Save the given User Agent and Device capabilities array to the database
	 * @param $userAgent User Agent
	 * @param $device Device capabilities array
	 * @return bool Success
	 */
	abstract public function saveDeviceInCache($userAgent,$device);
	/**
	 * Creates the cache table
	 * @return bool Success
	 */
	abstract public function createCacheTable();
	/**
	 * Rebuilds the cache table by redetecting the cached devices against the loaded WURFL
	 * @return bool Success
	 */
	abstract public function rebuildCacheTable();
	
	// Supporting DB Functions
	/**
	 * Creates the index table
	 * @return unknown_type
	 */
	abstract public function createIndexTable();
	/**
	 * Truncate or drop+create the given table
	 * @param $tablename
	 * @return bool Success
	 */
	abstract public function clearTable($tablename);
	/**
	 * Establishes a database connection and stores connection in $this->dbcon
	 * @return bool Success
	 */
	abstract public function connect();
	
	// drop+create supporting functions / procedures / views /etc...
	/**
	 * Creates supporting stored procedures
	 * @return bool Success
	 */
	public function createProcedures(){}
	/**
	 * Prepares raw text for use in queries (adding quotes and escaping characters if necessary)
	 * @param $raw_text
	 * @return string SQL-Safe text
	 */
	abstract public function SQLPrep($raw_text);
	/**
	 * Returns an array of all the tables in the database
	 * @return array
	 */
	abstract public function getTableList();
	/**
	 * Returns an array of the User Agent Matcher tables in the database
	 * @return array
	 */
	abstract public function getMatcherTableList();
	/**
	 * Returns an associative array of statistics from given table
	 * @param $table
	 * @return array
	 */
	abstract public function getTableStats($table);
	/**
	 * Returns and array of the cached User Agents
	 * @return array
	 */
	abstract public function getCachedUserAgents();
	/**
	 * Creates and prepares the database
	 * @return void
	 */
	public function initializeDB(){
		$this->createDeviceTable();
		$this->createPatchTable();
		$this->createCacheTable();
		$this->createIndexTable();
		$this->createProcedures();
	}
	/**
	 * Returns the most recent error message
	 * @return string Error message
	 */
	public function getLastError(){
		return $this->errors[count($this->errors)-1];
	}
	
}
/**
 * Includes the necessary database connector as defined in sfTeraWurflConfig::$DB_CONNECTOR
 */
require_once('DatabaseConnectors/TeraWurflDatabase_'.sfTeraWurflConfig::getDataBaseConnector().'.php');
?>