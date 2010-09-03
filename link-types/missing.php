<?php

class cflk_link_missing extends cflk_link_base {
	function __construct() {
		parent::__construct('missing', __('Missing', 'cf-links'));
		if (is_admin()) {
			$this->show_new_window_field = false;
			$this->show_title_field = false;
			$this->show_edit_button = false;
		}
	}
	
	/**
	 * Front end display
	 *
	 * @param array $data 
	 * @return string html
	 */
	function display($data) {
		$data['link'] = '';
		$data['title'] = '';
		return parent::display($data);
	}
	
	/**
	 * Admin info display
	 *
	 * @param array $data 
	 * @return string html
	 */
	function admin_display($data) {
		$debug = '';
		if (is_array($data) && !empty($data)) {
			$id = $data['list_key'].'-'.$data['link'].'-'.$data['type'];
			$debug .= '
			<div class="cflk-missing-debug">
				'.__('DEBUG Info for Missing Link Type.', 'cf-links').' | <a href="#" class="cflk-missing-debug-show" id="cflk-missing-debug-show-'.$id.'">'.__('Show', 'cf-links').'</a><a href="#" class="cflk-missing-debug-hide" id="cflk-missing-debug-hide-'.$id.'">'.__('Hide', 'cf-links').'</a>
				<div class="cflk-missing-debug-info" id="cflk-missing-debug-info-'.$id.'">
				';
				foreach ($data as $key => $value) {
					$debug .= '<b>Key:</b> '.esc_html($key).' -- <b>Value:</b> '.esc_html($value).'<br />';
				}
				$debug .= '
				</div>
			</div>
			';
		}
		
		return '
			<div>
				'.__('Unknown Link Type:', 'cf-links').' <span class="link">'.esc_html($data['type']).'</span>
				'.$debug.'
			</div>
			';
	}
	
	function admin_form($data) {
		return '
			<div>
				'.__('Unknown Link Type:', 'cf-links').' <span class="link">'.esc_html($data['type']).'</span>
			</div>
			';
	}
	
	function update($data) {
		return $data;
	}
	
	function admin_css() {
		return '
			/* Missing Link Type Debug Info CSS */
			.cflk-missing {
				background-color:#DFDFDF;
			}
			.cflk-missing-debug {
				background-color:#FFFFE0;
				border:1px solid #E6DB55;
				-moz-border-radius:10px;
				-webkit-border-radius:10px;
				-khtml-border-radius:10px;
				border-radius:10px;
				padding:5px;
				margin:10px;
			}

			.cflk-missing-debug-hide,
			.cflk-missing-debug-info {
				display:none;
			}
		';
	}
	
	function admin_js() {
		return '
		;(function($) {
			$(function() {
				/* Missing Link Type Debug Info JS */
				// Show the Debug Info
				$(".cflk-missing-debug-show").click(function() {
					var _this = $(this);
					var id = _this.attr("id").replace("cflk-missing-debug-show-", "");
					$("#cflk-missing-debug-info-"+id).slideDown();
					$("#cflk-missing-debug-hide-"+id).show();
					_this.hide();
					return false;
				});
				// Hide the Debug Info
				$(".cflk-missing-debug-hide").click(function() {
					var _this = $(this);
					var id = _this.attr("id").replace("cflk-missing-debug-hide-", "");
					$("#cflk-missing-debug-info-"+id).slideUp();
					$("#cflk-missing-debug-show-"+id).show();
					_this.hide();
					return false;
				});
			});
		})(jQuery);
		';
	}
}
cflk_register_link('missing','cflk_link_missing');

?>