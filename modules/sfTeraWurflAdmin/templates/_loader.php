<strong>Database Update OK</strong>
<hr />
Total Time: <?php echo $loader->totalLoadTime()?>
<br/>
Parse Time: <?php echo $loader->parseTime()?>
<br/>
Validate Time: <?php echo $loader->validateTime()?>
<br/>
Sort Time: <?php echo $loader->sortTime()?>
<br/>
Patch Time: <?php echo $loader->patchTime()?>
<br/>
Database Time: <?php echo $loader->databaseTime()?>
<br/>
Cache Rebuild Time: <?php echo $loader->cacheRebuildTime()?>
<br/>
Number of Queries: <?php echo $teraWurfl->db->numQueries?>
<br/>
<?php if(version_compare(PHP_VERSION,'5.2.0') === 1){?>
	PHP Memory Usage: <?php echo WurflSupport::formatBytes(memory_get_peak_usage()) ?>
	<br/>
<?php }?>
--------------------------------
<br/>
WURFL Version: <?php echo $loader->version." (".$loader->last_updated.")"?> 
<br />
WURFL Devices: <?php echo $loader->mainDevices ?>
<br/>
PATCH New Devices: <?php echo $loader->patchAddedDevices?>
<br/>
PATCH Merged Devices: <?php echo $loader->patchMergedDevices ?> <br/>;