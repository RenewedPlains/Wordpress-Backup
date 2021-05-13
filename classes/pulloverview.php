<?php
header("Expires: Mon, 12 Jul 1995 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
include('../../../../wp-load.php');
include('componentfunc.php');

global $wpdb;

$db = mysqli_connect($wpdb->dbhost, $wpdb->dbuser, $wpdb->dbpassword, $wpdb->dbname);
$selectallbackups = "select * from `awp_backup` ORDER BY `id` DESC";
$files = mysqli_query($db, $selectallbackups);
if($_GET['dir']) {
}
else {

	if(mysqli_num_rows($files) == 0) {
		echo '<tr class="even author"><td colspan="6">Es wurden noch keine Backups angelegt!</td></tr>';
	} else {
while($fileinp = mysqli_fetch_assoc($files)) {
    $filer = $fileinp['times'];
    global $plugin_url;

include('../../../../wp-load.php');
$timefile = date_i18n( get_option( 'date_format' ) . " | " . get_option( 'time_format' ), $filer );
echo '<tr class="even author" style="vertical-align: middle;">
    <td>
        <strong>';
            if (decrypt_backupname($filer) == 'full') {
            echo '<i class="dashicons dashicons-wordpress"></i>&nbsp;&nbsp;'. __('Full-Backup', 'backup');
            }
            else if(decrypt_backupname($filer) == 'mediafiles') {
            echo '<i class="dashicons dashicons-admin-media"></i>&nbsp;&nbsp;'. __('Media-Backup', 'backup');
            }
            else if(decrypt_backupname($filer) == 'data') {
            echo '<i class="dashicons dashicons-cloud"></i>&nbsp;&nbsp;'. __('Datei-Backup', 'backup');
            }
            else if(decrypt_backupname($filer) == 'database') {
            echo '<i class="dashicons dashicons-networking"></i>&nbsp;&nbsp;'. __('Datenbank-Backup', 'backup');
            }

            echo '</strong>
    </td>
    <td class="author">
        <span><a class="strong" style="font-weight: bold;" href="?page=backup_restore&dir='. encrypt_url(ABSPATH . 'wp-backups/'. strtotime("$timefile") . substr($filer, 0, 10)) .'"><nobr>'. $timefile.'</nobr></a></span>
    </td>
    <td class="state column-primary">';
        if(decrypt_backupstate($filer) == 'finished') {
        echo '<div style='. "'border-color: #ffba00;'". ' class='. "'updated backup-sated2 finished'". '><i class="dashicons dashicons-yes"></i>&nbsp;Dieses Backup wurde abgeschlossen.</div>';
        } else if(decrypt_backupstate($filer) == 'create') {
        echo '<div style='. "'border-color: #ffba00 !important;'". ' class='. "'updated notice backup-sated2'". '><img style='."'width: 16px;margin-bottom: -3px;'".' src='. "'/wp-admin/images/spinner-2x.gif'". ' />&nbsp;&nbsp;Dieses Backup wird erstellt.</div>';
        }
        echo'

    </td>
    <td class="weight column-author"><div class="blackbadge frontbadge" data-src="' . encrypt_url(ABSPATH . 'wp-backups/'. strtotime("$timefile") . substr($filer, 0, 10)) .'"><span class="dashicons dashicons-info"></span></div></td>
    <td class="process column-author" width="40px">'.decrypt_process($filer).'</td>
    <td class="author even filename">';
        if(decrypt_backupstate($filer) == 'create') {
        $link = '';
        $link_end = '';
        $disabled = 'disabled ';
        } else {
        $link = '<a href="/wp-admin/admin.php?page=backup_settings&deletebackup='. $filer .'">';
        $link2 = '<a href="/wp-content/plugins/backup/zip-download.php?path=' .encrypt_url(ABSPATH . 'wp-backups/'. strtotime("$timefile") . substr($filer, 0, 10)). '">';
        $link3 = '<a href="/wp-content/plugins/backup/database-download.php?path=' .encrypt_url(ABSPATH . 'wp-backups/'. strtotime("$timefile") . substr($filer, 0, 10)). '">';
	    $link_end = '</a>';
        $disabled = '';
        }
        echo $link . '<i title="'.__('Delete', 'backup') .'" class="'.$disabled.'dashicons dashicons-trash"></i>' .$link_end;
        echo $link2 . '<i title="'.__('Download', 'backup') .'" class="'.$disabled.'dashicons dashicons-download"></i>' . $link_end;
        if (decrypt_backupname($filer) == 'full' OR decrypt_backupname($filer) == 'database') {
        echo $link3  . '<i title="'.__('MySQL file download', 'backup') .'" class="'.$disabled.'dashicons dashicons-networking"></i>' . $link_end;
        }
        echo '</td>
</tr>'; }}}
echo '<script>$(function() {
    	$(".frontbadge").click(function() {
    	    var selector = $(this);
    	    selector.html("<img style=\'display:inline-block; margin: auto;height: 18.5px;\' src=\'/wp-admin/images/wpspin_light-2x.gif\' />&nbsp;");
            var calchash = $(this).attr(\'data-src\');
            $.ajax({url: "/wp-content/plugins/backup/calc.php?path=" + calchash, type: "POST", success: function(result){
                    selector.html(result);
            }});
        });
});
        </script>';