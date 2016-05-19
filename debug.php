<?php

file_put_contents(__FILE__.".".date("Y-m-d_His").".debug", __FILE__.":".__LINE__."\n".print_r(debug_backtrace(),1)."\n\n", FILE_APPEND | LOCK_EX);

?>