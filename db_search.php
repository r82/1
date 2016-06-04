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
date_default_timezone_set('Europe/Warsaw');

session_start();

if (isset($_POST) and array_key_exists('session_unset', $_POST)) {
  session_unset();
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
  input[type="text"], input[type="password"], input[type="number"], textarea  {
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
  .error {
    color: red;
    font-weight: bold;
    border: 2px solid red;
    background-color: yellow;
    padding: 0 3px;
  }
</style>
</head>
<body>

<form method="post" style='float: right;'>
  <input type="submit" name='session_unset' value="session_unset">
</form>

<?php
echo "<br> phpversion:". phpversion()."<br>";

$connection_params = array();
if (!array_key_exists('connection_params', $_SESSION)) $_SESSION['connection_params'] = array();
$connection_params = &$_SESSION['connection_params'];
if (!array_key_exists('db_host', $connection_params) or $connection_params['db_host'] === "") {
  $connection_params['db_host'] = "localhost";
}

if (isset($_POST)) {
  foreach(array('db_host', 'db_user', 'db_password', 'search_string') as $key) {
    if (!array_key_exists($key, $_POST)) continue;
    $_POST[$key] = trim($_POST[$key]);
    if ($key == 'db_host' and $_POST[$key] == "") {
      $_POST[$key] = "localhost";
    }
    if ($key == "search_string") {
      $_SESSION['search_string'] = $_POST['search_string'];
      // http://stackoverflow.com/questions/1162491/alternative-to-mysql-real-escape-string-without-connecting-to-db
      // we have to wait for a valid connection
      $_SESSION['search_string_escaped'] = NULL;
    }
    $connection_params[$key] = $_POST[$key];
  }
}

$db = false;
if (array_key_exists('db_user', $connection_params)) {
  $db = new mysqli($connection_params['db_host'], $connection_params['db_user'], $connection_params['db_password']);
  $db->set_charset("utf8");
}

function sql_get_single_row($db, $query_str) {
  $results = $db->query($query_str);
  $num_rows = $results->num_rows;
  if ($num_rows !== 1) {
    echo "<div class='error'>\$num_rows !== 1 : $num_rows</div>";
  }
  $row = $results->fetch_assoc();
  return $row;
}

function show_columns_info($db, $Database, $table) {
  $results = $db->query("SHOW COLUMNS FROM $table IN $Database;");
  $columns = array();
  while ($row = $results->fetch_assoc()) {
    $columns[$row['Field']] = array(
      'Type' => $row['Type'],
      'Extra' => $row['Extra'],
    );
    $columns[$row['Field']] = join(" ", $columns[$row['Field']]);
    $columns[$row['Field']] = trim($columns[$row['Field']]);
  }
  return $columns;
}

function create_sql_update_str($db, $table, array $columns, $where) {
  $ustr = "UPDATE $table SET";
  foreach ($columns as $field => $value) {
    $ustr .= " $field = '".$db->escape_string($value)."' ";
  }
  $ustr .= "WHERE $where;";
  return $ustr;
}

if (isset($_GET) and count($_GET)) {
  echo "<pre>\n";
  $Database = $_GET['Database'];
  $table = $_GET['table'];
  $where_sql = array();
  foreach ($_GET['where'] as $column => &$value) {
    $where_sql[] = $column . " = '". $db->escape_string($value)."'";
  }
  $where_sql = join(" AND ", $where_sql);
  $query_str = "SELECT * FROM $Database.$table WHERE $where_sql";
  $row = sql_get_single_row($db, $query_str);
  if ($_POST) {
    $backup_query = create_sql_update_str($db, "$Database.$table", array_diff_assoc(array_diff_assoc($row, $_POST['row']), $_GET['where']), $where_sql);
    $log_directory = getcwd();
    $log_file_name = preg_replace('/^.+[\\\\\\/]/', '', __FILE__).".log.sql";
    $fh = fopen($log_file_name, 'a') or die("<span class='error'>Can't create file: $log_file_name</span>");
    if (fwrite($fh, "\n"."-- ".date("Y-m-d H:i:s")."\n".$backup_query."\n\n") === false) {
      die("<span class='error'>fwrite === false</span>");
    }
    fclose($fh);
    $update_query = create_sql_update_str($db, "$Database.$table", array_diff_assoc($_POST['row'], $row), $where_sql);
    
  }
  $pks = show_primary_keys($db, "$Database.$table");
  $columns_info = show_columns_info($db, $Database, $table);
  echo "<form method='post'>";
  foreach ($row as $field => $value) {
    if (in_array($field, $pks)) {
      echo "(PK) ";
    }
    echo "<b>$field</b>";
    echo " <i>".$columns_info[$field]."</i>";
    echo "<b>: </b>";
    $value_html = htmlspecialchars($value, ENT_QUOTES | ENT_DISALLOWED);
    if ( array_key_exists($field, $_GET['where'])) {
      echo "$value_html";
    }
    elseif (!strstr($value, "\n")) {
      echo "<input type='text' name='row[$field]' value='$value_html'>";
    }
    else {
      $lines = substr_count($value, "\n");
      echo "<textarea name='row[$field]' rows='$lines'>$value_html</textarea>";
    }
    echo "\n";
  }
  echo "<input type='submit' name='replace' value='replace'>";
  echo "</form>";
  echo "\n\n\n";
  die();
}

if (array_key_exists('tables', $_POST) and array_key_exists("submit_connection", $_POST)) {
  unset($_POST['tables']);
}

// print_r($_POST);
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
  <?php echo show_input_field('search_string', $_SESSION); ?>
  <input type="submit" name='submit_connection' value="submit_connection">


<pre>

<?php

if ($db === false) {
  echo '$db === false';
  die();
}

// http://stackoverflow.com/questions/1162491/alternative-to-mysql-real-escape-string-without-connecting-to-db
$_SESSION['search_string_escaped'] = $db->escape_string($_SESSION['search_string']);
echo "search_string_escaped: ". $_SESSION['search_string_escaped']."<br>";

if ($db->connect_error) {
  die("Connection failed: " . $db->connect_error);
}

$results = $db->query("show databases");
echo "<table border='1'>";
while ($row = $results->fetch_assoc()) {
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
?>
<label><input type='checkbox' name='select_tables' value='select_tables'>select_tables</label>
<label><input type='checkbox' name='unselect_tables' value='unselect_tables'>unselect_tables</label>
<input type="submit" name='get_tables' value="get_tables">
<?php
  
if (array_key_exists('databases', $_POST) and (array_key_exists('get_tables', $_POST) or array_key_exists('tables', $_POST)) or array_key_exists('search_tables', $_POST)) {
  foreach ($_POST['databases'] as $Database) {
    echo "<table border='1'>";
    echo "<caption>$Database</caption>";
    $results = $db->query("SELECT TABLE_NAME, TABLE_ROWS, AVG_ROW_LENGTH, DATA_LENGTH, CREATE_TIME, UPDATE_TIME, TABLE_COMMENT FROM information_schema.TABLES WHERE table_schema = '$Database'");
    $first_row = true;
    while ($row = $results->fetch_assoc()) {
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
        echo "<td";
        if (preg_match("`^\d+([.]\d+)?$`", $value)) {
          echo " class='number'";
          $value = number_format($value, 0, '.', "'");
        }
        echo ">";
        if ($key == "TABLE_NAME") {
          echo "<label>";
          echo "<input type='checkbox' name='tables[$Database][]' value='$value'";
          if (array_key_exists('tables', $_POST) and array_key_exists($Database, $_POST['tables']) and in_array("$value", $_POST['tables'][$Database]) or array_key_exists('select_tables', $_POST) ) {
            if (!array_key_exists('unselect_tables', $_POST)) {
              echo " checked";
            }
          }
          echo ">";
        }
        echo "$value";
        if ($key == 'TABLE_NAME') {
          echo "<br><i>";
          $pks = show_primary_keys($db, "$Database.$value");
          $results_describe = $db->query("DESCRIBE $Database.$value");
          $fields = array();
          while ($row_describe = $results_describe->fetch_assoc()) {
            $Field = $row_describe['Field'];
            if (in_array($Field, $pks)) {
              $Field = "<b>$Field</b>";
            }
            $fields[] = $Field;
          }
          echo join(", ", $fields);
          echo "</i>";
        }
        echo "</td>";
        if ($key == "TABLE_NAME") {
          echo "</label>";
        }
      }
      echo "</tr>";
    }
    echo "</table>";
  }
}

if (array_key_exists('get_tables', $_POST) or array_key_exists('search_tables', $_POST)) {
  ?><input type="submit" name='search_tables' value="search_tables"><br><?php
}

if (array_key_exists('tables', $_POST) and array_key_exists('search_tables', $_POST)) {
  foreach ($_POST['tables'] as $Database => $tables) {
    echo "<table border=1>";
    foreach ($tables as $table) {
      echo "<tr>";
      echo "<td>";
      echo "<b>$Database.$table</b>";
      $searchable_columns = array();
      $results = $db->query("DESCRIBE $Database.$table");
      while ($row = $results->fetch_assoc()) {
        if (preg_match("`^tinyint`", $row['Type'])) continue;
        if (preg_match("`^smallint`", $row['Type'])) continue;
        if (preg_match("`^int`", $row['Type'])) continue;
        if (preg_match("`^mediumint`", $row['Type'])) continue;
        if (preg_match("`^bigint`", $row['Type'])) continue;
        if (preg_match("`^datetime`", $row['Type'])) continue;
        if (preg_match("`^blob`", $row['Type'])) continue;

        // these we search, here only for reference
        // if (preg_match("`^varchar`", $row['Type'])) continue;
        // if (preg_match("`^enum`", $row['Type'])) continue;
        // if (preg_match("`^text`", $row['Type'])) continue;
        // if (preg_match("`^longtext`", $row['Type'])) continue;
        // if (preg_match("`^tinytext`", $row['Type'])) continue;
        // if (preg_match("`^mediumtext`", $row['Type'])) continue;

        // print_r($row);
        $searchable_columns[] = $row['Field'];
      }
      $primary_keys = show_primary_keys($db, "$Database.$table");
      // http://stackoverflow.com/questions/4688782/use-the-if-else-condition-for-selecting-the-column-in-mysql
      $columns_if = array();
      $columns_like = array();
      foreach ($searchable_columns as $column) {
        $columns_like[] = "$column LIKE '".$_SESSION['search_string_escaped']."'";
        $columns_if[] = "IF($column LIKE '".$_SESSION['search_string_escaped']."', $column, NULL) AS $column";
      }
      $columns_if = join(", ", $columns_if);
      $columns_like = join(" OR ", $columns_like);
      $search_query = "SELECT ".join(", ", $primary_keys).", $columns_if FROM $Database.$table WHERE $columns_like";
      $results = $db->query($search_query);
      if ($results) {
        if ($results->num_rows) {
          echo "<br>\n";
        }
        while ($row = $results->fetch_assoc()) {
          foreach ($row as $key => $value) {
            if ($value === NULL) continue;
            echo "<b>$key: </b>";
            echo htmlentities($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8', false);
            echo "\n";
          }
          echo "<form method='get' target='_blank'>";
          echo "<input type='submit' name='edit_row' value='edit_row'>";
          echo "<input type='hidden' name='Database' value='$Database'>";
          echo "<input type='hidden' name='table' value='$table'>";
          foreach ($primary_keys as $primary_key) {
            $value_html = htmlspecialchars($row[$primary_key], ENT_QUOTES | ENT_DISALLOWED);
            echo "<input type='hidden' name='where[$primary_key]' value='$value_html'>";
          }
          echo "</form>";
          echo "\n";
          echo "\n";
        }
      }
      echo "</td>";
      echo "</tr>";
      echo "\n";
    }
    echo "</table>";
  }
}

mysqli_close($db);

function show_primary_keys($db, $table) {
  $primary_keys = array();
  $results = $db->query("SHOW INDEX FROM $table");
  while ($row = $results->fetch_assoc()) {
    if ($row['Key_name'] !== "PRIMARY") continue;
    $primary_keys[] = $row['Column_name'];
  }
  return $primary_keys;
}

?>

</form>
</pre>
</body>
</html>