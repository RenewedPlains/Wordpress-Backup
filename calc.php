<?php
include('../../../wp-load.php');
include('classes/componentfunc.php');

echo human_filesize(get_total_size(decrypt_url($_GET['path'])));