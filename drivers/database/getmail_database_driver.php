<?php

/**
 * Databse driver for the Getmail plugin
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
class getmail_database_driver extends getmail_driver
{
    private $rc;
    private $plugin;

    private $db_getmail_configs = 'getmail';

    private $configs = null;

    public function __construct($plugin)
    {
        $this->rc = $plugin->rc;
        $this->plugin = $plugin;

        // read database config
        $db = $this->rc->get_dbh();
        $this->db_getmail_configs = $this->rc->config->get('getmail_db_table', $db->table_name($this->db_getmail_configs));
    }

    function get_configs()
    {
        if($this->configs == null)
        {
            $result = null;

            if($this->rc->user->ID){
                $result = $this->rc->db->query(
                    "SELECT * FROM ".$this->db_getmail_configs." ".
                    "WHERE user_id=? ".
                    "ORDER BY name ASC", $this->rc->user->ID
                );
            }
            else
            {
                $result = $this->rc->db->query(
                    "SELECT * FROM ".$this->db_getmail_configs." ".
                    "ORDER BY name ASC"
                );
            }

            $this->configs = array();
            while ($result && ($arr = $this->rc->db->fetch_assoc($result))) {

                $this->configs[$arr['id']] = array(
                    'id' => $arr['id'],
                    'user_id' => $arr['user_id'],
                    'name' => $arr['name'],
                    'active' => (bool)$arr['active'],
                    'type' => $arr['type'],
                    'server' => $arr['server'],
                    'mailboxes' => ($arr['mailboxes'] ? $arr['mailboxes'] : null),
                    'port' => ($arr['port'] ? intval($arr['port']) : null),
                    'ssl' => (bool)$arr['ssl'],
                    'user' => $arr['user'],
                    'pass' => $this->rc->decrypt($arr['pass']),
                    'delete' => (bool)$arr['delete'],
                    'only_new' => (bool)$arr['only_new'],
                    'poll' => intval($arr['poll']),
                    'last_poll' => ($arr['last_poll'] ? new DateTime($arr['last_poll']) : null)
                );
            }
        }

        return $this->configs;
    }

    public function get_config($id)
    {
        $configs = $this->get_configs();

        if(isset($configs[$id]))
            return $configs[$id];

        else return null;
    }

    public function edit_config($config)
    {
        if(!isset($config['id']))
            $config['id'] = uniqid();

        // Encrypt password.
        $config['pass'] = $this->rc->encrypt($config['pass']);

        // Convert DateTime object to MySQL datetime string.
        if($config['last_poll'])
            $config['last_poll'] = $config['last_poll']->format("Y-m-d H:i:s");

        $sql_set = array();

        if(!isset($config["user_id"]))
            $config["user_id"] = $this->rc->db->quote($this->rc->user->ID);

        foreach(array('id', 'user_id', 'name', 'type', 'server', 'user', 'pass') as $col)
            array_push($sql_set, $this->rc->db->quote_identifier($col).'='.$this->rc->db->quote($config[$col]));

        // Optional
        foreach(array('poll', 'port', 'mailboxes', 'last_poll') as $col) {
            if(isset($config[$col]) && $config[$col] != null){
                array_push($sql_set, $this->rc->db->quote_identifier($col).'='.$this->rc->db->quote($config[$col]));
            } else {
                array_push($sql_set, $this->rc->db->quote_identifier($col).'= DEFAULT');
            }
        }

        // Boolean
        foreach(array('active', 'ssl', 'delete', 'only_new') as $col) {
            array_push($sql_set, $this->rc->db->quote_identifier($col).'='.($config[$col] ? 'TRUE' : 'FALSE'));
        }

        $query = $this->rc->db->query(sprintf(
            "INSERT INTO ".$this->db_getmail_configs." ".
            "SET %s ".
            "ON DUPLICATE KEY UPDATE %s ", join(', ', $sql_set), join(', ', $sql_set)));

        if(!$query) {
            $this->rc->db->handle_error($query);
            return false;
        }

        if($this->rc->db->affected_rows($query) > 0)
            return $config['id'];

        else return false;
    }


    public function delete_config($id)
    {
        $query = $this->rc->db->query(
            "DELETE FROM " . $this->db_getmail_configs." ".
            "WHERE id=? ".
            "AND user_id=?",
            $id, $this->rc->user->ID);

        return ($this->rc->db->affected_rows($query) > 0);
    }
}