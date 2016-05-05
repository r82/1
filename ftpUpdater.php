<?php
error_reporting(E_ALL);
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
  echo "<pre style='white-space: pre-wrap;'>";
  throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
set_error_handler("exception_error_handler");

session_start();
if (isset($_POST) and array_key_exists('session_unset', $_POST)) {
	session_unset();
}

print_r($_POST);

if (isset($_POST) and array_key_exists('clear_selected_connections', $_POST)) {
	if (!array_key_exists('connections_ids', $_POST)) {
		echo print_html_err("no connections selected");
		$_POST['connections_ids'] = array();
	}
	foreach ($_POST['connections_ids'] as $connection_id) {
		unset($_SESSION['connections_params'][$connection_id]);
	}
}

if (!array_key_exists('connections_params', $_SESSION)) $_SESSION['connections_params'] = array();
$connection_params = array();
if (!array_key_exists('connection_params', $_SESSION)) $_SESSION['connection_params'] = array();

if (isset($_POST) and array_key_exists('submit_connection', $_POST)) {
	foreach(array('ftp_server', 'ftp_username', 'ftp_userpass', 'remote_dir', 'local_dir', 'regex_filter') as $key) {
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
	<meta charset="utf-8" />
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
	<?php echo show_input_field('regex_filter', $_SESSION['connection_params']); ?>
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

if (isset($_POST) and array_key_exists('update_ftp', $_POST)) {
	if (!array_key_exists('connections_ids', $_POST)) {
		echo print_html_err("no connections selected");
		$_POST['connections_ids'] = array();
	}
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
		$ftp_list = ftp_list_raw($ftp_conn, $remote_dir, true, $regex_filter);
		$local_list = local_list($local_dir, true, $regex_filter);
		$backup_timestamp = date("Y-m-d H;i;s");
		$ftp_update_backup_count = 0;
		echo "<table border='1'>";
		foreach (array_keys($ftp_list) as $filename) {
			if (!is_file($local_dir."/".$filename)) continue;
			if ($local_list[$filename]['size'] != $ftp_list[$filename]['size']) {
				echo "<tr>";
				echo "<td>$filename</td>";
				echo "<td>".show_html_size_comparison($local_list[$filename]['size'], $ftp_list[$filename]['size'])."</td>";
				$ftp_list[$filename]['date'] = ftp_mdtm($ftp_conn, "$remote_dir/$filename");
				echo "<td>".show_html_timestamp_comparison($local_list[$filename]['date'], $ftp_list[$filename]['date'])."</td>";
				ftp_update_backup($ftp_conn, $filename, $local_dir, $remote_dir, $backup_timestamp);
				$ftp_update_backup_count++;
				echo "</tr>";
				echo "\n";
			} else {
				$local_list[$filename]['md5'] = md5_file($local_dir."/".$filename);
				ob_start();
				$ftp_get_result = ftp_get($ftp_conn, "php://output", "$remote_dir/$filename", FTP_BINARY);
				$remote_content = ob_get_clean();
				if (!$ftp_get_result) echo print_html_err("ftp_get failed");
				$ftp_list[$filename]['md5'] = md5($remote_content);
				if ($local_list[$filename]['md5'] != $ftp_list[$filename]['md5']) {
					echo "<tr>";
					echo "<td>$filename</td>";
					echo "<td>".$local_list[$filename]['md5']."=>".$ftp_list[$filename]['md5']."</td>";
					ftp_update_backup($ftp_conn, $filename, $local_dir, $remote_dir, $backup_timestamp);
					$ftp_update_backup_count++;
					echo "</tr>";
					echo "\n";
				}
			}
		}
		echo "</table>";
		if ($ftp_update_backup_count) {
			echo "backup_timestamp: $backup_timestamp\n";
		}
		ftp_close($ftp_conn);
		echo "\n\n";
	}
}

function ftp_update_backup($ftp_conn, $filename, $local_dir, $remote_dir, $timestamp) {
  $backup_dir = ".__FTP_BACKUP/".$timestamp."/";
  $backup_dir = $local_dir."/".$backup_dir;
  if (!file_exists($backup_dir)) {
  	mkdir($backup_dir, 0777, true);
  }
  $ftp_get_result = ftp_get($ftp_conn, "$backup_dir/$filename", "$remote_dir/$filename", FTP_BINARY);
  if (!$ftp_get_result) echo print_html_err("ftp_get_result failed");
  $remote_file_mtime = ftp_mdtm($ftp_conn, "$remote_dir/$filename");
  touch("$backup_dir/$filename", $remote_file_mtime);
  $ftp_put_result = ftp_put($ftp_conn, "$remote_dir/$filename", "$local_dir/$filename", FTP_BINARY);
  if (!$ftp_put_result) echo print_html_err("ftp_put failed");
}

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

function ftp_list_raw($ftp_conn, $remote_dir, $files_only = false, $regex_filter = "") {
	$rawlist = ftp_rawlist($ftp_conn, $remote_dir);
	file_put_contents("ftpUpdater_rawlist.txt", join("\n", $rawlist));
	$rawlist = explode("\n", file_get_contents("ftpUpdater_rawlist.txt"));
	$processed_list = array();
	foreach ($rawlist as &$item) {
		if (preg_match("`^total \d+$`", $item)) continue;
		if ($item[0] == 'd') {
			if ($files_only) continue;
			$item .= "/";
		}
		if (preg_match("`^[drwx-]{10} \d+ nobody nogroup +(?P<size>\d+) (?P<date>\w\w\w \d\d \d\d:\d\d|\w\w\w \d\d  \d\d\d\d) (?P<name>.*)`", $item, $m)) {
		  $item = array();
		  if($regex_filter !== "" and (!preg_match("`$regex_filter`i", $m['name']))) continue;
			$item['size'] = $m['size'];
			$item['date'] = $m['date'];
		}
		else user_error('wrong ftp line: $item');
		$processed_list[$m['name']] = $item;
	}
	return $processed_list;
}

function local_list($dir, $files_only = false, $regex_filter = "") {
  $processed_list = array();
  foreach (new DirectoryIterator($dir) as $fileInfo) {
    $filename = $fileInfo->getFilename();
    if ($filename == "." or $filename == "..") continue;
    if($fileInfo->isDir()) {
    	if ($files_only) continue;
    	$filename .= "/";
    } else {
    	$filesize = filesize($dir."/".$filename);
    }
    if($regex_filter !== "" and (!preg_match("`$regex_filter`i", $filename))) continue;
    $filemtime = filemtime($dir."/".$filename);
    $processed_list[$filename] = array(
    	'size' => $filesize,
    	'date' => $filemtime,
    );
	}
	return $processed_list;
}

function display_connection_params($cp) {
	$buf = "";
	if (array_key_exists('regex_filter', $cp) and $cp['regex_filter'] !== "") {
		$buf .= $cp['regex_filter'] . '||';
	}
	$buf .= $cp['local_dir'] . " -> ". $cp['ftp_username'] . ':' . '***'. '@'. $cp['ftp_server']. $cp['remote_dir'];
	return $buf;
}
function print_html_err($msg) {
	return "<span style='color: red;'>$msg</span>\n";
}

function show_html_size_comparison($size1, $size2) {
	if ($size1 > $size2) {
		$size1 = "<b><u>$size1</u></b>";
	}
	if ($size2 > $size1) {
		$size2 = "<b><u>$size2</u></b>";
	}
	return "$size1->$size2";
}

function show_html_timestamp_comparison($ts1, $ts2) {
	$ts1_hr = date("Y-m-d H:i:s", $ts1);
	$ts2_hr = date("Y-m-d H:i:s", $ts2);
	if ($ts1 > $ts2) {
		$ts1_hr = "<b><u>$ts1_hr</u></b>";
	}
	if ($ts2 > $ts1) {
		$ts2_hr = "<b><u>$ts2_hr</u></b>";
	}
	return "$ts1_hr->$ts2_hr";
}

?>