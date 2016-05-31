<?php

function failExit($msg, $status = 1) {
  fputs(STDERR, "$msg\n");
  exit($status);
}

function run($cmd, $info = "", $silent = false) {
  if (!$silent && $info) {
    echo "$info\n";
  }
  if (!$silent) {
    echo "Executing: $cmd\n";
  }
  passthru($cmd, $ret);
  if ($ret !== 0) {
    failExit("Command returned exit code $ret");
  }
}

