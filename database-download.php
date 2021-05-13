<?php
include '../../../wp-config.php';
include('classes/componentfunc.php');
global $wpdb;
$db = mysqli_connect($wpdb->dbhost, $wpdb->dbuser, $wpdb->dbpassword, $wpdb->dbname);
$timespread = decrypt_timestamp($_GET['path']);
$selectdump = "select * from `awp_backup` WHERE `times` = '$timespread'";
$selectdumpfile = mysqli_query( $db, $selectdump);
$filepath = mysqli_fetch_array($selectdumpfile);
$filepather = '../../../wp-backups/' .$filepath['package'];
header('Content-disposition: attachment; filename='.$filepather);
header('Content-type: application/sql');
readfile($filepather);
?>