<?php

/**
 * Widget for CF-Links Plugin
 * @package cf-links
 */
class cflk_widget extends WP_Widget {
	function cflk_widget() {
		$widget_ops = array('classname' => 'cflk-wiget', 'description' => __('Widget for displaying CF Links lists.', 'cf-links'));
		$this->WP_Widget('cflk-wiget', __('CF Links Widget', 'cf-links'), $widget_ops);
	}

	function widget( $args, $instance ) {
		extract( $args, EXTR_SKIP );
		
		$html = $before_widget.
				$before_title.'CF Links Widget'.$after_title;

		$list_args = array(
			'context' => 'widget'
		);
		$html .= cflk_get_links(esc_attr($instance['cflk-list']), $list_args);

		$html .= $after_widget;
		echo $html;
	}

	function update( $new_instance, $old_instance ) {
		$updated_instance = $new_instance;
		return $updated_instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array() );
		
		global $cflk_links;
		$lists = $cflk_links->get_all_lists_for_blog();
		
		if (!empty($lists)) {
			?>
			<p>
				<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'cf-links'); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('list_key'); ?>"><?php _e('List:', 'cf-links'); ?></label>
				<select id="<?php echo $this->get_field_id('list_key'); ?>" name="<?php echo $this->get_field_name('list_key'); ?>">
					<option value="0"><?php _e('--Select Links List--', 'cf-links'); ?></option>
					<?php foreach($lists as $id => $list) { ?>
						<option value="<?php echo $id; ?>"<?php selected($instance['list_key'], $id); ?>><?php echo $list['nicename']; ?></option>
					<?php } ?>
				</select>
			</p>
			<p>
				<a href="<?php echo admin_url('options-general.php?page=cf-links'); ?>"><?php _e('Edit CF Links'); ?></a>
			</p>
			<?php
		}
		else {
			echo '<p>'.__('No CF Links Lists have been setup.  Please ', 'cf-links').'<a href="'.admin_url('options-general.php?page=cf-links').'">'.__('create a CF Links list').'</a>'.__(' before proceeding', 'cf-links').'.</p>';
		}
	}
}
add_action('widgets_init', create_function('', "register_widget('cflk_widget');"));

?>