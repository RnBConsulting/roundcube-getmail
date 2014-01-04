/**
 * Client scripts for the Getmail plugin
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

function getmail_config()
{
    /* private members */
    var http_lock = null,
        active_config = null;

    rcmail.register_command('plugin.delete-config', delete_config);
    rcmail.register_command('plugin.save-config', save_config);

    // TODO: Do this only if a config was chosen.
    rcmail.enable_command('plugin.delete-config', true);
    rcmail.enable_command('plugin.save-config', true);

    rcmail.register_command('plugin.add-config', add_config);
    rcmail.enable_command('plugin.add-config', true);

    rcmail.addEventListener('plugin.save-config-complete', save_complete);
    rcmail.addEventListener('plugin.delete-config-complete', delete_complete);

    if (rcmail.gui_objects.configlist) {
        var configlist = new rcube_list_widget(rcmail.gui_objects.configlist,
            { multiselect:true, draggable:false, keyboard:true });
        configlist.addEventListener('select', select_config);
        configlist.init();

        // Load frame if there are no devices
        if (!rcmail.env.configcount)
            config_select();
    }

    /* private methods */
    function select_config(list)
    {
        active_config = list.get_single_selection();

        if (active_config)
            config_select(active_config);
        else if (rcmail.env.contentframe)
            rcmail.show_contentframe(false);
    };

    function config_select(id)
    {
        var win, target = window, url = '&_action=plugin.getmail-config';

        if (id) {
            url += '&_id='+urlencode(id);
        }

        if (win = rcmail.get_frame_window(rcmail.env.contentframe)) {
            target = win;
            url += '&_framed=1';
        }

        rcmail.location_href(rcmail.env.comm_path + url, target, true);
    };

    // Submit current configuration form to server
    function save_config()
    {
        var data = {
            cmd: 'save',
            id: rcmail.env.active_config,
            data: $('#configform').serialize()
        };

        http_lock = rcmail.set_busy(true, 'getmail.savingdata');
        rcmail.http_post('plugin.getmail-json', data, http_lock);
    };

    // Callback function after saving a config
    function save_complete(p)
    {
console.log("save_complete:");
console.log(p);

        // TODO: Insert a new row using configlist. Page reloading seems not to be "common".
    }

    // Handler for delete Getmail config
    function delete_config()
    {
        if (active_config && confirm(rcmail.gettext('configdeleteconfirm', 'getmail'))) {
            http_lock = rcmail.set_busy(true, 'getmail.savingdata');
            rcmail.http_post('plugin.getmail-json', { cmd:'delete', id:active_config }, http_lock);
        }
    };

    // Callback function after deleting a config
    function delete_complete(p)
    {
console.log("delete_complete:");
console.log(p);
        active_config = null;
        configlist.remove_row(p.id);
        config_select();
    };

    // Show blank config form for creating new Getmail configs
    function add_config()
    {
        active_config = null;
        config_select();
    }
};

window.rcmail && rcmail.addEventListener('init', function(evt) {

  // Add button to tabs list
  var tab = $('<span>').attr('id', 'settingstabplugingetmail').addClass('tablink'),
    button = $('<a>').attr('href', rcmail.env.comm_path+'&_action=plugin.getmail')
      .html(rcmail.gettext('tabtitle', 'getmail'))
      .appendTo(tab);
  rcmail.add_element(tab, 'tabs');

  if (/^plugin.getmail/.test(rcmail.env.action))
    getmail_obj = new getmail_config();
});