<?php
$config['getmail_driver'] = "database";
$config['getmail_debug'] = true;
$config['getmail_command'] = "/usr/bin/getmail";
$config['getmail_tmp_dir'] = "/tmp";
$config['getmail_dir'] = "/tmp/.getmail";

/**
 * Uncomment your appropriate Getmail destination below.
 * Note that null value results in using the default.
 */

/**
 * Maildir Destination
 * http://pyropus.ca/software/getmail/configuration.html#destination-maildir
 */
//$config['getmail_destination'] = array(
//    "type" => "Maildir",
//    "path" => "/var/spool/imap/", // Note that the user can append "Destination Folder".
//    "user" => "cyrus",
//    "filemode" => null
//);

/**
 * Mboxrd Destination
 * http://pyropus.ca/software/getmail/configuration.html#destination-mboxrd
 */
//$config['getmail_destination'] = array(
//    "type" => "Mboxrd",
//    "path" => "/path/to/mbox",
//    "user" => null,
//    "locktype" => null
//);

/**
 * MDA External Destination
 * http://pyropus.ca/software/getmail/configuration.html#destination-mdaexternal
 */
$config['getmail_destination'] = array(
    "type" => "MDA_external",


    // Sendmail arguments e.g. Postfix
    //"path" => "/usr/sbin/sendmail",
    //"arguments" => array("-i", "-bm", "%(recipient)"),
    //"user" => null,
    //"group" => null,
    //"unixfrom" => true,

    // Cyrus Imap Delivery
    "path" => "/usr/lib64/cyrus/deliver",
    "arguments" => array("%(username)"), // Note, %(username) will be replaced with the email address of the appropriate user.
    "user" => "cyrus",
    "group" => "mail",
    "unixfrom" => false,

    "allow_root_commands" => null,
    "ignore_stderr" => null
);

/**
 * Additional Getmail Options
 * http://pyropus.ca/software/getmail/configuration.html#conf-options
 *
 * Note that some of them are defined by the user config. Setting the option here
 * will overwrite the user's configs.
 */

$config['getmail_options'] = array(
    "verbose" => $config['getmail_debug'],
    "read_all" => null,
    "delete" => null,
    "delete_after" => null,
    "delete_bigger_than" => null,
    "max_bytes_per_session" => null,
    "max_message_size" => null,
    "max_messages_per_session" => null,
    "delivered_to" => null,
    "received" => null,
    "message_log" => null,
    "message_log_syslog" => null,
    "message_log_verbose" => null
);
?>