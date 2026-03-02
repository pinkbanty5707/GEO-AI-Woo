<?php
/**
 * Plugin Name: GEO AI Woo
 * Plugin URI: https://github.com/madebureau/geo-ai-woo
 * Description: Generative Engine Optimization for WordPress & WooCommerce. Optimize your site for AI search engines like ChatGPT, Claude, Gemini, Perplexity, YandexGPT, GigaChat, and more.
 * Version: 0.1.0
 * Author: Made Büro
 * Author URI: https://madeburo.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: geo-ai-woo
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 * WC tested up to: 8.5
 *
 * @package GeoAiWoo
 */

defined( 'ABSPATH' ) || exit;

// Plugin constants
define( 'GEO_AI_WOO_VERSION', '0.1.0' );
define( 'GEO_AI_WOO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GEO_AI_WOO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'GEO_AI_WOO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin class
 */
final class Geo_Ai_Woo {

    /**
     * Single instance of the class
     *
     * @var Geo_Ai_Woo
     */
    private static $instance = null;

    /**
     * Get single instance of the class
     *
     * @return Geo_Ai_Woo
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Include required files
     */
    private function includes() {
        require_once GEO_AI_WOO_PLUGIN_DIR . 'includes/class-llms-generator.php';
        require_once GEO_AI_WOO_PLUGIN_DIR . 'includes/class-meta-box.php';
        require_once GEO_AI_WOO_PLUGIN_DIR . 'includes/class-settings.php';

        // WooCommerce integration (if WooCommerce is active)
        if ( class_exists( 'WooCommerce' ) ) {
            require_once GEO_AI_WOO_PLUGIN_DIR . 'includes/class-woocommerce.php';
        }
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Load text domain
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

        // Initialize components
        add_action( 'init', array( $this, 'init' ), 0 );

        // Admin assets
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );

        // Plugin action links
        add_filter( 'plugin_action_links_' . GEO_AI_WOO_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );

        // Activation/Deactivation hooks
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
    }

    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'geo-ai-woo',
            false,
            dirname( GEO_AI_WOO_PLUGIN_BASENAME ) . '/languages'
        );
    }

    /**
     * Initialize plugin components
     */
    public function init() {
        // Initialize LLMS generator
        Geo_Ai_Woo_LLMS_Generator::instance();

        // Initialize Meta Box
        Geo_Ai_Woo_Meta_Box::instance();

        // Initialize Settings
        Geo_Ai_Woo_Settings::instance();

        // Initialize WooCommerce integration
        if ( class_exists( 'WooCommerce' ) ) {
            Geo_Ai_Woo_WooCommerce::instance();
        }
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook.
     */
    public function admin_assets( $hook ) {
        $screen = get_current_screen();

        // Load on settings page and post/product edit screens
        $is_settings = ( 'settings_page_geo-ai-woo' === $hook );
        $is_editor   = ( $screen && 'post' === $screen->base );

        if ( ! $is_settings && ! $is_editor ) {
            return;
        }

        wp_enqueue_style(
            'geo-ai-woo-admin',
            GEO_AI_WOO_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            GEO_AI_WOO_VERSION
        );

        wp_enqueue_script(
            'geo-ai-woo-admin',
            GEO_AI_WOO_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery' ),
            GEO_AI_WOO_VERSION,
            true
        );

        wp_localize_script( 'geo-ai-woo-admin', 'geo_ai_woo_admin', array(
            'nonce'        => wp_create_nonce( 'geo_ai_woo_regenerate' ),
            'regenerating' => __( 'Regenerating...', 'geo-ai-woo' ),
            'done'         => __( 'Done!', 'geo-ai-woo' ),
            'error'        => __( 'Error', 'geo-ai-woo' ),
        ) );
    }

    /**
     * Add plugin action links
     *
     * @param array $links Existing links.
     * @return array Modified links.
     */
    public function plugin_action_links( $links ) {
        $plugin_links = array(
            '<a href="' . admin_url( 'options-general.php?page=geo-ai-woo' ) . '">' . __( 'Settings', 'geo-ai-woo' ) . '</a>',
        );
        return array_merge( $plugin_links, $links );
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Set default options
        $defaults = array(
            'post_types'       => array( 'post', 'page', 'product' ),
            'bot_rules'        => array(
                'GPTBot'           => 'allow',
                'ClaudeBot'        => 'allow',
                'Google-Extended'  => 'allow',
                'PerplexityBot'    => 'allow',
                'YandexBot'        => 'allow',
                'SputnikBot'       => 'allow',
                'Bytespider'       => 'allow',
                'Baiduspider'      => 'allow',
            ),
            'cache_duration'   => 'daily',
            'site_description' => get_bloginfo( 'description' ),
        );

        if ( ! get_option( 'geo_ai_woo_settings' ) ) {
            add_option( 'geo_ai_woo_settings', $defaults );
        }

        // Register rewrite rules before flushing so they are included
        add_rewrite_rule( '^llms\.txt$', 'index.php?geo_ai_woo_llms=1', 'top' );
        add_rewrite_rule( '^llms-full\.txt$', 'index.php?geo_ai_woo_llms=full', 'top' );
        flush_rewrite_rules();

        // Schedule cache regeneration
        if ( ! wp_next_scheduled( 'geo_ai_woo_regenerate_llms' ) ) {
            wp_schedule_event( time(), 'daily', 'geo_ai_woo_regenerate_llms' );
        }
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook( 'geo_ai_woo_regenerate_llms' );

        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

/**
 * Initialize the plugin
 *
 * @return Geo_Ai_Woo
 */
function geo_ai_woo() {
    return Geo_Ai_Woo::instance();
}

// Start the plugin
geo_ai_woo();
