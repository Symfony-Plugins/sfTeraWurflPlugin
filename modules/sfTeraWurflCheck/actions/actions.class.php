<?php

require_once dirname(__FILE__).'/../lib/BasesfTeraWurflCheckActions.class.php';

/**
 * sfTeraWurflTest actions.
 * 
 * @package    sfTeraWurflPlugin
 * @subpackage sfTeraWurflCheck
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 12534 2008-11-01 13:38:27Z Kris.Wallsmith $
 */
class sfTeraWurflCheckActions extends BasesfTeraWurflCheckActions
{
	public function executeIndex(sfWebRequest $request){
		
		// used to create ie procedure
		/**try {
			$wr = new TeraWurflDatabase_MySQL5();
			$wr->connect();
			$wr->createProcedures();
			
			print_r("okkkkkkkkkkkkkkkkkkkkkk");
		}
		catch (Exception $e){
			echo $e->getMessage();
		}**/
		
	}
}
