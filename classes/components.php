<?php
    global $plugin_url;
    global $timestamp;
    if($_GET['read']) {
        function encrypt_url($string)
        {
            $stringin = str_replace('\\', '/', $string);
            $filepathhash = substr(md5($stringin), 0, 8);
            return $filepathhash;
        }

        function decrypt_url($string)
        {
            $db = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            $selecthash = "select * from `awp_backupfiles` WHERE `url` = '$string'";
            $selecthash_query = mysqli_query($db, $selecthash);
            $result = mysqli_fetch_array($selecthash_query);
            return $result['filename'];
        }

        function decrypt_timestamp($string)
        {
            $db = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            $selecthash = "select * from `awp_backupfiles` WHERE `url` = '$string'";
            $selecthash_query = mysqli_query($db, $selecthash);
            $result = mysqli_fetch_array($selecthash_query);
            return $result['backuptime'];
        }

        function decrypt_backupname($string)
        {
            $db = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            $selecthash = "select * from `awp_backup` WHERE `times` = '$string'";
            $selecthash_query = mysqli_query($db, $selecthash);
            $result = mysqli_fetch_array($selecthash_query);
            return $result['backupname'];
        }

        function decrypt_backupstate($string)
        {
            $db = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            $selecthash = "select * from `awp_backup` WHERE `times` = '$string'";
            $selecthash_query = mysqli_query($db, $selecthash);
            $result = mysqli_fetch_array($selecthash_query);
            return $result['state'];
        }

        function decrypt_process($string)
        {
            $db = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            $selecthash = "select * from `awp_backup` WHERE `times` = '$string'";
            $selecthash_query = mysqli_query($db, $selecthash);
            $result = mysqli_fetch_array($selecthash_query);
            if ($result['started'] == 'wp-user') {
                $user_info = get_userdata($result['user']);
                $username = $user_info->user_login;
                $returner = '<i title="von Benutzer ' . $username . ' ausgeführt" class="dashicons-admin-users dashicons"></i>&nbsp;&nbsp;' . $username;

            } else {
                $returner = '<i title="von Wordpress Cron ausgeführt" class="dashicons-wordpress dashicons"></i>&nbsp;&nbsp;WP Cron';
            }
            return $returner;
        }

        function encrypt_rwt($timestamp)
        {
            $db = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            $selecthash = "select * from `awp_backupfiles` WHERE `backuptime` = '$timestamp' order by `id` asc LIMIT 0, 1";
            $selecthash_query = mysqli_query($db, $selecthash);
            $result = mysqli_fetch_array($selecthash_query);
            return $result['url'];
        }

        function human_filesize($bytes, $decimals = 2)
        {
            $units = array('B', 'KB', 'MB', 'GB', 'TB');

            $bytes = max($bytes, 0);
            $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
            $pow = min($pow, count($units) - 1);

            // Uncomment one of the following alternatives
            $bytes /= pow(1024, $pow);

            return round($bytes, 2) . ' ' . $units[$pow];
        }

        function get_total_size($path)
        {
            $bytestotal = 0;
            $path = realpath($path);
            if ($path !== false && $path != '' && file_exists($path)) {
                foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object) {
                    $bytestotal += $object->getSize();
                }
            }
            return $bytestotal;
        }
    } else {
        function encrypt_url($string)
        {
            $stringin = str_replace('\\', '/', $string);
            $filepathhash = substr(md5($stringin), 0, 8);
            return $filepathhash;
        }

        function decrypt_url($string)
        {
            $db = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            $selecthash = "select * from `awp_backupfiles` WHERE `url` = '$string'";
            $selecthash_query = mysqli_query($db, $selecthash);
            $result = mysqli_fetch_array($selecthash_query);
            return $result['filename'];
        }

        function decrypt_timestamp($string)
        {
            $db = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            $selecthash = "select * from `awp_backupfiles` WHERE `url` = '$string'";
            $selecthash_query = mysqli_query($db, $selecthash);
            $result = mysqli_fetch_array($selecthash_query);
            return $result['backuptime'];
        }

        function decrypt_backupname($string)
        {
            $db = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            $selecthash = "select * from `awp_backup` WHERE `times` = '$string'";
            $selecthash_query = mysqli_query($db, $selecthash);
            $result = mysqli_fetch_array($selecthash_query);
            return $result['backupname'];
        }

        function decrypt_backupstate($string)
        {
            $db = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            $selecthash = "select * from `awp_backup` WHERE `times` = '$string'";
            $selecthash_query = mysqli_query($db, $selecthash);
            $result = mysqli_fetch_array($selecthash_query);
            return $result['state'];
        }

        function decrypt_process($string)
        {
            $db = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            $selecthash = "select * from `awp_backup` WHERE `times` = '$string'";
            $selecthash_query = mysqli_query($db, $selecthash);
            $result = mysqli_fetch_array($selecthash_query);
            if ($result['started'] == 'wp-user') {
                $user_info = get_userdata($result['user']);
                $username = $user_info->user_login;
                $returner = '<i title="von Benutzer ' . $username . ' ausgeführt" class="dashicons-admin-users dashicons"></i>&nbsp;&nbsp;' . $username;

            } else {
                $returner = '<i title="von Wordpress Cron ausgeführt" class="dashicons-wordpress dashicons"></i>&nbsp;&nbsp;WP Cron';
            }
            return $returner;
        }

        function encrypt_rwt($timestamp)
        {
            $db = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            $selecthash = "select * from `awp_backupfiles` WHERE `backuptime` = '$timestamp' order by `id` asc LIMIT 0, 1";
            $selecthash_query = mysqli_query($db, $selecthash);
            $result = mysqli_fetch_array($selecthash_query);
            return $result['url'];
        }

        function human_filesize($bytes, $decimals = 2)
        {
            $units = array('B', 'KB', 'MB', 'GB', 'TB');

            $bytes = max($bytes, 0);
            $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
            $pow = min($pow, count($units) - 1);

            // Uncomment one of the following alternatives
            $bytes /= pow(1024, $pow);

            return round($bytes, 2) . ' ' . $units[$pow];
        }

        function get_total_size($path)
        {
            $bytestotal = 0;
            $path = realpath($path);
            if ($path !== false && $path != '' && file_exists($path)) {
                foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object) {
                    $bytestotal += $object->getSize();
                }
            }
            return $bytestotal;
        }

    if($_GET['filer']) { } else {
        echo '<link rel="stylesheet" type="text/css" href="' . $plugin_url . '/css/style.css">';

        echo '
        <div class="wrap">
            <h1 class="wp-heading-inline">Wordpress Backup</h1>
            <a href="#" class="full-backup-now page-title-action">Backup erstellen</a>
            <hr class="wp-header-end">
            <ul class="subsubsub">
                <li class="all"><a href="admin.php?page=backup"';
        if ($_GET['page'] == 'backup') {
            echo ' class="current"';
        }
        echo '>Backup</a> |</li>
                <li class="restore"><a href="admin.php?page=backup_restore"';
        if ($_GET['page'] == 'backup_restore') {
            echo ' class="current"';
        }
        echo '>Backup & Wiederherstellung</a> |</li>
                <li class="settings"><a href="admin.php?page=backup_settings"';
        if ($_GET['page'] == 'backup_settings') {
            echo ' class="current"';
        }
        echo '>Einstellungen</a></li>
            </ul><br /><br />
            <div id="backup-state"></div><br />';
    }}