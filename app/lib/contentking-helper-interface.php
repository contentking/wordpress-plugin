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
interface ContentkingHelperInterface
{

	/**
	 * Get all URLs on all different domains given WordPress instance.
	 *
	 * @return array<string>
	 */
	public function get_websites();

	/**
	 * Get list of features.
	 *
	 * @return array<array{type: string, api_url: string}>
	 */
	public function get_features();

}
