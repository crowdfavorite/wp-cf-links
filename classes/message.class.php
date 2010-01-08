<?php

/**
 * Spitting image of cfct_message
 *
 * @package default
 */
class cflk_message {
	private $data = array();

	public function __construct(array $args = array('success' => false)) {
		$this->add($args);
	}

// Setters
	public function add(array $args = array('success' => false)) {
		foreach($args as $name => $value) {
			$this->data[$name] = $value;
		}
	}

// Getters
	public function get_results() {
		return $this->data;
	}

	public function get_json() {
		return cf_json_encode($this->get_results());
	}

	public function __toString() {
		return $this->get_json();
	}

// Delivery
	/**
	 * Deliver the JSON and get out of the page load.
	 *
	 * @return void
	 */
	public function send() {
		header('Content-type: application/json');
		echo $this->get_json();
		exit;
	}		
}

?>