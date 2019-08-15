<?php
/**
 * Cronjob backend for the Getmail plugin
 *
 * @version @package_version@
 * @author Daniel Morlock <daniel.morlock@awesome-it.de>
 *
 * Copyright (C) 2014, Awesome Information Technology GbR <info@awesome-it.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
class getmail_cron {

    private $rc;
    private $plugin;

    private $users = array();
    private $lock_file = null;

    public function __construct($rcmail)
    {
        $this->rc = $rcmail;

        $this->plugin = new getmail(rcube_plugin_api::get_instance());
        $this->plugin->init();

        $this->driver = $this->plugin->get_driver();
        $this->lock_file = $this->rc->config->get("getmail_lock_file");
    }

    public function run($argv)
    {
        if($this->is_locked())
        {
            getmail::debug_log("Another Getmail is still running, exit.");
            return 0;
        }

        $configs = $this->driver->get_configs();
        foreach($configs as $config)
        {
            $now = new DateTime();

            if($config["active"] && ($now->getTimestamp() - $config["last_poll"]->getTimestamp()) > $config["poll"])
            {
                getmail::debug_log("Preparing getmail \"".$config["name"]."\" (".$config["id"].") ...");
                $getmail_config = $this->_generate_getmail_config($config);
                if(!$getmail_config)
                {
                    getmail::error_log("Invalid config for getmail \"".$config["name"]."\" (".$config["id"]."), skip.");

                    // Tolerate config error, try next.
                    continue;
                }
                else
                {
                    getmail::debug_log("Running getmail \"".$config["name"]."\" (".$config["id"].") ...");
                    if(!$this->_run_getmail($getmail_config)) {
                        getmail::error_log("Getmail \"".$config["name"]."\" (".$config["id"].") failed, ignore.");

                        // Tolerate getmail error, try next.
                        continue;
                    }
                }

                // Update last_poll.
                $config["last_poll"] = new DateTime();
                $this->driver->edit_config($config);

                getmail::debug_log("Getmail \"".$config["name"]."\" (".$config["id"].") finished successfully.");
            }
        }

        unlink($this->lock_file);

        return 0;
    }

    /**
     * If lock file exists, check if stale.  If exists and is not stale, return true
     * otherwise create lock file and return false.
     */
    private function is_locked()
    {
        if (file_exists($this->lock_file)) {
            # check if it's stale
            $locked_pid = trim(file_get_contents($this->lock_file));

            # Get all active PIDs.
            $pids = explode("\n", trim(`ps -e | awk '{print $1}'`));

            # If PID is still active, return true
            if (in_array($locked_pid, $pids))
                return true;

            # Lock-file is stale, so kill it. Then move on to re-creating it.
            unlink($this->lock_file);
        }

        file_put_contents($this->lock_file, getmypid() . "\n");
        return false;
    }

    private function _generate_getmail_config($config)
    {
        $retriever = $this->_get_retriever($config);
        if(!$retriever) {
            getmail::error_log("Could not get retriever from getmail \"".$config["name"]."\" (".$config["id"]."), skip!");
            return false;
        }

        $destination = $this->_get_destination($config);
        if(!$destination) {
            getmail::error_log("Could not get destination from config, skip!");
            return false;
        }

        $options = $this->_get_options($config);
        if(!$options) {
            getmail::error_log("Could not get getmail options from config, skip!");
            return false;
        }
        $header = $this->_get_header($config);

        return $retriever.$destination.$options.$header;
    }

    private function _get_header($config)
    {
        if(!isset($config["header"]))
          return "";
        if($config["header"] == "")
          return "";

        $rc_config = $this->rc->config->get("getmail_destination");

        $header = $config["header"];

        return "[filter-account]\n".
               ($rc_config["user"] ? "user = ".$rc_config["user"]."\n" : "").
               ($rc_config["group"] ? "group = ".$rc_config["group"]."\n" : "").
               "type = Filter_external"."\n".
               ($this->rc->config->get("getmail_reformail") ? "path = ".$this->rc->config->get("getmail_reformail")."\n" : "").
               ($config["header"] ? "arguments = ('-a".$config["header"]."', )"."\n" : "");
    }


    private function _get_retriever($config)
    {
        $retriever = $this->_get_retriever_type($config);
        if(!$retriever) return false;

        return  "[retriever]\n".
                "type = ".$retriever."\n".
                "server = ".$config["server"]."\n".
                "username = ".$config["user"]."\n".
                "password = ".$config["pass"]."\n".
                ($config["mailboxes"] ? "mailboxes = ".$config["mailboxes"]."\n" : "").
                ($config["port"] ? "port = ".$config["port"]."\n" : "");
    }

    private function _get_retriever_type($config)
    {
        $ssl = (isset($config["ssl"]) && $config["ssl"]);

        switch(strtolower($config["type"]))
        {
            case "pop3": return $ssl ? "SimplePOP3SSLRetriever" : "SimplePOP3Retriever";
            case "imap": return $ssl ? "SimpleIMAPSSLRetriever" : "SimpleIMAPRetriever";
        }

        getmail::error_log("Unkown or invalid retriever type: \"".$config["type"]."\", skip!");
        return false;
    }

    private function _get_destination($config)
    {
        $rc_config = $this->rc->config->get("getmail_destination");

        $destination = "[destination]\n";

        $has_type = false;
        $has_path = false;

        foreach($rc_config as $key => $value)
        {
            $value = $this->_prepare_args($value, $config);

            if(is_array($value)) {
                $destination .= "$key = (\"".implode("\",\"",$value)."\",)\n";
            }

            elseif($value !== null) {

                if($key == "type") $has_type = true;
                elseif($key == "path") $has_path = true;

                $destination .= "$key = $value\n";
            }
        }

        if(!$has_type || !$has_path)
        {
            getmail::error_log("Invalid destination config: Missing type or path property, skip!");
            return false;
        }

        return $destination;
    }

    private function _get_options($config)
    {
        $options = "[options]\n";
        $rc_config = $this->rc->config->get("getmail_options");

        foreach($rc_config as $key => $value)
        {
            $value = $this->_prepare_args($value, $config);

            // Non-null rc_config value overwrites user config.
            if(isset($config[$key]) && $value === null)
                $value = $this->_prepare_args($config[$key], $config);

            if($value !== null)
                $options .= "$key = $value\n";
        }

        return $options;
    }

    private function _run_getmail($getmail_config)
    {
        $cmd = $this->rc->config->get("getmail_command");
        if(!file_exists($cmd)) {
            getmail::error_log("Getmail command not found: \"$cmd\".");
            return false;
        }

        // Make sure temp dir and getmail dir does exist and is writeable.
        $tmp_dir = $this->rc->config->get("getmail_tmp_dir");
        if(!is_dir($tmp_dir)) @mkdir($tmp_dir);
        if(!is_dir($tmp_dir) || !is_writable($tmp_dir)) {
            getmail::error_log("Getmail temp dir \"$tmp_dir\" does not exist or is not writeable!\n");
        }

        $getmail_dir =$this->rc->config->get("getmail_dir");
        if(!is_dir($getmail_dir)) @mkdir($getmail_dir);
        if(!is_dir($getmail_dir) || !is_writable($getmail_dir)) {
            getmail::error_log("Getmail dir \"$getmail_dir\" does not exist or is not writeable!\n");
        }

        // Create getmail config file.
        $rc_path = tempnam($tmp_dir, "getmail_rc_");
        $rc_fd = fopen($rc_path, "w");
        fwrite($rc_fd, $getmail_config);
        fclose($rc_fd);

        $output = array();
        $return = null;

        exec("$cmd --getmaildir=\"$getmail_dir\" --rcfile=\"$rc_path\" 2>&1", $output, $return);
        getmail::debug_log(implode("\n", $output));

        unlink($rc_path);
        return ($return == 0);
    }

    private function _prepare_args($value, $config)
    {
        if(is_array($value))
        {
            for($i = 0; $i < sizeof($value); $i ++)
            {
                $value[$i] = $this->_prepare_args($value[$i], $config);
            }
        }
        else
        {
            if(strstr($value, "%(username)")) {
                if(!isset($this->users[$config["user_id"]]))
                    $this->users[$config["user_id"]] = new rcube_user($config["user_id"]);
                $user = $this->users[$config["user_id"]];
                $value = str_replace("%(username)", $user->get_username(null), $value);
            }

            if($value === false) $value = "False";
            elseif($value === true) $value = "True";
        }

        return $value;
    }
}
