<html>
	<head>
		<title><?php _e('Select CF Links List', 'cf-links'); ?></title>
		<script type="text/javascript" src="<?php echo includes_url('js/jquery/jquery.js'); ?>"></script>
		<script type="text/javascript" src="<?php echo includes_url('js/tinymce/tiny_mce_popup.js'); ?>"></script>
		<script type='text/javascript' src='<?php echo includes_url('js/quicktags.js'); ?>'></script>
		<script type="text/javascript">
			;(function($) {
				$(function() {
					$(".cflk-list-link").live('click', function(e) {
						var key = $(this).attr('rel');
						cflk_insert(key);
						e.preventDefault();
					});
				});
			})(jQuery);
		
			function cflk_insert(key) {
				tinyMCEPopup.execCommand("mceBeginUndoLevel");
				tinyMCEPopup.execCommand('mceInsertContent', false, '[cflk name="'+key+'"]');
				tinyMCEPopup.execCommand("mceEndUndoLevel");
				tinyMCEPopup.close();
				return false;
			}
		</script>
		<style type="text/css">
			.cflk-list {
				padding-left:10px;
			}
		</style>
	</head>
	<body>
		<?php
		if (is_array($cflk_list) && !empty($cflk_list)) {
			echo '
				<p>'.__('Click on the name of the CF Links List below to add it to the content of the current post.', 'cf-links').'</p>
				<ul class="cflk-list">
			';
			foreach ($cflk_list as $cflk) {
				$options = maybe_unserialize(maybe_unserialize($cflk->option_value));
				?>
				<li>
					<a href="#" rel="<?php echo esc_attr($cflk->option_name); ?>" class="cflk-list-link"><?php echo esc_html($options['nicename']); ?></a>
				</li>
				<?php
			}
			echo '</ul>';
		}
		else {
			echo '<p>'.__('No CF Links Lists have been setup.  Please setup a list before proceeding.', 'cf-links').'</p>';
		}
		?>
	</body>
</html>