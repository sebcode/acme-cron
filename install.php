#!/usr/bin/env php
<?php

require_once __DIR__ . '/lib/helper.php';

$workDir = realpath(__DIR__) . "/";
$wsCertDir = "/etc/nginx/certs/";

$needRestart = false;
$validDomains = 0;

foreach (glob("{$workDir}domains/*") as $domainDir) {
  $domain = basename($domainDir);
  $wsDomainDir = "{$wsCertDir}/$domain/";

  if (!file_exists("{$domainDir}/.auto")) {
    continue;
  }

  if (!file_exists($chainedCertFile = "$domainDir/domain-chained.crt")) {
    continue;
  }
  if (!file_exists($keyFile = "$domainDir/domain.key")) {
    continue;
  }

  $validDomains++;

  if (!is_dir($wsDomainDir)) {
    run("sudo mkdir $wsDomainDir");
  }

  if (!file_exists($wsKeyFile = "{$wsDomainDir}domain.key")) {
    run("sudo cp $keyFile $wsKeyFile");
    $needRestart = true;
  }

  $wsCertFile = "{$wsDomainDir}domain.crt";
  passthru("cmp --silent $chainedCertFile $wsCertFile", $ret);
  $needCopy = $ret !== 0;

  if ($needCopy) {
    run("sudo cp $chainedCertFile $wsCertFile");
    $needRestart = true;
  }
}

if (!$validDomains) {
  failExit("No domains configured.");
}

if ($needRestart) {
  run("sudo nginx -t");
  run("sudo service nginx restart");
}

