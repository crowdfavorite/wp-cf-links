(function() {
    tinymce.create('tinymce.plugins.cflinks', {
        init: function(ed, url) {
            this.editor = ed;
            ed.addCommand('cfLinks',
            function() {
                var se = ed.selection;
                ed.windowManager.open({
					title: 'Select Links List',
                    file: 'options-general.php?page=cf-links.php&cflk_page=dialog',
                    width: 350 + parseInt(ed.getLang('cflinks.delta_width', 0)),
                    height: 450 + parseInt(ed.getLang('cflinks.delta_height', 0)),
                    inline: 1
                },
                {
                    plugin_url: url
                });
            });
            ed.addButton('cfLinksBtn', {
                title: 'Select Link List Below',
                cmd: 'cfLinks',
				image : url + '/images/brick_add.png',
            });
            ed.addShortcut('ctrl+k', 'Select Link List', 'cfLinks');
            ed.onNodeChange.add(function(ed, cm, n, co) {
                cm.setDisabled('cfLinks', co && n.nodeName != 'A');
                cm.setActive('cfLinks', n.nodeName == 'A' && !n.name);
            });
        },
        getInfo: function() {
            return {
                longname: 'CF Links',
                author: 'Crowd Favorite',
                authorurl: 'http://crowdfavorite.com',
                infourl: 'http://crowdfavorite.com',
                version: "1.2"
            };
        }
    });
    tinymce.PluginManager.add('cflinks', tinymce.plugins.cflinks);
})();