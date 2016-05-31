# acme-cron

Wrapper script for acme-tiny. Simplifies the management for a large number of
domains, each with their own account keys. Performs validation to prevent from
being temp-banned from let's encrypt due to too many failed requests.  Certs
are renewed 1 week before they expire.

Assumptions:

 * Web server is nginx
 * Challenge-directory is /var/www/challenges/
 * Certificates are stored in /etc/nginx/certs/, one dir for each domain, e.g.
   * `/etc/nginx/certs/example.com/domain.crt`
   * `/etc/nginx/certs/example.com/domain.key`

## Install

    git clone https://github.com/sebcode/acme-cron.git
    cd acme-cron
    git clone https://github.com/diafygi/acme-tiny.git

Install cron job:

    `0 3 * * * cd /path/to/acme-cron && ./renew.php --all && ./install.php`

## Add new domain

    ./newdomain.php example.com www.example.com
    sudo nginx -t
    sudo service nginx restart
    ./renew example.com
    ./install.php
    # Configure your nginx vhost now
    sudo nginx -t
    sudo service nginx restart

## Setup vhost

    location /.well-known/acme-challenge/ {
      alias /var/www/challenges/;
      try_files \$uri =404;
    }

