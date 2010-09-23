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
		global $cflk_links;
		$type = $data['type'];
		$html = '
			<dl class="menu-item-bar">
				<dt class="menu-item-handle">
					<span class="item-view">
						<p class="item-title">'.__('Missing Link Type', 'cf-links').': '.strip_tags(stripslashes($data['type'])).'</p>
						<p>'.__('Original Type', 'cf-links').': '.strip_tags(stripslashes($type)).'</p>
						<p class="cflk-missing-debug-head">'.__('DEBUG Info for Missing Link Type.', 'cf-links').' | <a href="#" class="cflk-missing-debug-show" id="cflk-missing-debug-show-'.$item_id.'">'.__('Show', 'cf-links').'</a><a href="#" class="cflk-missing-debug-hide" id="cflk-missing-debug-hide-'.$item_id.'">'.__('Hide', 'cf-links').'</a></p>
						<p class="cflk-missing-debug-info" id="cflk-missing-debug-info-'.$item_id.'">
						';
						if (is_array($data) && !empty($data)) {
							foreach ($data as $key => $value) {
								$html .= '<b>Key:</b> '.strip_tags(stripslashes($key)).' -- <b>Value:</b> '.strip_tags(stripslashes($value)).'<br />';
							}
						}
						$html .= '
						</p>
						<input type="hidden" class="clfk-link-data" name="cflk_links['.$item_id.'][data]" value="'.(!empty($data) ? esc_attr(cf_json_encode($data)) : null).'" />
						<input class="menu-item-depth" type="hidden" name="cflk_links['.$item_id.'][level]" value="'.$level.'" />
					</span>
				</dt>
			</dl>
		';

		return $html;
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
			.cflk-missing-debug-head {
				background-color:#FFFFE0;
				border:1px solid #E6DB55;
				-moz-border-radius:5px;
				-webkit-border-radius:5px;
				-khtml-border-radius:5px;
				border-radius:5px;
				padding:5px;
				margin:10px;
			}

			.cflk-missing-debug-head-open {
				-moz-border-radius-topleft: 5px;
				-webkit-border-top-left-radius: 5px;
				-khtml-border-top-left-radius: 5px;
				border-top-left-radius: 5px;
				-moz-border-radius-topright: 5px;
				-webkit-border-top-right-radius: 5px;
				-khtml-border-top-right-radius: 5px;
				border-top-right-radius: 5px;
				background-color:#FFFFE0;
				border:1px solid #E6DB55;
				padding:5px;
				margin:10px;
			}
			
			.cflk-missing-debug-info {
				-moz-border-radius-bottomleft: 5px;
				-webkit-border-bottom-left-radius: 5px;
				-khtml-border-bottom-left-radius: 5px;
				border-bottom-left-radius: 5px;
				-moz-border-radius-bottomright: 5px;
				-webkit-border-bottom-right-radius: 5px;
				-khtml-border-bottom-right-radius: 5px;
				border-bottom-right-radius: 5px;
				padding:5px 0 5px 10px;
				border:1px solid #E6DB55;
				background-color:#DFDFDF;
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
				_this.parent().removeClass("cflk-missing-debug-head").addClass("cflk-missing-debug-head-open");
				$("#cflk-missing-debug-info-"+id).slideDown();
				$("#cflk-missing-debug-hide-"+id).show();
				_this.hide();
				return false;
			});
			// Hide the Debug Info
			$(".cflk-missing-debug-hide").click(function() {
				var _this = $(this);
				var id = _this.attr("id").replace("cflk-missing-debug-hide-", "");
				_this.parent().removeClass("cflk-missing-debug-head-open").addClass("cflk-missing-debug-head");
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