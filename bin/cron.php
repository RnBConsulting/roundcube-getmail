#!/usr/bin/php
<?php
if(!isset($argv)) {
    echo "Script must be run in a shell.";
    exit(1);
}

$rcmail_path = realpath(dirname(realpath($argv[0]))."/../../../");
define('INSTALL_PATH', $rcmail_path."/");

// Include environment
require_once $rcmail_path.'/program/include/iniset.php';
require_once $rcmail_path.'/program/lib/Roundcube/rcube_plugin_api.php';
require_once $rcmail_path.'/plugins/getmail/getmail.php';
require_once $rcmail_path.'/plugins/getmail/getmail_cron.php';
require_once $rcmail_path.'/plugins/getmail/drivers/getmail_driver.php';

// Init application, start session, init output class, etc.
$rcmail = rcmail::get_instance($GLOBALS['env']);

// Initialize and run cronjob
$getmail_cron = new getmail_cron($rcmail, $rmail_path);
exit($getmail_cron->run($argv));