<?php
/**
 * LLMS.txt Generator
 *
 * @package GeoAiWoo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class for generating llms.txt file
 */
class Geo_Ai_Woo_LLMS_Generator {

    /**
     * Single instance
     *
     * @var Geo_Ai_Woo_LLMS_Generator
     */
    private static $instance = null;

    /**
     * Supported AI bots
     *
     * @var array
     */
    private $ai_bots = array(
        'GPTBot'          => 'OpenAI / ChatGPT',
        'ClaudeBot'       => 'Anthropic / Claude',
        'Google-Extended' => 'Google / Gemini',
        'PerplexityBot'   => 'Perplexity AI',
        'YandexBot'       => 'Yandex / YandexGPT',
        'SputnikBot'      => 'Sber / GigaChat',
        'Bytespider'      => 'ByteDance / Douyin',
        'Baiduspider'     => 'Baidu / ERNIE',
    );

    /**
     * Get single instance
     *
     * @return Geo_Ai_Woo_LLMS_Generator
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
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Register rewrite rules
        add_action( 'init', array( $this, 'add_rewrite_rules' ) );

        // Handle llms.txt request
        add_action( 'template_redirect', array( $this, 'serve_llms_txt' ) );

        // Regenerate on post save
        add_action( 'save_post', array( $this, 'maybe_regenerate' ), 10, 2 );

        // Scheduled regeneration
        add_action( 'geo_ai_woo_regenerate_llms', array( $this, 'regenerate_cache' ) );

        // Query vars
        add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
    }

    /**
     * Add rewrite rules for llms.txt
     */
    public function add_rewrite_rules() {
        add_rewrite_rule( '^llms\.txt$', 'index.php?geo_ai_woo_llms=1', 'top' );
        add_rewrite_rule( '^llms-full\.txt$', 'index.php?geo_ai_woo_llms=full', 'top' );
    }

    /**
     * Add query vars
     *
     * @param array $vars Query vars.
     * @return array
     */
    public function add_query_vars( $vars ) {
        $vars[] = 'geo_ai_woo_llms';
        return $vars;
    }

    /**
     * Serve llms.txt file
     */
    public function serve_llms_txt() {
        $llms_type = get_query_var( 'geo_ai_woo_llms' );

        if ( ! $llms_type ) {
            return;
        }

        $is_full = ( 'full' === $llms_type );

        // Set headers
        header( 'Content-Type: text/plain; charset=utf-8' );
        header( 'X-Robots-Tag: noindex' );
        header( 'Cache-Control: public, max-age=86400' );

        // Get cached content or generate new
        $cache_key = $is_full ? 'geo_ai_woo_llms_full' : 'geo_ai_woo_llms';
        $duration  = $this->get_cache_duration();

        $content = ( $duration > 0 ) ? get_transient( $cache_key ) : false;

        if ( false === $content ) {
            $content = $this->generate( $is_full );
            if ( $duration > 0 ) {
                set_transient( $cache_key, $content, $duration );
            }
        }

        echo $content;
        exit;
    }

    /**
     * Generate llms.txt content
     *
     * @param bool $is_full Whether to generate full version.
     * @return string
     */
    public function generate( $is_full = false ) {
        $settings = get_option( 'geo_ai_woo_settings', array() );
        $output   = array();

        // Header
        $site_name = get_bloginfo( 'name' );
        $output[]  = '# ' . $site_name;
        $output[]  = '';

        // Site description
        $description = ! empty( $settings['site_description'] )
            ? $settings['site_description']
            : get_bloginfo( 'description' );

        if ( $description ) {
            $output[] = '> ' . wp_strip_all_tags( $description );
            $output[] = '';
        }

        // Bot rules section
        $output[] = '## AI Crawler Rules';
        $output[] = '';
        
        $bot_rules = isset( $settings['bot_rules'] ) ? $settings['bot_rules'] : array();
        foreach ( $this->ai_bots as $bot => $provider ) {
            $rule     = isset( $bot_rules[ $bot ] ) ? $bot_rules[ $bot ] : 'allow';
            $status   = ( 'allow' === $rule ) ? '✓ Allowed' : '✗ Disallowed';
            $output[] = "- {$bot} ({$provider}): {$status}";
        }
        $output[] = '';

        // Get post types to include
        $post_types = isset( $settings['post_types'] ) 
            ? $settings['post_types'] 
            : array( 'post', 'page' );

        // Section labels for known post types
        $section_labels = array(
            'page'    => __( 'Pages', 'geo-ai-woo' ),
            'post'    => __( 'Blog Posts', 'geo-ai-woo' ),
            'product' => __( 'Products', 'geo-ai-woo' ),
        );

        foreach ( $post_types as $post_type ) {
            // Skip product if WooCommerce is not active
            if ( 'product' === $post_type && ! class_exists( 'WooCommerce' ) ) {
                continue;
            }

            $items = $this->get_content( $post_type, $is_full );
            if ( ! empty( $items ) ) {
                // Use known label or post type object label
                if ( isset( $section_labels[ $post_type ] ) ) {
                    $label = $section_labels[ $post_type ];
                } else {
                    $pt_object = get_post_type_object( $post_type );
                    $label     = $pt_object ? $pt_object->label : ucfirst( $post_type );
                }

                $output[] = '## ' . $label;
                $output[] = '';
                $output   = array_merge( $output, $items );
                $output[] = '';
            }
        }

        // Footer
        $output[] = '---';
        $output[] = 'Generated by GEO AI Woo';
        $output[] = 'Last updated: ' . gmdate( 'Y-m-d' );

        return implode( "\n", $output );
    }

    /**
     * Get content for a post type
     *
     * @param string $post_type Post type.
     * @param bool   $is_full   Whether to include full content.
     * @return array
     */
    private function get_content( $post_type, $is_full = false ) {
        $output = array();

        $args = array(
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => $is_full ? apply_filters( 'geo_ai_woo_full_posts_limit', 500 ) : 50,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'     => '_geo_ai_woo_exclude',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key'     => '_geo_ai_woo_exclude',
                    'value'   => '1',
                    'compare' => '!=',
                ),
            ),
        );

        $query = new WP_Query( $args );

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();

                $post_id = get_the_ID();
                $title   = wp_strip_all_tags( get_the_title() );
                $url     = esc_url( get_permalink() );

                // Get AI description — use filter for products, fallback to excerpt
                $ai_description = get_post_meta( $post_id, '_geo_ai_woo_description', true );

                if ( ! empty( $ai_description ) ) {
                    $description = wp_strip_all_tags( $ai_description );
                } elseif ( 'product' === $post_type && class_exists( 'WooCommerce' ) ) {
                    $description = wp_strip_all_tags( apply_filters( 'geo_ai_woo_product_description', '', $post_id ) );
                } else {
                    $description = wp_trim_words( get_the_excerpt(), 20, '...' );
                }

                // Build entry line
                $line = "- [{$title}]({$url})";
                if ( $description ) {
                    $line .= ": {$description}";
                }

                // Append keywords if set
                $keywords = get_post_meta( $post_id, '_geo_ai_woo_keywords', true );
                if ( ! empty( $keywords ) ) {
                    $line .= " [" . wp_strip_all_tags( $keywords ) . "]";
                }

                $output[] = $line;

                // Include full content in llms-full.txt
                if ( $is_full ) {
                    $content  = wp_strip_all_tags( get_the_content(), true );
                    $content  = wp_trim_words( $content, 200, '...' );
                    $output[] = "  " . $content;
                    $output[] = '';
                }
            }
            wp_reset_postdata();
        }

        return $output;
    }

    /**
     * Maybe regenerate cache on post save
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     */
    public function maybe_regenerate( $post_id, $post ) {
        // Skip autosaves and revisions
        if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
            return;
        }

        // Skip if not a tracked post type
        $settings   = get_option( 'geo_ai_woo_settings', array() );
        $post_types = isset( $settings['post_types'] ) 
            ? $settings['post_types'] 
            : array( 'post', 'page' );

        if ( ! in_array( $post->post_type, $post_types, true ) ) {
            return;
        }

        // Clear cache
        $this->clear_cache();
    }

    /**
     * Regenerate cache
     */
    public function regenerate_cache() {
        $this->clear_cache();

        // Pre-generate both versions and store them
        $duration = $this->get_cache_duration();
        set_transient( 'geo_ai_woo_llms', $this->generate( false ), $duration );
        set_transient( 'geo_ai_woo_llms_full', $this->generate( true ), $duration );
    }

    /**
     * Get cache duration in seconds based on settings
     *
     * @return int
     */
    private function get_cache_duration() {
        $settings = get_option( 'geo_ai_woo_settings', array() );
        $duration = isset( $settings['cache_duration'] ) ? $settings['cache_duration'] : 'daily';

        $map = array(
            'immediate' => 0,
            'hourly'    => HOUR_IN_SECONDS,
            'daily'     => DAY_IN_SECONDS,
            'weekly'    => WEEK_IN_SECONDS,
        );

        return isset( $map[ $duration ] ) ? $map[ $duration ] : DAY_IN_SECONDS;
    }

    /**
     * Clear cache
     */
    public function clear_cache() {
        delete_transient( 'geo_ai_woo_llms' );
        delete_transient( 'geo_ai_woo_llms_full' );
    }

    /**
     * Get supported AI bots
     *
     * @return array
     */
    public function get_ai_bots() {
        return $this->ai_bots;
    }
}
