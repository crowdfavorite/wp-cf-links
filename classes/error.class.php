<?php
	/**
	 * Simple & Generic Error Wrapper to WP_Error to give auto-output functionality
	 *
	 * @package cf-links
	 */
	class cflk_error extends WP_Error {
				
		/**
		 * Construct
		 * Properly constructs the parent class
		 *
		 * @see /wp-includes/classes.php for WP_Error definition
		 */
		public function __construct($code = '', $message = '', $data = '') {
			parent::WP_Error($code = '', $message = '', $data = '');
		}
		
		/**
		 * Return the simple HTML for the errors in this instance
		 *
		 * @return string html
		 */
		public function html() {
			$html = '';
			foreach($this->errors as $key => $messages) {
				$html .= '<div class="cflk-error-'.$key.' error below-h2">';
				foreach($messages as $id => $message) {
					$html .= '<p>'.$message.'</p>';
				}
				$html .= '</div>';
			}
			return $html;
		}
		
		/**
		 * Echo errors, if any
		 *
		 * @return void
		 */
		public function display() {
			if ($this->have_errors()) {
				echo $this->html();
			}
		}
		
		/**
		 * Check to see if we have errors
		 *
		 * @return bool
		 */
		public function have_errors() {
			return !empty($this->errors);
		}
	}

?>