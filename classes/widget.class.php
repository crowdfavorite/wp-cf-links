<?php

/**
 * new WordPress Widget format
 * Wordpress 2.8 and above
 * @see http://codex.wordpress.org/Widgets_API#Developing_Widgets
 */
class cflk_widget extends WP_Widget {
	function cflk_widget() {
		$widget_ops = array( 'classname' => 'cflk-wiget', 'description' => 'CF Links Widget' );
		$this->WP_Widget( 'cflk-wiget', 'CF Links Widget', $widget_ops );
	}

	function widget( $args, $instance ) {
		extract( $args, EXTR_SKIP );
		echo $before_widget;
		echo $before_title;
		echo 'CF Links Widget'; // Can set this with a widget option, or omit altogether
		echo $after_title;

		//
		// Widget display logic goes here
		//

		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		// update logic goes here
		$updated_instance = $new_instance;
		return $updated_instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array() );

		// display field names here using:
		// $this->get_field_id('option_name') - the CSS ID
		// $this->get_field_name('option_name') - the HTML name
		// $instance['option_name'] - the option value
	}
}

add_action( 'widgets_init', create_function( '', "register_widget('cflk_widget');" ) );

?>