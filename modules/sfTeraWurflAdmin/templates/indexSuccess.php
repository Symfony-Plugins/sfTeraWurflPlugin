<div id="sfta_Admin">
	<?php
	if (isset($errorMsg) && !empty($errorMsg)):
	?>
		<div id="sfta_error">
			<?php echo $errorMsg?>
		</div>
	<?php else :?>
	<div id="sfta_title">
		<h1>sfTeraWurfl Admin</h1>
	</div>
	<div id="sfta_version">
		<span class="sfta_version">Version <?php echo $teraWurfl->release_branch." ".$teraWurfl->release_version; ?></span></p>
	</div>
	<?php if (isset($message) && !empty($message)):?>
		<div id="sfta_message">
			<?php echo $message?>
		</div>
	<?php endif;?>
	
	<div id="sfta_admin_tools">
		<a href="<?php echo url_for('sfTeraWurflAdmin/updateDataBase?source=local')?>"> > Update database from local file</a>
		<br/>
		<a href="<?php echo url_for('sfTeraWurflAdmin/updateDataBase?source=remote')?>"> > Update database from wurfl.sourceforge.net</a>
		<span><strong>WARNING: </strong>This will replace your existing wurfl.xml</span>
		<br />
		<a href="<?php echo url_for('sfTeraWurflAdmin/rebuildCache')?>"> > Rebuild Cache</a>
		<br />
		<a href="<?php echo url_for('sfTeraWurflAdmin/clearCache')?>"> > Clear Cache</a>
	</div>
	<?php endif;?>
	<?php 
		if (isset($loader) && !empty($loader))
			include_partial('loader',array('loader'=>$loader,'teraWurfl'=>$teraWurfl));
	?>
</div>