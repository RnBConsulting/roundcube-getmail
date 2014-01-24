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
            $getmail_config = $this->_generate_getmail_config($config);

            if(!$this->_run_getmail($getmail_config)) {
                rcmail::write_log("errors",  "Running getmail failed for user \"".$config["username"]."\".");
                // TODO: Log error, tolerate.
            }

            // TODO: Log success.
        }

        // TODO: Log success.
        return 0;
    }

    private function _generate_getmail_config($config)
    {
        $getmail_config = null;

        $getmail_config .= $this->_get_receivers($config);
        $getmail_config .= $this->_get_destination();
        $getmail_config .= $this->_get_options();

        return $getmail_config;
    }

    private function _get_receivers($config)
    {
        $receiver = null;
        // TODO
        return $receiver;
    }

    private function _get_destination()
    {
        $destination = null;
        // TODO
        return $destination;
    }

    private function _get_options()
    {
        $options = null;
        // TODO
        return $options;
    }

    private function _run_getmail($getmail_config)
    {
        // TODO
        return true;
    }
}
