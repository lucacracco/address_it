#### Introduction

The module contains italian functionality for Address module, how subdivision
for "comuni".

#### Local Development

1. Clone module `git clone https://github.com/lucacracco/address_it.git`
1. `cd address_it`
1. Install the composer plugin from
   https://gitlab.com/drupalspoons/composer-plugin.
1. Configure a web server to serve module's `/web` directory as docroot.
   __Either__ of these works fine:
    1. `vendor/bin/spoon runserver`
    1. Setup Apache/Nginx/Other. A virtual host will work fine. Any domain name
       works.
1. Configure a database server and a database.
1. Install a testing site
   `vendor/bin/spoon si -- --db-url=mysql://user:pass@localhost/db`. Adjust as
   needed.
