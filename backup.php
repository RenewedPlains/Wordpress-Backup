<?php

/*
Plugin Name: Backup
Plugin URI: http://webcoder.ch
Description: Create and manage backups for your site.
Version: 2.0
Author: webcoder.ch, Mario Freuler
Author URI: http://webcoder.ch
License: GPL
*/

// PHP Settings overwrite
@ini_set( 'memory_limit', apply_filters( 'admin_memory_limit', WP_MAX_MEMORY_LIMIT ) );
ini_set('max_execution_time', '3600');


// Aktivierungshook für Plugin
/* Erstellt Datenbanktabellen awp_backup und awp_backupfiles */
function backup_activate() {
	global $wpdb;
	$db = mysqli_connect($wpdb->dbhost, $wpdb->dbuser, $wpdb->dbpassword, $wpdb->dbname);
	$createtable = "create table if not exists `awp_backup` (`id` int auto_increment primary key, `backupname` varchar(255) null, `times` varchar(255) null, `user` varchar(255) null, `state` varchar(255) null, `started` varchar(255) null, `package` varchar(255) null)";
	$createtable_query = mysqli_query($db, $createtable);
	$createtable2 = "create table if not exists `awp_backupfiles` (`id` int auto_increment primary key, `filename` varchar(255) null,    `url` varchar(255) null, `backuptime` varchar(255) null)";
	$createtable2_query = mysqli_query($db, $createtable2);
	if (!is_dir(ABSPATH . 'wp-backups')) {
		mkdir(ABSPATH . 'wp-backups');
	}
}
register_activation_hook( __FILE__, 'backup_activate' );


// jQuery injection for Plugin
/* jQuery Eingabe für Plugins Optionen */
function jquery_inject() {
	if($_GET['page'] == 'backup_restore' OR $_GET['page'] == 'backup_settings' OR $_GET['page'] == 'backup') {
		wp_enqueue_script( 'jquery_inject', plugin_dir_url( __FILE__ ) . 'js/jquery.js', array( 'jquery' ), '1.0.0', false );
		wp_enqueue_script( 'backupscript', plugin_dir_url( __FILE__ ) . 'js/backup.js', array( 'jquery' ), '1.0.0', false );
	}
}
add_action( 'admin_enqueue_scripts', 'jquery_inject' );



// Creating navigation
/* Navigation für Admin Sidebar */
function backup_setup_menu(){
	add_menu_page( 'Backup', 'Backup', 'manage_options', 'backup', 'backup_init', 'dashicons-backup' );
	add_submenu_page( 'backup', 'Backup & Wiederherstellung', 'Backup & Wiederherstellung', 'manage_options',
		'backup_restore', 'backup_restore');
	add_submenu_page( 'backup', 'Einstellungen', 'Einstellungen', 'manage_options',
		'backup_settings', 'backup_settings');
}
add_action('admin_menu', 'backup_setup_menu');


// Add boxwidget on dashboard
function backup_add_dashboard_widgets() {
	wp_add_dashboard_widget(
		'backup_dashboard_widget',         // Widget slug.
		__('Next backups', 'backup'),         // Title.
		'backup_dashboard_widget_function' // Display function.
	);
}
add_action( 'wp_dashboard_setup', 'backup_add_dashboard_widgets' );

/**
 * Create the function to output the contents of our Dashboard Widget.
 */
function backup_dashboard_widget_function() {

	// Display whatever it is you want to show.
	echo '<div class="nextbackups" style="text-align: center; width: ;"><div class="dashicons-backup dashicons" style="width: 100%;font-size: 18rem;color:rgba(0,0,0,0.2);display:block;height: 18rem;"></div>';
	$cron_jobs = get_option( 'cron' );
	var_dump($cron_jobs);
	echo '<br /></div>';


}/* Funktionierender stündlicher Hook für WP Cron (mit my_task_function())
if ( ! wp_next_scheduled( 'my_task_hook' ) ) {
	wp_schedule_event( time(), 'hourly', 'my_task_hook' );
}

add_action( 'my_task_hook', 'my_task_function' );*/

function my_task_function() {
	include '../../../../wp-config.php';
	global $wpdb;
	if (!is_dir(ABSPATH . 'wp-backups')) {
		mkdir(ABSPATH . '/wp-backups');
	}
	$db = mysqli_connect($wpdb->dbhost, $wpdb->dbuser, $wpdb->dbpassword, $wpdb->dbname);
	$pathe = ABSPATH;
	$timestamp = current_time('timestamp');
	if (!$db) {
		_e('Connection failed. Please check your permissions', 'backup');
	} else {
		function recurse_copy($src, $dst, $timestamp) {
			$dir = opendir($src);
			while (false !== ($file = readdir($dir))) {
				if (($file != '.') && ($file != '..') && ($file != '.idea') && ($file != 'wp-backups')) {
					if (is_dir($src . $file . '/')) {
						if (strpos($src . $file, 'wp-backups/') !== false) {
							continue;
						} else {
							global $db;
							$str = str_replace('\\', '/', $dst);
							$bodytag = str_replace("//", "/", $str . $file);

							$filepath = $bodytag;
							mkdir($dst . $file);
							$filepathhash = substr(md5($filepath), 0, 8);
							$insertfiledata = "insert into `awp_backupfiles` (`filename`, `url`, `backuptime`) values ('$filepath', '$filepathhash', '$timestamp')";
							$insertfiledata_query = mysqli_query($db, $insertfiledata);
							recurse_copy($src . '/' . $file . '/', $dst . '/' . $file . '/', $timestamp);
						}
					} else {
						global $db;
						$str = str_replace('\\', '/', $dst);
						$bodytag = str_replace("//", "/", $str . $file);
						$filepath = $bodytag;
						$filepathhash = substr(md5($filepath), 0, 8);
						$insertfiledata = "insert into `awp_backupfiles` (`filename`, `url`, `backuptime`) values ('$filepath', '$filepathhash', '$timestamp')";
						$insertfiledata_query = mysqli_query($db, $insertfiledata);

						if (strpos($src . $file, 'wp-backups/') !== false) {
							continue;
						} else {
							copy($src . '/' . $file, $dst . '/' . $file);
						}
					}
				}
			}
			closedir($dir);
		}

		function do_this_in_an_hour() {
			global $wpdb;
			global $db;
			$db = mysqli_connect($wpdb->dbhost, $wpdb->dbuser, $wpdb->dbpassword, $wpdb->dbname);

			$pathe = ABSPATH;
			$timestamp = current_time('timestamp');
			if (!is_dir($pathe . 'wp-backups' . '/' . $timestamp . '/')) {
				mkdir($pathe . 'wp-backups' . '/' . $timestamp . '/');
			}
			$str1 = str_replace('\\', '/', ABSPATH . 'wp-backups/' . $timestamp);
			$str = str_replace('/', '/', $str1);
			$filepath = $str;
			$filepathhash = substr(md5($filepath), 0, 8);
			$insertfiledata = "insert into `awp_backupfiles` (`filename`, `url`, `backuptime`) values ('$filepath', '$filepathhash', '$timestamp')";
			$insertfiledata_query = mysqli_query($db, $insertfiledata);
			recurse_copy($pathe, $pathe . "wp-backups" . '/' . $timestamp . '/', $timestamp);


		}


		$inc = $_POST['inc'];
		$timestamp = current_time('timestamp');

		$selectonce = "select * from `awp_backup` where `state` = 'create'";
		$selectonce_query = mysqli_query($db, $selectonce);

		if (mysqli_num_rows($selectonce_query) == 0) {
			if ($_GET['doing_wp_cron']) {
				$process = 'wp-cron';
			} else {
				$process = 'wp-user';
			}
			$userid = 0;
			$insertbackup = "insert into `awp_backup` (`backupname`, `times`, `user`, `state`, `started`) values ('full', '$timestamp', '$userid', 'create', '$process')";
			$insertbackup_query = mysqli_query($db, $insertbackup);
		} else {
			echo 'once-error';
			exit();
		}
		add_action($inc . 'backup_now', do_this_in_an_hour());
		wp_schedule_single_event(time(), $inc . 'backup_now');

		$stashbackup = "update `awp_backup` set `state` = 'finished' where `times` = '$timestamp'";
		$stashbackup_query = mysqli_query($db, $stashbackup);

	}

	wp_mail( 'mario.freuler@snk.ch', 'Automatisches Backup abgeschlossen', 'Das Backup für die Seite test.ch wurde erfolgreich abgeschlossen. Juhui.');
}

$plugin_url = plugin_dir_url( __FILE__ );
/**
 * Enqueue plugin style-file
 */

function backup_settings() {
	include '../wp-content/plugins/backup/classes/components.php';


	function getSymbolByQuantity($bytes) {
		$symbols = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB');
		$exp = floor(log($bytes)/log(1024));

		return sprintf('%.2f '.$symbols[$exp], ($bytes/pow(1024, floor($exp))));
	}
	$hdGnu = disk_free_space("/home/httpd/vhosts/dynamicdevices.ch/");
	echo "Diskspace left on / ".getSymbolByQuantity($hdGnu);





	if($_GET['deletebackup']) {
		global $plugin_url;
		$timefile = date_i18n( get_option( 'date_format' ) . " | " . get_option( 'time_format' ), $_GET['deletebackup'] );
		// $backupsize = human_filesize(get_total_size(ABSPATH . 'wp-backups/' . substr($_GET['deletebackup'], 0, 10) ));
		echo 'Sind Sie sicher, dass Sie das Backup vom <strong>'. $timefile . '</strong> löschen möchten?<br />';
		//Das Backup beträgt '. $backupsize .'.';
		echo '<form method="post" action="'.$plugin_url. 'classes/deletebackup.php"><input type="hidden" name="backupchoice" value="'. $_GET['deletebackup']. '"/><p class="submit"><input name="submit" id="submit" class="button button-primary" value="Backup löschen" type="submit"></p></form>';
	}
	add_action( 'admin_enqueue_scripts', 'my_admin_scripts' );
}

function backup_init() {
	echo '
    <div class="wrap about-wrap full-width-layout">
		<h1>Willkommen bei Wordpress Backup</h1>

		<p class="about-text">Danke für die Aktualisierung auf die neueste Version! WordPress 4.9.4 wird Ihren Arbeitsablauf verbessern und Sie vor Programmierfehlern schützen.</p>
		<div class="wp-badge">Version 4.9.4</div>

		<h2 class="nav-tab-wrapper wp-clearfix">
			<a href="/wp-admin/admin.php?page=backup" class="nav-tab nav-tab-active">Was gibt\'s Neues</a>
			<a href="/wp-admin/admin.php?page=backup_restore" class="nav-tab">Backup & Wiederherstellung</a>
			<a href="/wp-admin/admin.php?page=backup_settings" class="nav-tab">Einstellungen</a>
		</h2>

		<div class="changelog point-releases">
			<h3>Wartungs- und Sicherheits-Updates</h3>
			<p>
				<strong>Version 4.9.4</strong> behob 1 Fehler.				Weitere Informationen finden Sie in den <a href="https://codex.wordpress.org/Version_4.9.4">Veröffentlichtungsmitteilungen</a>.</p>
			<p>
				<strong>Version 4.9.3</strong> behob 34 Fehler.				Weitere Informationen finden Sie in den <a href="https://codex.wordpress.org/Version_4.9.3">Veröffentlichtungsmitteilungen</a>.</p>
			<p>
				<strong>Version 4.9.2</strong> behob Sicherheitsprobleme und 22 Fehler.				Weitere Informationen finden Sie in den <a href="https://codex.wordpress.org/Version_4.9.2">Veröffentlichtungsmitteilungen</a>.			</p>
			<p>
				<strong>Version 4.9.1</strong> behob Sicherheitsprobleme und 11 Fehler.				Weitere Informationen finden Sie in den <a href="https://codex.wordpress.org/Version_4.9.1">Veröffentlichtungsmitteilungen</a>.			</p>
		</div>';
	/*
			<div class="feature-section one-col">
				<div class="col">
					<h2>
						Wesentliche Verbesserungen des Customizers, Code-Fehlerprüfung und mehr! 🎉				</h2>
					<p>Willkommen zu einem verbesserten Workflow im Customizer mit Design-Entwürfen und einer Design-Sperre, Planen (Terminierung) und Vorschau-Links. Darüber hinaus Code-Syntaxhervorhebung und Fehlerprüfung, was das Erstellen Ihrer Website zu einem leichtgängigeren Erlebnis macht. Ausserdem haben wir, wenn das noch nicht gut genug ist, ein grossartiges, neues Galerie-Widget und es gibt Verbesserungen für das Stöbern durch Themes und deren Wechsel.</p>
				</div>
			</div>

			<div class="inline-svg full-width">
				<picture>
					<source media="(max-width: 500px)" srcset="https://s.w.org/images/core/4.9/banner-mobile.svg">
					<img src="https://s.w.org/images/core/4.9/banner.svg" alt="">
				</picture>
			</div>*/
	echo '
	</div>';
}

function backup_restore() {
	include '../wp-content/plugins/backup/classes/components.php';
	if($_GET['dir']) {
		echo '  <div class="whiteboard">
                    <div class="backall leftfloat">
                        <div class="arrowleft" title="' . __('Go back', 'backup') .'"><a style="text-decoration:none;" href="#" onclick="window.history.go(-1); return false;"><i class="iconnav dashicons dashicons-arrow-left-alt"></i></a></div>
                        <div class="arrowleft" title="' . __('Go to /', 'backup') .'"><a style="text-decoration:none;" href="admin.php?page=backup_restore&dir=' . encrypt_rwt(decrypt_timestamp($_GET['dir'])) .'"><i class="iconnav dashicons dashicons-admin-home"></i></a></div>
                        <div class="textleft"><a style="text-decoration:none;color:black;" href="admin.php?page=backup_restore">' . __('Back to overview', 'backup') .'</a></div>
                        <div class="clear"></div>
                    </div>';
		$breadcrumb = explode('/', strstr(decrypt_url($_GET['dir']), 'wp-backups/'), 2);
		$breadcrumber = explode('/', $breadcrumb['1']);
		echo '<div class="breadcrumbs">';
		$bread = array();
		echo '<div class="breadcrumb leftfloat">';
		_e('You are here: <br />', 'backup');
		foreach ($breadcrumber as $bready) {
			array_push($bread, $bready);
			$breadlink = implode('/', $bread);
			if ($bread[0] == $bready) {
				$breadup = date_i18n(get_option('date_format') . " | " . get_option('time_format'), $bread[0]);
				$hashbreadlink = encrypt_url(ABSPATH . 'wp-backups/' . $breadlink);
				echo '<a href="admin.php?page=backup_restore&dir=' . $hashbreadlink . '"><strong>' . $breadup . ' [' . $bready . ']</strong> </a>/';
			} else {
				$hashbreadlink = encrypt_url(ABSPATH . 'wp-backups/' . $breadlink);
				echo '<a href="admin.php?page=backup_restore&dir=' . $hashbreadlink . '">' . $bready . '</a>/';
			}
		}
		echo '
</div>                  <div class="rightrestore" title="' . __('Restore this folder', 'backup') .'"><div class="iconnav dashicons dashicons-image-rotate"></div></div>
                        <div class="rightdownload" title="' . __('Download this folder', 'backup') .'">
                        <div class="iconnav dashicons dashicons-download"></div>
</div><div class="rightdownload" title="' . __('Search in backup', 'backup') .'">
                        <div class="iconnav dashicons dashicons-search"></div>
</div>

                    <div class="clear"></div>
                </div></div><br />';


		$dir    = decrypt_url($_GET['dir']);

		$db         = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
		$selectallbackups = "select * from `awp_backupfiles` where `filename` LIKE '$dir/%' AND `filename` NOT LIKE '$dir/%/%'";
		$files = mysqli_query($db, $selectallbackups);
		//$files = scandir($dir, SCANDIR_SORT_ASCENDING );

	} else {
		$db         = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
		$dir    = ABSPATH .'wp-backups/';
		$selectallbackups = "select * from `awp_backup` ORDER BY `id` DESC";
		$files = mysqli_query($db, $selectallbackups);
		echo '
        <script>setTimeout(function() {
            checkbase();
            }, 250);
        </script>';
	}
	global $plugin_url;

	echo '<script>$(function() {
                $.ajax({
                  url: "'.$plugin_url.'classes/checkfiles.php?filer='.$dir.'"
                }).done(function( result ) {
                  reallengther = result;
                });
                  if(lengther < reallengther) {
                         checkbase(reallengther);
                     }
            });
            function checkbase(size = 1) {
                $( "#the-comment-list" ).html("");
                function repeat(funct, times) {
                    for(var i = 0; i < times; i++) funct();
                }
                function pullr() { 
                    $.ajax({
                      url: "' . $plugin_url . 'classes/pulloverview.php"
                    }).done(function( result ) {
                      $( "#the-comment-list" ).append( result );
                    });
                }
                if(size) { repeat(pullr, size); } 
            }
            setInterval(function() {
                lengther = $("#the-comment-list tr").length;
                $.ajax({
                  url: "'.$plugin_url.'classes/checkfiles.php"
                }).done(function( result ) {
                  reallengther = result;
                });
                return lengther;
            }, 5000);
                </script>
                <table class="wp-list-table widefat fixed striped">
			<thead>
			<tr>';
	if($_GET['dir']) { echo '<th scope="row" class="check-column">
		<input class="markall" name="delete_comments[]" value="1" type="checkbox">
		</th><th scope="col" id="author" class="manage-column column-author"><span>Type / Size</span></th><th scope="col" id="comment" class="manage-column column-comment column-primary">Filename</th><th scope="col" id="date" class="column-date"><span>Options</span></th></tr>'; } else { echo '<th scope="col" id="author" class="manage-column column-author" style="width: 220px;"><span>'. __('Backup options', 'backup') .'</span></th><th scope="col" id="comment" class="column-author">'. __('Creation date', 'backup') .'</th><th scope="col" id="comment" class="manage-column column-primary">'. __('State', 'backup') .'</th><th scope="col" id="comment" class="column-author">'. __('Weight', 'backup') .'</th><th scope="col" id="comment" class="column-author">'. __('Process', 'backup') .'</th><th scope="col" id="date" class="manage-column column-date"><span>'. __('Options', 'backup') .'</span></th></tr>'; }
	echo '</thead>
			<tbody id="the-comment-list">';

	while($fileinp = mysqli_fetch_assoc($files)) {
		$filer = substr(strrchr($fileinp['filename'], "/"), 1);
		global $plugin_url;
		if($filer == '.' || $filer == '.git' || $filer == '.gitignore' || $filer == '..' || $filer == '.DS_Store') {
			continue;
		} else {
		}
		if($_GET['dir']) {

			echo '
		    <tr id="comment-1" class="comment even thread-even depth-1 approved">
			<td class="check-column">		
                <input class="backupdelete" name="delete_comments[]" value="1" type="checkbox">
            </td>
			<td class="author column-author">';
			echo '<svg xmlns="http://www.w3.org/2000/svg" style="display: none;"><symbol id="folder" viewBox="0 0 57.912 57.912"><title>folder</title><path style="fill:#F7B563;" d="M52.723,55.956H5.189c-0.134,0-0.248-0.098-0.268-0.23L0.003,23.268 c-0.025-0.164,0.102-0.312,0.268-0.312h57.37c0.166,0,0.293,0.148,0.268,0.312l-4.918,32.458 C52.971,55.858,52.857,55.956,52.723,55.956z"/><g> <path style="fill:#B5885B;" d="M54,22.956V7.724c0-0.424-0.344-0.768-0.768-0.768H22.435c-0.27,0-0.52-0.141-0.658-0.373 l-2.553-4.255c-0.139-0.231-0.389-0.373-0.658-0.373H4.768C4.344,1.956,4,2.3,4,2.724v20.232H54z"/> </g><rect x="7" y="18.956" style="fill:#FFFFFF;" width="44" height="4"/><rect x="8" y="15.956" style="fill:#E7ECED;" width="42" height="3"/><rect x="9" y="12.956" style="fill:#C7CAC7;" width="40" height="3"/></symbol><symbol id="svg" viewBox="0 0 384 384"><title>svg</title><linearGradient id="SVGID_1_" gradientUnits="userSpaceOnUse" x1="-89.7993" y1="548.9443" x2="-36.9513" y2="496.0983" gradientTransform="matrix(8 0 0 -8 827.7248 4468.8887)"> <stop offset="0" style="stop-color:#EFEEEE"/> <stop offset="1" style="stop-color:#DEDEDD"/> </linearGradient><polygon style="fill:url(#SVGID_1_);" points="64,0 64,384 288,384 384,288 384,0 "/><polygon style="fill:#ABABAB;" points="288,288 288,384 384,288 "/><polygon style="fill:#DEDEDD;" points="192,384 288,384 288,288 "/><path style="fill:#CB5641;" d="M0,96v112h256V96L0,96L0,96z"/><g> <path style="fill:#FFFFFF;" d="M47.904,167.008c0,1.6,0.16,3.056,0.416,4.352c0.256,1.312,0.736,2.416,1.44,3.312 c0.704,0.912,1.648,1.616,2.832,2.128c1.184,0.496,2.672,0.768,4.464,0.768c2.112,0,4.016-0.688,5.696-2.064 c1.696-1.376,2.544-3.52,2.544-6.384c0-1.536-0.208-2.864-0.624-3.984c-0.4-1.12-1.088-2.128-2.064-3.008 c-0.96-0.912-2.224-1.712-3.792-2.448c-1.568-0.736-3.504-1.488-5.792-2.256c-3.072-1.024-5.728-2.16-7.968-3.376 c-2.256-1.2-4.112-2.624-5.632-4.272c-1.504-1.632-2.608-3.52-3.312-5.664c-0.704-2.16-1.04-4.624-1.04-7.456 c0-6.784,1.888-11.824,5.664-15.152s8.976-4.992,15.568-4.992c3.056,0,5.904,0.336,8.48,1.008c2.592,0.672,4.848,1.744,6.736,3.264 c1.872,1.504,3.36,3.424,4.4,5.744c1.056,2.336,1.6,5.136,1.6,8.4v1.92H64.272c0-3.264-0.576-5.776-1.728-7.552 c-1.152-1.744-3.072-2.64-5.76-2.64c-1.536,0-2.816,0.24-3.824,0.672c-1.024,0.448-1.84,1.04-2.448,1.776 c-0.608,0.736-1.056,1.616-1.28,2.576c-0.224,0.96-0.336,1.952-0.336,2.976c0,2.128,0.432,3.888,1.344,5.328 c0.896,1.456,2.816,2.784,5.744,3.984l10.656,4.608c2.624,1.152,4.768,2.352,6.432,3.616c1.664,1.248,2.992,2.592,3.984,4.032 s1.664,3.008,2.064,4.752c0.384,1.712,0.576,3.648,0.576,5.744c0,7.232-2.096,12.496-6.304,15.792 c-4.192,3.296-10.032,4.96-17.52,4.96c-7.808,0-13.392-1.696-16.752-5.088s-5.04-8.256-5.04-14.592v-2.784h13.824L47.904,167.008 L47.904,167.008z"/> <path style="fill:#FFFFFF;" d="M116.192,168.544h0.288l10.176-50.688h14.32l-15.76,68.544h-17.76l-15.728-68.544h14.784 L116.192,168.544z"/> <path style="fill:#FFFFFF;" d="M185.648,134.288c-0.288-1.344-0.752-2.576-1.392-3.696c-0.64-1.104-1.456-2.048-2.432-2.784 c-0.992-0.736-2.208-1.104-3.616-1.104c-3.328,0-5.712,1.856-7.2,5.584c-1.472,3.696-2.208,9.856-2.208,18.416 c0,4.112,0.128,7.808,0.384,11.136s0.72,6.16,1.392,8.496s1.632,4.128,2.88,5.376c1.248,1.248,2.864,1.872,4.848,1.872 c0.848,0,1.744-0.24,2.752-0.672c0.992-0.448,1.904-1.12,2.784-2.016c0.864-0.912,1.584-2.032,2.16-3.408s0.864-3.008,0.864-4.864 v-7.008h-9.104V149.44h22.352v36.96H189.92v-6.336h-0.192c-1.664,2.704-3.664,4.592-6,5.712c-2.336,1.12-5.136,1.68-8.4,1.68 c-4.224,0-7.664-0.752-10.336-2.224c-2.656-1.472-4.736-3.728-6.24-6.816c-1.504-3.088-2.512-6.864-3.008-11.376 c-0.512-4.512-0.784-9.744-0.784-15.696c0-5.744,0.368-10.816,1.104-15.152c0.736-4.352,2-7.984,3.792-10.912 c1.776-2.912,4.16-5.088,7.088-6.576c2.96-1.472,6.624-2.208,11.04-2.208c7.552,0,12.992,1.872,16.32,5.632 c3.328,3.728,4.992,9.088,4.992,16.08H186.08C186.08,136.944,185.936,135.632,185.648,134.288z"/> </g></symbol><symbol id="php" viewBox="0 0 384 384"><title>php</title><polygon style="fill:#EFEEEE;" points="64,0 64,384 288,384 384,288 384,0 "/><polygon style="fill:#ABABAB;" points="288,288 288,384 384,288 "/><polygon style="fill:#DEDEDD;" points="192,384 288,384 288,288 "/><path style="fill:#8992BF;" d="M0,96v112h256V96H0z"/><g> <path style="fill:#FFFFFF;" d="M61.008,117.856c3.52,0,6.464,0.592,8.88,1.776s4.336,2.736,5.808,4.656s2.528,4.112,3.168,6.576 c0.624,2.448,0.96,4.976,0.96,7.536c0,3.52-0.528,6.592-1.584,9.216c-1.056,2.624-2.528,4.768-4.416,6.416 c-1.888,1.664-4.176,2.928-6.864,3.744c-2.688,0.816-5.68,1.264-8.944,1.264h-7.392v27.36H36.8v-68.544L61.008,117.856z M56.768,148.896c2.752,0,4.992-0.848,6.672-2.512c1.696-1.664,2.544-4.256,2.544-7.776c0-3.456-0.736-6.08-2.208-7.872 c-1.472-1.776-3.936-2.688-7.392-2.688h-5.76v20.848L56.768,148.896L56.768,148.896z"/> <path style="fill:#FFFFFF;" d="M110.048,117.856v26.208h16.128v-26.208H140V186.4h-13.824v-30.432h-16.128V186.4H96.224v-68.544 L110.048,117.856z"/> <path style="fill:#FFFFFF;" d="M183.392,117.84c3.52,0,6.48,0.608,8.896,1.792c2.384,1.184,4.336,2.736,5.792,4.656 s2.528,4.112,3.168,6.576c0.64,2.448,0.96,4.976,0.96,7.536c0,3.52-0.512,6.592-1.584,9.216c-1.04,2.624-2.528,4.768-4.416,6.416 c-1.872,1.664-4.176,2.928-6.864,3.744c-2.688,0.816-5.664,1.248-8.928,1.248h-7.392v27.36H159.2V117.84L183.392,117.84 L183.392,117.84z M179.168,148.896c2.752,0,4.976-0.848,6.672-2.512c1.68-1.664,2.544-4.256,2.544-7.776 c0-3.456-0.736-6.08-2.224-7.872c-1.472-1.776-3.92-2.688-7.392-2.688h-5.744v20.848L179.168,148.896L179.168,148.896z"/> </g></symbol><symbol id="rar" viewBox="0 0 384 384"><title>rar</title><linearGradient id="SVGID_1_" gradientUnits="userSpaceOnUse" x1="-91.8312" y1="547.1898" x2="-38.9842" y2="494.3478" gradientTransform="matrix(8 0 0 -8 843.976 4454.856)"> <stop offset="0" style="stop-color:#EFEEEE"/> <stop offset="1" style="stop-color:#DEDEDD"/> </linearGradient><polygon style="fill:url(#SVGID_1_);" points="64,0 64,384 288,384 384,288 384,0 "/><polygon style="fill:#ABABAB;" points="288,288 288,384 384,288 "/><polygon style="fill:#DEDEDD;" points="192,384 288,384 288,288 "/><path style="fill:#682767;" d="M0,96v112h256V96L0,96L0,96z"/><g> <path style="fill:#FFFFFF;" d="M62.336,117.856c5.744,0,10.24,1.472,13.44,4.384s4.8,7.344,4.8,13.296 c0,4.608-0.928,8.352-2.784,11.28c-1.856,2.912-4.736,4.784-8.64,5.616v0.192c3.456,0.512,5.984,1.648,7.584,3.36 c1.616,1.728,2.592,4.512,2.992,8.352c0.112,1.28,0.208,2.672,0.272,4.176c0.064,1.504,0.128,3.184,0.208,5.04 c0.128,3.648,0.32,6.416,0.56,8.336c0.384,1.92,1.216,3.232,2.496,3.936v0.576h-14.96c-0.704-0.96-1.152-2.064-1.344-3.312 c-0.208-1.248-0.336-2.544-0.384-3.888l-0.368-13.152c-0.144-2.688-0.816-4.8-2.032-6.336c-1.2-1.536-3.248-2.304-6.144-2.304 h-7.408V186.4H36.8v-68.544L62.336,117.856L62.336,117.856z M56.384,147.824c3.328,0,5.888-0.784,7.664-2.336 c1.792-1.584,2.688-4.224,2.688-7.92c0-6.336-3.184-9.52-9.6-9.52h-6.512v19.776H56.384z"/> <path style="fill:#FFFFFF;" d="M126.368,117.872l18.144,68.544h-14.4l-3.168-14.496h-18.24l-3.168,14.496h-14.4l18.144-68.544 H126.368z M124.544,160.592l-6.624-30.816h-0.192l-6.624,30.816H124.544z"/> <path style="fill:#FFFFFF;" d="M180.608,117.856c5.76,0,10.256,1.472,13.44,4.384c3.2,2.912,4.816,7.344,4.816,13.296 c0,4.608-0.944,8.352-2.8,11.28c-1.84,2.912-4.736,4.784-8.64,5.616v0.192c3.472,0.512,5.984,1.648,7.6,3.36 c1.6,1.728,2.592,4.512,2.976,8.352c0.128,1.28,0.24,2.672,0.304,4.176s0.128,3.184,0.192,5.04c0.128,3.648,0.32,6.416,0.576,8.336 c0.368,1.92,1.216,3.232,2.496,3.936v0.576H186.56c-0.704-0.96-1.152-2.064-1.344-3.312c-0.192-1.248-0.32-2.544-0.368-3.888 l-0.384-13.152c-0.128-2.688-0.8-4.8-2.016-6.336c-1.216-1.536-3.264-2.304-6.144-2.304h-7.392V186.4h-13.824v-68.544 L180.608,117.856L180.608,117.856z M174.656,147.824c3.328,0,5.888-0.784,7.68-2.336c1.776-1.584,2.688-4.224,2.688-7.92 c0-6.336-3.2-9.52-9.616-9.52h-6.512v19.776H174.656z"/> </g></symbol><symbol id="sql" viewBox="0 0 384 384"><title>sql</title><polygon style="fill:#EFEEEE;" points="64,0 64,384 288,384 384,288 384,0 "/><polygon style="fill:#ABABAB;" points="288,288 288,384 384,288 "/><polygon style="fill:#DEDEDD;" points="192,384 288,384 288,288 "/><path style="fill:#527DA1;" d="M0,96v112h256V96H0z"/><g> <path style="fill:#FFFFFF;" d="M54.624,167.024c0,1.6,0.112,3.056,0.368,4.352c0.256,1.312,0.72,2.416,1.44,3.312 c0.688,0.912,1.648,1.616,2.832,2.128c1.168,0.496,2.672,0.768,4.448,0.768c2.128,0,4.032-0.688,5.712-2.064 c1.68-1.376,2.544-3.52,2.544-6.384c0-1.536-0.208-2.864-0.624-3.984s-1.104-2.128-2.064-3.008c-0.96-0.912-2.24-1.712-3.792-2.448 c-1.552-0.736-3.504-1.488-5.808-2.256c-3.056-1.024-5.712-2.16-7.968-3.376c-2.24-1.2-4.096-2.624-5.616-4.272 c-1.504-1.632-2.608-3.52-3.312-5.664c-0.704-2.16-1.04-4.624-1.04-7.456c0-6.784,1.872-11.824,5.664-15.152 s8.976-4.992,15.568-4.992c3.056,0,5.904,0.336,8.48,1.008c2.592,0.672,4.832,1.744,6.736,3.264c1.872,1.504,3.344,3.424,4.4,5.744 c1.056,2.336,1.6,5.136,1.6,8.4v1.92H70.944c0-3.264-0.592-5.776-1.728-7.552c-1.152-1.744-3.072-2.64-5.76-2.64 c-1.536,0-2.816,0.24-3.84,0.672c-1.008,0.448-1.84,1.04-2.432,1.776c-0.624,0.736-1.024,1.584-1.248,2.544 c-0.224,0.96-0.336,1.952-0.336,2.976c0,2.128,0.448,3.888,1.344,5.328c0.912,1.456,2.816,2.784,5.76,3.984l10.656,4.608 c2.624,1.152,4.768,2.352,6.416,3.616c1.664,1.248,3.008,2.592,4,4.032s1.664,3.008,2.064,4.752c0.368,1.712,0.56,3.648,0.56,5.744 c0,7.232-2.096,12.496-6.288,15.792s-10.032,4.96-17.52,4.96c-7.808,0-13.392-1.696-16.768-5.088 c-3.344-3.392-5.024-8.256-5.024-14.592v-2.784h13.824L54.624,167.024L54.624,167.024z"/> <path style="fill:#FFFFFF;" d="M130.064,186.416c-2.56,0.704-5.6,1.056-9.12,1.056c-5.168,0-9.312-0.848-12.368-2.496 c-3.056-1.664-5.424-4.064-7.056-7.2c-1.632-3.136-2.688-6.88-3.152-11.232c-0.48-4.336-0.72-9.152-0.72-14.384 c0-5.184,0.24-9.968,0.72-14.352c0.464-4.368,1.536-8.144,3.152-11.28c1.648-3.12,4-5.584,7.056-7.344 c3.056-1.744,7.2-2.64,12.368-2.64c5.184,0,9.312,0.896,12.384,2.64c3.072,1.76,5.424,4.224,7.04,7.344 c1.648,3.136,2.688,6.912,3.184,11.28c0.464,4.384,0.72,9.168,0.72,14.352c0,5.808-0.336,11.088-0.976,15.824 c-0.64,4.752-2.08,8.736-4.32,12l7.376,7.2l-8.16,7.392L130.064,186.416z M111.776,163.904c0.224,3.232,0.672,5.856,1.344,7.872 s1.632,3.488,2.88,4.416c1.248,0.928,2.912,1.392,4.944,1.392c2.064,0,3.696-0.464,4.96-1.392c1.248-0.944,2.208-2.4,2.896-4.416 c0.656-2.016,1.104-4.64,1.344-7.872c0.224-3.232,0.336-7.136,0.336-11.744s-0.112-8.512-0.336-11.712 c-0.24-3.2-0.688-5.824-1.344-7.872c-0.688-2.048-1.648-3.536-2.896-4.448c-1.248-0.944-2.896-1.408-4.96-1.408 c-2.032,0-3.68,0.464-4.944,1.408c-1.248,0.928-2.208,2.4-2.88,4.448s-1.12,4.656-1.344,7.856c-0.224,3.2-0.336,7.104-0.336,11.712 S111.568,160.672,111.776,163.904z"/> <path style="fill:#FFFFFF;" d="M158.176,117.872h13.84v57.216h24.48v11.328h-38.32V117.872z"/> </g></symbol><symbol id="jpg" viewBox="0 0 384 384"><title>jpg</title><linearGradient id="SVGID_1_" gradientUnits="userSpaceOnUse" x1="-88.4651" y1="545.5741" x2="-35.6171" y2="492.7321" gradientTransform="matrix(8 0 0 -8 817.04 4441.9248)"> <stop offset="0" style="stop-color:#EFEEEE"/> <stop offset="1" style="stop-color:#DEDEDD"/> </linearGradient><polygon style="fill:url(#SVGID_1_);" points="64,0 64,384 288,384 384,288 384,0 "/><polygon style="fill:#ABABAB;" points="288,288 288,384 384,288 "/><polygon style="fill:#DEDEDD;" points="192,384 288,384 288,288 "/><path style="fill:#71A742;" d="M0,96v112h256V96L0,96L0,96z"/><g> <path style="fill:#FFFFFF;" d="M72.24,167.2c0,7.296-1.696,12.496-5.088,15.616c-3.392,3.088-8.576,4.656-15.552,4.656 c-3.664,0-6.64-0.496-8.992-1.504c-2.336-0.992-4.192-2.384-5.568-4.224s-2.32-3.952-2.816-6.384 c-0.528-2.416-0.784-4.992-0.784-7.68v-2.992h12.672v2.128c0,3.648,0.4,6.336,1.2,8.112c0.8,1.744,2.448,2.64,4.96,2.64 c2.48,0,4.144-0.896,4.944-2.64c0.8-1.76,1.2-4.464,1.2-8.112V117.84H72.24V167.2z"/> <path style="fill:#FFFFFF;" d="M115.04,117.84c3.52,0,6.464,0.608,8.88,1.792s4.336,2.736,5.808,4.656s2.528,4.112,3.168,6.576 c0.624,2.448,0.96,4.976,0.96,7.536c0,3.52-0.528,6.592-1.584,9.216c-1.056,2.624-2.528,4.768-4.416,6.416 c-1.888,1.664-4.176,2.928-6.864,3.744c-2.688,0.832-5.664,1.248-8.944,1.248h-7.376v27.36H90.848V117.84L115.04,117.84 L115.04,117.84z M110.816,148.896c2.752,0,4.992-0.848,6.672-2.512c1.696-1.664,2.544-4.256,2.544-7.776 c0-3.456-0.736-6.08-2.208-7.872c-1.472-1.776-3.936-2.688-7.392-2.688h-5.76v20.848L110.816,148.896L110.816,148.896z"/> <path style="fill:#FFFFFF;" d="M180.272,134.288c-0.288-1.344-0.752-2.576-1.392-3.696c-0.64-1.104-1.456-2.048-2.432-2.784 c-0.992-0.736-2.208-1.104-3.616-1.104c-3.328,0-5.712,1.856-7.2,5.584c-1.472,3.696-2.208,9.856-2.208,18.416 c0,4.112,0.128,7.808,0.384,11.136s0.72,6.16,1.392,8.496s1.632,4.128,2.88,5.376s2.864,1.872,4.848,1.872 c0.848,0,1.744-0.24,2.752-0.672c0.992-0.448,1.904-1.12,2.784-2.016c0.864-0.912,1.584-2.032,2.16-3.408s0.864-3.008,0.864-4.864 v-7.008h-9.104V149.44h22.352v36.96H184.56v-6.336h-0.192c-1.664,2.704-3.664,4.592-6,5.712s-5.136,1.68-8.4,1.68 c-4.224,0-7.664-0.752-10.336-2.224c-2.672-1.472-4.736-3.728-6.24-6.816s-2.512-6.864-3.008-11.376 c-0.512-4.512-0.784-9.744-0.784-15.696c0-5.744,0.368-10.816,1.104-15.152c0.736-4.352,2-7.984,3.792-10.912 c1.776-2.912,4.16-5.088,7.088-6.576c2.96-1.472,6.624-2.208,11.04-2.208c7.552,0,12.992,1.872,16.32,5.632 c3.328,3.728,4.992,9.088,4.992,16.08h-13.248C180.704,136.944,180.56,135.632,180.272,134.288z"/> </g></symbol><symbol id="css" viewBox="0 0 384 384"><title>css</title><polygon style="fill:#EFEEEE;" points="64,0 64,384 288,384 384,288 384,0 "/><polygon style="fill:#ABABAB;" points="288,288 288,384 384,288 "/><polygon style="fill:#DEDEDD;" points="192,384 288,384 288,288 "/><path style="fill:#A5CBD8;" d="M0,96v112h256V96H0z"/><g> <path style="fill:#010101;" d="M64.32,130.112c-1.184-2.288-3.344-3.424-6.48-3.424c-1.728,0-3.152,0.464-4.272,1.408 c-1.12,0.928-2,2.416-2.64,4.496s-1.088,4.8-1.344,8.176c-0.272,3.36-0.384,7.472-0.384,12.336c0,5.184,0.176,9.376,0.528,12.576 c0.336,3.2,0.896,5.664,1.632,7.44s1.664,2.96,2.784,3.552c1.12,0.608,2.416,0.928,3.888,0.928c1.216,0,2.352-0.208,3.408-0.624 s1.968-1.248,2.736-2.496c0.784-1.248,1.392-3.008,1.824-5.28c0.448-2.272,0.672-5.264,0.672-8.976H80.48 c0,3.696-0.288,7.232-0.864,10.56s-1.664,6.24-3.216,8.736c-1.584,2.48-3.776,4.432-6.624,5.84s-6.544,2.128-11.088,2.128 c-5.168,0-9.312-0.848-12.368-2.496c-3.072-1.664-5.424-4.064-7.056-7.2s-2.688-6.88-3.168-11.232 c-0.464-4.336-0.72-9.152-0.72-14.384c0-5.184,0.256-9.968,0.72-14.352c0.48-4.368,1.552-8.144,3.168-11.28 c1.648-3.12,3.984-5.584,7.056-7.344c3.056-1.744,7.2-2.64,12.368-2.64c4.944,0,8.816,0.8,11.664,2.4 c2.848,1.6,4.976,3.632,6.368,6.096s2.304,5.12,2.64,7.968c0.352,2.848,0.528,5.52,0.528,8.016H66.08 C66.08,136,65.488,132.368,64.32,130.112z"/> <path style="fill:#010101;" d="M109.072,167.008c0,1.6,0.144,3.056,0.384,4.352c0.272,1.312,0.736,2.416,1.44,3.312 c0.704,0.912,1.664,1.616,2.848,2.128c1.168,0.496,2.672,0.768,4.448,0.768c2.128,0,4.016-0.688,5.712-2.064 c1.68-1.376,2.544-3.52,2.544-6.384c0-1.536-0.224-2.864-0.624-3.984c-0.416-1.12-1.104-2.128-2.064-3.008 c-0.976-0.912-2.24-1.712-3.792-2.448s-3.504-1.488-5.808-2.256c-3.056-1.024-5.712-2.16-7.968-3.376 c-2.24-1.2-4.112-2.624-5.616-4.272c-1.504-1.632-2.608-3.52-3.312-5.664c-0.704-2.16-1.056-4.624-1.056-7.456 c0-6.784,1.888-11.824,5.664-15.152c3.76-3.328,8.96-4.992,15.552-4.992c3.072,0,5.904,0.336,8.496,1.008 c2.592,0.672,4.832,1.744,6.72,3.264c1.888,1.504,3.36,3.424,4.416,5.744c1.04,2.336,1.584,5.136,1.584,8.4v1.92h-13.232 c0-3.264-0.576-5.776-1.712-7.552c-1.152-1.744-3.072-2.64-5.76-2.64c-1.536,0-2.816,0.24-3.84,0.672 c-1.008,0.448-1.84,1.04-2.448,1.776s-1.04,1.616-1.264,2.576c-0.24,0.96-0.336,1.952-0.336,2.976c0,2.128,0.448,3.888,1.344,5.328 c0.896,1.456,2.816,2.784,5.76,3.984l10.656,4.608c2.624,1.152,4.768,2.352,6.416,3.616c1.664,1.248,3.008,2.592,3.984,4.032 c0.992,1.44,1.68,3.008,2.064,4.752c0.384,1.712,0.576,3.648,0.576,5.744c0,7.232-2.096,12.496-6.288,15.792 c-4.192,3.296-10.032,4.96-17.52,4.96c-7.808,0-13.392-1.696-16.768-5.088c-3.36-3.392-5.024-8.256-5.024-14.592v-2.784h13.824 L109.072,167.008L109.072,167.008z"/> <path style="fill:#010101;" d="M168.512,167.008c0,1.6,0.128,3.056,0.384,4.352c0.256,1.312,0.736,2.416,1.44,3.312 c0.704,0.912,1.648,1.616,2.832,2.128c1.184,0.496,2.672,0.768,4.464,0.768c2.112,0,4.016-0.688,5.696-2.064 c1.696-1.376,2.544-3.52,2.544-6.384c0-1.536-0.208-2.864-0.624-3.984c-0.4-1.12-1.088-2.128-2.064-3.008 c-0.96-0.912-2.224-1.712-3.792-2.448s-3.504-1.488-5.792-2.256c-3.072-1.024-5.728-2.16-7.968-3.376 c-2.256-1.2-4.112-2.624-5.632-4.272c-1.504-1.632-2.608-3.52-3.312-5.664c-0.704-2.16-1.04-4.624-1.04-7.456 c0-6.784,1.888-11.824,5.664-15.152s8.976-4.992,15.568-4.992c3.056,0,5.904,0.336,8.48,1.008c2.592,0.672,4.848,1.744,6.736,3.264 c1.872,1.504,3.36,3.424,4.4,5.744c1.056,2.336,1.6,5.136,1.6,8.4v1.92h-13.248c0-3.264-0.576-5.776-1.728-7.552 c-1.152-1.744-3.072-2.64-5.76-2.64c-1.536,0-2.816,0.24-3.824,0.672c-1.024,0.448-1.84,1.04-2.448,1.776s-1.024,1.584-1.248,2.544 s-0.336,1.952-0.336,2.976c0,2.128,0.432,3.888,1.344,5.328c0.896,1.456,2.816,2.784,5.744,3.984l10.656,4.608 c2.624,1.152,4.768,2.352,6.432,3.616c1.664,1.248,2.992,2.592,3.984,4.032s1.664,3.008,2.064,4.752 c0.384,1.712,0.576,3.648,0.576,5.744c0,7.232-2.096,12.496-6.304,15.792c-4.192,3.296-10.032,4.96-17.52,4.96 c-7.808,0-13.392-1.696-16.752-5.088c-3.36-3.392-5.04-8.256-5.04-14.592v-2.784h13.824L168.512,167.008z"/> </g></symbol><symbol id="sh" viewBox="0 0 384 384"><title>sh</title><polygon style="fill:#EFEEEE;" points="64,0 64,384 288,384 384,288 384,0 "/><polygon style="fill:#ABABAB;" points="288,288 288,384 384,288 "/><polygon style="fill:#DEDEDD;" points="192,384 288,384 288,288 "/><path style="fill:#CB5641;" d="M0,96v112h256V96H0z"/><g> <path style="fill:#FFFFFF;" d="M47.904,167.008c0,1.6,0.16,3.056,0.416,4.352c0.256,1.312,0.736,2.416,1.44,3.312 c0.704,0.912,1.648,1.616,2.832,2.128c1.184,0.496,2.672,0.768,4.464,0.768c2.112,0,4.016-0.688,5.696-2.064 c1.696-1.376,2.544-3.52,2.544-6.384c0-1.536-0.208-2.864-0.624-3.984c-0.4-1.12-1.088-2.128-2.064-3.008 c-0.96-0.912-2.224-1.712-3.792-2.448s-3.504-1.488-5.792-2.256c-3.072-1.024-5.728-2.16-7.968-3.376 c-2.256-1.2-4.112-2.624-5.632-4.272c-1.504-1.632-2.608-3.52-3.312-5.664c-0.704-2.16-1.04-4.624-1.04-7.456 c0-6.784,1.888-11.824,5.664-15.152c3.776-3.328,8.976-4.992,15.568-4.992c3.056,0,5.904,0.336,8.48,1.008 c2.592,0.672,4.848,1.744,6.736,3.264c1.872,1.504,3.36,3.424,4.4,5.744c1.056,2.336,1.6,5.136,1.6,8.4v1.92H64.272 c0-3.264-0.576-5.776-1.728-7.552c-1.152-1.744-3.072-2.64-5.76-2.64c-1.536,0-2.816,0.24-3.824,0.672 c-1.024,0.448-1.84,1.04-2.448,1.776c-0.608,0.736-1.056,1.616-1.28,2.576c-0.224,0.96-0.336,1.952-0.336,2.976 c0,2.128,0.432,3.888,1.344,5.328c0.896,1.456,2.816,2.784,5.744,3.984l10.656,4.608c2.624,1.152,4.768,2.352,6.432,3.616 c1.664,1.248,2.992,2.592,3.984,4.032c0.992,1.44,1.664,3.008,2.064,4.752c0.384,1.712,0.576,3.648,0.576,5.744 c0,7.232-2.096,12.496-6.304,15.792c-4.192,3.296-10.032,4.96-17.52,4.96c-7.808,0-13.392-1.696-16.752-5.088 s-5.04-8.256-5.04-14.592v-2.784h13.824L47.904,167.008L47.904,167.008z"/> <path style="fill:#FFFFFF;" d="M110.048,117.856v26.208h16.128v-26.208H140V186.4h-13.824v-30.432h-16.128V186.4H96.224v-68.544 H110.048z"/> </g></symbol><symbol id="mpg" viewBox="0 0 384 384"><title>mpg</title><linearGradient id="SVGID_1_" gradientUnits="userSpaceOnUse" x1="-89.5544" y1="553.7436" x2="-36.7124" y2="500.8936" gradientTransform="matrix(8 0 0 -8 825.776 4507.272)"> <stop offset="0" style="stop-color:#EFEEEE"/> <stop offset="1" style="stop-color:#DEDEDD"/> </linearGradient><polygon style="fill:url(#SVGID_1_);" points="64,0 64,384 288,384 384,288 384,0 "/><polygon style="fill:#ABABAB;" points="288,288 288,384 384,288 "/><polygon style="fill:#DEDEDD;" points="192,384 288,384 288,288 "/><path style="fill:#2F2E7C;" d="M0,96v112h256V96L0,96L0,96z"/><g> <path style="fill:#FFFFFF;" d="M57.648,117.856l9.776,48.384h0.208l9.888-48.384h20.432V186.4H85.28v-54.72h-0.192L72.896,186.4 H62.144l-12.192-54.72H49.76v54.72H37.088v-68.544H57.648z"/> <path style="fill:#FFFFFF;" d="M137.792,117.84c3.52,0,6.464,0.608,8.88,1.792s4.32,2.736,5.808,4.656 c1.472,1.92,2.528,4.112,3.168,6.576c0.624,2.448,0.96,4.976,0.96,7.536c0,3.52-0.528,6.592-1.584,9.216 c-1.056,2.624-2.528,4.768-4.416,6.416c-1.888,1.664-4.176,2.928-6.864,3.744s-5.664,1.248-8.944,1.248h-7.392v27.36H113.6V117.84 L137.792,117.84L137.792,117.84z M133.568,148.896c2.752,0,4.992-0.848,6.672-2.512c1.696-1.664,2.544-4.256,2.544-7.776 c0-3.456-0.736-6.08-2.208-7.872c-1.472-1.776-3.936-2.688-7.392-2.688h-5.76v20.848L133.568,148.896L133.568,148.896z"/> <path style="fill:#FFFFFF;" d="M199.184,134.288c-0.288-1.344-0.752-2.576-1.392-3.696c-0.64-1.104-1.456-2.048-2.448-2.784 s-2.192-1.104-3.6-1.104c-3.328,0-5.728,1.856-7.2,5.584c-1.472,3.696-2.208,9.856-2.208,18.416c0,4.112,0.128,7.808,0.368,11.136 c0.272,3.328,0.72,6.16,1.392,8.496s1.648,4.128,2.896,5.376s2.864,1.872,4.848,1.872c0.832,0,1.728-0.24,2.736-0.672 c0.992-0.448,1.92-1.12,2.784-2.016c0.88-0.912,1.6-2.032,2.16-3.408c0.576-1.376,0.88-3.008,0.88-4.864v-7.008h-9.12V149.44 h22.368v36.96h-10.192v-6.336h-0.192c-1.664,2.704-3.664,4.592-6,5.712c-2.336,1.12-5.136,1.68-8.4,1.68 c-4.224,0-7.664-0.752-10.32-2.224s-4.736-3.728-6.24-6.816c-1.52-3.072-2.512-6.864-3.024-11.376s-0.768-9.744-0.768-15.696 c0-5.744,0.352-10.816,1.104-15.152c0.736-4.352,2-7.984,3.776-10.912c1.792-2.912,4.16-5.088,7.104-6.576 c2.944-1.472,6.624-2.208,11.04-2.208c7.552,0,12.992,1.872,16.32,5.632c3.328,3.728,4.992,9.088,4.992,16.08h-13.232 C199.632,136.944,199.472,135.632,199.184,134.288z"/> </g></symbol><symbol id="mov" viewBox="0 0 384 384"><title>mov</title><linearGradient id="SVGID_1_" gradientUnits="userSpaceOnUse" x1="-86.1533" y1="545.6279" x2="-33.3053" y2="492.7799" gradientTransform="matrix(8 0 0 -8 798.56 4442.3569)"> <stop offset="0" style="stop-color:#EFEEEE"/> <stop offset="1" style="stop-color:#DEDEDD"/> </linearGradient><polygon style="fill:url(#SVGID_1_);" points="64,0 64,384 288,384 384,288 384,0 "/><polygon style="fill:#ABABAB;" points="288,288 288,384 384,288 "/><polygon style="fill:#DEDEDD;" points="192,384 288,384 288,288 "/><path style="fill:#50B6E7;" d="M0,96v112h256V96L0,96L0,96z"/><g> <path style="fill:#010101;" d="M57.648,117.856l9.792,48.384h0.192l9.888-48.384h20.448V186.4H85.28v-54.72h-0.192L72.896,186.4 H62.16l-12.192-54.72H49.76v54.72H37.088v-68.544H57.648z"/> <path style="fill:#010101;" d="M110.976,137.776c0.48-4.368,1.536-8.144,3.168-11.28c1.648-3.12,3.984-5.584,7.04-7.344 c3.072-1.744,7.216-2.64,12.384-2.64c5.184,0,9.312,0.896,12.384,2.64c3.056,1.76,5.408,4.224,7.04,7.344 c1.648,3.136,2.688,6.912,3.168,11.28c0.464,4.384,0.72,9.168,0.72,14.352c0,5.232-0.256,10.048-0.72,14.384 c-0.48,4.352-1.536,8.096-3.168,11.232s-3.984,5.536-7.04,7.2c-3.072,1.664-7.2,2.496-12.384,2.496 c-5.168,0-9.312-0.848-12.384-2.496c-3.056-1.664-5.408-4.064-7.04-7.2s-2.688-6.88-3.168-11.232 c-0.464-4.336-0.72-9.152-0.72-14.384C110.256,146.96,110.496,142.176,110.976,137.776z M124.4,163.888 c0.224,3.232,0.688,5.856,1.344,7.872c0.688,2.016,1.632,3.488,2.88,4.416s2.912,1.392,4.96,1.392s3.696-0.464,4.96-1.392 c1.248-0.944,2.208-2.4,2.88-4.416s1.12-4.64,1.344-7.872s0.336-7.136,0.336-11.744s-0.128-8.512-0.336-11.712 s-0.672-5.824-1.344-7.872s-1.632-3.536-2.88-4.448c-1.248-0.944-2.912-1.408-4.96-1.408s-3.696,0.464-4.96,1.408 c-1.248,0.928-2.192,2.4-2.88,4.448c-0.656,2.048-1.12,4.672-1.344,7.872s-0.336,7.104-0.336,11.712S124.192,160.656,124.4,163.888 z"/> <path style="fill:#010101;" d="M188.88,168.544h0.288l10.192-50.688h14.304L197.92,186.4h-17.76l-15.728-68.544h14.784 L188.88,168.544z"/> </g></symbol><symbol id="mp3" viewBox="0 0 384 384"><title>mp3</title><linearGradient id="SVGID_1_" gradientUnits="userSpaceOnUse" x1="-89.3171" y1="553.7958" x2="-36.4711" y2="500.9509" gradientTransform="matrix(8 0 0 -8 823.872 4507.7041)"> <stop offset="0" style="stop-color:#EFEEEE"/> <stop offset="1" style="stop-color:#DEDEDD"/> </linearGradient><polygon style="fill:url(#SVGID_1_);" points="64,0 64,384 288,384 384,288 384,0 "/><polygon style="fill:#ABABAB;" points="288,288 288,384 384,288 "/><polygon style="fill:#DEDEDD;" points="192,384 288,384 288,288 "/><path style="fill:#527CA3;" d="M0,96v112h256V96L0,96L0,96z"/><g> <path style="fill:#FFFFFF;" d="M57.648,117.856l9.776,48.384h0.208l9.888-48.384h20.432V186.4H85.28v-54.72h-0.192L72.896,186.4 H62.144l-12.192-54.72H49.76v54.72H37.088v-68.544H57.648z"/> <path style="fill:#FFFFFF;" d="M141.648,117.84c3.52,0,6.464,0.608,8.88,1.792s4.336,2.736,5.808,4.656s2.528,4.112,3.168,6.576 c0.624,2.448,0.96,4.976,0.96,7.536c0,3.52-0.528,6.592-1.584,9.216c-1.056,2.624-2.528,4.768-4.416,6.416 c-1.888,1.664-4.176,2.928-6.864,3.744s-5.664,1.248-8.944,1.248h-7.392v27.36H117.44V117.84L141.648,117.84L141.648,117.84z M137.408,148.896c2.752,0,4.992-0.848,6.672-2.512c1.696-1.664,2.544-4.256,2.544-7.776c0-3.456-0.736-6.08-2.208-7.872 c-1.472-1.776-3.936-2.688-7.392-2.688h-5.76v20.848L137.408,148.896L137.408,148.896z"/> <path style="fill:#FFFFFF;" d="M188.816,175.616c0.992,2.08,3.008,3.104,6,3.104c1.664,0,3.008-0.288,3.984-0.864 c0.992-0.576,1.76-1.36,2.32-2.336c0.544-0.992,0.896-2.176,1.04-3.52c0.176-1.344,0.256-2.752,0.256-4.224 c0-1.536-0.112-2.992-0.336-4.352c-0.224-1.392-0.656-2.608-1.296-3.696c-0.64-1.088-1.536-1.952-2.688-2.592 c-1.136-0.64-2.688-0.96-4.608-0.96h-4.608v-9.024h4.512c1.472,0,2.704-0.304,3.696-0.912s1.776-1.408,2.384-2.4 c0.608-0.992,1.056-2.128,1.344-3.408c0.304-1.28,0.432-2.624,0.432-4.032c0-3.264-0.544-5.568-1.632-6.912 c-1.088-1.344-2.768-2.016-4.992-2.016c-1.472,0-2.672,0.272-3.6,0.816c-0.944,0.544-1.664,1.312-2.176,2.304 c-0.496,0.992-0.848,2.176-0.992,3.52c-0.144,1.344-0.24,2.816-0.24,4.4h-12.672c0-6.784,1.68-11.824,5.072-15.12 c3.392-3.28,8.352-4.944,14.896-4.944c6.208,0,11.008,1.408,14.432,4.224c3.424,2.832,5.136,7.232,5.136,13.248 c0,4.096-0.912,7.44-2.736,10.032s-4.368,4.256-7.648,4.96v0.192c4.416,0.704,7.552,2.48,9.36,5.328 c1.824,2.848,2.736,6.416,2.736,10.704c0,2.32-0.288,4.672-0.864,7.104c-0.576,2.416-1.68,4.64-3.36,6.624 c-1.664,1.984-3.952,3.584-6.864,4.8c-2.928,1.216-6.736,1.824-11.472,1.824c-6.592,0-11.536-1.792-14.8-5.376 c-3.248-3.6-4.896-8.672-4.896-15.264v-0.304h13.248C187.296,170.496,187.824,173.536,188.816,175.616z"/> </g></symbol><symbol id="psd" viewBox="0 0 384 384"><title>psd</title><linearGradient id="SVGID_1_" gradientUnits="userSpaceOnUse" x1="-92.1654" y1="548.774" x2="-39.3154" y2="495.927" gradientTransform="matrix(8 0 0 -8 846.6528 4467.5278)"> <stop offset="0" style="stop-color:#EFEEEE"/> <stop offset="1" style="stop-color:#DEDEDD"/> </linearGradient><polygon style="fill:url(#SVGID_1_);" points="64,0 64,384 288,384 384,288 384,0 "/><polygon style="fill:#ABABAB;" points="288,288 288,384 384,288 "/><polygon style="fill:#DEDEDD;" points="192,384 288,384 288,288 "/><path style="fill:#1F60A4;" d="M0,96v112h256V96H0z"/><g> <path style="fill:#FFFFFF;" d="M60.992,117.856c3.52,0,6.464,0.592,8.88,1.776s4.336,2.736,5.808,4.656s2.528,4.112,3.168,6.576 c0.624,2.448,0.96,4.976,0.96,7.536c0,3.52-0.528,6.592-1.584,9.216c-1.056,2.624-2.528,4.768-4.416,6.416 c-1.888,1.664-4.176,2.928-6.864,3.744c-2.688,0.816-5.664,1.264-8.928,1.264h-7.392v27.36H36.8v-68.544L60.992,117.856z M56.768,148.896c2.752,0,4.992-0.848,6.672-2.512c1.696-1.664,2.544-4.256,2.544-7.776c0-3.456-0.736-6.08-2.208-7.872 c-1.472-1.776-3.936-2.688-7.392-2.688h-5.76v20.848H56.768z"/> <path style="fill:#FFFFFF;" d="M107.36,167.008c0,1.6,0.128,3.056,0.384,4.352c0.256,1.312,0.736,2.416,1.44,3.312 c0.704,0.912,1.648,1.616,2.832,2.128c1.184,0.496,2.672,0.768,4.464,0.768c2.112,0,4.016-0.688,5.696-2.064 c1.696-1.376,2.544-3.52,2.544-6.384c0-1.536-0.208-2.864-0.624-3.984c-0.4-1.12-1.088-2.128-2.064-3.008 c-0.96-0.912-2.224-1.712-3.792-2.448c-1.568-0.736-3.504-1.488-5.792-2.256c-3.072-1.024-5.728-2.16-7.968-3.376 c-2.256-1.2-4.112-2.624-5.632-4.272c-1.504-1.632-2.608-3.52-3.312-5.664c-0.704-2.16-1.04-4.624-1.04-7.456 c0-6.784,1.888-11.824,5.664-15.152c3.776-3.328,8.976-4.992,15.568-4.992c3.056,0,5.904,0.336,8.48,1.008 c2.592,0.672,4.848,1.744,6.736,3.264c1.872,1.504,3.36,3.424,4.4,5.744c1.056,2.336,1.6,5.136,1.6,8.4v1.92H123.68 c0-3.264-0.576-5.776-1.728-7.552c-1.152-1.744-3.072-2.64-5.76-2.64c-1.536,0-2.816,0.24-3.824,0.672 c-1.024,0.448-1.84,1.04-2.448,1.776s-1.04,1.616-1.264,2.576c-0.224,0.96-0.336,1.952-0.336,2.976 c0,2.128,0.432,3.888,1.344,5.328c0.896,1.456,2.816,2.784,5.744,3.984l10.656,4.608c2.624,1.152,4.768,2.352,6.432,3.616 c1.664,1.248,2.992,2.592,3.984,4.032s1.664,3.008,2.064,4.752c0.384,1.712,0.576,3.648,0.576,5.744 c0,7.232-2.096,12.496-6.304,15.792c-4.192,3.296-10.032,4.96-17.52,4.96c-7.808,0-13.392-1.696-16.752-5.088 s-5.04-8.256-5.04-14.592v-2.784h13.856L107.36,167.008L107.36,167.008z"/> <path style="fill:#FFFFFF;" d="M178.976,117.84c4.816,0,8.672,0.8,11.632,2.368c2.944,1.568,5.232,3.792,6.864,6.72 c1.648,2.912,2.736,6.4,3.312,10.512c0.576,4.096,0.864,8.672,0.864,13.712c0,6.016-0.352,11.248-1.088,15.696 c-0.736,4.432-2,8.112-3.792,10.992c-1.792,2.896-4.192,5.024-7.2,6.432s-6.816,2.112-11.424,2.112H156.32V117.84H178.976z M176.288,176.24c2.432,0,4.384-0.416,5.856-1.248c1.472-0.832,2.64-2.208,3.504-4.128c0.88-1.92,1.456-4.448,1.728-7.6 c0.288-3.12,0.432-7.024,0.432-11.696c0-3.904-0.144-7.328-0.384-10.288c-0.256-2.944-0.784-5.392-1.584-7.344 c-0.816-1.952-1.984-3.424-3.568-4.4c-1.568-0.992-3.664-1.504-6.288-1.504h-5.856v48.208L176.288,176.24z"/> </g></symbol><symbol id="eps" viewBox="0 0 384 384"><title>eps</title><linearGradient id="SVGID_1_" gradientUnits="userSpaceOnUse" x1="-89.0662" y1="549.4557" x2="-36.2262" y2="496.6158" gradientTransform="matrix(8 0 0 -8 821.884 4473)"> <stop offset="0" style="stop-color:#EFEEEE"/> <stop offset="1" style="stop-color:#DEDEDD"/> </linearGradient><polygon style="fill:url(#SVGID_1_);" points="64,0 64,384 288,384 384,288 384,0 "/><polygon style="fill:#ABABAB;" points="288,288 288,384 384,288 "/><polygon style="fill:#DEDEDD;" points="192,384 288,384 288,288 "/><path style="fill:#F47821;" d="M0,96v112h256V96H0z"/><g> <path style="fill:#FFFFFF;" d="M75.12,129.184H50.624v16.128h23.04v11.328h-23.04v18.432h25.44V186.4H36.8v-68.544h38.32 L75.12,129.184L75.12,129.184z"/> <path style="fill:#FFFFFF;" d="M116.768,117.856c3.52,0,6.48,0.592,8.896,1.776c2.384,1.184,4.336,2.736,5.792,4.656 s2.528,4.112,3.168,6.576c0.64,2.448,0.96,4.976,0.96,7.536c0,3.52-0.512,6.592-1.584,9.216c-1.04,2.624-2.528,4.768-4.416,6.416 c-1.872,1.664-4.176,2.928-6.864,3.744s-5.664,1.264-8.928,1.264H106.4v27.36H92.576v-68.544L116.768,117.856z M112.544,148.896 c2.752,0,4.976-0.848,6.672-2.512c1.68-1.664,2.544-4.256,2.544-7.776c0-3.456-0.736-6.08-2.224-7.872 c-1.472-1.776-3.92-2.688-7.392-2.688H106.4v20.848H112.544z"/> <path style="fill:#FFFFFF;" d="M163.136,167.008c0,1.6,0.128,3.056,0.368,4.352c0.272,1.312,0.736,2.416,1.44,3.312 c0.704,0.912,1.664,1.616,2.848,2.128c1.168,0.496,2.672,0.768,4.448,0.768c2.128,0,4.016-0.688,5.712-2.064 c1.68-1.376,2.544-3.52,2.544-6.384c0-1.536-0.224-2.864-0.624-3.984c-0.416-1.12-1.104-2.128-2.064-3.008 c-0.976-0.912-2.24-1.712-3.792-2.448s-3.504-1.488-5.808-2.256c-3.056-1.024-5.712-2.16-7.968-3.376 c-2.24-1.2-4.112-2.624-5.616-4.272c-1.504-1.632-2.608-3.52-3.312-5.664c-0.704-2.16-1.056-4.624-1.056-7.456 c0-6.784,1.888-11.824,5.664-15.152c3.76-3.328,8.96-4.992,15.552-4.992c3.072,0,5.904,0.336,8.496,1.008 c2.592,0.672,4.832,1.744,6.72,3.264c1.888,1.504,3.36,3.424,4.416,5.744c1.04,2.336,1.584,5.136,1.584,8.4v1.92H179.44 c0-3.264-0.576-5.776-1.712-7.552c-1.152-1.744-3.072-2.64-5.76-2.64c-1.536,0-2.816,0.24-3.84,0.672 c-1.008,0.448-1.84,1.04-2.448,1.776s-1.008,1.584-1.232,2.544c-0.24,0.96-0.336,1.952-0.336,2.976 c0,2.128,0.448,3.888,1.344,5.328c0.896,1.456,2.816,2.784,5.76,3.984l10.656,4.608c2.624,1.152,4.768,2.352,6.416,3.616 c1.664,1.248,3.008,2.592,3.984,4.032c0.992,1.44,1.68,3.008,2.064,4.752c0.384,1.712,0.576,3.648,0.576,5.744 c0,7.232-2.096,12.496-6.288,15.792c-4.192,3.296-10.032,4.96-17.52,4.96c-7.808,0-13.392-1.696-16.768-5.088 c-3.36-3.392-5.024-8.256-5.024-14.592v-2.784h13.824L163.136,167.008z"/> </g></symbol><symbol id="mp4" viewBox="0 0 384 384"><title>mp4</title><linearGradient id="SVGID_1_" gradientUnits="userSpaceOnUse" x1="-89.5551" y1="553.744" x2="-36.7121" y2="500.894" gradientTransform="matrix(8 0 0 -8 825.776 4507.272)"> <stop offset="0" style="stop-color:#EFEEEE"/> <stop offset="1" style="stop-color:#DEDEDD"/> </linearGradient><polygon style="fill:url(#SVGID_1_);" points="64,0 64,384 288,384 384,288 384,0 "/><polygon style="fill:#ABABAB;" points="288,288 288,384 384,288 "/><polygon style="fill:#DEDEDD;" points="192,384 288,384 288,288 "/><path style="fill:#3A3A39;" d="M0,96v112h256V96L0,96L0,96z"/><g> <path style="fill:#FFFFFF;" d="M57.648,117.856l9.776,48.384h0.208l9.888-48.384h20.432V186.4H85.28v-54.72h-0.192L72.896,186.4 H62.144l-12.192-54.72H49.76v54.72H37.088v-68.544H57.648z"/> <path style="fill:#FFFFFF;" d="M137.792,117.84c3.52,0,6.464,0.608,8.88,1.792s4.32,2.736,5.808,4.656 c1.472,1.92,2.528,4.112,3.168,6.576c0.624,2.448,0.96,4.976,0.96,7.536c0,3.52-0.528,6.592-1.584,9.216 c-1.056,2.624-2.528,4.768-4.416,6.416c-1.888,1.664-4.176,2.928-6.864,3.744s-5.664,1.248-8.944,1.248h-7.376v27.36H113.6V117.84 L137.792,117.84L137.792,117.84z M133.568,148.88c2.752,0,4.992-0.848,6.672-2.512c1.696-1.664,2.544-4.256,2.544-7.776 c0-3.456-0.736-6.08-2.208-7.872c-1.472-1.776-3.936-2.688-7.392-2.688h-5.76v20.848L133.568,148.88L133.568,148.88z"/> <path style="fill:#FFFFFF;" d="M188.304,118.432h14.304v43.104h6.528v10.752h-6.528V186.4H189.92v-14.112h-24.192V160.96 L188.304,118.432z M189.728,135.712l-13.248,25.824h13.44v-25.824H189.728z"/> </g></symbol><symbol id="gif" viewBox="0 0 384 384"><title>gif</title><linearGradient id="SVGID_1_" gradientUnits="userSpaceOnUse" x1="-90.3426" y1="549.4554" x2="-37.4926" y2="496.6154" gradientTransform="matrix(8 0 0 -8 832.076 4473)"> <stop offset="0" style="stop-color:#EFEEEE"/> <stop offset="1" style="stop-color:#DEDEDD"/> </linearGradient><polygon style="fill:url(#SVGID_1_);" points="64,0 64,384 288,384 384,288 384,0 "/><polygon style="fill:#ABABAB;" points="288,288 288,384 384,288 "/><polygon style="fill:#DEDEDD;" points="192,384 288,384 288,288 "/><path style="fill:#010101;" d="M0,96v112h256V96H0z"/><g> <path style="fill:#FFFFFF;" d="M66.8,134.288c-0.288-1.344-0.752-2.576-1.392-3.696c-0.64-1.104-1.456-2.048-2.432-2.784 c-0.992-0.736-2.208-1.104-3.616-1.104c-3.328,0-5.712,1.856-7.2,5.584c-1.472,3.696-2.208,9.856-2.208,18.416 c0,4.112,0.128,7.808,0.384,11.136c0.256,3.328,0.72,6.16,1.392,8.496s1.632,4.128,2.88,5.376c1.248,1.248,2.864,1.872,4.848,1.872 c0.848,0,1.744-0.24,2.752-0.672c0.992-0.448,1.904-1.12,2.784-2.016c0.864-0.912,1.584-2.032,2.16-3.408S68,168.48,68,166.624 v-7.008h-9.104V149.44h22.352v36.96H71.072v-6.336h-0.176c-1.664,2.704-3.664,4.592-6,5.712s-5.136,1.68-8.4,1.68 c-4.224,0-7.664-0.752-10.336-2.224c-2.672-1.472-4.736-3.728-6.24-6.816c-1.504-3.088-2.512-6.864-3.008-11.376 c-0.512-4.512-0.784-9.744-0.784-15.696c0-5.744,0.368-10.816,1.104-15.152c0.736-4.352,2-7.984,3.792-10.912 c1.776-2.912,4.16-5.088,7.088-6.576c2.96-1.472,6.624-2.208,11.04-2.208c7.552,0,12.992,1.872,16.32,5.632 c3.328,3.728,4.992,9.088,4.992,16.08H67.232C67.232,136.944,67.088,135.632,66.8,134.288z"/> <path style="fill:#FFFFFF;" d="M100.448,117.856h13.824V186.4h-13.824V117.856z"/> <path style="fill:#FFFFFF;" d="M172.448,117.856v11.328h-24.48v16.128h23.04v11.328h-23.04v29.76h-13.824v-68.544L172.448,117.856z "/> </g></symbol><symbol id="ppt" viewBox="0 0 384 384"><title>ppt</title><polygon style="fill:#EFEEEE;" points="64,0 64,384 288,384 384,288 384,0 "/><polygon style="fill:#ABABAB;" points="288,288 288,384 384,288 "/><polygon style="fill:#DEDEDD;" points="192,384 288,384 288,288 "/><path style="fill:#CB7B29;" d="M0,96v112h256V96H0z"/><g> <path style="fill:#FFFFFF;" d="M60.992,117.856c3.52,0,6.464,0.592,8.88,1.776s4.336,2.736,5.808,4.656s2.528,4.112,3.168,6.576 c0.624,2.448,0.96,4.976,0.96,7.536c0,3.52-0.528,6.592-1.584,9.216c-1.056,2.624-2.528,4.768-4.416,6.416 c-1.888,1.664-4.176,2.928-6.864,3.744c-2.688,0.816-5.664,1.264-8.928,1.264h-7.392v27.36H36.8v-68.544L60.992,117.856z M56.768,148.896c2.752,0,4.992-0.848,6.672-2.512c1.696-1.664,2.544-4.256,2.544-7.776c0-3.456-0.736-6.08-2.208-7.872 c-1.472-1.776-3.936-2.688-7.392-2.688h-5.76v20.848L56.768,148.896L56.768,148.896z"/> <path style="fill:#FFFFFF;" d="M120.416,117.856c3.52,0,6.464,0.592,8.88,1.776c2.416,1.184,4.336,2.736,5.808,4.656 c1.472,1.92,2.528,4.112,3.168,6.576c0.624,2.448,0.96,4.976,0.96,7.536c0,3.52-0.528,6.592-1.584,9.216 c-1.056,2.624-2.528,4.768-4.416,6.416c-1.888,1.664-4.176,2.928-6.864,3.744c-2.688,0.816-5.664,1.264-8.928,1.264h-7.392v27.36 H96.224v-68.544L120.416,117.856z M116.192,148.896c2.752,0,4.992-0.848,6.672-2.512c1.696-1.664,2.544-4.256,2.544-7.776 c0-3.456-0.736-6.08-2.208-7.872c-1.472-1.776-3.936-2.688-7.392-2.688h-5.76v20.848L116.192,148.896L116.192,148.896z"/> <path style="fill:#FFFFFF;" d="M195.776,129.184H180.8V186.4h-13.824v-57.216H152v-11.328h43.776V129.184z"/> </g></symbol><symbol id="js" viewBox="0 0 384 384"><title>js</title><polygon style="fill:#EFEEEE;" points="64,0 64,384 288,384 384,288 384,0 "/><polygon style="fill:#ABABAB;" points="288,288 288,384 384,288 "/><polygon style="fill:#DEDEDD;" points="192,384 288,384 288,288 "/><path style="fill:#CB5641;" d="M0,96v112h256V96L0,96L0,96z"/><g> <path style="fill:#FFFFFF;" d="M72.24,167.2c0,7.296-1.696,12.496-5.088,15.616c-3.392,3.088-8.576,4.656-15.552,4.656 c-3.664,0-6.64-0.496-8.992-1.504c-2.336-0.992-4.192-2.384-5.568-4.224s-2.32-3.952-2.816-6.384 c-0.528-2.416-0.784-4.992-0.784-7.68v-2.992h12.672v2.128c0,3.648,0.4,6.336,1.2,8.112c0.8,1.744,2.448,2.64,4.96,2.64 c2.48,0,4.144-0.896,4.944-2.64c0.8-1.76,1.2-4.464,1.2-8.112V117.84H72.24V167.2z"/> <path style="fill:#FFFFFF;" d="M101.984,167.008c0,1.6,0.128,3.056,0.384,4.352c0.256,1.312,0.736,2.416,1.44,3.312 c0.704,0.912,1.648,1.616,2.832,2.128c1.184,0.496,2.672,0.768,4.464,0.768c2.112,0,4.016-0.688,5.696-2.064 c1.696-1.376,2.544-3.52,2.544-6.384c0-1.536-0.208-2.864-0.624-3.984c-0.4-1.12-1.088-2.128-2.064-3.008 c-0.96-0.912-2.224-1.712-3.792-2.448c-1.568-0.736-3.504-1.488-5.792-2.256c-3.072-1.024-5.728-2.16-7.968-3.376 c-2.256-1.2-4.112-2.624-5.632-4.272c-1.504-1.632-2.608-3.52-3.312-5.664c-0.704-2.16-1.04-4.624-1.04-7.456 c0-6.784,1.888-11.824,5.664-15.152s8.976-4.992,15.568-4.992c3.056,0,5.904,0.336,8.48,1.008c2.592,0.672,4.848,1.744,6.736,3.264 c1.872,1.504,3.36,3.424,4.4,5.744c1.056,2.336,1.6,5.136,1.6,8.4v1.92H118.32c0-3.264-0.576-5.776-1.728-7.552 c-1.152-1.744-3.072-2.64-5.76-2.64c-1.536,0-2.816,0.24-3.824,0.672c-1.024,0.448-1.84,1.04-2.448,1.776s-1.056,1.616-1.28,2.576 s-0.336,1.952-0.336,2.976c0,2.128,0.432,3.888,1.344,5.328c0.896,1.456,2.816,2.784,5.744,3.984l10.656,4.608 c2.624,1.152,4.768,2.352,6.432,3.616c1.664,1.248,2.992,2.592,3.984,4.032c0.992,1.44,1.664,3.008,2.064,4.752 c0.384,1.712,0.576,3.648,0.576,5.744c0,7.232-2.096,12.496-6.304,15.792c-4.192,3.296-10.032,4.96-17.52,4.96 c-7.808,0-13.392-1.696-16.752-5.088s-5.008-8.256-5.008-14.608v-2.784h13.824L101.984,167.008z"/> </g></symbol><symbol id="html" viewBox="0 0 384 384"><title>html</title><polygon style="fill:#EFEEEE;" points="64.004,0 64.004,384 287.996,384 383.996,288 383.996,0 "/><polygon style="fill:#ABABAB;" points="287.996,288 287.996,384 383.996,288 "/><polygon style="fill:#DEDEDD;" points="191.996,384 287.996,384 287.996,288 "/><path style="fill:#2B84C3;" d="M0.004,96v112h256V96H0.004z"/><g> <path style="fill:#FFFFFF;" d="M30.956,117.856v26.208h16.128v-26.208h13.824V186.4H47.084v-30.432H30.956V186.4H17.132v-68.544 L30.956,117.856z"/> <path style="fill:#FFFFFF;" d="M116.788,129.184h-14.976V186.4h-13.84v-57.216h-14.96v-11.328h43.776V129.184z"/> <path style="fill:#FFFFFF;" d="M150.468,117.856l9.792,48.384h0.192l9.888-48.384h20.448V186.4h-12.672v-54.72h-0.208 l-12.192,54.72h-10.752l-12.192-54.72h-0.192v54.72h-12.672v-68.544L150.468,117.856z"/> <path style="fill:#FFFFFF;" d="M204.164,117.856h13.84v57.216h24.48V186.4h-38.32V117.856z"/> </g></symbol><symbol id="png" viewBox="0 0 384 384"><title>png</title><linearGradient id="SVGID_1_" gradientUnits="userSpaceOnUse" x1="-88.6647" y1="552.8239" x2="-35.8147" y2="499.9769" gradientTransform="matrix(8 0 0 -8 818.6472 4499.9277)"> <stop offset="0" style="stop-color:#EFEEEE"/> <stop offset="1" style="stop-color:#DEDEDD"/> </linearGradient><polygon style="fill:url(#SVGID_1_);" points="64,0 64,384 288,384 384,288 384,0 "/><polygon style="fill:#ABABAB;" points="288,288 288,384 384,288 "/><polygon style="fill:#DEDEDD;" points="192,384 288,384 288,288 "/><path style="fill:#CB5641;" d="M0,96v112h256V96H0z"/><g> <path style="fill:#FFFFFF;" d="M60.992,117.856c3.52,0,6.464,0.592,8.88,1.776s4.336,2.736,5.808,4.656s2.528,4.112,3.168,6.576 c0.624,2.448,0.96,4.976,0.96,7.536c0,3.52-0.528,6.592-1.584,9.216s-2.528,4.768-4.416,6.416 c-1.888,1.664-4.176,2.928-6.864,3.744s-5.664,1.264-8.928,1.264h-7.392v27.36H36.8v-68.544L60.992,117.856z M56.768,148.896 c2.752,0,4.992-0.848,6.672-2.512c1.696-1.664,2.544-4.256,2.544-7.776c0-3.456-0.736-6.08-2.208-7.872 c-1.472-1.776-3.936-2.688-7.392-2.688h-5.76v20.848L56.768,148.896L56.768,148.896z"/> <path style="fill:#FFFFFF;" d="M128.864,164.816h0.208v-46.96h12.672V186.4h-15.76l-16.896-48h-0.192v48H96.224v-68.544h15.936 L128.864,164.816z"/> <path style="fill:#FFFFFF;" d="M190.928,134.288c-0.288-1.344-0.752-2.576-1.392-3.696c-0.64-1.104-1.456-2.048-2.432-2.784 c-0.992-0.736-2.208-1.104-3.616-1.104c-3.328,0-5.712,1.856-7.2,5.584c-1.472,3.696-2.208,9.856-2.208,18.416 c0,4.112,0.128,7.808,0.384,11.136s0.72,6.16,1.392,8.496s1.632,4.128,2.88,5.376c1.248,1.248,2.864,1.872,4.848,1.872 c0.848,0,1.744-0.24,2.752-0.672c0.992-0.448,1.904-1.12,2.784-2.016c0.864-0.912,1.584-2.032,2.16-3.408s0.864-3.008,0.864-4.864 v-7.008h-9.104V149.44h22.352v36.96H195.2v-6.336h-0.192c-1.664,2.704-3.664,4.592-6,5.712c-2.336,1.12-5.136,1.68-8.4,1.68 c-4.224,0-7.664-0.752-10.336-2.224c-2.672-1.472-4.736-3.728-6.24-6.816c-1.504-3.088-2.512-6.864-3.008-11.376 c-0.512-4.512-0.784-9.744-0.784-15.696c0-5.744,0.368-10.816,1.104-15.152c0.736-4.352,2-7.984,3.792-10.912 c1.776-2.912,4.16-5.088,7.088-6.576c2.96-1.472,6.624-2.208,11.04-2.208c7.552,0,12.992,1.872,16.32,5.632 c3.328,3.728,4.992,9.088,4.992,16.08H191.36C191.36,136.944,191.216,135.632,190.928,134.288z"/> </g></symbol><symbol id="zip" viewBox="0 0 384 384"><title>zip</title><linearGradient id="SVGID_1_" gradientUnits="userSpaceOnUse" x1="-85.4605" y1="553.9575" x2="-32.6135" y2="501.1145" gradientTransform="matrix(8 0 0 -8 793.016 4509)"> <stop offset="0" style="stop-color:#EFEEEE"/> <stop offset="1" style="stop-color:#DEDEDD"/> </linearGradient><polygon style="fill:url(#SVGID_1_);" points="64,0 64,384 288,384 384,288 384,0 "/><polygon style="fill:#ABABAB;" points="288,288 288,384 384,288 "/><polygon style="fill:#DEDEDD;" points="192,384 288,384 288,288 "/><path style="fill:#402D7A;" d="M0,96v112h256V96H0z"/><g> <path style="fill:#FFFFFF;" d="M59.568,129.184H36.032v-11.328h39.456v10.464l-25.632,46.752h26.016V186.4H34.32v-10.08 L59.568,129.184z"/> <path style="fill:#FFFFFF;" d="M93.248,117.856h13.824V186.4H93.248V117.856z"/> <path style="fill:#FFFFFF;" d="M151.136,117.84c3.52,0,6.464,0.608,8.88,1.792s4.336,2.736,5.808,4.656s2.528,4.112,3.168,6.576 c0.624,2.448,0.96,4.976,0.96,7.536c0,3.52-0.528,6.592-1.584,9.216c-1.056,2.624-2.528,4.768-4.416,6.416 c-1.888,1.664-4.176,2.928-6.864,3.744s-5.664,1.248-8.944,1.248h-7.392v27.36h-13.808V117.84H151.136z M146.912,148.896 c2.752,0,4.992-0.848,6.672-2.512c1.696-1.664,2.544-4.256,2.544-7.776c0-3.456-0.736-6.08-2.208-7.872 c-1.472-1.776-3.936-2.688-7.392-2.688h-5.76v20.848H146.912z"/> </g></symbol><symbol id="xml" viewBox="0 0 384 384"><title>xml</title><polygon style="fill:#EFEEEE;" points="64,0 64,384 288,384 384,288 384,0 "/><polygon style="fill:#ABABAB;" points="288,288 288,384 384,288 "/><polygon style="fill:#DEDEDD;" points="192,384 288,384 288,288 "/><path style="fill:#CB5641;" d="M0,96v112h256V96H0z"/><g> <path style="fill:#FFFFFF;" d="M49.184,117.856l8.832,22.176l8.736-22.176h15.072l-15.936,33.888L82.976,186.4H67.424 l-9.872-23.232L47.744,186.4H32.576l17.088-34.656L33.92,117.856L49.184,117.856L49.184,117.856z"/> <path style="fill:#FFFFFF;" d="M118.784,117.856l9.792,48.384h0.192l9.888-48.384h20.448V186.4h-12.672v-54.72h-0.192 l-12.192,54.72h-10.752l-12.192-54.72h-0.192v54.72H98.24v-68.544L118.784,117.856z"/> <path style="fill:#FFFFFF;" d="M178.576,117.856h13.84v57.216h24.464V186.4h-38.304V117.856z"/> </g></symbol><symbol id="json" viewBox="0 0 384 384"><title>json</title><polygon style="fill:#EFEEEE;" points="64,0 64,384 288,384 384,288 384,0 "/><polygon style="fill:#ABABAB;" points="288,288 288,384 384,288 "/><polygon style="fill:#DEDEDD;" points="192,384 288,384 288,288 "/><path style="fill:#CB5641;" d="M0,96v112h256V96L0,96L0,96z"/><g> <path style="fill:#FFFFFF;" d="M54.88,167.2c0,7.296-1.68,12.496-5.088,15.616c-3.376,3.088-8.56,4.656-15.536,4.656 c-3.664,0-6.656-0.496-8.992-1.504c-2.336-0.992-4.208-2.384-5.584-4.224s-2.32-3.952-2.816-6.384 c-0.528-2.416-0.784-4.992-0.784-7.664v-2.992h12.672v2.128c0,3.648,0.384,6.336,1.2,8.112c0.8,1.744,2.432,2.64,4.944,2.64 s4.16-0.896,4.96-2.64c0.8-1.76,1.2-4.464,1.2-8.112v-48.976H54.88C54.88,117.856,54.88,167.2,54.88,167.2z"/> <path style="fill:#FFFFFF;" d="M80.8,167.008c0,1.6,0.144,3.056,0.384,4.352c0.256,1.312,0.736,2.416,1.44,3.312 c0.704,0.912,1.664,1.616,2.848,2.128c1.168,0.496,2.656,0.768,4.448,0.768c2.112,0,4.016-0.688,5.696-2.064 c1.696-1.376,2.56-3.52,2.56-6.384c0-1.536-0.224-2.864-0.624-3.984c-0.416-1.12-1.104-2.128-2.064-3.008 c-0.976-0.912-2.24-1.712-3.808-2.448s-3.504-1.488-5.792-2.256c-3.072-1.024-5.728-2.16-7.968-3.376 c-2.24-1.2-4.112-2.624-5.616-4.272c-1.504-1.632-2.624-3.52-3.312-5.664c-0.72-2.16-1.056-4.624-1.056-7.456 c0-6.784,1.888-11.824,5.664-15.152s8.976-4.992,15.568-4.992c3.056,0,5.904,0.336,8.496,1.008s4.832,1.744,6.72,3.264 c1.872,1.504,3.36,3.424,4.4,5.744c1.056,2.336,1.6,5.136,1.6,8.4v1.92H97.12c0-3.264-0.576-5.776-1.712-7.552 c-1.152-1.744-3.088-2.64-5.776-2.64c-1.536,0-2.816,0.24-3.824,0.672c-1.008,0.448-1.84,1.04-2.448,1.776s-1.04,1.616-1.264,2.576 c-0.224,0.96-0.336,1.952-0.336,2.976c0,2.128,0.448,3.888,1.344,5.328c0.896,1.456,2.816,2.784,5.76,3.984l10.656,4.608 c2.624,1.152,4.752,2.352,6.416,3.616s2.992,2.592,3.984,4.032s1.712,3.008,2.08,4.752c0.384,1.712,0.576,3.648,0.576,5.744 c0,7.232-2.096,12.496-6.304,15.792c-4.192,3.296-10.032,4.96-17.504,4.96c-7.808,0-13.408-1.696-16.768-5.088 s-5.04-8.256-5.04-14.592v-2.784H80.8L80.8,167.008L80.8,167.008z"/> <path style="fill:#FFFFFF;" d="M124.528,137.776c0.464-4.368,1.536-8.144,3.152-11.28c1.648-3.12,4-5.584,7.056-7.344 c3.056-1.744,7.2-2.64,12.368-2.64c5.184,0,9.312,0.896,12.384,2.64c3.072,1.76,5.424,4.224,7.04,7.344 c1.648,3.136,2.688,6.912,3.184,11.28c0.464,4.384,0.72,9.168,0.72,14.352c0,5.232-0.256,10.048-0.72,14.384 c-0.496,4.352-1.552,8.096-3.184,11.232c-1.632,3.136-3.968,5.536-7.04,7.2c-3.072,1.664-7.2,2.496-12.384,2.496 c-5.168,0-9.312-0.848-12.368-2.496c-3.056-1.664-5.424-4.064-7.056-7.2c-1.632-3.136-2.688-6.88-3.152-11.232 c-0.48-4.336-0.72-9.152-0.72-14.384C123.808,146.96,124.048,142.176,124.528,137.776z M137.968,163.888 c0.224,3.232,0.672,5.856,1.344,7.872s1.632,3.488,2.88,4.416c1.248,0.928,2.912,1.392,4.944,1.392c2.064,0,3.696-0.464,4.96-1.392 c1.248-0.944,2.208-2.4,2.896-4.416c0.656-2.016,1.104-4.64,1.344-7.872c0.224-3.232,0.336-7.136,0.336-11.744 s-0.112-8.512-0.336-11.712c-0.24-3.2-0.688-5.824-1.344-7.872c-0.688-2.048-1.648-3.536-2.896-4.448 c-1.248-0.944-2.896-1.408-4.96-1.408c-2.032,0-3.68,0.464-4.944,1.408c-1.248,0.928-2.208,2.4-2.88,4.448 c-0.672,2.048-1.12,4.672-1.344,7.872s-0.336,7.104-0.336,11.712S137.744,160.656,137.968,163.888z"/> <path style="fill:#FFFFFF;" d="M217.024,164.816h0.192v-46.96h12.672V186.4H214.16l-16.912-48h-0.192v48h-12.688v-68.544h15.936 L217.024,164.816z"/> </g></symbol><symbol id="doc" viewBox="0 0 384 384"><title>doc</title><polygon style="fill:#EFEEEE;" points="64,0 64,384 288,384 384,288 384,0 "/><polygon style="fill:#ABABAB;" points="288,288 288,384 384,288 "/><polygon style="fill:#DEDEDD;" points="192,384 288,384 288,288 "/><path style="fill:#4E95D0;" d="M0,96v112h256V96L0,96L0,96z"/><g> <path style="fill:#FFFFFF;" d="M60.128,117.856c4.816,0,8.672,0.784,11.632,2.352c2.944,1.568,5.232,3.792,6.864,6.72 c1.648,2.912,2.736,6.4,3.312,10.512c0.576,4.096,0.864,8.672,0.864,13.712c0,6.016-0.352,11.248-1.088,15.696 c-0.736,4.432-2,8.112-3.792,10.992c-1.792,2.896-4.192,5.024-7.2,6.432c-3.04,1.424-6.848,2.128-11.456,2.128H37.472v-68.544 L60.128,117.856z M57.44,176.24c2.432,0,4.384-0.416,5.856-1.248s2.64-2.208,3.504-4.128c0.88-1.92,1.456-4.448,1.728-7.6 c0.288-3.12,0.432-7.024,0.432-11.696c0-3.904-0.144-7.328-0.384-10.288c-0.256-2.944-0.784-5.392-1.584-7.344 c-0.816-1.952-1.984-3.424-3.568-4.4c-1.568-0.992-3.664-1.504-6.288-1.504h-5.84v48.208L57.44,176.24z"/> <path style="fill:#FFFFFF;" d="M100.784,137.776c0.48-4.368,1.552-8.144,3.168-11.28c1.648-3.12,3.984-5.584,7.056-7.344 c3.056-1.744,7.2-2.64,12.368-2.64c5.184,0,9.312,0.896,12.384,2.64c3.056,1.76,5.424,4.224,7.04,7.344 c1.648,3.136,2.688,6.912,3.168,11.28c0.48,4.384,0.72,9.168,0.72,14.352c0,5.232-0.24,10.048-0.72,14.384 c-0.48,4.352-1.536,8.096-3.168,11.232s-3.984,5.536-7.04,7.2c-3.072,1.664-7.2,2.496-12.384,2.496 c-5.168,0-9.312-0.848-12.368-2.496c-3.072-1.664-5.424-4.064-7.056-7.2s-2.688-6.88-3.168-11.232 c-0.464-4.336-0.72-9.152-0.72-14.384C100.064,146.96,100.32,142.176,100.784,137.776z M114.24,163.888 c0.224,3.232,0.672,5.856,1.344,7.872s1.632,3.488,2.88,4.416s2.896,1.392,4.944,1.392s3.696-0.464,4.96-1.392 c1.248-0.944,2.208-2.4,2.88-4.416s1.12-4.64,1.344-7.872s0.336-7.136,0.336-11.744s-0.128-8.512-0.336-11.712 s-0.672-5.824-1.344-7.872s-1.632-3.536-2.88-4.448c-1.248-0.944-2.912-1.408-4.96-1.408s-3.68,0.464-4.944,1.408 c-1.248,0.928-2.208,2.4-2.88,4.448s-1.12,4.672-1.344,7.872c-0.24,3.2-0.336,7.104-0.336,11.712S114,160.656,114.24,163.888z"/> <path style="fill:#FFFFFF;" d="M191.984,130.112c-1.184-2.288-3.344-3.424-6.48-3.424c-1.712,0-3.136,0.464-4.272,1.408 c-1.12,0.928-2,2.416-2.64,4.496s-1.088,4.8-1.344,8.176c-0.256,3.36-0.368,7.472-0.368,12.336c0,5.184,0.176,9.376,0.512,12.576 c0.352,3.2,0.912,5.664,1.648,7.44s1.664,2.96,2.784,3.552c1.12,0.608,2.416,0.928,3.888,0.928c1.216,0,2.336-0.208,3.408-0.624 c1.04-0.416,1.968-1.248,2.736-2.496s1.376-3.008,1.824-5.28c0.432-2.272,0.672-5.264,0.672-8.976h13.808 c0,3.696-0.288,7.232-0.864,10.56c-0.576,3.328-1.648,6.24-3.216,8.736c-1.568,2.48-3.76,4.432-6.624,5.84 c-2.848,1.408-6.528,2.128-11.072,2.128c-5.184,0-9.312-0.848-12.384-2.496c-3.056-1.664-5.424-4.064-7.04-7.2 c-1.648-3.136-2.688-6.88-3.168-11.232c-0.48-4.336-0.72-9.152-0.72-14.384c0-5.184,0.24-9.968,0.72-14.352 c0.48-4.368,1.536-8.144,3.168-11.28c1.632-3.12,3.984-5.584,7.04-7.344c3.072-1.744,7.2-2.64,12.384-2.64 c4.928,0,8.816,0.8,11.664,2.4c2.848,1.6,4.992,3.632,6.384,6.096c1.392,2.464,2.288,5.12,2.64,7.968 c0.336,2.848,0.528,5.52,0.528,8.016h-13.84C193.76,136,193.168,132.368,191.984,130.112z"/> </g></symbol><symbol id="txt" viewBox="0 0 384 384"><title>txt</title><polygon style="fill:#EFEEEE;" points="64,0 64,384 288,384 384,288 384,0 "/><polygon style="fill:#ABABAB;" points="288,288 288,384 384,288 "/><polygon style="fill:#DEDEDD;" points="192,384 288,384 288,288 "/><path style="fill:#15498A;" d="M0,96v112h256V96L0,96L0,96z"/><g> <path style="fill:#FFFFFF;" d="M76.928,129.184H61.952V186.4H48.128v-57.216H33.152v-11.328h43.776V129.184z"/> <path style="fill:#FFFFFF;" d="M104.88,117.856l8.832,22.176l8.736-22.176h15.056l-15.92,33.888l17.072,34.656h-15.552 l-9.888-23.232l-9.792,23.232H88.272l17.072-34.656l-15.728-33.888H104.88z"/> <path style="fill:#FFFFFF;" d="M193.76,129.184h-14.992V186.4H164.96v-57.216h-14.976v-11.328h43.776V129.184z"/> </g></symbol><symbol id="xls" viewBox="0 0 384 384"><title>xls</title><polygon style="fill:#EFEEEE;" points="64,0 64,384 288,384 384,288 384,0 "/><polygon style="fill:#ABABAB;" points="288,288 288,384 384,288 "/><polygon style="fill:#DEDEDD;" points="192,384 288,384 288,288 "/><path style="fill:#61B565;" d="M0,96v112h256V96H0z"/><g> <path style="fill:#FFFFFF;" d="M49.184,117.856l8.832,22.176l8.736-22.176h15.072l-15.936,33.888L82.976,186.4H67.424 l-9.872-23.232L47.744,186.4H32.576l17.088-34.656L33.92,117.856L49.184,117.856L49.184,117.856z"/> <path style="fill:#FFFFFF;" d="M97.952,117.856h13.824v57.216h24.464V186.4H97.952L97.952,117.856L97.952,117.856z"/> <path style="fill:#FFFFFF;" d="M163.136,167.008c0,1.6,0.128,3.056,0.368,4.352c0.272,1.312,0.736,2.416,1.44,3.312 c0.704,0.912,1.664,1.616,2.848,2.128c1.168,0.496,2.672,0.768,4.448,0.768c2.128,0,4.016-0.688,5.712-2.064 c1.68-1.376,2.544-3.52,2.544-6.384c0-1.536-0.224-2.864-0.624-3.984c-0.416-1.12-1.104-2.128-2.064-3.008 c-0.976-0.912-2.24-1.712-3.792-2.448s-3.504-1.488-5.808-2.256c-3.056-1.024-5.712-2.16-7.968-3.376 c-2.24-1.2-4.112-2.624-5.616-4.272c-1.504-1.632-2.608-3.52-3.312-5.664c-0.704-2.16-1.056-4.624-1.056-7.456 c0-6.784,1.888-11.824,5.664-15.152c3.76-3.328,8.96-4.992,15.552-4.992c3.072,0,5.904,0.336,8.496,1.008s4.832,1.744,6.72,3.264 c1.888,1.504,3.36,3.424,4.416,5.744c1.04,2.336,1.584,5.136,1.584,8.4v1.92H179.44c0-3.264-0.576-5.776-1.712-7.552 c-1.152-1.744-3.072-2.64-5.76-2.64c-1.536,0-2.816,0.24-3.84,0.672c-1.008,0.448-1.84,1.04-2.448,1.776 c-0.608,0.736-1.008,1.584-1.232,2.544c-0.24,0.96-0.336,1.952-0.336,2.976c0,2.128,0.448,3.888,1.344,5.328 c0.896,1.456,2.816,2.784,5.76,3.984l10.656,4.608c2.624,1.152,4.768,2.352,6.416,3.616c1.664,1.248,3.008,2.592,3.984,4.032 c0.992,1.44,1.68,3.008,2.064,4.752c0.384,1.712,0.576,3.648,0.576,5.744c0,7.232-2.096,12.496-6.288,15.792 c-4.192,3.296-10.032,4.96-17.52,4.96c-7.808,0-13.392-1.696-16.768-5.088c-3.36-3.392-5.024-8.256-5.024-14.592v-2.784h13.824 L163.136,167.008z"/> </g></symbol><symbol id="csv" viewBox="0 0 384 384"><title>csv</title><polygon style="fill:#EFEEEE;" points="64,0 64,384 288,384 384,288 384,0 "/><polygon style="fill:#ABABAB;" points="288,288 288,384 384,288 "/><polygon style="fill:#DEDEDD;" points="192,384 288,384 288,288 "/><path style="fill:#448E47;" d="M0,96v112h256V96L0,96L0,96z"/><g> <path style="fill:#FFFFFF;" d="M64.32,130.112c-1.184-2.288-3.344-3.424-6.48-3.424c-1.728,0-3.152,0.464-4.272,1.408 c-1.12,0.928-2,2.416-2.64,4.496s-1.088,4.8-1.344,8.176c-0.272,3.36-0.384,7.472-0.384,12.336c0,5.184,0.176,9.376,0.528,12.576 c0.336,3.2,0.896,5.664,1.632,7.44s1.664,2.96,2.784,3.552c1.12,0.608,2.416,0.928,3.888,0.928c1.216,0,2.352-0.208,3.408-0.624 s1.968-1.248,2.736-2.496c0.784-1.248,1.392-3.008,1.824-5.28c0.448-2.272,0.672-5.264,0.672-8.976H80.48 c0,3.696-0.288,7.232-0.864,10.56s-1.664,6.24-3.216,8.736c-1.584,2.48-3.776,4.432-6.624,5.84 c-2.848,1.408-6.544,2.128-11.088,2.128c-5.168,0-9.312-0.848-12.368-2.496c-3.072-1.664-5.424-4.064-7.056-7.2 s-2.688-6.88-3.168-11.232c-0.464-4.336-0.72-9.152-0.72-14.384c0-5.184,0.256-9.968,0.72-14.352 c0.48-4.368,1.552-8.144,3.168-11.28c1.648-3.12,3.984-5.584,7.056-7.344c3.056-1.744,7.2-2.64,12.368-2.64 c4.944,0,8.816,0.8,11.664,2.4c2.848,1.6,4.976,3.632,6.368,6.096s2.304,5.12,2.64,7.968c0.352,2.848,0.528,5.52,0.528,8.016H66.08 C66.08,136,65.488,132.368,64.32,130.112z"/> <path style="fill:#FFFFFF;" d="M109.072,167.008c0,1.6,0.144,3.056,0.384,4.352c0.272,1.312,0.736,2.416,1.44,3.312 c0.704,0.912,1.664,1.616,2.848,2.128c1.168,0.496,2.672,0.768,4.448,0.768c2.128,0,4.016-0.688,5.712-2.064 c1.68-1.376,2.544-3.52,2.544-6.384c0-1.536-0.224-2.864-0.624-3.984c-0.416-1.12-1.104-2.128-2.064-3.008 c-0.976-0.912-2.24-1.712-3.792-2.448s-3.504-1.488-5.808-2.256c-3.056-1.024-5.712-2.16-7.968-3.376 c-2.24-1.2-4.112-2.624-5.616-4.272c-1.504-1.632-2.608-3.52-3.312-5.664c-0.704-2.16-1.056-4.624-1.056-7.456 c0-6.784,1.888-11.824,5.664-15.152c3.76-3.328,8.96-4.992,15.552-4.992c3.072,0,5.904,0.336,8.496,1.008s4.832,1.744,6.72,3.264 c1.888,1.504,3.36,3.424,4.416,5.744c1.04,2.336,1.584,5.136,1.584,8.4v1.92h-13.232c0-3.264-0.576-5.776-1.712-7.552 c-1.152-1.744-3.072-2.64-5.76-2.64c-1.536,0-2.816,0.24-3.84,0.672c-1.008,0.448-1.84,1.04-2.448,1.776s-1.04,1.616-1.264,2.576 c-0.24,0.96-0.336,1.952-0.336,2.976c0,2.128,0.448,3.888,1.344,5.328c0.896,1.456,2.816,2.784,5.76,3.984l10.656,4.608 c2.624,1.152,4.768,2.352,6.416,3.616c1.664,1.248,3.008,2.592,3.984,4.032c0.992,1.44,1.68,3.008,2.064,4.752 c0.384,1.712,0.576,3.648,0.576,5.744c0,7.232-2.096,12.496-6.288,15.792c-4.192,3.296-10.032,4.96-17.52,4.96 c-7.808,0-13.392-1.696-16.768-5.088c-3.36-3.392-5.024-8.256-5.024-14.592v-2.784h13.824L109.072,167.008L109.072,167.008z"/> <path style="fill:#FFFFFF;" d="M177.344,168.544h0.304l10.176-50.688h14.32L186.4,186.4h-17.76l-15.728-68.544h14.784 L177.344,168.544z"/> </g></symbol><symbol id="pdf" viewBox="0 0 384 384"><title>pdf</title><polygon style="fill:#EFEEEE;" points="64,0 64,384 288,384 384,288 384,0 "/><polygon style="fill:#ABABAB;" points="288,288 288,384 384,288 "/><polygon style="fill:#DEDEDD;" points="192,384 288,384 288,288 "/><path style="fill:#CB5641;" d="M0,96v112h256V96H0z"/><g> <path style="fill:#FFFFFF;" d="M60.992,117.856c3.52,0,6.464,0.592,8.88,1.776s4.336,2.736,5.808,4.656s2.528,4.112,3.168,6.576 c0.624,2.448,0.96,4.976,0.96,7.536c0,3.52-0.528,6.592-1.584,9.216c-1.056,2.624-2.528,4.768-4.416,6.416 c-1.888,1.664-4.176,2.928-6.864,3.744c-2.688,0.816-5.664,1.264-8.928,1.264h-7.392v27.36H36.8v-68.544L60.992,117.856z M56.768,148.896c2.752,0,4.992-0.848,6.672-2.512c1.696-1.664,2.544-4.256,2.544-7.776c0-3.456-0.736-6.08-2.208-7.872 c-1.472-1.776-3.936-2.688-7.392-2.688h-5.76v20.848L56.768,148.896L56.768,148.896z"/> <path style="fill:#FFFFFF;" d="M119.552,117.84c4.816,0,8.672,0.8,11.632,2.368c2.944,1.568,5.232,3.792,6.864,6.72 c1.648,2.912,2.736,6.4,3.312,10.512c0.576,4.096,0.864,8.672,0.864,13.712c0,6.016-0.352,11.248-1.088,15.696 c-0.736,4.432-2,8.112-3.792,10.992c-1.792,2.896-4.192,5.024-7.2,6.432s-6.816,2.112-11.424,2.112H96.896V117.84L119.552,117.84 L119.552,117.84z M116.864,176.24c2.432,0,4.384-0.416,5.856-1.248s2.64-2.208,3.504-4.128c0.88-1.92,1.456-4.448,1.728-7.6 c0.288-3.12,0.432-7.024,0.432-11.696c0-3.904-0.144-7.328-0.384-10.288c-0.256-2.944-0.784-5.392-1.584-7.344 c-0.816-1.952-1.984-3.424-3.568-4.4c-1.568-0.992-3.664-1.504-6.288-1.504h-5.84v48.208L116.864,176.24z"/> <path style="fill:#FFFFFF;" d="M199.232,117.856v11.328h-24.48v16.128h23.04v11.328h-23.04v29.76h-13.824v-68.544L199.232,117.856z "/> </g></symbol></svg>';
			$origmime = mime_content_type($dir . '/' . $filer);
			$directorymime = array('directory');
			$mediaarray = array('text/x-php');
			$htmlarray = array('text/html');
			$pngarray = array('image/png');
			$jpgarray = array('image/jpg', 'image/jpeg');
			$txtarray = array('text/plain');
			$defaultarray = array('text/plain', 'image/x-icon', 'inode/x-empty');
			foreach($mediaarray as $mediaicon) {
				if($origmime == $mediaicon) {
					$img = '<svg class="icon" style="width: 32px;height:32px;">
								<use xlink:href="#php" />
							</svg> ';
					echo $img;

				} else {
					//continue...
				}
			}

			foreach($directorymime as $directoryicon) {
				if($origmime == $directoryicon) {
					$img = '<svg class="icon" style="width: 32px;height:32px;">
								<use xlink:href="#folder" />
							</svg> ';
					echo $img;
				} else {
					//continue...
				}
			}

			foreach($htmlarray as $htmlicon) {
				if($origmime == $htmlicon) {
					$img = '<svg class="icon" style="width: 32px;height:32px;">
								<use xlink:href="#html" />
							</svg> ';
					echo $img;
				} else {
					//continue...
				}
			}

			foreach($pngarray as $pngicon) {
				if($origmime == $pngicon) {
					$img = '<svg class="icon" style="width: 32px;height:32px;">
								<use xlink:href="#png" />
							</svg> ';
					echo $img;
				} else {
					//continue...
				}
			}

			foreach($jpgarray as $jpgicon) {
				if($origmime == $jpgicon) {
					$img = '<svg class="icon" style="width: 32px;height:32px;">
								<use xlink:href="#jpg" />
							</svg> ';
					echo $img;
				} else {
					//continue...
				}
			}

			foreach($defaultarray as $defaulticon) {
				if($origmime == $defaulticon) {
					$img = '<svg class="icon" style="width: 32px;height:32px;">
								<use xlink:href="#txt" />
							</svg> ';
					echo $img;
				} else {
					//continue...
				}
			}

			if(is_dir($dir . '/' . $filer)) {
				// $filesize = human_filesize(get_total_size( $dir . '/' . $filer ));
				$filesize = 'hoi';
			} else {
				$filesize = human_filesize(filesize( $dir . '/' . $filer ));
			}
			$upperpath = encrypt_url($dir . '/' . $filer);
			echo "<strong class='blackbadge'>".$filesize."</strong>";
			echo '
			<td class="filename">
				<p><a class="strong" style="font-weight: bold;" href="?page=backup_restore&dir='. $upperpath . '">'. $filer .'</a></p>
			</td><td class="filename">
				<i class="dashicons dashicons-trash"></i>
			</td>
		</tr>';
		} else {
		}
	}
	echo '</tbody>
	<tfoot>
	<tr>';
	if($_GET['dir']) {
		echo '<th scope="row" class="check-column">
		<input class="markall" name="delete_comments[]" value="1" type="checkbox">
		</th><th scope="col" id="author" class="manage-column column-author"><span>Type / Size</span></th><th scope="col" id="comment" class="manage-column column-comment column-primary">Filename</th><th scope="col" id="date" class="column-date"><span>Options</span></th>';
	} else {
		echo '
	
	<th scope="col" id="author" class="manage-column column-author" style="width: 220px;"><span>'. __('Backup options', 'backup') .'</span></th><th scope="col" id="comment" class="column-author">'. __('Creation date', 'backup') .'</th><th scope="col" id="comment" class="manage-column column-primary">'. __('State', 'backup') .'</th><th scope="col" id="comment" class="column-author">'. __('Weight', 'backup') .'</th><th scope="col" id="comment" class="column-author">'. __('Process', 'backup') .'</th><th scope="col" id="date" class="manage-column column-date"><span>'. __('Options', 'backup') .'</span></th>'; }
	echo '
		</tr>
	</tfoot>
</table>';
}

