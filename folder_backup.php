<?php

error_reporting(E_ALL);
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
  echo "<pre style='white-space: pre-wrap;'>";
  throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
set_error_handler("exception_error_handler");

$backup_storage = "bck";
$folder_to_archive = "";
$only_text_files = true;

# http://stackoverflow.com/questions/7004989/creating-zip-or-tar-gz-archive-without-exec
$a = new PharData('archive.tar');

$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder_to_archive, RecursiveDirectoryIterator::SKIP_DOTS));

echo "<pre>";

foreach($objects as $name => $object){
  if (isBinary($name)) continue;
  $a->addFile($name);
}
$a->compress(Phar::GZ);


# http://stackoverflow.com/questions/3872877/how-to-check-if-uploaded-file-is-binary-file
# https://www.drupal.org/node/760362
function isBinary($file) { 
  if (file_exists($file)) {
    if (!is_file($file)) return 0; 
    $fh  = fopen($file, "r");
    if ($fh === false) die('$fh === false: '.$file);
    $blk = fread($fh, 512); 
    fclose($fh); 
    clearstatcache(); 
    return (
    //hacked cf drupal.org/node/760362
    0 or substr_count($blk, "^\r\n")/512 > 0.3
	    or substr_count($blk, "^ -~")/512 > 0.3
	    or substr_count($blk, "\x00") > 0
	  );
	}
  return 0; 
}


?>