<?php

error_reporting(E_ALL);
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
  echo "<pre style='white-space: pre-wrap;'>";
  throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
set_error_handler("exception_error_handler");

if ($_SERVER["REMOTE_ADDR"] !== '10.0.1.1') die();

session_start();
if (isset($_POST) and array_key_exists('session_unset', $_POST)) {
  session_unset();
}

$connection_params = array();
if (!array_key_exists('connection_params', $_SESSION)) $connection_params = array();
$connection_params = &$connection_params;
if (!array_key_exists('db_host', $connection_params) or $connection_params['db_host'] === "") {
  $connection_params['db_host'] = "localhost";
}

if (isset($_POST) and array_key_exists('submit_connection', $_POST)) {
  foreach(array('db_host', 'db_user', 'db_password', 'search_string') as $key) {
    if (!array_key_exists($key, $_POST)) continue;
    $_POST[$key] = trim($_POST[$key]);
    if ($key == 'db_host' and $_POST[$key] == "") {
      $_POST[$key] = "localhost";
    }
    $connection_params[$key] = $_POST[$key];
  }
}

print_r($_POST);
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
  caption {
    font-weight: bold;
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
      $buf .= " value='".$source_arr[$name]."'";
    }
    $buf .= "><br>\n";
    return $buf;
  }
?>

<?php
?>

<form method="post" style="clear: both;">
  <?php echo show_input_field('db_host', $connection_params); ?>
  <?php echo show_input_field('db_user', $connection_params); ?>
  <?php echo show_input_field('db_password', $connection_params, "password"); ?>
  <?php echo show_input_field('search_string', $connection_params); ?>
  <input type="submit" name='submit_connection' value="submit_connection">


<pre>

<?php

if (!array_key_exists('db_user', $connection_params)) die();

$db = new mysqli($connection_params['db_host'], $connection_params['db_user'], $connection_params['db_password']);
if ($db->connect_error) {
  die("Connection failed: " . $db->connect_error);
}

$results = $db->query("show databases");
echo "<table border='1'>";
while($row = $results->fetch_assoc()) {
  $Database = $row['Database'];
  echo "<tr>";
  echo "<td><label>";
  echo "<input type='checkbox' name='databases[]' value='$Database'";
  if (array_key_exists('databases', $_POST) and in_array($Database, $_POST['databases'])) {
    echo "checked";
  }
  echo ">";
  echo $Database;
  echo "</label></td>";
  echo "</tr>";
}
echo "</table>";

if (array_key_exists('databases', $_POST)) {
  foreach ($_POST['databases'] as $Database) {
    echo "<table border='1'>";
    echo "<caption>$Database</caption>";
    $results = $db->query("SELECT TABLE_NAME, TABLE_ROWS, AVG_ROW_LENGTH, DATA_LENGTH, CREATE_TIME, UPDATE_TIME, TABLE_COMMENT FROM information_schema.TABLES WHERE table_schema = '$Database'");
    $first_row = true;
    while($row = $results->fetch_assoc()) {
      echo "<thead><tr>";
      if ($first_row) {
        foreach ($row as $key => $value) {
          echo "<th>$key</th>";
        }
        $first_row = false;
      }
      echo "</tr></thead>";
      echo "<tr>";
      foreach ($row as $key => $value) {
        echo "<td>$value</td>";
      }
      echo "</tr>";
    }
    echo "</table>";
  }
}

mysqli_close($db);

?>

</form>
</pre>
</body>
</html>