<?php
/**
 * Interface for helper class.
 *
 * @file Handling system info.
 * @package contentking-plugin
 */

/**
 * Interface for class ContentkingHelper
 */
interface ContentkingHelperInterface {

	/**
	 * Get all URLs on all different domains given WordPress instance.
	 *
	 * @return Array of URLs
	 */
	public function get_websites();

	/**
	 * Get list of features.
	 *
	 * @return Array of objects
	 */
	public function get_features();

}
