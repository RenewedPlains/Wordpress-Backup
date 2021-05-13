<?php
include('../../../../wp-load.php');
$dir    = ABSPATH .'wp-backups/' . $_GET['filer'];
if(is_dir($dir)) {
	$files = scandir($dir, SCANDIR_SORT_DESCENDING);
}
echo count($files) - 2;