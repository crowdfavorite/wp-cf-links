<?php echo screen_icon().'<h2>'.__('CF Links', 'cf-links').'</h2>'; ?>
<ul class="subsubsub">
	<li>
		<a href="<?php echo admin_url('options-general.php?page=cf-links.php&cflk_page=main'); ?>" <?php echo $main_text; ?>><?php _e('Links Lists', 'cf-links'); ?></a> | 
	</li>
	<li>
		<a href="<?php echo admin_url('options-general.php?page=cf-links.php&cflk_page=create'); ?>" <?php echo $add_text; ?>><?php _e('New Links List', 'cf-links'); ?></a> | 
	</li>
	<li>
		<a href="<?php echo admin_url('options-general.php?page=cf-links.php&cflk_page=import'); ?>" <?php echo $import_text; ?>><?php _e('Import/Export Links List', 'cf-links'); ?></a> | 
	</li>
	<li>
		<a href="<?php echo admin_url('widgets.php'); ?>"><?php _e('Edit Widgets','cf-links'); ?></a>
	</li>
</ul>
<?php if ($list != '') { ?>
	<h3 style="clear:both;">
		<?php _e('Links Options for: ', 'cf-links'); ?>
		<span id="cflk_nicename_h3"><?php echo $list; ?></span>
		&nbsp;<a href="#" class="cflk_edit_link"><?php _e('Edit', 'cf-links'); ?></a>
		<span id="cflk_nicename_input" style="display:none;">
			<input type="text" name="cflk_nicename" id="cflk_nicename" value="<?php echo esc_attr($list); ?>" />
			<input type="button" name="cflk-nicename-submit" id="cflk-nicename-submit" class="button" value="<?php _e('Save', 'cf-links'); ?>" />
			<input type="button" name="link_nicename_cancel" id="link_nicename_cancel" class="button" value="<?php _e('Cancel', 'cf-links'); ?>" onClick="cancelNicename()" />					
		</span>
	</h3>
<?php } ?>