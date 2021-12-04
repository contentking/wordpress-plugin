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
class ContentkingHelper implements ContentkingHelperInterface {
	/**
	 * Get all URLs on all different domains given WordPress instance.
	 *
	 * @return Array of URLs
	 */
	public function get_websites() {
		$urls = array();
		global $sitepress;
		// is_wpml?
		if ( function_exists( 'icl_object_id' ) && ! empty( $sitepress ) && method_exists( $sitepress, 'get_setting' ) ) :
			// is wmpl multidomain?
			if ( $sitepress->get_setting( 'language_negotiation_type', false ) === '2' ) :
				$langs = icl_get_languages();
				foreach ( $langs as $lang ) :
					array_push( $urls, $lang['url'] );
					endforeach;
				return $urls;
			endif;
		endif;

		array_push( $urls, get_site_url() );

		return $urls;

	}

	/**
	 * Get list of features.
	 *
	 * @return Array of objects
	 */
	public function get_features() {

		$features = array();
		// Determine whether we support WP REST API endpoint.
		// WP version test.
		global $wp_version;
		$version = str_replace( '-src', '', $wp_version );
		if ( version_compare( $version, '4.7', '>=' ) ) : // We have WP REST API, hurray!
			// Get REST API endpoint URL.
			$api_url   = get_rest_url() . 'contentking/v1/admin_url/';
			$edit_link = array(
				'type'    => 'edit_link',
				'api_url' => $api_url,
			);
			array_push( $features, $edit_link );
		endif;
		return $features;

	}

}
