<?php
/*
 * Plugin Name: Anywhere
 * Plugin URI: http://austinpassy.com/wordpress-plugins/anywhere
 * Description: Adds twitter <a href="http://dev.twitter.com/anywhere">@anywhere</a> javascript code to your blog. Built by <a href="http://twitter.com/thefrosty">@TheFrosty</a>.
 * Version: 0.4.3
 * Author: Austin Passy
 * Author URI: http://frostywebdesigns.com
 *
 * @copyright 2010
 * @author Austin Passy
 * @link http://frostywebdesigns.com/
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @package Anywhere
 */

if ( !class_exists( 'Frostys_Anywhere' ) ) {
	class Frostys_Anywhere {
		
		static $anywhere;
		const version = '0.4.3';
		const domain  = 'anywhere';
		
		/* PHP4 constructor */
		function Frostys_Anywhere() {
			$this->__construct();
		}
		
		/* PHP5 constructor */
		function __construct() {
			global $anywhere;
			
			$anywhere = get_option( 'anywhere_settings' );
			
			add_action( 'plugins_loaded', array( __CLASS__, 'define' ) );
			
			add_action( 'admin_init', array( __CLASS__, 'admin_required' ) );
			add_action( 'init', array( __CLASS__, 'required' ) );
			
			add_action( 'admin_init', array( __CLASS__, 'admin_warnings' ) );
			add_action( 'admin_init', array( __CLASS__, 'wp_register_style' ) );
			
			add_action( 'admin_menu', array( __CLASS__, 'add_options_page' ) );
			
			add_action( 'wp_print_scripts', array( __CLASS__, 'wp_print_scripts' ) );
			
			add_action( 'wp_head', array( __CLASS__, 'options' ) );
			add_action( 'wp_head', array( __CLASS__, 'tweet_box' ) );
			
			add_filter( 'plugin_action_links', array( __CLASS__, 'plugin_actions' ), 10, 2 ); //Add a settings page to the plugin menu
			add_filter( 'the_content', array( __CLASS__, 'tweet_box_div' ) );
		}
		
		/**
		 * Define constant paths to the plugin folder.
		 * @since 0.1
		 */
		function define() {
			define( 'ANYWHERE', plugin_dir_path( __FILE__ ) );
			define( 'ANYWHERE_URL', plugin_dir_url( __FILE__ ) );
			
			define( 'ANYWHERE_ADMIN', trailingslashit( ANYWHERE ) . 'library/admin' );
			define( 'ANYWHERE_INCLUDES', trailingslashit( ANYWHERE ) . 'library/includes' );
			define( 'ANYWHERE_CSS', plugins_url( 'library/css', __FILE__ ) );
			define( 'ANYWHERE_JS', plugins_url( 'library/js', __FILE__ ) );
		}
		
		function admin_required() {
			require_once( ANYWHERE_ADMIN . '/settings-admin.php' );
			require_once( ANYWHERE_ADMIN . '/dashboard.php' );
		}
		
		function required() {
			require_once( ANYWHERE_INCLUDES . '/shortcodes.php' );
		}
		
		/**
		 * WordPress 3.x check
		 *
		 * @since 0.3.5
		 */
		public function is_version( $version = '3.0' ) {
			global $wp_version;
			
			if ( version_compare( $wp_version, $version, '<' ) ) {
				return false;
			}
			return true;
		}
		
		/**
		 * Load the stylesheet
		 * @since 0.1
		 */   
		function wp_register_style() {
			wp_register_style( 'anywhere-tabs', ANYWHERE_CSS . '/tabs.css' );
			wp_register_style( 'anywhere-admin', ANYWHERE_CSS . '/anywhere-admin.css' );
		}
		
		/**
		 * Function to add the settings page
		 * @since 0.1
		 */
		function add_options_page() {
			$page = add_options_page( 'Anywhere Settings', '@Anywhere', 'activate_plugins', 'anywhere.php', 'anywhere_page' );
				add_action( 'admin_print_styles-' . $page, array( __CLASS__, 'wp_enqueue_style' ) );
				add_action( 'admin_print_scripts-' . $page, array( __CLASS__, 'wp_enqueue_script' ) );
		}
		
		/**
		 * Function to add the style to the settings page
		 * @since 0.1
		 */
		function wp_enqueue_style() {
			wp_enqueue_style( 'thickbox' );
			wp_enqueue_style( 'anywhere-tabs' );
			wp_enqueue_style( 'anywhere-admin' );
		}
		
		/**
		 * Function to add the script to the settings page
		 * @since 0.1
		 */
		function wp_enqueue_script() {
			wp_enqueue_script( 'thickbox' );
			wp_enqueue_script( 'theme-preview' );
			wp_enqueue_script( 'anywhere-admin', ANYWHERE_JS . '/anywhere.js', array( 'jquery' ), '0.1', false );
		}
		
		/**
		 * Adds the @anywhere script
		 * @since 0.1
		 */
		function wp_print_scripts() {
			global $anywhere;
			
			$api = $anywhere['api'];
			$ver = $anywhere['version'];
			
			if ( !is_admin() ) {
				if ( ( !empty( $api ) && $api != '' ) && is_singular() )
				wp_enqueue_script( self::domain, 'http://platform.twitter.com/anywhere.js?id=' . $api . '&amp;v=' . $ver, false, $ver, false );
			}
		}
		
		/**
		 * Adds @anywhere options
		 * @since 0.1
		 */
		function options() {
			global $anywhere;
			
			$api   = $anywhere['api'];
			$users = $anywhere['linkifyusers'];
			$cards = $anywhere['hovercards'];
			
			if ( ( $api != '' && ( $users != false || $cards != false ) ) && is_singular() ) { ?>
<!-- @anywhere by Austin Passy of Frosty Web Designs -->
<script type="text/javascript">
twttr.anywhere(onAnywhereLoad);
    function onAnywhereLoad(twitter) {
        // configure the @anywhere environment
        <?php if ( $users != false ) echo 'twitter.linkifyUsers();' . "\n"; ?>
        <?php if ( $cards != false ) echo 'twitter.hovercards();' . "\n"; ?>
    };
</script>
<!-- /@anywhere -->
		<?php }
		}
		
		/**
		 * Adds @anywhere tweetbox
		 * @since 0.1.1
		 */
		function tweet_box() {
			global $post, $anywhere;
			
			$ver = $anywhere['version'];
			$box = $anywhere['tweetbox'];
			$label = $anywhere['tweetbox_label'];
			$usebox = $anywhere['use_tweetbox_content'];
			$tcontent = $anywhere['tweetbox_content'];
			$via = $anywhere['twitter_handle'];
			$height = $anywhere['tweetbox_height'];
			$width = $anywhere['tweetbox_width'];
			
			if ( ( $box != false ) && is_singular() ) : ?>
<!-- @anywhere tweetbox by Austin Passy of Frosty Web Designs -->
<script type="text/javascript">
twttr.anywhere("<?php echo $ver; ?>", function (twitter) {
    twitter("#tweetbox").tweetBox({
        <?php if ( $usebox != false && $tcontent != '' ) echo 'defaultContent: "' . esc_html( do_shortcode( $tcontent ) ) . '",' . "\n"; 
        else echo 'defaultContent: "' . esc_html( get_the_title() ) . ' ' . esc_url( do_shortcode( '[anywhere-link]' ) ) . ' /via ' . esc_attr( $via ) . '",' . "\n";
        if ( $label != '' ) echo 'label: "' . esc_attr( $label ). '",' . "\n";
        if ( $height != '' ) echo 'height: "' . esc_attr( $height ) . '",' . "\n";
        if ( $width != '' ) echo 'width: "' . esc_attr( $width ) . '",' . "\n"; ?>
    });
});
</script>
<!-- /@anywhere -->
		<?php endif;
		}
		
		/**
		 * filter @anywhere tweetbox into the content
		 * @since 0.1.1
		 */
		function tweet_box_div( $content ) {
			global $anywhere;
			
			$box		= $anywhere['tweetbox'];
			$placement	= $anywhere['tweetbox_placement'];
			$author		= $anywhere['author'];
			
			if ( !empty( $author ) || $author != false )
				$ll = '<span id="anywhere-author" style="display:inline-block;margin:0 0 20px;font-size:80%"><a href="http://austinpassy.com/wordpress-plugins/anywhere" title="@Anywhere WordPress plugin">@Anywhere</a> plugin made by <a href="http://twitter.com/thefrosty" title="Austin Passy on twitter" class="twitter-anywhere-user">@TheFrosty</a></span>'; 
			else 
				$ll = false;
			
			if ( $box != false ) :
				$tb = '<div id="tweetbox-wrapper"><div id="tweetbox"></div>'.$ll.'</div>';
				if ( is_singular() && !is_page() ) :
					if ( $placement == 'before' )
						return $tb.$content;
					elseif ( $placement == 'after' )
						return $content.$tb;
					elseif ( $placement == 'manual' )
						return $content;
					else 
						return $content;
				else :
					return $content;
				endif;
			else :
				return $content;
			endif;
		}
		
		/**
		 * Plugin Action /Settings on plugins page
		 * @since 0.1
		 * @package plugin
		 */
		function plugin_actions( $links, $file ) {
			if( $file == 'anywhere/anywhere.php' && function_exists( "admin_url" ) ) {
				$settings_link = '<a href="' . admin_url( 'options-general.php?page=anywhere.php' ) . '">' . __('Settings') . '</a>';
				array_unshift( $links, $settings_link ); // before other links
			}
			return $links;
		}
		
		/**
		 * Warnings
		 * @since 0.1
		 * @package admin
		 */
		function admin_warnings() {
			global $anywhere;
				
				function anywhere_warning() {
					global $anywhere;
		
					if ( $anywhere['api'] == '' )
						echo '<div id="anywhere-warning" class="updated fade"><p><strong>@anywhere plugin is not configured yet.</strong> It will not load until you enter your <a href="' . esc_url( admin_url( 'options-general.php?page=anywhere.php' ) ) . '">api</a>.</p></div>';
				}
		
				add_action( 'admin_notices', 'anywhere_warning' );
		
			return;
		}
		
		/**
		 * RSS Feed
		 * @since 0.1
		 * @package Admin
		 */
		function thefrosty_network_feed( $attr, $count ) {		
			global $wpdb;
			
			$domain = preg_replace( '|https?://([^/]+)|', '$1', get_option( 'siteurl' ) );
			include_once( ABSPATH . WPINC . '/class-simplepie.php' );
			
			$feed = new SimplePie();
			
			$feed->set_feed_url( 'http://pipes.yahoo.com/pipes/pipe.run?_id=52c339c010550750e3e64d478b1c96ea&_render=rss' );
			
			if ( false !== strpos( $domain, '/' ) || 'localhost' == $domain || preg_match( '|[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+|', $domain ) ) {
				$feed->enable_cache( false );
			} else {
				$feed->enable_cache( true );
				$feed->set_cache_location( plugin_dir_path( ANYWHERE_ADMIN ) . 'cache' );
			}
			
			$feed->init();
			$feed->handle_content_type();
	
			$items = $feed->get_item();
			echo '<div class="t' . $count . ' tab-content postbox open feed">';		
			echo '<ul>';		
			if ( empty( $items ) ) { 
				echo '<li>No items</li>';		
			} else {
				foreach( $feed->get_items( 0, 3 ) as $item ) : ?>		
					<li>		
						<a href='<?php echo $item->get_permalink(); ?>' title='<?php echo $item->get_description(); ?>'><?php echo $item->get_title(); ?></a><br /> 		
						<span style="font-size:10px; color:#aaa;"><?php echo $item->get_date('F, jS Y | g:i a'); ?></span>		
					</li>		
				<?php endforeach;
			}
			echo '</ul>';		
			echo '</div>';
		}
		
	}
};

$frostys_anywhere = new Frostys_Anywhere;

?>