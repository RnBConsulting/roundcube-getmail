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

    public $rc;
    private $ui;

    public $driver = null;

    static private $debug = null;

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

        // load plugin configuration
        $this->load_config();

        // Set debug state
        if(self::$debug === null)
            self::$debug = $this->rc->config->get('getmail_debug', false);
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
        $config = $this->get_driver()->get_config($id);

        if ($config)
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
        $driver = $this->get_driver();

        switch ($cmd) {
            case 'save':
                $config = $driver->get_config($id);
                $data = get_input_value('data', RCUBE_INPUT_GPC);

                if(!$this->check_form_data($data))
                    $this->rc->output->show_message($this->gettext('formincomplete'), 'warning');

                else
                {
                    // Create or edit.
                    $new = ($config == null);
                    $config = ($config ? $data + $config : $data);
                    $id = $driver->edit_config($config);
                    $err = ($id === false);

                    $this->rc->output->command('plugin.save-config-complete', array(
                            'success' => !$err, 'id' => $id, 'name' => Q($config['name']), 'new' => $new));

                    if ($err)
                        $this->rc->output->show_message($this->gettext('savingerror'), 'error');
                    else
                        $this->rc->output->show_message($this->gettext('successfullysaved'), 'confirmation');
                }

            break;

            case 'delete':
                $err = !$driver->delete_config($id);
                $this->rc->output->command('plugin.delete-config-complete', array(
                    'success' => $err, 'id' => $id));

                if($err)
                    $this->rc->output->show_message($this->gettext('savingerror'), 'error');
                else
                    $this->rc->output->show_message($this->gettext('successfullydeleted'), 'confirmation');
                break;
        }

        $this->rc->output->send();
    }

    /**
     * Checks validity of given form data.
     *
     * @param $data array Hash array with form data to validate.
     * @return bool Returns true if valid, false otherwise.
     */
    public function check_form_data($data)
    {
        return isset($data['name']) && strlen($data['name']) > 0;
    }


    /**
     * Helper method to get the backend driver according to local config
     */
    public function get_driver()
    {
        if ($this->driver == null)
        {
            $driver_name = $this->rc->config->get('getmail_driver', 'database');
            $driver_class = 'getmail_' . $driver_name . '_driver';

            require_once($this->home . '/drivers/getmail_driver.php');
            require_once($this->home . '/drivers/' . $driver_name . '/' . $driver_class . '.php');

            $this->driver = new $driver_class($this);
        }

        return $this->driver;
    }
}
?>