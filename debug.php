<?php

file_put_contents(__FILE__.".".date("Y-m-d_His").".debug", "microtime(): ".microtime()."\nphpversion(): ".phpversion()."\n".__FILE__.":".__LINE__."\n__TRAIT__: ".__TRAIT__. "\n__NAMESPACE__: ".__NAMESPACE__. "\n__CLASS__: ".__CLASS__. "\n__METHOD__: ".__METHOD__. "\n__FUNCTION__: ".__FUNCTION__."\ndebug_backtrace(): ".print_r(debug_backtrace(),1)."\n\$_POST: ".print_r($_POST,1)."\n\$_GET: ".print_r($_GET,1)."\n\$_COOKIE: ".print_r($_COOKIE,1)."\n\$_SERVER: ".print_r($_SERVER,1)."\n\n", FILE_APPEND | LOCK_EX);

?>