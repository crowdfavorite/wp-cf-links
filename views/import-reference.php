<?php
/** ::Reference Functionality:: **/
if (function_exists('get_blog_list')) {
	global $wpdb, $blog_id;
	$reference_data = array();
	$sites = $wpdb->get_results($wpdb->prepare("SELECT id, domain FROM $wpdb->site ORDER BY ID ASC"), ARRAY_A);
	
	if (is_array($sites) && count($sites)) {
		foreach ($sites as $site) {
			$site_id = $site['id'];
			$blogs = $wpdb->get_results($wpdb->prepare("SELECT blog_id FROM $wpdb->blogs WHERE site_id = '$site_id' ORDER BY blog_id ASC"), ARRAY_A);
			
			if (is_array($blogs) && !empty($blogs)) {
				foreach ($blogs as $blog) {
					if ($blog['blog_id'] == $blog_id) { continue; }
					$details = get_blog_details($blog['blog_id']);
					$reference_data[$details->blog_id] = array(
						'id'		=> $details->blog_id,
						'name'		=> $details->blogname,
					);
				}
			}
		}
	}
}
?>
<form action="<?php echo admin_url(); ?>" method="post" id="cflk-create">
	<table class="widefat">
		<thead>
			<tr>
				<th scope="col"><?php _e('Select List to Create Reference', 'cf-links'); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>
					<p>
						<?php _e('To reference a list in another blog, select it from the list below and click "Create Reference List".  This provides the ability to have a read only links list that updates when the referenced list is updated.','cf-links'); ?>
					</p>
				</td>
			</tr>
			<tr>
				<td>
					<select name="cflk_reference_list">
						<option value=""><?php _e('Select Reference List:','cf-links'); ?></option>
						<?php
						if (is_array($reference_data) && !empty($reference_data)) {
							foreach ($reference_data as $blog) {
								switch_to_blog($blog['id']);
								$blog_links = cflk_get_list_links();
								restore_current_blog();
								if (is_array($blog_links) && !empty($blog_links)) {
									foreach ($blog_links as $key => $data) {
										if ($data['reference']) { continue; }
										echo '<option_value="'.$blog['id'].'-'.$key.'">'.$blog['name'].' - '.$data['nicename'].'</option>';
									}
								}
							}
						}
						?>
					</select>
				</td>
			</tr>
		</tbody>
	</table>				
	<p class="submit" style="border-top: none;">
		<input type="hidden" name="cf_action" value="cflk_insert_reference" />
		<input type="hidden" name="cflk_reference" id="cflk_reference" value="reference_list" />
		<input type="submit" name="submit" id="cflk-submit" class="button-primary button" value="<?php _e('Create Reference List', 'cf-links'); ?>" />
	</p>
</form>