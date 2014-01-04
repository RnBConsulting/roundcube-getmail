<?php
/**
 * Getmail
 *
 * Plugin to allow the user to configure Getmail to fetch mails from POP/IMAP accounts.
 *
 * @version @package_version@
 * @requires jQueryUI plugin
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
class getmail extends rcube_plugin
{
    public $task = 'settings';

    private $rc;
    private $ui;

    static private $debug = true; // TODO: null;

    /**
     * Plugin initialization.
     */
    public function init()
    {
        $this->rc = rcube::get_instance();

        $this->require_plugin('jqueryui');

        $this->register_action('plugin.getmail', array($this, 'view'));
        $this->register_action('plugin.getmail-config', array($this, 'config_view'));
        $this->register_action('plugin.getmail-add', array($this, 'config_view'));
        $this->register_action('plugin.getmail-json', array($this, 'json_command'));

        $this->add_texts('localization/', true);
        $this->include_script('getmail.js');

        // Set debug state
        if(self::$debug === null)
            self::$debug = $this->rc->config->get('getmail_debug', False);
    }

    /**
     * Helper method to log debug msg if debug mode is enabled.
     */
    static public function debug_log($msg)
    {
        if(self::$debug === true)
            rcmail::console(__CLASS__.': '.$msg);
    }

    /**
     * Render main view
     */
    public function view()
    {
        require_once $this->home . '/getmail_ui.php';

        $this->ui = new getmail_ui($this);
        $this->register_handler('plugin.configlist', array($this->ui, 'config_list'));

        $this->rc->output->send('getmail.view');
    }

    /**
     * Render Getmail settings
     */
    public function config_view()
    {
        require_once $this->home . '/getmail_ui.php';

        $this->ui = new getmail_ui($this);
        $this->register_handler('plugin.configform', array($this->ui, 'config_form'));

        $id = get_input_value('_id', RCUBE_INPUT_GPC);
        $configs = $this->get_configs();

        if ($id && $config = $configs[$id])
        {
            $this->ui->config = $config;
            $this->ui->config['_id'] = $id;

            $this->rc->output->set_env('active_config', $id);
            $this->rc->output->command('parent.enable_command','plugin.delete-config', true);
        }

        $this->rc->output->send('getmail.config');
    }

    /**
     * Handle JSON requests
     */
    public function json_command()
    {
        $cmd  = get_input_value('cmd', RCUBE_INPUT_GPC);
        $id = get_input_value('id', RCUBE_INPUT_GPC);

        switch ($cmd) {
            case 'save':
                $config = $this->get_config($id);
                $data = $this->_unserialize(get_input_value('data', RCUBE_INPUT_GPC));

                // Create or edit.
                $config = ($config ? $data + $config : $data);
                $id = $this->edit_config($config);

                $this->rc->output->command('plugin.save-config-complete', array(
                        'success' => ($id !== false), 'id' => $id, 'name' => Q($config['name'])));

            break;

            case 'delete':
                $success = $this->delete_config($id);
                $this->rc->output->command('plugin.delete-config-complete', array(
                    'success' => $success, 'id' => $id));
                break;
        }

        $this->rc->output->send();
    }

    /**
     * Unserializes serialized form data.
     */
    private function _unserialize($data)
    {
        $unserialized = array();
        parse_str($data, $unserialized);
        return $unserialized;
    }

    /**
     * Reads configured Getmail account from current user
     */
    public function get_configs()
    {
        if(!isset($_SESSION['getmail_configs']))
        {
            $_SESSION['getmail_configs'] = array();

            $id = 1;
            $_SESSION['getmail_configs'][$id] = array(
                "id" => $id,
                "name" => "Gmail",
                "type" => "POP3"
            );

            $id = 2;
            $_SESSION['getmail_configs'][$id] = array(
                "id" => $id,
                "name" => "Awesome IT",
                "type" => "IMAP"
            );
        }

        return $_SESSION['getmail_configs'];
    }

    public function edit_config($config)
    {
        $new = false;
        if(!isset($config['id']))
        {
            $config['id'] = uniqid();
            $new = true;
        }
        else if(!isset($_SESSION['getmail_configs'][$config['id']]))
        {
            // Config id is given but does not exist.
            return false;
        }

        $_SESSION["getmail_configs"][$config['id']] = $config;
        return $config['id'];
    }

    public function delete_config($id)
    {
        if(is_object($id)) $id = $id['id'];

        if($id && isset($_SESSION["getmail_configs"][$id]))
        {
            unset($_SESSION["getmail_configs"][$id]);
            return true;
        }

        return false;
    }

    public function get_config($id)
    {
        // TODO: Cache via local var.

        if(isset($_SESSION["getmail_configs"][$id]))
            return $_SESSION["getmail_configs"][$id];

        return null;
    }
}
?>