<div class="wrap">
	<?php echo cflk_nav('main'); ?>
	<form action="<?php echo admin_url(); ?>" method="post" id="cflk-form">
		<table class="widefat">
			<thead>
				<tr>
					<th scope="col"><?php _e('Links List', 'cf-links'); ?></th>
					<th scope="col" style="text-align: center;" width="80px"><?php _e('Links Count', 'cf-links'); ?></th>
					<th scope="col" style="text-align: center;" width="60px"><?php _e('Edit', 'cf-links'); ?></th>
					<th scope="col" style="text-align: center;" width="60px"><?php _e('Delete', 'cf-links'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if (count($form_data) > 0) { foreach ($form_data as $key => $info) { ?>
				<tr id="link_main_<?php echo $key; ?>">
					<td style="vertical-align: middle;">
						<a href="<?php echo admin_url('options-general.php?page=cf-links.php&cflk_page=edit&link='.$key); ?>" style="font-weight: bold; font-size: 20px;"><?php echo $info['nicename']; ?></a>
						<br />
						<?php _e('Show: ','cf-links'); ?><a href="#" id="cflk-show-<?php echo $key; ?>" class="cflk-show"><?php _e('Template Tag &amp; Shortcode','cf-links'); ?></a>
						<div id="<?php echo $key; ?>-TemplateTag" class="cflk-codebox" style="display:none;">
							<div style="float: left;">
								<?php _e('Template Tag: ', 'cf-links'); ?><code>&lt;?php if (function_exists("cflk_links")) { cflk_links("'.$key.'"); } ?&gt;</code>
								<br />
								<?php _e('Shortcode: ', 'cf-links'); ?><code>[cflk_links name="<?php echo $key; ?>"]</code>
							</div>
							<div style="float: right;">
								<a href="#" id="cflk-hide-<?php echo $key; ?>" class="cflk-hide"><?php _e('Hide','cf-links'); ?></a>
							</div>
							<div class="clear"></div>
						</div>
					</td>
					<td style="text-align: center; vertical-align: middle;" width="80px">
						<?php echo $info['count']; ?>
					</td>
					<td style="text-align: center; vertical-align: middle;" width="60px">
						<p class="submit" style="border-top: none; padding: 0; margin: 0;">
							<input type="button" name="link_edit" value="<?php _e('Edit', 'cf-links'); ?>" class="button-secondary edit" rel="<?php echo $key; ?>" />
						</p>
					</td>
					<td style="text-align: center; vertical-align: middle;" width="60px">
						<p class="submit" style="border-top: none; padding: 0; margin: 0;">
							<input type="button" id="link_delete_<?php echo $key; ?>" onclick="deleteMain('<?php echo $key; ?>')" value="<?php _e('Delete', 'cf-links'); ?>" />
						</p>
					</td>
				</tr>
				<?php } } ?>
			</tbody>
		</table>
	</form>
</div>