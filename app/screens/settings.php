<?php
/**
 * Plugin settings screen accesible via WP Admin.
 *
 * @package contentking-plugin
 */

defined( 'ABSPATH' ) || die( 'This file should not be accessed directly!' );

if ( ! current_user_can( 'manage_options' ) ) :
	wp_die( __( 'You do not have sufficient permissions to access this page.', 'contentking-plugin' ) );
endif;

?>

<h1> <?php _e( 'ContentKing settings', 'contentking-plugin' ); ?></h1>
<form action="options.php" method="post">

<?php
		settings_fields( 'contentking_setting_section' );
		$contentking_client_token = get_option( 'contentking_client_token' );
?>

	<table class = "contentking-setting">
		<tr>
			<th scope="row"> <?php _e( 'API token', 'contentking-plugin' ); ?> </th>
			<td><input type="text" id="contentking_client_token" name="contentking_client_token" value="<?php echo $contentking_client_token; ?>" />	</td>
			<td>
				<?php

				$flag = get_option( 'contentking_status_flag' );
				if ( '' === $flag ) :
				?>

					<i class = "icon-ok"> </i>

				<?php else : ?>

					<i class = "icon-cancel"> </i>

				<?php endif; ?>
			</td>
		</tr>

	</table>

	<input type="hidden" name="update_contentking_token" value="1"/>
	<br/>
	<input name="Submit" type="submit" class="button button-primary" value="<?php _e( 'Save changes', 'contentking-plugin' ); ?>" />
</form>
<br/>
<?php
if ( strlen( $contentking_client_token ) > 5 && $flag === '0' ) :
	?>
	<form method="post">
		<?php wp_nonce_field( 'contentking_validate_token', 'ck_validate_token' ); ?>
	<input type="hidden" name="validate_contentking_token" value="1"/>
	<input name="Validate" type="submit" class="button button-primary" value="<?php _e( 'Validate token', 'contentking-plugin' ); ?>" />
	<p class="error">
	<?php _e( 'It looks like your API token is not validated. Click to validate.','contentking-plugin' ); ?>
	</p>
	</form>

<?php
	endif;
	?>
<h2> <?php _e( 'Instructions', 'contentking-plugin' ); ?></h2>
<ol>
	<li><?php _e( 'In a new window, log in to the ContentKing app.','contentking-plugin' ); ?></li>
	<li><?php _e( 'Go to <strong>Account</strong> then <strong>Team profile</strong>.','contentking-plugin' ); ?></li>
	<li><?php _e( 'Copy the API token from the <strong>Integrations</strong> section.','contentking-plugin' ); ?></li>
	<li><?php _e( 'Come back to this WordPress screen and paste the token into the <strong>API token</strong> field.','contentking-plugin' ); ?></li>
	<li><?php _e( 'Press <strong>Save changes</strong> and once you see the green validation tick mark, you\'re good to go.','contentking-plugin' ); ?></li>
</ol>
<p>
<?php
_e(
	'The ContentKing plugin is active when you see its name in green in the upper WordPress bar.<br/>
As always, our Support team is ready to help if you have any trouble.'
);
?>
</p>
