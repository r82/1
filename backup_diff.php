<?php

$help = <<<help
#==============================================================================
# example usage:
# bash folder_backup.sh --bck backup.zip --prod public_html --diff diff_directory
# ver. 2016-05-27 15:11:57
#==============================================================================
help;

error_reporting(E_ALL);
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
  echo "<pre style='white-space: pre-wrap;'>";
  throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
set_error_handler("exception_error_handler");

if (php_sapi_name() !== "cli") {
  die('php_sapi_name() !== "cli"'."\n");
}

echo "phpversion(): ".phpversion()."\n";
echo "php_uname(): ".php_uname()."\n";
echo "get_current_user(): ".get_current_user()."\n";

// $options = getopt("bck:prod:");
$args = parse_cmd_args($argv, $help);

$backup_file = $args['bck'];
do_file_path('backup_file');
$prod_folder = $args['prod'];
do_folder_path('prod_folder');
$backup_file_size = filesize($backup_file);

cli_disp_var('backup_file');
cli_disp_var('backup_file_size');
cli_disp_var('prod_folder');

$zip = new ZipArchive(); 
if (!$zip) die('!$zip');

$zip->open($backup_file);
$zip_num_files = $zip->numFiles;
cli_disp_var('zip_num_files');

$files = array();

$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($prod_folder));
foreach ($rii as $file) {
  if ($file->isDir()) continue;
  $pathname = $file->getPathname();
  $pathname = substr($pathname, strlen($prod_folder));
  $files[$pathname] = array();
}

for ( $i = 0; $i < $zip->numFiles; $i++ ) {
  $stat = $zip->statIndex($i);
  $files[$stat['name']] = array(
    'zip_crc' => $stat['crc'],
    'zip_size' => $stat['size'],
    'zip_mtime' => $stat['mtime'],
  );
}

ksort($files);

# http://misc.flogisoft.com/bash/tip_colors_and_formatting

foreach ($files as $file_path => $file_data) {
  if (!$file_data) {
    echo "\033[30;48;5;82mi:\033[0m ".$file_path."\n"; // inserted
  }
  elseif (!is_file($prod_folder.$file_path)) {
    echo "\033[30;41;5;82md:\033[0m ".$file_path."\n"; // deleted
  }
  elseif(
      filesize($prod_folder.$file_path) !== $file_data['zip_size']
      or
      file_crc32_dec($prod_folder.$file_path) !== $file_data['zip_crc']
    )
  {
    $prod_filemtime = filemtime($prod_folder.$file_path);
    if ($prod_filemtime > $file_data['zip_mtime']) {
      echo "\033[32mu:\033[0m $file_path"."\n"; // updated
    }
    elseif ($prod_filemtime < $file_data['zip_mtime']) {
      echo "\033[31mr:\033[0m $file_path"."\n"; // restored
    }
    else {
      echo "\033[33mm:\033[0m $file_path"."\n"; // modified
    }  
  }
}

function cli_disp_var() {
  $args = func_get_args();
  $var = $args[0];
  if (count($args) === 2) {
    $val = $args[1];
  } else {
    $val = $GLOBALS[$var];
  }
  if (preg_match("`^\d+$`", $val)) {
    $val = number_format($val, 0, ".", "`");
  }
  echo "\033[1;37m$var: \033[1;33m$val\033[0m\n";
}

function do_error($msg) {
  fwrite(STDERR, "ERROR: ".$msg."\n");
  die();
}

function parse_cmd_args($argv, $help = null) {
  if ($argv[0] === basename(__FILE__)) {
    array_shift($argv);
  }
  if (count($argv) % 2 !== 0) {
    if ($help) echo $help."\n";
    do_error('count($argv) % 2 !== 0: '.count($argv));
  }
  $parsed = array();
  for ($i=0; $i<count($argv); $i+=2) {
    $param_name = $argv[$i];
    if (!preg_match("`^--[a-zA-Z0-9_]+$`", $param_name)) {
      do_error('!preg_match("`^--[a-zA-Z0-9_]+$`", $param_name): '.$param_name);
    }
    $param_name = ltrim($param_name, "-");
    $parsed[$param_name] = $argv[$i+1];
  }
  return $parsed;
}

function do_file_path($var) {
  $val = &$GLOBALS[$var];
  if (!is_file($val)) {
    do_error("not a file: [$var]: $val");
  }
  $val = realpath($val);
}

function do_folder_path($var) {
  $val = &$GLOBALS[$var];
  if (!is_dir($val)) {
    do_error("not a dir: [$var]: $val");
  }
  $val = rtrim(realpath($val),"/\\")."/";
}

function file_crc32_dec($fp) {
  $crc32b = hash_file("crc32b", $fp);
  $unpack_crc = unpack('N', pack('H*', $crc32b));
  $crc32_dec = $unpack_crc[1];
  return $crc32_dec;
}

?>