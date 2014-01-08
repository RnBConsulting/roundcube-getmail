<?php

/**
 * Driver interface for the Getmail plugin
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

 /**
  * Struct of an internal Getmail object how it is passed from/to the driver classes:
  *
  *  $task = array(
  *            'id' => 'Unique identifier of this Getmail object used for editing',
  *       'user_id' => 'User identifier this Getmail config belongs to',
  *          'name' => 'Getmail config name/summary',
  *        'active' => 'Boolean value indicating whether this Getmail config is active or not. Default: True.',
  *          'type' => 'Retriever type, see http://pyropus.ca/software/getmail/configuration.html#conf-retriever',
  *        'server' => 'Retriever server hostname',
  *          'port' => 'Port of retriever server',
  *           'ssl' => 'Boolean value indicating whether retriever server supports SSL. Default: True.',
  *          'user' => 'Username of retriever server',
  *          'pass' => 'Password of retriever server',
  *        'delete' => 'Boolean, if set, Getmail will delete messages after retrieving and successfully delivering them. Default: False.',
  *      'only_new' => 'Boolean, if set, Getmail retrieves only new messages, otherwise all messages are retried. Default: True.',
  *          'poll' => 'Polling interval in seconds. Default: 300s.'
  *  );
  */

/**
 * Driver interface for the Getmail plugin
 */
abstract class getmail_driver
{
    /**
     * Gets all Getmail config records
     *
     * @return array List of configs.
     */
    abstract function get_configs();

    /**
     * Returns Getmail config by id.
     *
     * @param $id int Identifier for the Getmail config to retrieve.
     * @return mixed The requested config or null if it does not exist.
     */
    abstract public function get_config($id);

    /**
     * Saves changes of given config or creates a new one.
     *
     * @param $config array Hash list with Getmail config properties to store. If "id" is not specifed, a new config will be created.
     * @return mixed Returns false on error or the id of the create or modified config on success.
     */
    abstract function edit_config($config);

    /**
     * Deletes specified Getmail config.
     *
     * @param $id int Identifier for the Getmail config to delete. If an object is passed, the property "id" is used.
     * @return boolean Returns true on success, false otherwise.
     */
    abstract function delete_config($id);
}
