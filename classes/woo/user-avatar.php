<?php
/**
 * Plugin Name: Basic User Avatars
 * Plugin URI:  https://wordpress.org/plugins/basic-user-avatars/
 * Description: Add an avatar upload field on frontend pages and Edit Profile screen so users can add a custom profile picture.
 * Version:     1.0.7
 * Author:      Stranger Studios
 * Author URI:  https://www.strangerstudios.com/
 * Text Domain: basic-user-avatars
 * Domain Path: /languages
 *
 * ---------------------------------------------------------------------------//
 * This plugin is a fork of Simple Local Avatars v1.3.1 by Jake Goldman (10up).
 *
 * Orignal author url:  http://get10up.com
 * Original plugin url: http://wordpress.org/plugins/simple-local-avatars
 *
 * Check out Simple Local Avatars to compare their latest release to this plugin.
 * ---------------------------------------------------------------------------//
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Basic User Avatars. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author     Stranger Studios
 * @version    1.0.5
 * @package    JA_BasicLocalAvatars
 * @copyright  Copyright (c) 2015, Jared Atchison, 2020 Stranger Studios
 * @link       https://www.strangerstudios.com/
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

class dina_user_avatars {

	/**
	 * User ID
	 *
	 * @since 1.0.0
	 * @var int
	 */
	private $user_id_being_edited;

	/**
	 * Initialize all the things
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Text domain
		//$this->load_textdomain();

		// Actions
		add_action( 'admin_init',				 array( $this, 'admin_init'               )        );
		add_action( 'show_user_profile',		 array( $this, 'edit_user_profile'        )        );
		add_action( 'edit_user_profile',		 array( $this, 'edit_user_profile'        )        );
		add_action( 'personal_options_update',	 array( $this, 'edit_user_profile_update' )        );
		add_action( 'edit_user_profile_update',	 array( $this, 'edit_user_profile_update' )        );
		//add_action( 'bbp_user_edit_after_about', array( $this, 'bbpress_user_profile'     )        );

		// Shortcode
		add_shortcode( 'dina-user-avatars',	 array( $this, 'shortcode'                )        );

		// Filters
		add_filter( 'get_avatar_data',			 array( $this, 'get_avatar_data'               ), 10, 2 );
		add_filter( 'get_avatar',				 array( $this, 'get_avatar'               ), 10, 6 );
		add_filter( 'avatar_defaults',			 array( $this, 'avatar_defaults'          )        );
	}

	/**
	 * Loads the plugin language files.
	 *
	 * @since 1.0.1
	 */
	/*public function load_textdomain() {
		$domain = 'basic-user-avatars';
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
		load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}*/

	/**
	 * Start the admin engine.
	 *
	 * @since 1.0.0
	 */
	public function admin_init() {

		// Register/add the Discussion setting to restrict avatar upload capabilites
		//register_setting( 'discussion', 'dina_user_avatars_caps', array( $this, 'sanitize_options' ) );
		//add_settings_field( 'dina-user-avatars-caps', esc_html__( 'Local Avatar Permissions', 'dina-kala' ), array( $this, 'avatar_settings_field' ), 'discussion', 'avatars' );
	}

	/**
	 * Discussion settings option
	 *
	 * @since 1.0.0
	 * @param array $args [description]
	 */
	public function avatar_settings_field( $args ) {
		$options = get_option( 'dina_user_avatars_caps' );
		?>
		<label for="dina_user_avatars_caps">
			<input type="checkbox" name="dina_user_avatars_caps" id="dina_user_avatars_caps" value="1" <?php checked( $options['dina_user_avatars_caps'], 1 ); ?>/>
			<?php esc_html_e( 'Only allow users with file upload capabilities to upload local avatars (Authors and above)', 'dina-kala' ); ?>
		</label>
		<?php
	}

	/**
	 * Sanitize the Discussion settings option
	 *
	 * @since 1.0.0
	 * @param array $input
	 * @return array
	 */
	public function sanitize_options( $input ) {
		$new_input['dina_user_avatars_caps'] = empty( $input ) ? 0 : 1;
		return $new_input;
	}

	/**
	 * Filter the normal avatar data and show our avatar if set.
	 *
	 * @since 1.0.6
	 * @param array $args        Arguments passed to get_avatar_data(), after processing.
	 * @param mixed $id_or_email The avatar to retrieve. Accepts a user_id, Gravatar MD5 hash,
	 *                           user email, WP_User object, WP_Post object, or WP_Comment object.
	 * @return array             The filtered avatar data.
	 */
	public function get_avatar_data( $args, $id_or_email ) {
		if ( ! empty( $args['force_default'] ) ) {
			return $args;
		}

		global $wpdb;

		$return_args = $args;

		// Determine if we received an ID or string. Then, set the $user_id variable.
		if ( is_numeric( $id_or_email ) && 0 < $id_or_email ) {
			$user_id = (int) $id_or_email;
		} elseif ( is_object( $id_or_email ) && isset( $id_or_email->user_id ) && 0 < $id_or_email->user_id ) {
			$user_id = $id_or_email->user_id;
		} elseif ( is_object( $id_or_email ) && isset( $id_or_email->ID ) && isset( $id_or_email->user_login ) && 0 < $id_or_email->ID ) {
			$user_id = $id_or_email->ID;
		} elseif ( is_string( $id_or_email ) && false !== strpos( $id_or_email, '@' ) ) {
			$_user = get_user_by( 'email', $id_or_email );

			if ( ! empty( $_user ) ) {
				$user_id = $_user->ID;
			}
		}

		if ( empty( $user_id ) ) {
			return $args;
		}

		$user_avatar_url = null;

		// Get the user's local avatar from usermeta.
		$local_avatars = get_user_meta( $user_id, 'dina_user_avatar', true );

		if ( empty( $local_avatars ) || empty( $local_avatars['full'] ) ) {
			// Try to pull avatar from WP User Avatar.
			$wp_user_avatar_id = get_user_meta( $user_id, $wpdb->get_blog_prefix() . 'user_avatar', true );
			if ( ! empty( $wp_user_avatar_id ) ) {
				$wp_user_avatar_url = wp_get_attachment_url( intval( $wp_user_avatar_id ) );
				$local_avatars = array( 'full' => $wp_user_avatar_url );
				update_user_meta( $user_id, 'dina_user_avatar', $local_avatars );
			} else {
				// We don't have a local avatar, just return.
				return $args;
			}	
		}

		$size = (int) $args['size'];

		// Generate a new size
		if ( empty( $local_avatars[$size] ) ) {

			$upload_path      = wp_upload_dir();
			$avatar_full_path = str_replace( $upload_path['baseurl'], $upload_path['basedir'], $local_avatars['full'] );
			$image            = wp_get_image_editor( $avatar_full_path );
			$image_sized      = null;

			if ( ! is_wp_error( $image ) ) {
				$image->resize( $size, $size, true );
				$image_sized = $image->save();
			}

			// Deal with original being >= to original image (or lack of sizing ability).
			if ( empty( $image_sized ) || is_wp_error( $image_sized ) ) {
				$local_avatars[ $size ] = $local_avatars['full'];
			} else {
				$local_avatars[ $size ] = str_replace( $upload_path['basedir'], $upload_path['baseurl'], $image_sized['path'] );
			}

			// Save updated avatar sizes
			update_user_meta( $user_id, 'dina_user_avatar', $local_avatars );

		} elseif ( substr( $local_avatars[ $size ], 0, 4 ) != 'http' ) {
			$local_avatars[ $size ] = home_url( $local_avatars[ $size ] );
		}

		if ( is_ssl() ) {
			$local_avatars[ $size ] = str_replace( 'http:', 'https:', $local_avatars[ $size ] );
		}

		$user_avatar_url = $local_avatars[ $size ];

		if ( $user_avatar_url ) {
			$return_args['url'] = $user_avatar_url;
			$return_args['found_avatar'] = true;
		}

		/**
		 * Allow filtering the avatar data that we are overriding.
		 *
		 * @since 1.0.6
		 *
		 * @param array $return_args The list of user avatar data arguments.
		 */
		return apply_filters( 'dina_user_avatar_data', $return_args );
	}

	/**
	 * Add a backwards compatible hook to further filter our customized avatar HTML.
	 *
	 * @since 1.0.0
	 * 
	 * @param string $avatar      HTML for the user's avatar.
	 * @param mixed  $id_or_email The avatar to retrieve. Accepts a user_id, Gravatar MD5 hash,
	 *                            user email, WP_User object, WP_Post object, or WP_Comment object.
	 * @param int    $size        Square avatar width and height in pixels to retrieve.
	 * @param string $default     URL for the default image or a default type. Accepts '404', 'retro', 'monsterid',
	 *                            'wavatar', 'indenticon', 'mystery', 'mm', 'mysteryman', 'blank', or 'gravatar_default'.
	 * @param string $alt         Alternative text to use in the avatar image tag.
	 * @param array  $args        Arguments passed to get_avatar_data(), after processing.
	 * @return string             The filtered avatar HTML.
	 */
	public function get_avatar( $avatar, $id_or_email, $size = 96, $default = '', $alt = false, $args = array() ) {
		/**
		 * Filter to further customize the avatar HTML.
		 * 
		 * @since 1.0.0
		 * @param string $avatar HTML for the user's avatar.
		 * @param mixed  $id_or_email The avatar to retrieve. Accepts a user_id, Gravatar MD5 hash,
	 	 *                            user email, WP_User object, WP_Post object, or WP_Comment object.
	 	 * @return string The filtered avatar HTML.
		 * @deprecated since 1.0.6
		 */
		return apply_filters( 'dina_user_avatar', $avatar, $id_or_email );
	}

	/**
	 * Form to display on the user profile edit screen
	 *
	 * @since 1.0.0
	 * @param object $profileuser
	 * @return
	 */
	public function edit_user_profile( $profileuser ) {

		// bbPress will try to auto-add this to user profiles - don't let it.
		// Instead we hook our own proper function that displays cleaner.
		if ( function_exists( 'is_bbpress') && is_bbpress() )
			return;
		?>

		<h2><?php _e( 'Avatar', 'dina-kala' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><label for="dina-user-avatar"><?php esc_html_e( 'Upload Avatar', 'dina-kala' ); ?></label></th>
				<td style="width: 50px;" valign="top">
					<?php echo get_avatar( $profileuser->ID ); ?>
				</td>
				<td>
				<?php
				$options = get_option( 'dina_user_avatars_caps' );
				if ( empty( $options['dina_user_avatars_caps'] ) || current_user_can( 'upload_files' ) ) {
					// Nonce security ftw
					wp_nonce_field( 'dina_user_avatar_nonce', '_dina_user_avatar_nonce', false );
					
					// File upload input
					echo '<input type="file" name="dina-user-avatar" id="dina-local-avatar" />';

					if ( empty( $profileuser->dina_user_avatar ) ) {
						echo '<p class="description">' . esc_html__( 'No local avatar is set. Use the upload field to add a local avatar.', 'dina-kala' ) . '</p>';
					} else {
						echo '<p><input type="checkbox" name="dina-user-avatar-erase" id="dina-user-avatar-erase" value="1" /><label for="dina-user-avatar-erase">' . esc_html__( 'Delete local avatar', 'dina-kala' ) . '</label></p>';
						echo '<p class="description">' . esc_html__( 'Replace the local avatar by uploading a new avatar, or erase the local avatar (falling back to a gravatar) by checking the delete option.', 'dina-kala' ) . '</p>';
					}

				} else {
					if ( empty( $profileuser->dina_user_avatar ) ) {
						echo '<p class="description">' . esc_html__( 'No local avatar is set. Set up your avatar at Gravatar.com.', 'dina-kala' ) . '</p>';
					} else {
						echo '<p class="description">' . esc_html__( 'You do not have media management permissions. To change your local avatar, contact the site administrator.', 'dina-kala' ) . '</p>';
					}	
				}
				?>
				</td>
			</tr>
		</table>
		<script type="text/javascript">var form = document.getElementById('your-profile');form.encoding = 'multipart/form-data';form.setAttribute('enctype', 'multipart/form-data');</script>
		<?php
	}

	/**
	 * Update the user's avatar setting
	 *
	 * @since 1.0.0
	 * @param int $user_id
	 */
	public function edit_user_profile_update( $user_id ) {

		// Check for nonce otherwise bail
		if ( ! isset( $_POST['_dina_user_avatar_nonce'] ) || ! wp_verify_nonce( $_POST['_dina_user_avatar_nonce'], 'dina_user_avatar_nonce' ) )
			return;

		if ( ! empty( $_FILES['dina-user-avatar']['name'] ) ) {

			// Allowed file extensions/types
			$mimes = array(
				'jpg|jpeg|jpe' => 'image/jpeg',
				'gif'          => 'image/gif',
				'png'          => 'image/png',
			);

			// Front end support - shortcode, bbPress, etc
			if ( ! function_exists( 'wp_handle_upload' ) )
				require_once ABSPATH . 'wp-admin/includes/file.php';

			$this->avatar_delete( $this->user_id_being_edited );

			// Need to be more secure since low privelege users can upload
			if ( strstr( $_FILES['dina-user-avatar']['name'], '.php' ) )
				wp_die( 'For security reasons, the extension ".php" cannot be in your file name.' );

			// Make user_id known to unique_filename_callback function
			$this->user_id_being_edited = $user_id; 
			$avatar = wp_handle_upload( $_FILES['dina-user-avatar'], array( 'mimes' => $mimes, 'test_form' => false, 'unique_filename_callback' => array( $this, 'unique_filename_callback' ) ) );

			// Handle failures
			if ( empty( $avatar['file'] ) ) {  
				switch ( $avatar['error'] ) {
				case 'File type does not meet security guidelines. Try another.' :
					add_action( 'user_profile_update_errors', function( $error = 'avatar_error' ){
						esc_html__("Please upload a valid image file for the avatar.","dina-kala");
					} );
					break;
				default :
					add_action( 'user_profile_update_errors', function( $error = 'avatar_error' ){
						"<strong>".esc_html__("There was an error uploading the avatar:","dina-kala")."</strong> ". esc_attr( $avatar['error'] );
					} );
				}
				return;
			}

			// Save user information (overwriting previous)
			update_user_meta( $user_id, 'dina_user_avatar', array( 'full' => $avatar['url'] ) );

		} elseif ( ! empty( $_POST['dina-user-avatar-erase'] ) ) {
			// Nuke the current avatar
			$this->avatar_delete( $user_id );
		}
	}

	/**
	 * Enable avatar management on the frontend via this shortocde.
	 *
	 * @since 1.0.0
	 */
	function shortcode() {

		// Don't bother if the user isn't logged in
		if ( ! is_user_logged_in() )
			return;

		$user_id     = get_current_user_id();
		$profileuser = get_userdata( $user_id );

		if ( isset( $_POST['manage_avatar_submit'] ) ){
			$this->edit_user_profile_update( $user_id );
		}

		ob_start();
		?>
		<form id="dina-user-avatar-form" method="post" enctype="multipart/form-data">
		<?php if ( !empty( $profileuser->dina_user_avatar ) ) { ?>

			<img src="<?= $profileuser->dina_user_avatar['full']?>" width="10%" />
			<?php
		}

			$options = get_option( 'dina_user_avatars_caps' );
			if ( empty( $options['dina_user_avatars_caps'] ) || current_user_can( 'upload_files' ) ) {
				// Nonce security ftw
				wp_nonce_field( 'dina_user_avatar_nonce', '_dina_user_avatar_nonce', false );
				
				// File upload input
				echo '<p><input type="file" name="dina-user-avatar" id="dina-local-avatar" /></p>';

				if ( empty( $profileuser->dina_user_avatar ) ) {
					echo '<p class="description">' . apply_filters( 'bu_avatars_no_avatar_set_text',esc_html__( 'عکسی جهت نمایش انتخاب نشده است.', 'dina-kala' ), $profileuser ) . '</p>';
				} else {
					echo '<p><input type="checkbox" name="dina-user-avatar-erase" id="dina-user-avatar-erase" value="1" /> <label for="dina-user-avatar-erase">' . apply_filters( 'bu_avatars_delete_avatar_text', esc_html__( 'حذف عکس', 'dina-kala' ), $profileuser ) . '</label></p>';					
					echo '<p class="description">' . apply_filters( 'bu_avatars_replace_avatar_text', esc_html__( 'عکس پروفایل انتخاب نمایید.', 'dina-kala' ), $profileuser ) . '</p>';
				}

				echo '<input type="submit" name="manage_avatar_submit" value="' . apply_filters( 'bu_avatars_update_button_text', esc_attr__( 'آپلود عکس', 'dina-kala' ) ) . '" />';

			} else {
				if ( empty( $profileuser->dina_user_avatar ) ) {
					echo '<p class="description">' . apply_filters( 'bu_avatars_no_avatar_set_text', esc_html__( 'No local avatar is set. Set up your avatar at Gravatar.com.', 'dina-kala' ), $profileuser ) . '</p>';
				} else {
					echo '<p class="description">' . apply_filters( 'bu_avatars_permissions_text', esc_html__( 'You do not have media management permissions. To change your local avatar, contact the site administrator.', 'dina-kala' ), $profileuser ) . '</p>';
				}	
			}
			?>
		</form>
		<?php
		return ob_get_clean();
	}

	/**
	 * Form to display on the bbPress user profile edit screen
	 *
	 * @since 1.0.0
	 */
	public function bbpress_user_profile() {

		if ( !bbp_is_user_home_edit() )
			return;

		$user_id     = get_current_user_id();
		$profileuser = get_userdata( $user_id );

		echo '<div>';
			echo '<label for="dina-local-avatar">' . esc_html__( 'Avatar', 'dina-kala' ) . '</label>';
 			echo '<fieldset class="bbp-form avatar">';

	 			echo get_avatar( $profileuser->ID );
				$options = get_option( 'dina_user_avatars_caps' );
				if ( empty( $options['dina_user_avatars_caps'] ) || current_user_can( 'upload_files' ) ) {
					// Nonce security ftw
					wp_nonce_field( 'dina_user_avatar_nonce', '_dina_user_avatar_nonce', false );
					
					// File upload input
					echo '<br /><input type="file" name="dina-user-avatar" id="dina-local-avatar" /><br />';

					if ( empty( $profileuser->dina_user_avatar ) ) {
						echo '<span class="description" style="margin-left:0;">' . apply_filters( 'bu_avatars_no_avatar_set_text', esc_html__( 'No local avatar is set. Use the upload field to add a local avatar.', 'dina-kala' ), $profileuser ) . '</span>';
					} else {
							echo '<input type="checkbox" name="dina-user-avatar-erase" id="dina-user-avatar-erase" value="1" style="width:auto" /> <label for="dina-user-avatar-erase">' . apply_filters( 'bu_avatars_delete_avatar_text', __( 'Delete local avatar', 'dina-kala' ), $profileuser ) . '<label><br />';
						echo '<span class="description" style="margin-left:0;">' . apply_filters( '', esc_html__( 'Replace the local avatar by uploading a new avatar, or erase the local avatar (falling back to a gravatar) by checking the delete option.', 'dina-kala' ), $profileuser ) . '</span>';
					}

				} else {
					if ( empty( $profileuser->dina_user_avatar ) ) {
						echo '<span class="description" style="margin-left:0;">' . apply_filters( 'bu_avatars_no_avatar_set_text', esc_html__( 'No local avatar is set. Set up your avatar at Gravatar.com.', 'dina-kala' ), $profileuser ) . '</span>';
					} else {
						echo '<span class="description" style="margin-left:0;">' . apply_filters( 'bu_avatars_permissions_text', esc_html__( 'You do not have media management permissions. To change your local avatar, contact the site administrator.', 'dina-kala' ), $profileuser ) . '</span>';
					}	
				}

			echo '</fieldset>';
		echo '</div>';
		?>
		<script type="text/javascript">var form = document.getElementById('bbp-your-profile');form.encoding = 'multipart/form-data';form.setAttribute('enctype', 'multipart/form-data');</script>
		<?php
	}

	/**
	 * Remove the custom get_avatar hook for the default avatar list output on 
	 * the Discussion Settings page.
	 *
	 * @since 1.0.0
	 * @param array $avatar_defaults
	 * @return array
	 */
	public function avatar_defaults( $avatar_defaults ) {
		remove_action( 'get_avatar', array( $this, 'get_avatar' ) );
		return $avatar_defaults;
	}

	/**
	 * Delete avatars based on user_id
	 *
	 * @since 1.0.0
	 * @param int $user_id
	 */
	public function avatar_delete( $user_id ) {
		$old_avatars = get_user_meta( $user_id, 'dina_user_avatar', true );
		$upload_path = wp_upload_dir();

		if ( is_array( $old_avatars ) ) {
			foreach ( $old_avatars as $old_avatar ) {
				$old_avatar_path = str_replace( $upload_path['baseurl'], $upload_path['basedir'], $old_avatar );
				@unlink( $old_avatar_path );
			}
		}

		delete_user_meta( $user_id, 'dina_user_avatar' );
	}

	/**
	 * File names are magic
	 *
	 * @since 1.0.0
	 * @param string $dir
	 * @param string $name
	 * @param string $ext
	 * @return string
	 */
	public function unique_filename_callback( $dir, $name, $ext ) {
		$user = get_user_by( 'id', (int) $this->user_id_being_edited );
		$name = $base_name = sanitize_file_name( strtolower( $user->display_name ) . '_avatar' );

		$number = 1;

		while ( file_exists( $dir . "/$name$ext" ) ) {
			$name = $base_name . '_' . $number;
			$number++;
		}

		return $name . $ext;
	}
}
$dina_user_avatars = new dina_user_avatars;

/**
 * During uninstallation, remove the custom field from the users and delete the local avatars
 *
 * @since 1.0.0
 */
function dina_user_avatars_uninstall() {
	$dina_user_avatars = new dina_user_avatars;
	$users = get_users();

	foreach ( $users as $user )
		$dina_user_avatars->avatar_delete( $user->user_id );

	delete_option( 'dina_user_avatars_caps' );
}
register_uninstall_hook( __FILE__, 'dina_user_avatars_uninstall' );

