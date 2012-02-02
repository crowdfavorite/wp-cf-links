<div class="wrap">
	<?php echo cflk_nav('import'); ?>
	<table class="widefat" style="margin-bottom:10px;">
		<thead>
			<tr>
				<th scope="col"><?php _e('Export Link Data','cf-links'); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>
					<select id="list-export" onChange="changeExportList()">
						<option value="0"><?php _e('Select List:', 'cf-links'); ?></option>
						<?php
						if (is_array($links_lists) && !empty($links_lists)) {
							foreach ($links_lists as $key => $value) {
								echo '<option value="'.$key.'">'.$value['nicename'].'</option>';
							}
						}
						?>
					</select>	
					<input alt="" title="Export <?php echo $cflk['nicename']; ?>" class="thickbox button" type="button" value="<?php _e('Export', 'cf-links'); ?>" id="cflk-export-btn" />						
				</td>
			</tr>
		</tbody>
	</table>
	<form action="<?php echo admin_url(); ?>" method="post" id="cflk-create">
		<table class="widefat">
			<thead>
				<tr>
					<th scope="col"><?php _e('Enter Data From Export', 'cf-links'); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>
						<textarea name="cflk_import" rows="15" style="width:100%;"></textarea>
					</td>
				</tr>
			</tbody>
		</table>				
		<p class="submit" style="border-top: none;">
			<input type="hidden" name="cf_action" value="cflk_insert_new" />
			<input type="hidden" name="cflk_create" id="cflk_create" value="import_list" />
			<input type="submit" name="submit" class="button-primary button" id="cflk-submit" value="<?php _e('Import List', 'cf-links'); ?>" />
		</p>
	</form>
	<?php
	include('import-reference.php');
	?>
</div>