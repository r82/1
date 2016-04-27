<?php
error_reporting(E_ALL);
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
  throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
set_error_handler("exception_error_handler");

session_start();
if (isset($_POST) and array_key_exists('session_unset', $_POST)) {
	session_unset();
}

print_r($_POST);

if (isset($_POST) and array_key_exists('clear_selected_connections', $_POST)) {
	foreach ($_POST['connections_ids'] as $connection_id) {
		unset($_SESSION['connections_params'][$connection_id]);
	}
}

if (!array_key_exists('connections_params', $_SESSION)) $_SESSION['connections_params'] = array();
$connection_params = array();
if (!array_key_exists('connection_params', $_SESSION)) {
	$_SESSION['connection_params'] = array();
}
if (isset($_POST) and array_key_exists('submit_connection', $_POST)) {
	foreach(array('ftp_server', 'ftp_username', 'ftp_userpass', 'remote_dir', 'local_dir', 'filter') as $key) {
		if (!array_key_exists($key, $_POST)) continue;
		$_POST[$key] = trim($_POST[$key]);
		$connection_params[$key] = $_POST[$key];
	}
	$connection_params['remote_dir'] = preg_replace("`^\d:`", '', $connection_params['remote_dir']);
	$connection_params['remote_dir'] = "/".trim($connection_params['remote_dir'], "/")."/";
	$connection_params['local_dir'] = str_replace("\\", "/", $connection_params['local_dir']);
	$_SESSION['connection_params'] = $connection_params;
	$found_connection_params = false;
	foreach ($_SESSION['connections_params'] as &$stored_connections_params) {
		if ($connection_params == $stored_connections_params) {
			$found_connection_params = true;
			break;
		}
	}
	if(
		!$found_connection_params
		and strlen($connection_params['ftp_server'])
		and strlen($connection_params['ftp_username'])
		and strlen($connection_params['ftp_userpass'])
		and strlen($connection_params['local_dir'])
	)
	{
		$_SESSION['connections_params'][] = $connection_params;
	}
}



?>

<!DOCTYPE html>
<html>
<head>
	<title><?php echo basename(__FILE__); ?></title>
	<style>
		input[type="text"], input[type="password"]  {
    	width: 100%; 
     	box-sizing: border-box;
      -webkit-box-sizing:border-box;
      -moz-box-sizing: border-box;
		}
		input[type="checkbox"] {
			position: relative;
			top: 3px;
		}
	</style>
</head>
<body>

<form method="post" style='float: right;'>
	<input type="submit" name='session_unset' value="session_unset">
</form>

<?php
	function show_input_field($name, $source_arr, $type="text") {
		$buf = "";
		$buf .= "$name: <input type='".$type."' name='$name'";
		if (array_key_exists($name, $source_arr)) {
			$buf .= " value='".$source_arr[$name]."'";
		}
		$buf .= "><br>\n";
		return $buf;
	}

?>

<form method="post" style="clear: both;">
	<?php echo show_input_field('ftp_server', $_SESSION['connection_params']); ?>
	<?php echo show_input_field('ftp_username', $_SESSION['connection_params']); ?>
	<?php echo show_input_field('ftp_userpass', $_SESSION['connection_params'], "password"); ?>
	<?php echo show_input_field('remote_dir', $_SESSION['connection_params']); ?>
	<?php echo show_input_field('local_dir', $_SESSION['connection_params']); ?>
	<?php echo show_input_field('filter', $_SESSION['connection_params']); ?>
	<input type="submit" name='submit_connection' value="submit_connection">
</form>
<pre>

<?php

echo '<form method="post">';
echo "<table border='1'>";
foreach ($_SESSION['connections_params'] as $connection_id => $connection_params) {
	if (!$connection_params) continue;
	echo "<tr>";
	echo "<td><label>[$connection_id]<input type='checkbox' name='connections_ids[]' value='$connection_id'";
	if (array_key_exists('connections_ids', $_POST) and in_array($connection_id, $_POST['connections_ids'])) {
		echo "checked";
	}
	echo ">".display_connection_params($connection_params)."</label></td>";
	echo "</tr>";
	echo "\n";
}
echo "</table>";
echo "<input type='submit' name='clear_selected_connections' value='clear_selected_connections' style='float: right'>";
echo "<input type='submit' name='update_ftp' value='update_ftp'>";
echo '</form>';

if (isset($_POST) and array_key_exists('update_ftp', $_POST) and array_key_exists('connections_ids', $_POST)) {
	foreach ($_POST['connections_ids'] as $connection_id) {
		echo "<b>[$connection_id]: </b>: ". display_connection_params($_SESSION['connections_params'][$connection_id]);
		echo "\n";
		extract($_SESSION['connections_params'][$connection_id]);
		$ftp_conn = ftp_connect($ftp_server);
		echo "ftp_conn: $ftp_conn";
		echo "\n";
		$ftp_login = ftp_login($ftp_conn, $ftp_username, $ftp_userpass);
		echo "ftp_login: $ftp_login";
		echo "\n";
		$ftp_pasv = ftp_pasv($ftp_conn, true);
		echo "ftp_pasv: $ftp_pasv";
		echo "\n";
		$ftp_list = ftp_list($ftp_conn, $remote_dir, false);
		print_r($ftp_list);
		ftp_close($ftp_conn);
		echo "\n\n";
	}
}


exit();



echo "<pre>";

echo "remote_dir: <b>$remote_dir</b>/\n\n";

// echo "<table border=1>";

$files_data = array();

print_r($ftp_list);

// foreach (scandir($local_dir) as $item) {
// 	if ($item == "." or $item == "..") continue;
// 	$size = filesize($item);
// 	$timestamp = filemtime($item);
// 	$date = date("Y-m-d H:i:s", $timestamp);
// 	array_push2($files_data, $item, array(
// 		'local_date' => $date,
// 		'local_size' => filesize($item),
// 	));
// }

// foreach ($files_data as $file_name => &$file_data) {
// 	if (!array_key_exists('remote_size', $file_data)) continue;
// 	if ($file_data['remote_size'] == $file_data['local_size']) {
// 		$file_data['local_checksum'] = md5_file($file_name);
// 		ob_start();
// 		$result = ftp_get($ftp_conn, "php://output", "$remote_dir/$file_name", FTP_BINARY);
// 		$remote_content = ob_get_clean();
// 		$file_data['remote_checksum'] = md5($remote_content);
// 	}
// }

// foreach ($files_data as $file_name => &$file_data) {
// 	if (!array_key_exists('remote_size', $file_data)) continue;
// 	if (
// 		$file_data['local_size'] == $file_data['remote_size']
// 		and
// 		$file_data['local_checksum'] == $file_data['remote_checksum']
// 		)
// 	{
// 		continue;
// 	}
// 	$ftp_put_result = ftp_put($ftp_conn, "$remote_dir/$file_name", $file_name, FTP_BINARY);
// 	echo print_info($file_name, $ftp_put_result);
// 	echo "\n";
// }

// echo "\n\n";


// print_r($files_data);


// echo "</pre>";


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

function ftp_list($ftp_conn, $remote_dir, $recursive = false, $include_parent = false, $filter = false) {
	$rawlist = ftp_rawlist($ftp_conn, $remote_dir);
	// file_put_contents("ftpUpdater_rawlist.txt", join("\n", $rawlist));
	// $rawlist = explode("\n",file_get_contents("ftpUpdater_rawlist.txt"));
	$processed_list = array();
	foreach ($rawlist as &$item) {
		if (preg_match("`^total \d+$`", $item)) continue;
		if ($item[0] == 'd') $item .= "/";
		if (preg_match("`^[drwx-]{10} \d+ nobody nogroup +(?P<size>\d+) (?P<date>\w\w\w \d\d \d\d:\d\d|\w\w\w \d\d  \d\d\d\d) (?P<name>.*)`", $item, $m)) {
		  $item = array();
			$item['size'] = $m['size'];
			$item['name'] = $m['name'];
			$timestamp = ftp_mdtm($ftp_conn, $remote_dir."/".$m['name']);
			$item['date'] = $timestamp;
		}
		else user_error('wrong ftp line: $item');
		$processed_list[] = $item;
	}
	return $processed_list;
}

function display_connection_params($cp) {
	$buf = "";
	if (array_key_exists('filter', $cp)) {
		$buf .= $cp['filter'] . '||';
	}
	$buf .= $cp['local_dir'] . " -> ". $cp['ftp_username'] . ':' . '***'. '@'. $cp['ftp_server']. $cp['remote_dir'];
	return $buf;
}

?>