<?php
include('../../../../wp-load.php');
$backupchoice = $_POST['backupchoice'];
include('componentfunc.php');
global $wpdb;
$db = mysqli_connect($wpdb->dbhost, $wpdb->dbuser, $wpdb->dbpassword, $wpdb->dbname);
$killbackup = "DELETE from `awp_backup` where `times` = '$backupchoice'";
$killbackupquery = mysqli_query($db, $killbackup);
$killallbackupselements = "DELETE from `awp_backupfiles` where `backuptime` = '$backupchoice'";
$killallbackupselementsquery = mysqli_query($db, $killallbackupselements);

function rrmdir($dir) {
	if (is_dir($dir)) {
		$objects = scandir($dir);
		foreach ($objects as $object) {
			if ($object != "." && $object != "..") {
				if (filetype($dir."/".$object) == "dir")
					rrmdir($dir."/".$object);
				else unlink   ($dir."/".$object);
			}
		}
		reset($objects);
		rmdir($dir);
	}
}

rrmdir('../../../../wp-backups/'. $backupchoice.'/');

header("Location: /wp-admin/admin.php?page=backup_restore&delete=success");