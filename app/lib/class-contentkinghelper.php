<?php
/**
 * Helper methods.
 *
 * @file
 * Handling system info.
 * @package contentking-plugin
 */

/**
 * Class ContentkingHelper.
 *
 * @package contentking-plugin
 */
class ContentkingHelper implements ContentkingHelperInterface
{

	/**
	 * Get all URLs on all different domains given WordPress instance.
	 *
	 * @return array<string>
	 */
	public function get_websites()
	{
		global $sitepress;

		if (
			function_exists('icl_object_id')
			&& is_object($sitepress)
			&& method_exists($sitepress, 'get_setting')
			&& $sitepress->get_setting('language_negotiation_type', FALSE) === '2'
		) {
			return array_map(
				function ($lang) {
					return $lang['url'];
				},
				icl_get_languages()
			);
		}

		return [get_site_url()];
	}

	/**
	 * Get list of features.
	 *
	 * @return array<array{type: string, api_url: string}>
	 */
	public function get_features()
	{
		global $wp_version;

		$features = [];
		$version = str_replace( '-src', '', $wp_version);

		if (version_compare($version, '4.7', '>=')) {
			$api_url = get_rest_url() . 'contentking/v1/admin_url/';
			$edit_link = [
				'type' => 'edit_link',
				'api_url' => $api_url,
			];

			$features[] = $edit_link;
		}

		return $features;
	}

}
