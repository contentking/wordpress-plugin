<?php
/**
 * Plugin settings screen accesible via WP Admin.
 *
 * @package contentking-plugin
 */

defined( 'ABSPATH' ) || die( 'This file should not be accessed directly!' );

if ( ! current_user_can( 'manage_options' ) ) :
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'contentking-plugin' ) );
endif;

?>

<h1> <?php echo esc_html( __( 'ContentKing settings', 'contentking-plugin' ) ); ?></h1>
<form action="options.php" method="post">

<?php
		settings_fields( 'contentking_setting_section' );
		$contentking_client_token = get_option( 'contentking_client_token' );
?>

	<table class = "contentking-setting">
		<tr>
			<th scope="row"> <?php echo esc_html__( 'CMS API token', 'contentking-plugin' ); ?> </th>
			<td><input type="text" id="contentking_client_token" name="contentking_client_token" value="<?php echo esc_attr( $contentking_client_token ); ?>" />	</td>
			<td>
				<?php

				$flag = get_option( 'contentking_status_flag' );
				if ( '1' === $flag ) :
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
	<input name="Submit" type="submit" class="button button-primary" value="<?php echo esc_attr__( 'Save changes', 'contentking-plugin' ); ?>" />
</form>
<br/>
<?php
if ( 5 < strlen( $contentking_client_token ) && '0' === $flag ) :
	?>
	<form method="post">
		<?php wp_nonce_field( 'contentking_validate_token', 'ck_validate_token' ); ?>
	<input type="hidden" name="validate_contentking_token" value="1"/>
	<input name="Validate" type="submit" class="button button-primary" value="<?php echo esc_attr__( 'Validate token', 'contentking-plugin' ); ?>" />
	<p class="error">
	<?php esc_html__( 'It looks like your CMS API token is not validated. Click to validate.','contentking-plugin' ); ?>
	</p>
	</form>

<?php
	endif;
	?>
<h2> <?php echo esc_html__( 'Instructions', 'contentking-plugin' ); ?></h2>
<ol>
	<li>
	<?php
	_e( // WPCS: XSS OK.
		'In a new window, log in to the ContentKing app.','contentking-plugin'
	);
			?>
		 </li>
	<li>
	<?php
	_e( // WPCS: XSS OK.
		'Go to <strong>Account</strong> then <strong>Team profile</strong>.','contentking-plugin'
	);
			?>
		 </li>
	<li>
	<?php
	_e( // WPCS: XSS OK.
		'Copy the <strong>CMS API</strong> token from the <strong><a href="https://app.contentkingapp.com/account/settings/integration_tokens" target="_blank">Integration tokens</a></strong> section.','contentking-plugin'
	);
			?>
		 </li>
	<li>
	<?php
	_e( // WPCS: XSS OK.
		'Come back to this WordPress screen and paste it into the <strong>CMS API token</strong> field.','contentking-plugin'
	);
			?>
		 </li>
	<li>
	<?php
	_e( // WPCS: XSS OK.
		'Press <strong>Save changes</strong> and once you see the green validation tick mark, you\'re good to go.','contentking-plugin'
	);
			?>
		 </li>
</ol>
<p>
<?php
_e( // WPCS: XSS OK.
	'The ContentKing plugin is active when you see its name in green in the upper WordPress bar.<br/>
As always, our Support team is ready to help if you have any trouble.', 'contentking-plugin'
);
?>
</p>
