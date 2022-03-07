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
        $configs = $this->plugin->get_driver()->get_configs();
        $table   = new html_table();

        foreach ($configs as $id => $config) {
            $name = $config["name"];
            $table->add_row(array('id' => 'rcmrow' . $id));
            $table->add(null, html::span('configname', rcube::Q($name)));
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

        $field_id = 'config-active';
        $input = new html_checkbox(array('name' => 'active', 'id' => $field_id, 'value' => true));
        $table->add('title', html::label($field_id, $this->plugin->gettext('configactive')));
        $table->add(null, $input->show($this->config ? $this->config['active'] : true));

        array_push($html,
            html::tag('fieldset', null,
                html::tag('legend', 'main', $this->plugin->gettext('configgeneral')).
                $table->show($attrib)));

        $table = new html_table(array('cols' => 2));

        $field_id = 'config-type';
        $select = new html_select(array('name' => 'type', 'id' => $field_id));
        $select->add(array('IMAP', 'POP3'), array('IMAP', 'POP3'));
        $table->add('title', html::label($field_id, $this->plugin->gettext('configtype')));
        $table->add(null, $select->show($this->config ? $this->config['type'] : 'IMAP')); // Default

        $field_id = 'config-ssl';
        $input = new html_checkbox(array('name' => 'ssl', 'id' => $field_id, 'value' => true));
        $table->add('title', html::label($field_id, $this->plugin->gettext('configssl')));
        $table->add(null, $input->show($this->config ? $this->config['ssl'] : true));

        $field_id = 'config-server';
        $input = new html_inputfield(array('name' => 'server', 'id' => $field_id, 'size' => 40));
        $table->add('title', html::label($field_id, $this->plugin->gettext('confighost')));
        $table->add(null, $input->show($this->config ? $this->config['server'] : null));

        $field_id = 'config-port';
        $input = new html_inputfield(array('name' => 'port', 'id' => $field_id, 'size' => 40));
        $table->add('title', html::label($field_id, $this->plugin->gettext('configport')));
        $table->add(null, $input->show($this->config ? $this->config['port'] : null));

        array_push($html,
            html::tag('fieldset', null,
                html::tag('legend', 'main', $this->plugin->gettext('configserver')).
                $table->show($attrib)));

        $table = new html_table(array('cols' => 2));

        $field_id = 'config-user';
        $input = new html_inputfield(array('name' => 'user', 'id' => $field_id, 'size' => 40));
        $table->add('title', html::label($field_id, $this->plugin->gettext('configuser')));
        $table->add(null, $input->show($this->config ? $this->config['user'] : null));

        $field_id = 'config-pass';
        $input = new html_passwordfield(array('name' => 'pass', 'id' => $field_id, 'size' => 40));
        $table->add('title', html::label($field_id, $this->plugin->gettext('configpass')));
        $table->add(null, $input->show($this->config ? $this->config['pass'] : null));

        array_push($html,
            html::tag('fieldset', null,
                html::tag('legend', 'main', $this->plugin->gettext('configauth')).
                $table->show($attrib)));

        $table = new html_table(array('cols' => 2));

        $field_id = 'config-mailboxes';
        $input = new html_inputfield(array('name' => 'mailboxes', 'id' => $field_id, 'size' => 40));
        $table->add('title', html::label($field_id, $this->plugin->gettext('configmailboxes')));
        $table->add(null, $input->show($this->config ? $this->config['mailboxes'] : null));

        $field_id = 'config-delete';
        $input = new html_checkbox(array('name' => 'delete', 'id' => $field_id, 'value' => true));
        $table->add('title', html::label($field_id, $this->plugin->gettext('configdelete')));
        $table->add(null, $input->show($this->config ? $this->config['delete'] : false));

        // Note that read_all attribute is set in javascript!
        $field_id = 'config-only_new';
        $input = new html_checkbox(array('name' => 'only_new', 'id' => $field_id, 'value' => true));
        $table->add('title', html::label($field_id, $this->plugin->gettext('configonlynew')));
        $table->add(null, $input->show($this->config ? !$this->config['read_all'] : false));

        $field_id = 'config-poll';
        $input = new html_inputfield(array('name' => 'poll', 'id' => $field_id, 'size' => 10));
        $table->add('title', html::label($field_id, $this->plugin->gettext('configpoll')));
        $table->add(null, $input->show($this->config ? $this->config['poll'] : 300));

        $field_id = 'config-header';
        $input = new html_inputfield(array('name' => 'header', 'id' => $field_id, 'size' => 40));
        $table->add('title', html::label($field_id, $this->plugin->gettext('configheader')));
        $table->add(null, $input->show($this->config ? $this->config['header'] : null));


        array_push($html,
            html::tag('fieldset', null,
                html::tag('legend', 'main', $this->plugin->gettext('configadvanced')).
                $table->show($attrib)));

        return implode($html, null);
    }
}
