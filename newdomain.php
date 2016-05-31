#!/usr/bin/env php
<?php

require_once __DIR__ . '/lib/helper.php';

if (empty($argv[1])) {
  failExit("Usage: newdomain [domain] [altnames...]");
}

$workDir = realpath(__DIR__) . "/";
$domainsDir = "{$workDir}domains/";
$domain = $argv[1];
$domainDir = "{$domainsDir}$domain/";

if (is_dir("{$domainsDir}{$domain}")) {
  failExit("Configuration already exists for domain: $domain");
}

run("mkdir $domainDir");

chdir($domainDir);

run("openssl genrsa 4096 > account.key");
run("openssl genrsa 4096 > domain.key");

$altNames = [];

foreach ($argv as $i => $domain) {
  if ($i == 0) {
    continue;
  }

  $altNames[] = "DNS:$domain";
}

$altNames = implode(",", $altNames);

$conf = file_get_contents('/etc/ssl/openssl.cnf');
$conf .= "\n[SAN]\n";
$conf .= "subjectAltName={$altNames}\n";
file_put_contents($sslConfFile = "$domainDir/ssl.conf", $conf);

run("openssl req -new -sha256 -key domain.key -subj \"/\" -reqexts SAN -config $sslConfFile > domain.csr");

unlink($sslConfFile);

run("echo 1 > {$domainDir}.auto");

echo "Done.\n\n";

echo "Next steps:\n";
echo "- put this into vhost of $domain:\n";

echo <<<EOF
location /.well-known/acme-challenge/ {
  alias /var/www/challenges/;
  try_files \$uri =404;
}

EOF;

echo "- sudo nginx -t\n";
echo "- sudo service nginx restart\n";
echo "- ./renew.php $domain\n";
echo "- echo 1 > domains/$domain/.auto"
echo "- ./install.php\n";
echo "- configure vhost for ssl\n";
echo "- sudo nginx -t\n";
echo "- sudo service nginx restart\n";
echo "\n";

