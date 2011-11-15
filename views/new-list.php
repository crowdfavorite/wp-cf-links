<div class="wrap">
	<?php echo cflk_nav('create'); ?>
	<form action="<?php echo admin_url(); ?>" method="post" id="cflk-create">
		<table class="widefat">
			<thead>
				<tr>
					<th scope="col"><?php _e('Link List Name', 'cf-links'); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>
						<input type="text" name="cflk_nicename" size="55" />
					</td>
				</tr>
			</tbody>
		</table>
		<p class="submit" style="border-top: none;">
			<input type="hidden" name="cf_action" value="cflk_insert_new" />
			<input type="hidden" name="cflk_create" id="cflk_create" value="new_list" />
			<input type="submit" name="submit" id="cflk-submit" value="<?php _e('Create List', 'cf-links'); ?>" />
		</p>
	</form>
</div>
