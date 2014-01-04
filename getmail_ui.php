<?php

/**
 * Getmail plugin's User Interface
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

class getmail_ui
{
    private $rc;
    private $plugin;

    public $config = null; // Currently selected config if any

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
        $this->rc = rcube::get_instance();
        $skin_path = $this->plugin->local_skin_path() . '/';
        $this->skin_path = 'plugins/getmail/' . $skin_path;

        $this->plugin->include_stylesheet($skin_path . 'getmail.css');
        $this->rc->output->include_script('list.js');
    }

    public function config_list($attrib = array())
    {
        $attrib += array('id' => 'config-list');

        $configs = $this->plugin->get_configs();
        $table   = new html_table();

        foreach ($configs as $id => $config) {
            $name = $config["name"];
            $table->add_row(array('id' => 'rcmrow' . $id));
            $table->add(null, html::span('configname', Q($name)));
        }

        $this->rc->output->add_gui_object('configlist', $attrib['id']);
        $this->rc->output->set_env('configcount', count($configs));

        return $table->show($attrib);
    }

    public function config_form($attrib = array())
    {
        $html = array();
        $table = new html_table(array('cols' => 2));

        $field_id = 'config-name';
        $input = new html_inputfield(array('name' => 'name', 'id' => $field_id, 'size' => 40));
        $table->add('title', html::label($field_id, $this->plugin->gettext('configname')));
        $table->add(null, $input->show($this->config ? $this->config['name'] : null));

        $field_id = 'config-type';
        $select = new html_select(array('name' => 'type', 'id' => $field_id));
        $select->add('IMAP', 'SimpleIMAPRetriever');
        $select->add('POP3', 'SimplePOP3Retriever');
        $table->add('title', html::label($field_id, $this->plugin->gettext('configtype')));
        $table->add(null, $select->show($this->config ? $this->config['type'] : 'IMAP')); // Default

        array_push($html,
            html::tag('fieldset', null,
                html::tag('legend', 'main', $this->plugin->gettext('configgeneral')).
                $table->show($attrib)));

        $table = new html_table(array('cols' => 2));

        $field_id = 'config-host';
        $input = new html_inputfield(array('name' => 'host', 'id' => $field_id, 'size' => 40));
        $table->add('title', html::label($field_id, $this->plugin->gettext('confighost')));
        $table->add(null, $input->show($this->config ? $this->config['host'] : null));

        $field_id = 'config-port';
        $input = new html_inputfield(array('name' => 'port', 'id' => $field_id, 'size' => 40));
        $table->add('title', html::label($field_id, $this->plugin->gettext('configport')));
        $table->add(null, $input->show($this->config ? $this->config['port'] : null));

        array_push($html,
            html::tag('fieldset', null,
                html::tag('legend', 'main', $this->plugin->gettext('configserver')).
                $table->show($attrib)));

        $table = new html_table(array('cols' => 2));

        $field_id = 'config-username';
        $input = new html_inputfield(array('name' => 'username', 'id' => $field_id, 'size' => 40));
        $table->add('title', html::label($field_id, $this->plugin->gettext('configusername')));
        $table->add(null, $input->show($this->config ? $this->config['username'] : null));

        $field_id = 'config-password';
        $input = new html_passwordfield(array('name' => 'password', 'id' => $field_id, 'size' => 40));
        $table->add('title', html::label($field_id, $this->plugin->gettext('configpassword')));
        $table->add(null, $input->show($this->config ? $this->config['password'] : null));

        array_push($html,
            html::tag('fieldset', null,
                html::tag('legend', 'main', $this->plugin->gettext('configauth')).
                $table->show($attrib)));

        return implode($html, null);
    }
}
