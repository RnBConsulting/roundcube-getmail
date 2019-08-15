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

var getmail_config =
{
    /* private members */
    http_lock: null,
    active_config: null,
    configlist: null,

    init: function() {

        rcmail.register_command('plugin.delete-config', $.proxy(this.delete_config, parent.getmail_config));
        rcmail.register_command('plugin.save-config', $.proxy(this.save_config, parent.getmail_config));
        rcmail.register_command('plugin.add-config', $.proxy(this.add_config, parent.getmail_config));

        // TODO: Do this only if a config was chosen.
        rcmail.enable_command('plugin.delete-config', true);
        rcmail.enable_command('plugin.save-config', true);
        rcmail.enable_command('plugin.add-config', true);

        if (rcmail.gui_objects.configlist) {

            this.configlist = new rcube_list_widget(rcmail.gui_objects.configlist, { multiselect:true, draggable:false, keyboard:true });
            this.configlist.addEventListener('select', $.proxy(function(list)
            {
                this.active_config = list.get_single_selection();

                if (this.active_config)
                    this.select_config(this.active_config);

            }, this));
            this.configlist.init();
        }

        $("#config-type").on("change", this.update_service_port);
        $("#config-ssl").on("change", this.update_service_port);

        // Only show mailboxes for IMAP types.
        $("#config-type").on("change", this.update_mailboxes);
        this.update_mailboxes();
    },

    update_mailboxes: function()
    {
        var imap = $("#config-type option:selected").val().search(/imap/i) >= 0;
        var $mailboxes = $("#config-mailboxes").closest("tr");

        if(imap) $mailboxes.show();
        else $mailboxes.hide();
    },

    update_service_port: function(type, ssl)
    {
        var $port = $("#config-port");
        var ssl = $("#config-ssl").is(":checked");
        var type = $("#config-type option:selected").val();

        if(type.search(/pop3/i) >= 0) {
            if(!ssl) $port.val(110);
            else $port.val(995);
        }
        else if(type.search(/imap/i) >= 0) {
            if(!ssl) $port.val(143);
            else $port.val(993);
        }
    },

    select_config: function(id)
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
    },

    // Submit current configuration form to server
    save_config: function()
    {
        var data = {
            cmd: 'save',
            id: rcmail.env.active_config,
            data: {
                name: $("#config-name").val(),
                type: $("#config-type").val(),
                active: $("#config-active").prop("checked"),
                server: $("#config-server").val(),
                port: $("#config-port").val(),
                ssl: $("#config-ssl").prop("checked"),
                user: $("#config-user").val(),
                pass: $("#config-pass").val(),
                delete: $("#config-delete").prop("checked"),
                read_all: !$("#config-only_new").prop("checked"),
                poll: $("#config-poll").val(),
                mailboxes: $("#config-mailboxes").val(),
                header: $("#config-header").val()
            }
        };

        rcmail.addEventListener('plugin.save-config-complete', $.proxy(function(p) {

            if(p.new){
                this.configlist.insert_row({ id:'rcmrow' + p.id, cols:[ { className:'configname', innerHTML: p.name } ] });
                this.configlist.select(p.id);
            }
            else {
                this.configlist.update_row(p.id, [ p.name ]);
            }

        }, this));

        this.http_lock = rcmail.set_busy(true, 'getmail.savingdata');
        rcmail.http_post('plugin.getmail-json', data, this.http_lock);
    },

    // Handler for delete Getmail config
    delete_config: function()
    {
        if (this.active_config && confirm(rcmail.gettext('configdeleteconfirm', 'getmail'))) {

            rcmail.addEventListener('plugin.delete-config-complete', $.proxy(function(p) {
                this.active_config = null;
                this.configlist.remove_row(p.id);
                this.select_config();
            }, this));

            this.http_lock = rcmail.set_busy(true, 'getmail.savingdata');
            rcmail.http_post('plugin.getmail-json', { cmd:'delete', id:this.active_config }, this.http_lock);
        }
    },

    // Show blank config form for creating new Getmail configs
    add_config: function()
    {
        this.active_config = null;
        this.select_config();
    }
};

window.rcmail && rcmail.addEventListener('init', function (evt) {

    // Add button to tabs list
    var tab = $('<span>').attr('id', 'settingstabplugingetmail').addClass('tablink'),
        button = $('<a>').attr('href', rcmail.env.comm_path + '&_action=plugin.getmail')
            .html(rcmail.gettext('tabtitle', 'getmail'))
            .appendTo(tab);
    rcmail.add_element(tab, 'tabs');

    if (/^plugin.getmail/.test(rcmail.env.action)) {
        getmail_config.init();
    }
});
