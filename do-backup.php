<?php
require_once('../../../wp-load.php');
include '../../../wp-config.php';
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
	function recurse_copy( $src, $dst, $timestamp ) {
		$dir = opendir( $src );
		while ( false !== ( $file = readdir( $dir ) ) ) {
			if ( ( $file != '.' ) && ( $file != '..' ) && ( $file != '.idea' ) && ( $file != 'wp-backups' ) ) {
				if ( is_dir( $src . $file . '/' ) ) {
					if ( strpos( $src . $file, 'wp-backups/' ) !== false ) {
						continue;
					} else {
						global $db;
						$str     = str_replace( '\\', '/', $dst );
						$bodytag = str_replace( "//", "/", $str . $file );

						$filepath = $bodytag;
						mkdir( $dst . $file );
						$filepathhash         = substr( md5( $filepath ), 0, 8 );
						$insertfiledata       = "insert into `awp_backupfiles` (`filename`, `url`, `backuptime`) values ('$filepath', '$filepathhash', '$timestamp')";
						$insertfiledata_query = mysqli_query( $db, $insertfiledata );
						recurse_copy( $src . '/' . $file . '/', $dst . '/' . $file . '/', $timestamp );
					}
				} else {
					global $db;
					$str                  = str_replace( '\\', '/', $dst );
					$bodytag              = str_replace( "//", "/", $str . $file );
					$filepath             = $bodytag;
					$filepathhash         = substr( md5( $filepath ), 0, 8 );
					$insertfiledata       = "insert into `awp_backupfiles` (`filename`, `url`, `backuptime`) values ('$filepath', '$filepathhash', '$timestamp')";
					$insertfiledata_query = mysqli_query( $db, $insertfiledata );

					if ( strpos( $src . $file, 'wp-backups/' ) !== false ) {
						continue;
					} else {
						copy( $src . '/' . $file, $dst . '/' . $file );
					}
				}
			}
		}
		closedir( $dir );
	}

	function do_this_in_an_hour() {
		global $wpdb;
		global $db;
		$pathe     = ABSPATH;
		$timestamp = current_time( 'timestamp' );
		if ( ! is_dir( $pathe . 'wp-backups' . '/' . $timestamp . '/' ) ) {
			mkdir( $pathe . 'wp-backups' . '/' . $timestamp . '/' );
		}
		$str1                 = str_replace( '\\', '/', ABSPATH . 'wp-backups/' . $timestamp );
		$str                  = str_replace( '/', '/', $str1 );
		$filepath             = $str;
		$filepathhash         = substr( md5( $filepath ), 0, 8 );
		$insertfiledata       = "insert into `awp_backupfiles` (`filename`, `url`, `backuptime`) values ('$filepath', '$filepathhash', '$timestamp')";
		$insertfiledata_query = mysqli_query( $db, $insertfiledata );
		recurse_copy( $pathe, $pathe . "wp-backups" . '/' . $timestamp . '/', $timestamp );

		$stashbackup       = "update `awp_backup` set `state` = 'finished' where `times` = '$timestamp'";
		$stashbackup_query = mysqli_query( $db, $stashbackup );
	}


	$inc       = $_POST['inc'];
	$timestamp = current_time( 'timestamp' );

	$selectonce       = "select * from `awp_backup` where `state` = 'create'";
	$selectonce_query = mysqli_query( $db, $selectonce );

	if ( mysqli_num_rows( $selectonce_query ) == 0 ) {
		if ( $_GET['doing_wp_cron'] ) {
			$process = 'wp-cron';
		} else {
			$process = 'wp-user';
		}
		$userid             = get_current_user_id();
		$insertbackup       = "insert into `awp_backup` (`backupname`, `times`, `user`, `state`, `started`) values ('$inc', '$timestamp', '$userid', 'create', '$process')";
		$insertbackup_query = mysqli_query( $db, $insertbackup );
		echo '<script>checkbase();</script>';
	} else {
		echo 'once-error';
		exit();
	}
	add_action( $inc . 'backup_now', do_this_in_an_hour() );
	wp_schedule_single_event( time(), $inc . 'backup_now' );


//Call the core function

//Core function
	function backup_tables( $host, $user, $pass, $dbname, $tables = '*' ) {
		$link = mysqli_connect( $host, $user, $pass, $dbname );

		// Check connection
		if ( mysqli_connect_errno() ) {
			echo "Failed to connect to MySQL: " . mysqli_connect_error();
			exit;
		}

		mysqli_query( $link, "SET NAMES 'utf8'" );

		//get all of the tables
		if ( $tables == '*' ) {
			$tables = array();
			$result = mysqli_query( $link, 'SHOW TABLES' );
			while ( $row = mysqli_fetch_row( $result ) ) {
				$tables[] = $row[0];
			}
		} else {
			$tables = is_array( $tables ) ? $tables : explode( ',', $tables );
		}

		$return = '';
		//cycle through
		foreach ( $tables as $table ) {
			$result     = mysqli_query( $link, 'SELECT * FROM ' . $table );
			$num_fields = mysqli_num_fields( $result );
			$num_rows   = mysqli_num_rows( $result );

			$return  .= 'DROP TABLE IF EXISTS ' . $table . ';';
			$row2    = mysqli_fetch_row( mysqli_query( $link, 'SHOW CREATE TABLE ' . $table ) );
			$return  .= "\n\n" . $row2[1] . ";\n\n";
			$counter = 1;

			//Over tables
			for ( $i = 0; $i < $num_fields; $i ++ ) {   //Over rows
				while ( $row = mysqli_fetch_row( $result ) ) {
					if ( $counter == 1 ) {
						$return .= 'INSERT INTO ' . $table . ' VALUES(';
					} else {
						$return .= '(';
					}

					//Over fields
					for ( $j = 0; $j < $num_fields; $j ++ ) {
						$row[ $j ] = addslashes( $row[ $j ] );
						$row[ $j ] = str_replace( "\n", "\\n", $row[ $j ] );
						if ( isset( $row[ $j ] ) ) {
							$return .= '"' . $row[ $j ] . '"';
						} else {
							$return .= '""';
						}
						if ( $j < ( $num_fields - 1 ) ) {
							$return .= ',';
						}
					}

					if ( $num_rows == $counter ) {
						$return .= ");\n";
					} else {
						$return .= "),\n";
					}
					++ $counter;
				}
			}
			$return .= "\n\n\n";
		}

		//save file
		global $timestamp;
		$fileout = md5( implode( ',', $tables ) );
		$fileName = '../../../wp-backups/'. $timestamp . '/db-backup-' . $timestamp . '-' . $fileout . '.sql';
		$handle   = fopen( $fileName, 'w+' );
		fwrite( $handle, $return );
		if ( fclose( $handle ) ) {
			global $db;
			global $timestamp;
			$insertsqldump       = "update `awp_backup` set `package` = '$timestamp/db-backup-$timestamp-$fileout.sql' WHERE `times` = '$timestamp'";
			$insertsqldump_query = mysqli_query( $db, $insertsqldump );
		}
	}
	backup_tables( $wpdb->dbhost, $wpdb->dbuser, $wpdb->dbpassword, $wpdb->dbname, '*' );

}

  /*  if (!wp_next_scheduled('cron_' . $inc . 'backup')) {
        // wp_schedule_event(time(), 'hourly', $inc . 'backup');
        wp_schedule_single_event(current_time( 'timestamp' ) + 30, 'cron_' . $inc . 'backup');
    }
    add_action('cron_' . $inc . 'backup', 'do_timebackup', array(
        $inc
    ));
    // wp_schedule_single_event( time() + 30, $inc . 'backup', array(rand(10,10000)) );
    // wp_schedule_single_event( current_time( 'timestamp' ), $inc . 'backup', array(rand(10,10000)) );

function do_timebackup($inc) {
	$empfaenger = 'mario.freuler@snk.ch';
	$betreff = 'Der Betreff';
	$nachricht = 'Hallo';
	$header = 'From: mario.freuler@snk.ch' . "\r\n" .
	          'Reply-To: webmaster@example.com' . "\r\n" .
	          'X-Mailer: PHP/' . phpversion();

	mail($empfaenger, $betreff, $nachricht, $header);
    add_action('cron_' . $inc . 'backup', 'do_this_in_an_hour_cron', 10, 0);
}*/
  /*
	if (! wp_next_scheduled ( 'my_hourly_event' )) {
		wp_schedule_event(time(), 'hourly', 'my_hourly_event');

	}


function my_hourly_event() {
	$empfaenger = 'mario.freuler@snk.ch';
	$betreff = 'Der Betreff';
	$nachricht = 'Hallo';
	$header = 'From: mario.freuler@snk.ch' . "\r\n" .
	          'Reply-To: webmaster@example.com' . "\r\n" .
	          'X-Mailer: PHP/' . phpversion();

	mail($empfaenger, $betreff, $nachricht, $header);
}

function do_this_hourly() {
	$empfaenger = 'mario.freuler@snk.ch';
	$betreff = 'Der Betreff';
	$nachricht = 'Hallo';
	$header = 'From: mario.freuler@snk.ch' . "\r\n" .
	          'Reply-To: webmaster@example.com' . "\r\n" .
	          'X-Mailer: PHP/' . phpversion();

	mail($empfaenger, $betreff, $nachricht, $header);
}

*/

//Add Interval [ Day ]
