<div class="wrap">
	<?php
	include('message-updated.php');
	include('message-link-delete.php');
	include('message-import-problem.php');
	?>
	<form action="<?php echo admin_url(); ?>" method="post" id="cflk-form">
		<?php echo cflk_nav('edit', htmlspecialchars($cflk['nicename'])); ?>
		<table class="widefat" style="margin-bottom: 10px;">
			<thead>
				<tr>
					<th scope="col" colspan="2">
						<?php _e('Description', 'cf-links'); ?>
					</th>
				</tr>
			</thead>
			<tr>
				<td>
					<div id="description_text">
						<p>
							<?php
							if (!empty($cflk['description'])) {
								echo esc_html($cflk['description']);
							}
							else {
								echo '<span style="color: #999999;">'.__('No Description has been set for this list. Click the edit button to enter a description. &rarr;', 'cf-links').'</span>';
							}
							
							// If this is a reference list, add that to the description.
							if ($cflk['reference']) {
								$ref_blog = get_blog_details($cflk['reference_parent_blog']);
								echo '<div class="cflk-reference-info"><strong>'.__('This list is a reference to ', 'cf-links').$ref_blog->blogname.__("'s links list.", 'cf-links').'</strong></div>';
							}
							// If this list has been referenced on other sites, note that
							if (is_array($cflk['reference_children']) && !empty($cflk['reference_children'])) {
								echo '<div class="cflk-reference-info">';
								$child_names = '';
								$child_count = 1;
								foreach ($cflk['reference_children'] as $child) {
									$child_info = explode('-', $child, 2);
									$child_blog = get_blog_details($child_info[0]);
									if ($i > 1) {
										$child_names .= ', ';
									}
									$child_names .= $child_blog->blogname;
									echo '<input type="hidden" name="cflk_reference_children[]" value="'.$child.'" />';
									$child_count++;
								}
								echo __('This list is referenced by the following blogs: ', 'cf-links').$child_names.__('. Any updates to this list will be reflected on those blogs.', 'cf-links');
								echo '</div>';
							}
							?>
						</p>
					</div>
					<?php if (!$cflk['reference']) { ?>
					<div id="description_edit" style="display:none;">
						<textarea name="cflk_description" rows="5" style="width:100%;"><?php echo esc_textarea($cflk['description']); ?></textarea>
					</div>
					<?php } ?>
				</td>
				<td width="150px" style="text-align:right; vertical-align:middle;">
					<?php if (!$cflk['reference']) { ?>
					<div id="description_edit_btn">
						<input type="button" class="button" id="link_description_btn" value="<?php _e('Edit', 'cf-links'); ?>" onClick="editDescription()" />
					</div>
					<div id="description_cancel_btn" style="display:none;">
						<input type="button" class="button" id="link_description_cancel" value="<?php _e('Cancel', 'cf-links'); ?>" onClick="cancelDescription()" />
					</div>
					<?php } ?>
				</td>
			</tr>
		</table>
		<table class="widefat">
			<thead>
				<tr>
					<th scope="col" class="link-level"><?php _e('Level','cf-links'); ?></th>
					<th scope="col" class="link-order" style="text-align: center;"><?php _e('Order', 'cf-links'); ?></th>
					<th scope="col" class="link-type"><?php _e('Type', 'cf-links'); ?></th>
					<th scope="col" class="link-value"><?php _e('Link', 'cf-links'); ?></th>
					<th scope="col" class="link-text"><?php _e('Link Text (Optional)', 'cf-links'); ?></th>
					<th scope="col" class="link-open-new"><?php _e('New Window', 'cf-links'); ?></th>
					<th scope="col" class="link-nofollow"><?php _e('No Follow', 'cf-links'); ?></th>
					<th scope="col" class="link-delete"><?php _e('Delete', 'cf-links'); ?></th>
				</tr>
			</thead>
		</table>
		<ul id="cflk-list">
			<?php
			if ($cflk_count > 0 && is_array($cflk['data']) && !empty($cflk['data'])) {
				foreach ($cflk['data'] as $key => $setting) {
					$tr_class = '';
					if($setting['link'] == 'HOLDER') {
						$tr_class = ' class="tr_holder"';
					}
					$buttons_style = (!$cflk['reference'] ? '' : ' disabled="disabled" style="visibility: hidden;"'); 
					?>
					<li id="listitem_<?php echo $key; ?>" class="level-<?php echo $setting['level']; ?>">
						<table class="widefat">
							<tr<?php echo $tr_class; ?>>
								<td class="link-level">
									<div>
										<input type="hidden" class="link-level-input" name="cflk[<?php echo $key; ?>][level]" value="<?php echo $setting['level']; ?>" />
										<button class="level-decrement decrement-<?php echo $key; ?>"<?php echo $buttons_style; ?>>&laquo;</button>
										<button class="level-increment increment-<?php echo $key; ?>"<?php echo $buttons_style; ?>>&raquo;</button>
									</div>
								</td>
								<td class="link-order" style="text-align: center; vertical-align:middle;">
									<img src="<?php echo CFLK_DIR_URL; ?>images/arrow_up_down.png" class="handle" alt="move"<?php echo $buttons_style; ?> />
								</td>
								<td class="link-type" style="vertical-align:middle;">
									<?php
									$type_selected = '';
									$type_options = '';
									foreach ($cflk_types as $type) {
										$selected = '';
										if($type['type'] == $setting['type']) {
											$selected = ' selected="selected"';
											$type_selected = $type['nicename'];
										}
										$type_options .= '<option value="'.$type['type'].'"'.$selected.'>'.$type['nicename'].'</option>';
									}
									
									if (!$cflk['reference']) { ?>
									?>
									<select name="cflk[<?php echo $key; ?>][type]" id="cflk_<?php echo $key; ?>_type" onChange="showLinkType(<?php echo $key; ?>)">
										<?php echo $type_options; ?>
									</select>
									<?php 
									} 
									else { 
										echo $type_selected;
									} ?>
								</td>
								<td class="link-value" style="vertical-align:middle;">
									<?php
									foreach ($cflk_types as $type) {
										echo cflk_get_type_input($type, $type_selected, $key, $setting['cat_posts'], $setting['link']);
									}
									?>
								</td>
								<td class="link-text" style="vertical-align:middle;">
									<?php
									if (!$cflk['reference']) {
										if (strip_tags($setting['title']) == '') {
											$edit_show = '';
											$input_show = ' style="display:none;"';
										}
										else {
											$edit_show = ' style="display:none;"';
											$input_show = '';
										}
										?>
										<span id="cflk_<?php echo $key; ?>_title_edit"<?php echo $edit_show; ?>>
											<input type="button" class="button" id="link_edit_title_<?php echo $key; ?>" value="<?php _e('Edit Text', 'cf-links'); ?>" onClick="editTitle('<?php echo $key; ?>')" />
										</span>
										<span id="cflk_<?php echo $key; ?>_title_input"<?php echo $input_show; ?>>
											<input type="text" id="cflk_<?php echo $key; ?>_title" name="cflk[<?php echo $key; ?>][title]" value="<?php echo esc_attr($setting['title']); ?>" style="max-width: 150px;" />
											<input type="button" class="button" id="link_clear_title_<?php echo $key; ?>" value="<?php _e('&times;', 'cf-links'); ?>" onClick="clearTitle('<?php echo $key; ?>')" />
										</span>
										<?php
									}
									else {
										echo strip_tags($setting['title']);
									}
									?>
								</td>
								<td class="link-open-new" style="text-align: center; vertical-align:middle;">
									<?php
									if (!$cflk['reference']) {
										$opennew = '';
										if ($setting['opennew']) {
											$opennew = ' checked="checked"';
										}
										?>
										<input type="checkbox" id="link_opennew_<?php echo $key; ?>" name="cflk[<?php echo $key; ?>][opennew]"<?php echo $opennew; ?> />
										<?php
									}
									else {
										if ($setting['opennew']) {
											_e('Yes', 'cf-links');
										}
										else {
											_e('No', 'cf-links');
										}
									} 
									?>
								</td>
								<td class="link-nofollow" style="text-align: center; vertical-align:middle;">
									<?php
									if (!$cflk['reference']) {
										$nofollow = '';
										if ($setting['nofollow']) {
											$nofollow = ' checked="checked"';
										}
										?>
										<input type="checkbox" id="link_nofollow_<?php echo $key; ?>" name="cflk[<?php echo $key; ?>][nofollow]"<?php echo $nofollow; ?> />
									<?php
									}
									else {
										if ($setting['nofollow']) {
											_e('Yes', 'cf-links');
										}
										else {
											_e('No', 'cf-links');
										}
									}
									?>
								</td>
								<td class="link-delete" style="text-align: center; vertical-align:middle;">
									<?php if (!$cflk['reference']) { ?>
									<input type="button" class="button" id="link_delete_<?php echo $key; ?>" value="<?php _e('Delete', 'cf-links'); ?>" onClick="deleteLink('<?php echo $cflk_key; ?>','<?php echo $key; ?>')" />
									<?php } ?>
								</td>
							</tr>
						</table>
					</li>
					<?php
				}
			}
			?>
		</ul>	
		<?php if (!$cflk['reference']) { ?>
		<table class="widefat">
			<tr>
				<td style="text-align:left;">
					<input type="button" class="button" name="link_add" id="link_add" value="<?php _e('Add New Link', 'cf-links'); ?>" onClick="addLink()" />
				</td>
			</tr>
		</table>
		<p class="submit" style="border-top: none;">
			<input type="hidden" name="cf_action" value="cflk_update_settings" />
			<input type="hidden" name="cflk_key" value="<?php echo esc_attr($cflk_key); ?>" />
			<input type="submit" name="submit" id="cflk-submit" value="<?php _e('Update Settings', 'cf-links'); ?>" class="button-primary button" />
		</p>
		<?php } ?>
	</form>	
	<?php if (!$cflk['reference']) { ?>
	<div id="newitem_SECTION">
		<li id="listitem_###SECTION###" class="level-0" style="display:none;">
			<table class="widefat">
				<tr>
					<td class="link-level">
						<div>
							<input type="hidden" class="link-level-input" name="cflk[###SECTION###][level]" value="0" />
							<button class="level-decrement">&laquo;</button>
							<button class="level-increment">&raquo;</button>
						</div>
					</td>
					<td class="link-order" style="text-align: center;"><img src="<?php echo CFLK_DIR_URL; ?>images/arrow_up_down.png" class="handle" alt="move" /></td>
					<td class="link-type">
						<select name="cflk[###SECTION###][type]" id="cflk_###SECTION###_type" onChange="showLinkType('###SECTION###')">
							<?php
							foreach ($cflk_types as $type) {
								$select_settings[$type['type'].'_select'] = '';
								if ($type['type'] == 'url') {
									$select_settings[$type['type'].'_select'] = ' selected="selected"';
								}
								echo '<option value="'.$type['type'].'"'.$select_settings[$type['type'].'_select'].'>'.$type['nicename'].'</option>';
							}
							?>
						</select>
					</td>
					<td class="link-value">
						<?php
						$key = '###SECTION###';
						foreach ($cflk_types as $type) {
							$select_settings[$type['type'].'_show'] = 'style="display: none;"';
							if ($type['type'] == 'url') {
								$select_settings[$type['type'].'_show'] = 'style=""';
							}
							echo cflk_get_type_input($type, $select_settings[$type['type'].'_show'], $key, '', '');
						}
						?>
					</td>
					<td class="link-text">
						<span id="cflk_###SECTION###_title_edit" style="display: none">
							<input type="button" class="button" id="link_edit_title_###SECTION###" value="<?php _e('Edit Text', 'cf-links'); ?>" onClick="editTitle('###SECTION###')" />
						</span>
						<span id="cflk_###SECTION###_title_input">
							<input type="text" id="cflk_###SECTION###_title" name="cflk[###SECTION###][title]" value="" style="max-width: 150px;" />
							<input type="button" class="button" id="link_clear_title_###SECTION###" value="<?php _e('&times;', 'cf-links'); ?>" onClick="clearTitle('###SECTION###')" />
							<br />
							<?php _e('ex: Click Here','cf-links'); ?>
						</span>
					</td>
					<td class="link-open-new" style="text-align: center; vertical-align:middle;">
							<input type="checkbox" id="link_opennew_###SECTION###" name="cflk[###SECTION###][opennew]" />
					</td>
					<td class="link-nofollow" style="text-align: center; vertical-align:middle;">
							<input type="checkbox" id="link_nofollow_###SECTION###" name="cflk[###SECTION###][nofollow]" />
					</td>
					<td class="link-delete" style="text-align: center;">
						<input type="button" class="button" id="link_delete_###SECTION###" value="<?php _e('Delete', 'cf-links'); ?>" onClick="deleteLink('<?php echo $cflk_key; ?>', '###SECTION###')" />
					</td>
				</tr>
			</table>
		</li>
	</div>
	<?php
	}
	// select-modal placeholder
	//dp($cflk_types);
	foreach ($cflk_types as $type) {
		if($type['input'] == 'select-modal') {
			$select_settings[$type['type'].'_show'] = 'style="display: none;"';
			// fool cflk_get_type_input in to giving us a select list
			$type['input'] = 'select';
			echo '
				<div id="'.$type['type'].'-modal" class="'.$type['type'].'-modal" style="display: none;">
					'.cflk_get_type_input($type, $select_settings[$type['type'].'_show'], 'list', '', '').'
					<input type="button" name="'.$type['type'].'-set" value="Done" class="modal-done button"/> 
				</div>
				';
		}
	}
	// Allow other plugins the ability to display info on this page
	echo apply_filters('cflk_edit', '', $cflk_key);
	?>
</div><!--.wrap-->