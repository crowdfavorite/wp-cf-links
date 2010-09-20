<?php

class cflk_link_missing extends cflk_link_base {
	private $type_display = '';
	function __construct() {
		$this->type_display = __('Missing', 'cf-links');
		parent::__construct('missing', $this->type_display);
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
	
	function _admin_view($data, $item_id) {
		$type = $data['type'];
		$html = '
			<dl class="menu-item-bar">
				<dt class="menu-item-handle">
					<div class="item-view">
						<p class="item-title">'.__('Missing Link Type', 'cf-links').': '.$data['type'].'</p>
						<p>'.__('Original Type', 'cf-links').': '.$type.'</p>
						<span class="item-actions" id="item-actions-'.$item_id.'"><a href="#">Edit</a></span>
					</div>
				</dt>
			</dl>
		';

		return $html;
	}

	function admin_form($data) {
		$id = $data['list_key'].'-'.$data['link'].'-'.$data['type'];
		$debug .= '
		<div class="elm-block cflk-missing-debug">
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
		
		return '
			<div class="elm-block elm-width-200">
				<label>'.__('Unknown Link Type', 'cf-links').'</label>
				<span class="cflk-unknown-link-type">'.esc_html($data['type']).'</span>
			</div>
			'.$debug.'
		';
	}

	function type_display() {
		return $this->type_display;
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
		';
	}
}
cflk_register_link('cflk_link_missing');

?>