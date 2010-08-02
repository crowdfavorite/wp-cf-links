<?php

/**
 * Widget for CF-Links Plugin
 * @package cf-links
 */
class cflk_widget extends WP_Widget {
	function cflk_widget() {
		$widget_ops = array( 'classname' => 'cflk-wiget', 'description' => 'CF Links Widget' );
		$this->WP_Widget( 'cflk-wiget', 'CF Links Widget', $widget_ops );
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
		
		$html = '
			<div>
				<select name="'.$this->get_field_name('cflk-list').'" id="'.$this->get_field_id('cflk-list').'">';
		if (!empty($lists)) {
			foreach($lists as $id => $list) {
				$html .= '
						<option value="'.$id.'"'.selected($instance['cflk-list'], $id, false).'>'.$list['nicename'].'</option>';
				
			}
		}
		$html .= '
				</select>
			</div>';
		echo $html;
	}
}

add_action( 'widgets_init', create_function( '', "register_widget('cflk_widget');" ) );

?>