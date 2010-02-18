<?php
/*
 * This file is part of the sfTeraWurflPlugin plugin.
 * (c) 2010 Skander Mabrouk <mabroukskander@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * terawurflCheckconfigTask checks the environement configuration for sfTeraWurflPlugin
 *
 * @package    sfTeraWurflPlugin
 * @author     Skander Mabrouk <mabroukskander@gmail.com>
 * @version    SVN: $Id: teraWurflCheckconfigTask.class.php 1 skonsoft $
 */
class terawurflCheckconfigTask extends sfBaseTask
{
  protected function configure()
  {
    // // add your own arguments here
    // $this->addArguments(array(
    //   new sfCommandArgument('my_arg', sfCommandArgument::REQUIRED, 'My argument'),
    // ));

    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
    ));

    $this->namespace        = 'terawurfl';
    $this->name             = 'check-config';
    $this->briefDescription = 'check environnement configuration';
    $this->detailedDescription = <<<EOF
The [terawurfl:check-config|INFO] check environnement configuration.
Call it with:

  [php symfony terawurfl:check-config|INFO]
EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {
  	$app = "frontend";
  	$env = "dev";
  	if (isset($options["application"]))
  		$app = $options["application"];
  	if (isset($options["env"]))
  		$env = $options["env"];
  	$configuration = ProjectConfiguration::getApplicationConfiguration($app, $env, true);
  	$configuration->activate();
  	
	$error = FALSE;
    $tw = new sfTeraWurfl();
    $dir = sfTeraWurflConfig::getDataDir();
    echo "\nChecking TeraWurfl environnement ...\n";
    echo "TeraWurfl Version ".$tw->release_branch." ".$tw->release_version."\n";
    echo "*******************Checking PHP Configuration***********\n";
  	if(version_compare(PHP_VERSION,sfTeraWurfl::$required_php_version) === 1){
		echo "PHP version: ".PHP_VERSION."... [OK]\n";
	}else{
		echo "PHP".sfTeraWurfl::$required_php_version." is required but you have ".PHP_VERSION."[fail]";
		$error = TRUE;
	}
	if(class_exists("ZipArchive")){
		echo "ZIP Archive... [OK]\n";
	}else{
		echo "WARNING: In order to update the WURFL File from the Internet, you must have support for the ZipArchive module.
		This module is included with PHP since 5.2.0.  You can get the ZipArchive class from the http://pecl.php.net/package/zip link.
		Note: you can still use Tera-WURFL without ZipArchive, Tera-WURFL will attempt to call the gunzip program from your system to unzip the compressed WURFL archive.
		If this fails, you must download the archive manually and extract wurfl.xml to your data/ directory.\n";
	}
	if(sfTeraWurflConfig::isOverrideMemoryLimitEnabled()){
		echo "Memory Limit ".sfTeraWurflConfig::getMemoryLimit()." (via app_sfTeraWurflPlugin_memory_limit) \n";
	}else{
		echo "Memory Limit ".ini_get("memory_limit")." (via php.ini)\n";
	}
    echo "*******************File Permissions***********\n";
    echo "Wurfl File \t";
    if(is_file($dir.sfTeraWurflConfig::getDataFile()) && is_readable($dir.sfTeraWurflConfig::getDataFile()))
		echo "[OK]\n";
	else{
		echo "WARNING: File doesn't exist or isn't readable.  You can continue like this, you just can't update the database from your local wurfl.xml. [FAIL]\n";
		$error = TRUE;
	}
	echo "Patch Files:\n";
  	$files = explode(';',sfTeraWurflConfig::getPatchFile());
	foreach($files as $thisfile){
		echo "$dir.$thisfile...";
		if(is_file($dir.$thisfile) && is_readable($dir.$thisfile))
			echo "[OK]\n";
		else{
			echo "WARNING: File doesn't exist or isn't readable.  You may ignore this error if patching is disabled. [FAIL]\n";
			$error = TRUE;
		}
	}
	echo  "DATA Directory:  $dir...";
	if(is_dir($dir) && is_readable($dir) && is_writable($dir))
		echo "[OK]\n";
	else{
		echo "[FAIL]\nERROR: Directory doesn't exist or isn't writeable.  This directory should be owned by the webserver user or chmoded to 777 for the log file and the online updates to work.Here's the best way to do it in Ubuntu: sudo chgrp -R www-data data/
sudo chmod -R g+rw data/ \n";
		$error = TRUE;
	}
	echo "********************* Database Settings************\n";
	echo "Host: ".sfTeraWurflConfig::getDataBaseHost()."\n";
	echo "Username: ".sfTeraWurflConfig::getDataBaseUserName()."\n";
	echo "Connecting to server....";
	@$dbtest = new mysqli(sfTeraWurflConfig::getDataBaseHost(),sfTeraWurflConfig::getDataBaseUserName(),sfTeraWurflConfig::getDataBasePassword(),sfTeraWurflConfig::getDataBaseName());
	if(mysqli_connect_errno()){
		echo "[FAIL]\nERROR: ".mysqli_connect_error()."\n";
		$error = TRUE;
	}else{
		echo "[OK]\n";
	}
	
	echo "DB Name (schema):". sfTeraWurflConfig::getDataBaseName()."\n";
	
    if (!$error)
    	echo "TeraWurfl environnement checking \t [OK]\n";
    else
    	echo "TeraWurfl environnement checking \t [FAIL]\n";
  }
}
