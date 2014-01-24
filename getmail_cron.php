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

    public function __construct($rcmail, $root_path)
    {
        $this->rc = $rcmail;

        $this->plugin = new getmail(rcube_plugin_api::get_instance());
        $this->plugin->init();

        $this->driver = $this->plugin->get_driver();
    }

    public function run($argv)
    {
        $configs = $this->driver->get_configs();
        foreach($configs as $config)
        {
            if($config["active"])
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

                getmail::debug_log("Getmail \"".$config["name"]."\" (".$config["id"].") finished successfully.");
            }
        }

        return 0;
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

        return $retriever.$destination.$options;
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
            $value = $this->_prepare_args($config, $value);

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
            $value = $this->_prepare_args($config, $value);

            // Non-null rc_config value overwrites user config.
            if(isset($config[$key]) && $value !== null)
                $value = $config[$key];

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

        exec("$cmd --getmaildir=\"$getmail_dir\" --rcfile=\"$rc_path\" -vvv", $output, $return);
        getmail::debug_log(implode("\n", $output));

        unlink($rc_path);
        return ($return == 0);
    }

    private function _prepare_args($config, $value)
    {
        if(is_array($value))
        {
            for($i = 0; $i < sizeof($value); $i ++)
            {
                $value[$i] = $this->_prepare_args($config, $value[$i]);
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

            if($value === false) $value = "0";
            elseif($value === true) $value = "1";
        }

        return $value;
    }
}
