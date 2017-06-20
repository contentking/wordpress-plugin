<?php

defined( 'ABSPATH' )  or die('This file should not be accessed directly!');

if( !current_user_can( 'manage_options' ) ):
	wp_die( __( 'You do not have sufficient permissions to access this page.', 'contentking-plugin' ) );
endif;

?>

<h1> <?php _e('Contentking settings', 'contentking-plugin'); ?></h1>
<form action="options.php" method="post">

<?php
		settings_fields('contentking_setting_section');
		$contentking_client_token = get_option('contentking_client_token');
?>
	<table class = "contentking-setting">
		<tr>
			<th scope="row"> <?php _e( 'Token', 'contentking-plugin' );?> </th>
			<td><input type="text" id="contentking_client_token" name="contentking_client_token" value="<?php echo $contentking_client_token; ?>" />	</td>
			<td>
				<?php
				$flag = get_option('contentking_status_flag');
				if ($flag === '1'):
				?>
					<i class = "icon-ok"> </i>
				<?php 
					else: ?>
					<i class = "icon-cancel"> </i>
				<?php
				endif;

				?>
			</td>
		</tr>

	</table>

	<input type="hidden" name="update_contentking_token" value="1"/>
	<br>
	<input name="Submit" type="submit" class="button button-primary" value="<?php  _e('Save Changes', 'contentking-plugin'); ?>" />
</form>
