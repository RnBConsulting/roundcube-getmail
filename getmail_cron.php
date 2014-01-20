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
        var_dump($argv);
        return 0;
    }
}
