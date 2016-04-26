<?php
error_reporting(E_ALL);
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
  throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
set_error_handler("exception_error_handler");

$ftp_server = "impaq.home.pl";
$ftp_username = "ftphomepl";
$ftp_userpass = "e7r3bFXNqPmSO8SQWpDC";
$remote_dir = "/kariera/wordpress/wp-content/themes/wpjobus/js/";
$local_dir = "."

?>

<!DOCTYPE html>
<html>
<head>
	<title><?php echo basename(__FILE__); ?></title>
</head>
<body>

<?php

$ftp_conn = ftp_connect($ftp_server) or die("Could not connect to $ftp_server");
$login = ftp_login($ftp_conn, $ftp_username, $ftp_userpass);

$remote_dir = trim($remote_dir,"/");
$file_list = ftp_nlist($ftp_conn, $remote_dir);

echo "<pre>";

echo "remote_dir: <b>$remote_dir</b>/\n\n";

// echo "<table border=1>";

$files_data = array();

foreach ($file_list as $file_path) {
	$size = ftp_size($ftp_conn, $file_path);
	$timestamp = ftp_mdtm($ftp_conn, $file_path);
	$date = date("Y-m-d H:i:s", $timestamp);
	$file_name = substr($file_path, strlen($remote_dir)+1);
	array_push2($files_data, $file_name, array(
		'remote_date' => $date,
		'remote_size' => $size,
	));
}

foreach (scandir($local_dir) as $item) {
	if ($item == "." or $item == "..") continue;
	$size = filesize($item);
	$timestamp = filemtime($item);
	$date = date("Y-m-d H:i:s", $timestamp);
	array_push2($files_data, $item, array(
		'local_date' => $date,
		'local_size' => filesize($item),
	));
}

foreach ($files_data as $file_name => &$file_data) {
	if (!array_key_exists('remote_size', $file_data)) continue;
	if ($file_data['remote_size'] == $file_data['local_size']) {
		$file_data['local_checksum'] = md5_file($file_name);
		ob_start();
		$result = ftp_get($ftp_conn, "php://output", "$remote_dir/$file_name", FTP_BINARY);
		$remote_content = ob_get_clean();
		$file_data['remote_checksum'] = md5($remote_content);
	}
}

foreach ($files_data as $file_name => &$file_data) {
	if (!array_key_exists('remote_size', $file_data)) continue;
	if (
		$file_data['local_size'] == $file_data['remote_size']
		and
		$file_data['local_checksum'] == $file_data['remote_checksum']
		)
	{
		continue;
	}
	$ftp_put_result = ftp_put($ftp_conn, "$remote_dir/$file_name", $file_name, FTP_BINARY);
	echo print_info($file_name, $ftp_put_result);
}

echo "\n\n";


print_r($files_data);


echo "</pre>";


?>

</body>
</html>

<?php

function array_push2(&$arr, $key, $items) {
	if (!array_key_exists($key, $arr)) {
		$arr[$key] = array();
	}
	$arr[$key] = array_merge($arr[$key], $items);
}

function print_info($msg, $status) {
	$color = ($status) ? "darkgreen" : "red";
	return "<span style='color: $color'>$msg</span>";
}

?>