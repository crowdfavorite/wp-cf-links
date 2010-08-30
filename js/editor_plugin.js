(function() {
	tinymce.create('tinymce.plugins.cflinks', {
		init : function(ed, url) {
			var pluginUrl = url.replace('js', '');
			
			// Register button
			ed.addButton('cfLinksBtn', {
				title : 'Click to Insert CF Links List',
				image : pluginUrl + '/images/link-list.gif',
				cmd : 'CFLK_Insert'
			});
			
			// Register command
			ed.addCommand('CFLK_Insert', function() {
				ed.windowManager.open({
					file : 'index.php?cf_action=cflk-dialog',
					width : 350 + ed.getLang('cflinks.delta_width', 0),
					height : 450 + ed.getLang('cflinks.delta_height', 0),
					inline : 1
				}, {
					plugin_url: url
				});
			});
		},
		createControl : function(n, cm) {
			return null;
		},
		getInfo : function() {
			return {
				longname : "CFLinks",
				author : 'CrowdFavorite',
				authorurl : 'http://crowdfavorite.com',
				infourl : 'http://crowdfavorite.com',
				version : "2.0"
			};
		}
	});
	tinymce.PluginManager.add('cflinks', tinymce.plugins.cflinks);
})();