<?php

class cflk_links {
	protected $link_types;
	protected $lists;
		
	function __construct() {
		// enqueue_scripts
		// enqueue_styles
	}
	
	/**
	 * 
	 * Check to see that a link list exists by list id
	 * 
	 * @param string $list_id
	 * @return bool
	 */
	function is_valid_list($list_id) {
		$list = get_option($list_id);
		if (!$list) {
			return false;
		}
		return true;
	}
	
	/**
	 * Check to see that a link type is a valid
	 *
	 * @param string $type 
	 * @return void
	 */
	function is_valid_link_type($type) {
		return isset($this->link_types[$type]) && ($this->link_types[$type] instanceof cflk_link_base);
	}
	
	/**
	 * Retrieve a specific link type object
	 *
	 * @param string $type 
	 * @return mixed object/bool
	 */
	function get_link_type($type) {
		if ($this->is_valid_link_type($type)) {
			return $this->link_types[$type];
		}
		else {
			return false;
		}
	}
	
	/**
	 * Display a specific list
	 *
	 * @param string $list_id 
	 * @param array $args
	 * @return string html
	 */
	function get_list($list_id, $args) {
		if (!($list = $this->get_list_data($list_id))) {
			return false;
		}

		if (!isset($args['context'])) {
			$args['context'] == 'default';
		}

		$list = new cflk_list($list, $args, &$this->link_types);
		return apply_filters('cflk_get_list', $list);
	}

	/**
	 * Pull list from data base and store its raw form
	 *
	 * @param string $list_id 
	 * @return bool
	 */
	function get_list_data($list_id) {
		if (isset($this->lists[$list_id])) {
			return $this->lists[$list_id];
		}
		
		$list = maybe_unserialize(get_option($list_id));

		if (!is_array($list)) {
			$list = false;
		}
		else {
			$this->lists[$list_id] = $list;
		}
		
		return apply_filters('cflk_get_links_data', $list, $list_id);
	}
		
	/**
	 * Find all files to be imported.
	 * Logs all link type files to be imported to an array, caches results and returns
	 *
	 * Pretty much verbatim (with name changes) from Carrington Build
	 *
	 * @return array
	 */
	function find_included_link_types() {
		if ($types = wp_cache_get('cflk_included_modules', 'cflk_links')) {
			return $types;
		}

		$path = trailingslashit(CFLK_PLUGIN_DIR).'link-types';
		$types = array();
		if (is_dir($path)) {
			$types = array_merge($types, glob(trailingslashit($path).'*.php'));
		}

		wp_cache_set('cflk_included_modules', $types, 'cflk_links', 3600);
		return $types;			
	}
	
	/**
	 * Include the link-type files
	 * Each link type is responsible for registering itself inside the included file
	 *
	 * @return bool
	 */
	function import_included_link_types() {
		$link_type_files = $this->find_included_link_types();
		foreach ($link_type_files as $type) {
			include($type);
		}
		return true;			
	}
	
	/**
	 * Register a link type
	 * Anyone can register a link type with this function
	 * In the event of duplicate IDs, the last man in wins.
	 *
	 * @package default
	 */
	function register_link_type($classname) {
		if (class_exists($classname)) {
			$class = new $classname();
			$this->link_types[$class->id] = $class;
			return true;
		}
		return false;
	}
	
	function js() {} // not needed?
	function css() {} // not needed?
}

?>