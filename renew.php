#!/usr/bin/env php
<?php

require_once __DIR__ . '/lib/helper.php';

$challengeDir = "/var/www/challenges/";

if (empty($argv[1])) {
  failExit("arg1: domain name");
}

$workDir = realpath(__DIR__) . "/";
chdir($workDir);

if ($argv[1] == '--all') {
  foreach (glob("{$workDir}domains/*") as $domainDir) {
    if (!file_exists("{$domainDir}/.auto")) {
      continue;
    }

    $domain = basename($domainDir);
    run("php renew.php $domain", "", true);
  }
  exit(0);
}

$domain = $argv[1];
$domainDir = "{$workDir}domains/$domain/";

if (!is_dir($challengeDir)) {
  failExit("Challenge direectory not found: $challengeDir");
}

if (!is_dir($domainDir)) {
  failExit("Directory not found: $domain");
}

if (!file_exists($accountKeyFile = "{$domainDir}/account.key")) {
  failExit("Account key not found: $accountKeyFile");
}

if (!file_exists($domainKeyFile = "{$domainDir}/domain.key")) {
  failExit("Domain key not found: $domainKeyFile");
}

if (!file_exists($csrFile = "{$domainDir}/domain.csr")) {
  failExit("CSR not found: $csrFile");
}

$crtTmpFile = "{$domainDir}domain.crt-" . date('Y-m-d-His');
$crtFile = "{$domainDir}domain.crt";
$domainsFile = "{$domainDir}domains.txt";
$imCertFile = "{$domainDir}/im.crt";
$chainedCertFile = "{$domainDir}/domain-chained.crt";

/* check if cert is about to expire */
if (file_exists($crtFile)) {
  $seconds = 3600 * 24 * 7; /* 1 week */
  passthru("openssl x509 -in $crtFile -noout -checkend $seconds", $ret);
  if ($ret === 0) {
    exit(0);
  }
}

run("wget -O - https://letsencrypt.org/certs/lets-encrypt-x3-cross-signed.pem > {$imCertFile}", "Fetch intermediate certificate.");

if (!file_exists($imCertFile)) {
  failExit("Could not fetch intermediate cert.");
}

run("openssl req -in $csrFile -text -noout|grep DNS |xargs -n1|tr \",\" \"\\n\"|cut -d: -f2|grep -v '^$' > $domainsFile", "Extract domains from CSR");

$domains = file($domainsFile);
if (!count($domains)) {
  failExit("No domains found in CSR");
}

$tmpName = rand();
file_put_contents($tmpFile = "{$challengeDir}{$tmpName}", $rand = rand());

foreach ($domains as &$domain) {
  $domain = trim($domain);
  run("test \"$(curl -s -L http://{$domain}/.well-known/acme-challenge/{$tmpName})\" = \"$rand\"", "Check if challenge dir is accessible for $domain");
}

unlink($tmpFile);

$cmd = "python acme-tiny/acme_tiny.py \
  --account-key {$accountKeyFile} \
  --csr {$csrFile} \
  --acme-dir $challengeDir \
  > $crtTmpFile";

run($cmd);
run("openssl x509 -in $crtTmpFile -noout", "Check if generated cert is valid");
run("cp $crtTmpFile $crtFile");
run("cat $crtFile $imCertFile > $chainedCertFile");

echo "Done.\n";

