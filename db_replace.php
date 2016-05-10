<?php

error_reporting(E_ALL);
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
  echo "<pre style='white-space: pre-wrap;'>";
  throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
set_error_handler("exception_error_handler");

if ($_SERVER["REMOTE_ADDR"] !== '10.0.1.1') {
  echo $_SERVER["REMOTE_ADDR"];
  die();
}

session_start();
if (isset($_POST) and array_key_exists('session_unset', $_POST)) {
  session_unset();
}

$connection_params = array();
if (!array_key_exists('connection_params', $_SESSION)) $_SESSION['connection_params'] = array();
$connection_params = &$_SESSION['connection_params'];
if (!array_key_exists('db_host', $connection_params) or $connection_params['db_host'] === "") {
  $connection_params['db_host'] = "localhost";
}

if (isset($_POST)) {
  foreach(array('db_host', 'db_user', 'db_password', 'db_name', 'search_string', 'replace_string', 'table', 'column', 'where') as $key) {
    if (!array_key_exists($key, $_POST)) continue;
    $_POST[$key] = trim($_POST[$key]);
    if ($key == 'db_host' and $_POST[$key] == "") {
      $_POST[$key] = "localhost";
    }
    $connection_params[$key] = $_POST[$key];
  }
}


?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <title><?php echo basename(__FILE__); ?></title>
  <style>
  pre {
    white-space: pre-wrap;
  }
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
  caption {
    font-weight: bold;
  }
  .number {
    text-align: right;
  }
</style>
</head>
<body>

<?php
  echo "<br> phpversion:". phpversion()."<br>";
?>

<form method="post" style='float: right;'>
  <input type="submit" name='session_unset' value="session_unset">
</form>

<?php
  function show_input_field($name, $source_arr, $type="text") {
    $buf = "";
    $buf .= "$name: <input type='".$type."' name='$name'";
    if (array_key_exists($name, $source_arr)) {
      $buf .= " value='".htmlentities($source_arr[$name], ENT_QUOTES)."'";
    }
    $buf .= "><br>\n";
    return $buf;
  }
?>

<form method="post" style="clear: both;">
  <?php echo show_input_field('db_host', $connection_params); ?>
  <?php echo show_input_field('db_user', $connection_params); ?>
  <?php echo show_input_field('db_password', $connection_params, "password"); ?>
  <?php echo show_input_field('db_name', $connection_params); ?>
  <?php echo show_input_field('table', $connection_params); ?>
  <?php echo show_input_field('column', $connection_params); ?>
  <?php echo show_input_field('where', $connection_params); ?>
  <?php echo show_input_field('search_string', $connection_params); ?>
  <?php echo show_input_field('replace_string', $connection_params); ?>
  <input type="submit" name='submit_connection' value="submit_connection">
