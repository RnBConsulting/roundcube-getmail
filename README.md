Getmail Plugin for Roundcube
============================
This Roundcube plugins allows to configure Getmail (<http://pyropus.ca/software/getmail/>) to automatically fetch mails from POP3/IMAP accounts.

Requirements
============
* Roundcube 1.0-RC or higher
* Getmail 4.43.0 or higher

Installation
============
* Checkout this repo into your `roundcube/plugins` directory:

    ```bash
    $ cd /path/to/your/roundcube/plugins
    $ git clone https://gitlab.awesome-it.de/kolab/roundcube-getmail.git getmail
    ```

* Copy `roundcube/plugins/getmail/config.inc.php.dist` to `roundcube/plugins/getmail/config.inc.php` and configure according to your needs:

    ```bash
    $ cd /path/to/your/roundcube/plugins/getmail
    $ cp roundcube/plugins/getmail/config.inc.php.dist roundcube/plugins/getmail/config.inc.php
    ```

* It is always a good idea to set a new crypt key in `config.inc.php` for password encryption:

    ```php
    $config['getmail_crypt_key'] = 'some_random_characters`;
    ```

* Update Roundcube's MySQL database:

    ```bash
    $ mysql -h <db-host> -u <db-user> -p <db-name> < /path/to/your/roundcube/plugins/getmail/drivers/database/SQL/mysql.initial.sql
    ```

* Setup `roundcube/plugins/getmail/bin/cron.php` as cronjob for `root`:

    ```bash
    $ chmod 755 /path/to/your/roundcube/plugins/getmail/bin/cron.php
    $ echo "* * * * * root /path/to/your/roundcube/plugins/getmail/bin/cron.php" >> /etc/cron.d/getmail
    ```

* You can now head up to your Roundcube and configure Getmail accounts in the settings tab.

Troubleshooting
===============

* Enabling debug mode in `config.inc.php` will output additional debug information to `/path/to/your/roundcube/logs/console`:

    ```php
    $config['getmail_debug'] = true;
    ```

* Feel free to post issues in our tracker: https://gitlab.awesome-it.de/kolab/roundcube-getmail/issues

License
=======
Copyright (C) 2014, Awesome Information Technology GbR <info@awesome-it.de>
 
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.
 
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.
 
You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
