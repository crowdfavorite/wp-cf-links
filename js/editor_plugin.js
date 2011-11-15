(function() {
    tinymce.create('tinymce.plugins.cflinks', {
        init: function(ed, url) {
			pluginUrl = url.replace('js', '');
			imageUrl = pluginUrl+'images/link-list.gif';
            this.editor = ed;
            ed.addCommand('cfLinks',
            function() {
                var se = ed.selection;
                ed.windowManager.open({
					title: 'Select CF Links List',
                    file: 'options-general.php?page=cf-links.php&cflk_page=dialog',
                    width: 350 + parseInt(ed.getLang('cflinks.delta_width', 0)),
                    height: 450 + parseInt(ed.getLang('cflinks.delta_height', 0)),
                    inline: 1
                },
                {
                    plugin_url: pluginUrl
                });
            });
            ed.addButton('cfLinksBtn', {
                title: 'CF Links',
                cmd: 'cfLinks',
				image : imageUrl
            });
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