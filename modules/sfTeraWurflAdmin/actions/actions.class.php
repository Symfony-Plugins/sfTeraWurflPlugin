<?php

require_once dirname(__FILE__).'/../lib/BasesfTeraWurflAdminActions.class.php';

/**
 * sfTeraWurflAdmin actions.
 * 
 * @package    sfTeraWurflPlugin
 * @subpackage sfTeraWurflAdmin
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 12534 2008-11-01 13:38:27Z Kris.Wallsmith $
 */
class sfTeraWurflAdminActions extends BasesfTeraWurflAdminActions
{
	public function executeIndex( sfWebRequest $request){
		$this->teraWurfl = new sfTeraWurfl();
		try {
			$this->checkDB();
			$this->checkRequiredTables();
		}catch (Exception $e){
			$this->errorMsg = $e->getMessage();
		}
	}
	protected function checkDB(){
		if (!($this->teraWurfl->db->connected === true) )
			throw new Exception("Could not connect to DB. ");
	}
	/**
	 * Check the existence of required tables on DB
	 * @throws Exception when a required table not found
	 */
	protected function checkRequiredTables(){
		$missing_tables = "";
		$missed = FALSE;
		$required_tables = array(sfTeraWurflConfig::getCacheTable(),sfTeraWurflConfig::getIndexTable(),sfTeraWurflConfig::getMergeTable());
		$tables = $this->teraWurfl->db->getTableList();
		
		foreach($required_tables as $req_table){
			if(!in_array($req_table,$tables)){
				$missed = true;
				$missing_tables .=$tables.", ";
			}
		}
		if ($missed)
			throw new Exception("Required tables missed: ".$missing_tables);
	}
	public function executeUpdateDataBase( sfWebRequest $request){
		$this->teraWurfl = new sfTeraWurfl();
		$source = $request->getParameter('source');
		
		$this->wurflfile = sfTeraWurfl::getAbsoluteDataDir().sfTeraWurflConfig::getDataFile();
		try {
			$this->checkDB();
			$this->checkRequiredTables();
			
			if ($source =="remote"){
				$this->newfile = sfTeraWurfl::getAbsoluteDataDir().sfTeraWurflConfig::getDataFile().".zip";
				$this->updateFromRemote();
				$this->updateFromLocal();
			}else{ //by default the source is local
				$this->updateFromLocal();
			}
		}catch (Exception $e){
			$this->errorMsg = $e->getMessage();
		}
		$this->setTemplate('index');
	}
	
	public function executeRebuildCache(sfWebRequest $request){
		$this->teraWurfl = new sfTeraWurfl();
		try {
			$this->checkDB();
			$this->checkRequiredTables();
			$this->teraWurfl->db->rebuildCacheTable();
			$this->message="the cache has been successfully rebuilt ({$this->teraWurfl->db->numQueries} queries).";
		}
		catch (Exception $e){
			$this->errorMsg = $e->getMessage();
		}
		$this->setTemplate('index');
	}
	public function executeClearCache(sfWebRequest $request){
		$this->teraWurfl = new sfTeraWurfl();
		try {
			$this->checkDB();
			$this->checkRequiredTables();
			$this->teraWurfl->db->createCacheTable();
			$this->message="The cache has been successfully cleared ({$this->teraWurfl->db->numQueries} queries).";
		}
		catch (Exception $e){
			$this->errorMsg = $e->getMessage();
		}
		$this->setTemplate('index');
	}
	/**
	 * updates db from remote file
	 */
	protected function updateFromRemote(){
		$dl_url = sfTeraWurflConfig::getDownloadUrl(); 
		flush();
		$this->teraWurfl = new sfTeraWurfl();
			if(!file_exists($this->newfile) && !is_writable(sfTeraWurflConfig::getDataDir())){
				throw new Exception("Cannot write to data directory (permission denied). ".sfTeraWurflConfig::getDataDir().".");
			}
			if(file_exists($this->newfile) && !is_writable($this->newfile)){
				throw new Exception("Cannot overwrite WURFL file (permission denied). (".sfTeraWurflConfig::getDataDir().")");
			}
			// Download the new WURFL file and save it in the DATADIR as wurfl.zip
			@ini_set('user_agent', "PHP/Tera-WURFL_$version");
			$download_start = microtime(true);
			if(!$gzdata = file_get_contents($dl_url)){
				Throw New Exception("Error: Unable to download WURFL file from ".sfTeraWurflConfig::getDownloadUrl());
			}
			//saving the downloaded file
			$download_time = microtime(true) - $download_start;
			file_put_contents($this->newfile,$gzdata);
			$gzsize = WurflSupport::formatBytes(filesize($this->newfile));
			// saving the downloaded file
			/*$destination=fopen($this->newfile,"w"); 
			$source=fopen($dl_url,"r"); 
			while ($block=fread($source,256*1024)) fwrite($destination,$block);
			fclose($source);
			fclose($destination);
			*/
			
			// Try to use ZipArchive, included from 5.2.0
			if(class_exists("ZipArchive")){
				$zip = new ZipArchive();
				if ($zip->open(str_replace('\\','/',$this->newfile)) === TRUE) {
				    $zip->extractTo(str_replace('\\','/',dirname($this->wurflfile)),array('wurfl.xml'));
				    $zip->close();
				} else {
				    Throw New Exception("Error: Unable to extract wurfl.xml from downloaded archive: {$this->newfile}");
				}
			}else{
				system("gunzip {$this->newfile}");
			}
			$size = WurflSupport::formatBytes(filesize($this->wurflfile))." [$gzsize compressed]";
			$download_rate = WurflSupport::formatBitrate(filesize($this->newfile), $download_time);
			usleep(50000);
			flush();
	}
	/**
	 * updates db from local file
	 */
	protected function updateFromLocal(){
		$loader = new TeraWurflLoader($this->teraWurfl,TeraWurflLoader::$WURFL_LOCAL);
		$ok = $loader->load();
		if($ok){
			$this->loader = $loader;
		}else{
			$this->errorMsg= "ERROR LOADING DATA!
			<br/>
			Errors: <br/>
			<pre>".htmlspecialchars(var_export($loader->errors,true))."</pre>";
		}
		
	}
}
