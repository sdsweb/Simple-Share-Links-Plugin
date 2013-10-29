<?php
/**
 * Plugin Name: Simple Share Links
 * Plugin URI: http://www.slocumstudio.com
 * Description: A plugin from Slocum Design Studio which adds basic share buttons after all content within a site. Also contains a shortcode for share button output.
 * Version: 1.0
 * Author: Slocum Design Studio
 * Author URI: http://www.slocumstudio.com
 * License: GPL2+
 */

define( 'SSL_VERSION', '1.0' ); // Version
define( 'SSL_PLUGIN_FILE', __FILE__ ); // Reference to this plugin file (i.e. wp-content/plugins/simple-share-links/simple-share-links.php)
define( 'SSL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) ); // Plugin directory path (i.e. wp-content/plugins/simple-share-links/)
define( 'SSL_PLUGIN_URL', trailingslashit( plugins_url( '' , __FILE__ ) ) ); // Plugin url (i.e. http://example.com/wp-content/plugins/simple-share-links/)

if( ! class_exists( 'Simple_Share_Links' ) ) {
	class Simple_Share_Links {

		private static $instance; // Keep track of the instance
		public $sds_ssl_options;

		/**
		 * Function used to create instance of class.
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) )
				self::$instance = new Simple_Share_Links;

			return self::$instance;
		}


		/**
		 * This function sets up all of the actions and filters on instance
		 */
		function __construct( ) {
			/*
			 * Load existing option values on construct.
			 *
			 * This occurs on every admin pageload, however options are cached so there's no need to worry about performance,
			 * especially since we only have one option.
			 */
			$this->sds_ssl_options = get_option( 'sds_ssl_options' );

			// Plugin Activation
			register_activation_hook( SSL_PLUGIN_FILE, array( $this, 'activation' ) );
			add_action( 'admin_notices', array( $this, 'admin_notices' ) ); // We use this hook with the activation to output a message to the user

			// Plugin De-Activation
			register_deactivation_hook( SSL_PLUGIN_FILE, array( $this, 'deactivation' ) );

			// Hooks
			add_filter( 'the_content', array( $this, 'the_content' ) ); // Append our share buttons to the post content
			add_shortcode( 'simple_share_links', array( $this, 'simple_share_links' ) ); // Register [simple_share_links] shortcode for use in content

			add_action( 'admin_menu', array( $this, 'admin_menu' ) ); // Register our options page
			add_action( 'admin_init', array( $this, 'admin_init' ) ); // Register our settings
		}

		/********************************************************************
		 * Functions coresponding with hooks above (in order of appearance) *
		 ********************************************************************/

		/**
		 * This function handles the activation of the our plugin.
		 * It sets an activation flag for the function below to verify that the plugin was just activated.
		 */
		function activation() {
			// If our option is not stored, add it to the database and output our welcome message
			if( ! get_option( 'sds_ssl_activate_flag' ) )
				update_option( 'sds_ssl_activate_flag', true );
		}

		/**
		 * This function outputs an activation message if the activation flag was just set.
		 */
		function admin_notices() {
			// If our option is set to true, user just activated the plugin.
			if( get_option( 'sds_ssl_activate_flag' ) ) :
			?>
				<div class="updated" style="background-color: #5f87af; border-color: #354f6b; color:#fff;">
					<p>Thank you for installing Simple Share Links! Take a look at the plugin <a href="<?php echo admin_url( 'options-general.php?page=simple-share-links-options' ); ?>" style="color:#fff; text-decoration: underline;">settings page</a> for various options.</p>
				</div>
			<?php
				update_option( 'sds_ssl_activate_flag', false ); // Setting the flag to false, ultimately it would be best to remove this option now, however we wanted to include a deactivation hook as well
			endif;
		}
		/**
		 * This function handles the deactivation of the our plugin.
		 * It deletes our activation flag option that is set on plugin activation.
		 */
		function deactivation() {
			delete_option( 'sds_ssl_activate_flag' );
		}

		/**
		 * This function is used to output our share buttons after the_content.
		 *
		 * @param string $content - The content of the current post.
		 */
		 function the_content( $content ) {
			global $post; // We need this to fetch data such as post_excerpt, post ID, etc...

			// If we're on a single post or we're on a single page and option is not set to hide share buttons on pages, append share buttons.
			if ( ( $post->post_type === 'post' && is_single() ) || ( $post->post_type === 'page' && is_page() && ( ! isset( $this->sds_ssl_options['hide_on_pages'] ) || ! $this->sds_ssl_options['hide_on_pages'] ) ) )
				$content .= $this->get_simple_share_links();

			return $content;
		}

		/**
		 * This function is used to output our share buttons when our shortcode is used.
		 *
		 * @param string $args - The arguments of the shortcode.
		 */
		 function simple_share_links( $args ) {
			global $post; // We need this to fetch data such as post_excerpt, post ID, etc...

			return $this->get_simple_share_links(); // Fetch the share button output and return it
		}

		/**
		 * This function adds our options page to the Settings menu in the Dashboard.
		 */
		function admin_menu() {
			//				  Page Title				, Menu Title		 , Capability	   , Menu (Page) Slug		   , Callback Function (used to display the page)
			add_options_page( 'Simple Share Links Options', 'Simple Share Links', 'manage_options', 'simple-share-links-options', array( $this, 'simple_share_links_options' ) );
		}

		/**
		 * This function registers our settings for use on the options page (registered above).
		 */
		function admin_init() {
			/*
			 * Register our setting in WP.
			 *
			 * Notice we've left it open to more options by specifying the name as plural. Doing this does not directly allow more options,
			 * it simply stops us from having to re-name this option in the future. Alternatively we could store our options in separate
			 * keys, but there are many factors to consider.
			 */
			register_setting( 'sds_ssl_options', 'sds_ssl_options', array( $this, 'sds_ssl_validate' ) );

			/*
			 * Add settings section to WP.
			 *
			 * This function adds (creates) our settings section. We're using this section to ensure our option is displayed on this page
			 * when do_settings_section is called in our "render" callback.
			 */
			add_settings_section( 'sds_ssl_options_section', 'Simple Share Links Options', array( $this, 'sds_ssl_options_section' ), 'simple-share-links-options' );

			/*
			 * Add settings field to WP.
			 *
			 * This function adds (creates) our settings field to our menu slug and allows us to output it during our options panel render function.
			 * We're passing in our current options value here to utilize the options within the callback
			 */
			add_settings_field( 'sds_ssl_hide_pages_field', 'Hide on Pages', array( $this, 'sds_ssl_hide_pages_field' ), 'simple-share-links-options', 'sds_ssl_options_section', $this->sds_ssl_options );
		}

		/**
		 * This is the callback function, used above, to display our settings section.
		 *
		 * In this case, we're just outputing a description on the page.
		 */
		function sds_ssl_options_section() {
		?>
			<p>Use this page to adjust the options for Simple Share Links.</p>
		<?php
		}

		/**
		 * This is the callback function, used above, to display our hide pages field.
		 *
		 * We control all of the HTML output here.
		 * @param mixed $options is passed via the add_settings_field function and is a reference to our existing options.
		 *
		 * Notice that we mix PHP and HTML here.
		 */
		function sds_ssl_hide_pages_field( $options ) {
			// If the hide_on_pages option is not set, make sure we set a default, which is false in this case
			if ( ! is_array( $options ) && ! isset( $options['hide_on_pages'] ) ) {
				$options = array();
				$options['hide_on_pages'] = false;
			}
		?>
			<input type="checkbox" id="sds_ssl_options_hide_on_pages" name="sds_ssl_options[hide_on_pages]" <?php checked( $options['hide_on_pages'] ); ?> /> <span class="description">Check this option to hide the social media share buttons on Pages.</span>
		<?php
		}

		/**
		 * This is the callback function, used when registering settings above, to validate our settings.
		 *
		 * This function is passed an $input parameter which contains the input data from the user (from the settings page).
		 * You'll want to sanitize this data, make sure it is safe to store in the database
		 * @see http://codex.wordpress.org/Data_Validation
		 *
		 * This function must return the data from the $input variable, after the data has been sanitized.
		 */
		function sds_ssl_validate( $input ) {
			// Sanitize our "hide_on_pages" option
			if ( isset( $input['hide_on_pages'] ) )
				$input['hide_on_pages'] = true;

			return $input;
		}

		/**
		 * This function renders (displays) our options panel and is the callback used in the admin_menu hook.
		 *
		 * Notes:
		 *  - Notice the class of .wrap which "wraps" all of our content
		 *  - We're using the options general screen icon @see http://codex.wordpress.org/Function_Reference/screen_icon
		 *  - We're not using settings_errors() in this case because we do not have any custom error mesages
		 *      - settings_errors() is called automatically on options panels (@see http://codex.wordpress.org/Function_Reference/settings_errors)
		 *  - Our HTML form is posting to options.php (wp-admin/options.php)
		 */
		function simple_share_links_options() {
		?>
			<div class="wrap">
				<?php screen_icon( 'options-general' ); ?>
				<h2>Simple Share Links Options</h2>
				<?php //settings_errors(); This function is already output on all settings/option pages so we don't need to include it here as it results in two error/saved messages. ?>

				<form method="post" action="options.php">
					<?php
						settings_fields( 'sds_ssl_options' );

						do_settings_sections( 'simple-share-links-options' );
						submit_button();
					?>
				</form>
			</div>
		<?php
		}


		/**********************
		 * Internal Functions *
		 **********************/

		/**
		 * This function generates the output for the share buttons. It is used on the_content filter and in the shortcode function.
		 */
		function get_simple_share_links() {
			global $post; // We need this to fetch data such as post_excerpt, post ID, etc...
			$post_permalink = get_permalink( $post->ID ); // Post permalink

			// Begin Share Button Output
			$simple_share_links = '<section class="simple-share-links share-buttons">';
				// Allow developers to "hook" their own label
				$simple_share_links .= apply_filters( 'simple_share_links_label', '<h4 class="simple-share-links-label">Share This</h4>', $post, $post_permalink );

				// Allow developers to "hook" their own social networks for sharing
				$simple_share_links = apply_filters( 'simple_share_links_before', $simple_share_links, $post, $post_permalink );

				// Facebook
				$simple_share_links .= '<section class="simple-share-link ssl-facebook-share facebook-share">';
					$simple_share_links .= '<a href="http://www.facebook.com/sharer/sharer.php?u=' . esc_attr( esc_url( $post_permalink ) ) . '" target="_blank">Facebook</a>';
				$simple_share_links .= '</section>';
				// Twitter
				$simple_share_links .= '<section class="simple-share-link ssl-twitter-share twitter-share">';
					// Generate tweet text (Post Title) which is trimmed if necessary
					$tweet_text = ( strlen( $post->post_title ) > 110 ) ? substr( $post->post_title, 0, 107 ) . '...' : $post->post_title;
					$simple_share_links .= '<a href="http://twitter.com/share?text=' . esc_attr( urlencode( $tweet_text ) ) . ' - ' . $post_permalink . '" target="_blank">Twitter</a>';
				$simple_share_links .= '</section>';
				// Google+
				$simple_share_links .= '<section class="simple-share-link ssl-google-plus-share google-plus-share">';
					$simple_share_links .= '<a href="https://plus.google.com/share?url=' . esc_attr( esc_url( $post_permalink ) ) . '" target="_blank">Google+</a>';
				$simple_share_links .= '</section>';

				// Allow developers to "hook" their own social networks for sharing
				$simple_share_links = apply_filters( 'simple_share_links_after', $simple_share_links, $post, $post_permalink );

			$simple_share_links .= '</section>';
			// End Share Button Output

			return $simple_share_links;
		}
	}


	/**
	 * This function creates an instance of our class.
	 */
	function Simple_Share_Links_Instance() {
		return Simple_Share_Links::instance();
	}

	// Start the plugin
	Simple_Share_Links_Instance();
}