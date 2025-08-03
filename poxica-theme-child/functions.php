<?php
/**
 * Poxica Theme Child Functions
 *
 * @package poxica-theme-child
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Theme setup
 */
function poxica_child_setup() {
    // Add theme support for modern features
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'custom-logo' );
    add_theme_support( 'title-tag' );
    add_theme_support( 'html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ));
    
    // Add support for WooCommerce
    add_theme_support( 'woocommerce' );
    add_theme_support( 'wc-product-gallery-zoom' );
    add_theme_support( 'wc-product-gallery-lightbox' );
    add_theme_support( 'wc-product-gallery-slider' );
    
    // Add support for responsive embeds
    add_theme_support( 'responsive-embeds' );
    
    // Add support for editor styles
    add_theme_support( 'editor-styles' );
    
    // Add support for wide and full alignment
    add_theme_support( 'align-wide' );
}
add_action( 'after_setup_theme', 'poxica_child_setup' );

/**
 * Enqueue parent and child theme styles
 */
function poxica_child_enqueue_styles() {
    $parent_style = 'poxica-theme-style';
    $theme_version = wp_get_theme()->get('Version');
    
    // Enqueue parent theme stylesheet
    wp_enqueue_style( 
        $parent_style, 
        get_template_directory_uri() . '/style.css',
        array(),
        $theme_version
    );
    
    // Enqueue child theme stylesheet
    wp_enqueue_style( 
        'poxica-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array( $parent_style ),
        $theme_version
    );
    
    // Enqueue modern typography fonts
    wp_enqueue_style(
        'poxica-modern-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=DM+Serif+Display:ital@0;1&family=Manrope:wght@200..800&display=swap',
        array(),
        null
    );
}
add_action( 'wp_enqueue_scripts', 'poxica_child_enqueue_styles' );

/**
 * Enqueue custom JavaScript for modern interactions
 */
function poxica_child_enqueue_scripts() {
    wp_enqueue_script(
        'poxica-child-scripts',
        get_stylesheet_directory_uri() . '/assets/js/poxica-child.js',
        array( 'jquery' ),
        wp_get_theme()->get('Version'),
        true
    );
    
    // Localize script for AJAX
    wp_localize_script( 'poxica-child-scripts', 'poxica_ajax', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'poxica_nonce' ),
    ));
}
add_action( 'wp_enqueue_scripts', 'poxica_child_enqueue_scripts' );

/**
 * Add modern CSS custom properties
 */
function poxica_child_add_css_variables() {
    echo '<style id="poxica-css-variables">
        :root {
            --poxica-container-max-width: 1200px;
            --poxica-gutter: 1.5rem;
            --poxica-border-radius: 12px;
            --poxica-transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --poxica-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --poxica-shadow-lg: 0 8px 25px rgba(0, 0, 0, 0.2);
        }
    </style>';
}
add_action( 'wp_head', 'poxica_child_add_css_variables' );

/**
 * Add accessibility improvements
 */
function poxica_child_accessibility_improvements() {
    // Add skip link
    echo '<a class="skip-link screen-reader-text" href="#main">' . __( 'Skip to main content', 'poxica-theme-child' ) . '</a>';
}
add_action( 'wp_body_open', 'poxica_child_accessibility_improvements' );

/**
 * Optimize WooCommerce for performance
 */
function poxica_child_optimize_woocommerce() {
    // Remove WooCommerce scripts on non-shop pages
    if ( ! is_woocommerce() && ! is_cart() && ! is_checkout() && ! is_account_page() ) {
        wp_dequeue_style( 'woocommerce-general' );
        wp_dequeue_style( 'woocommerce-layout' );
        wp_dequeue_style( 'woocommerce-smallscreen' );
        wp_dequeue_script( 'wc-cart-fragments' );
        wp_dequeue_script( 'woocommerce' );
    }
}
add_action( 'wp_enqueue_scripts', 'poxica_child_optimize_woocommerce', 99 );

/**
 * Add modern meta tags for better performance and SEO
 */
function poxica_child_add_meta_tags() {
    echo '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">';
    echo '<meta name="theme-color" content="#22d3ee">';
    echo '<meta name="color-scheme" content="dark light">';
    echo '<meta name="format-detection" content="telephone=no">';
}
add_action( 'wp_head', 'poxica_child_add_meta_tags', 1 );

/**
 * Add preload hints for better performance
 */
function poxica_child_add_preload_hints() {
    // Preload critical fonts
    echo '<link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">';
    echo '<noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"></noscript>';
}
add_action( 'wp_head', 'poxica_child_add_preload_hints', 2 );

/**
 * Customize WooCommerce product gallery
 */
function poxica_child_customize_product_gallery() {
    // Modern gallery features
    add_theme_support( 'wc-product-gallery-zoom' );
    add_theme_support( 'wc-product-gallery-lightbox' );
    add_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'poxica_child_customize_product_gallery' );

/**
 * Add modern image optimizations
 */
function poxica_child_optimize_images( $attr, $attachment, $size ) {
    // Add lazy loading and modern image attributes
    $attr['loading'] = 'lazy';
    $attr['decoding'] = 'async';
    
    return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'poxica_child_optimize_images', 10, 3 );

/**
 * Add security headers
 */
function poxica_child_security_headers() {
    if ( ! is_admin() ) {
        header( 'X-Content-Type-Options: nosniff' );
        header( 'X-Frame-Options: SAMEORIGIN' );
        header( 'X-XSS-Protection: 1; mode=block' );
        header( 'Referrer-Policy: strict-origin-when-cross-origin' );
    }
}
add_action( 'send_headers', 'poxica_child_security_headers' );

/**
 * Disable unnecessary WordPress features for better performance
 */
function poxica_child_disable_unnecessary_features() {
    // Remove emoji scripts
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    
    // Remove unnecessary WordPress generator meta
    remove_action( 'wp_head', 'wp_generator' );
    
    // Remove RSD link
    remove_action( 'wp_head', 'rsd_link' );
    
    // Remove Windows Live Writer manifest link
    remove_action( 'wp_head', 'wlwmanifest_link' );
    
    // Remove shortlink
    remove_action( 'wp_head', 'wp_shortlink_wp_head' );
}
add_action( 'init', 'poxica_child_disable_unnecessary_features' );

/**
 * Add modern structured data for better SEO
 */
function poxica_child_add_structured_data() {
    if ( is_single() && has_post_thumbnail() ) {
        global $post;
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => get_the_title(),
            'description' => get_the_excerpt(),
            'image' => get_the_post_thumbnail_url( $post->ID, 'full' ),
            'datePublished' => get_the_date( 'c' ),
            'dateModified' => get_the_modified_date( 'c' ),
            'author' => array(
                '@type' => 'Person',
                'name' => get_the_author()
            )
        );
        
        echo '<script type="application/ld+json">' . wp_json_encode( $schema ) . '</script>';
    }
}
add_action( 'wp_head', 'poxica_child_add_structured_data' );

/**
 * Add modern CSS reset and base styles
 */
function poxica_child_add_modern_css_reset() {
    echo '<style id="poxica-modern-reset">
        *, *::before, *::after {
            box-sizing: border-box;
        }
        
        * {
            margin: 0;
        }
        
        html, body {
            height: 100%;
        }
        
        body {
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }
        
        img, picture, video, canvas, svg {
            display: block;
            max-width: 100%;
        }
        
        input, button, textarea, select {
            font: inherit;
        }
        
        p, h1, h2, h3, h4, h5, h6 {
            overflow-wrap: break-word;
        }
        
        #root, #__next {
            isolation: isolate;
        }
    </style>';
}
add_action( 'wp_head', 'poxica_child_add_modern_css_reset', 0 );

/**
 * Customize excerpt length for better readability
 */
function poxica_child_custom_excerpt_length( $length ) {
    return 25;
}
add_filter( 'excerpt_length', 'poxica_child_custom_excerpt_length' );

/**
 * Add modern excerpt more text
 */
function poxica_child_custom_excerpt_more( $more ) {
    return '...';
}
add_filter( 'excerpt_more', 'poxica_child_custom_excerpt_more' );

/**
 * Add theme customization options
 */
function poxica_child_customize_register( $wp_customize ) {
    // Add section for Poxica child theme options
    $wp_customize->add_section( 'poxica_child_options', array(
        'title'    => __( 'Poxica Child Theme Options', 'poxica-theme-child' ),
        'priority' => 30,
    ));
    
    // Add setting for accent color
    $wp_customize->add_setting( 'poxica_accent_color', array(
        'default'           => '#22d3ee',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'poxica_accent_color', array(
        'label'    => __( 'Accent Color', 'poxica-theme-child' ),
        'section'  => 'poxica_child_options',
        'settings' => 'poxica_accent_color',
    )));
}
add_action( 'customize_register', 'poxica_child_customize_register' );

/**
 * Load text domain for translations
 */
function poxica_child_load_textdomain() {
    load_child_theme_textdomain( 'poxica-theme-child', get_stylesheet_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'poxica_child_load_textdomain' );

/**
 * Add admin notice for successful setup
 */
function poxica_child_admin_notice() {
    if ( is_admin() && current_user_can( 'manage_options' ) ) {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>' . __( 'Poxica Child Theme', 'poxica-theme-child' ) . '</strong>: ' . __( 'Modern dark theme with 2025 design trends activated successfully!', 'poxica-theme-child' ) . '</p>';
        echo '</div>';
    }
}
add_action( 'admin_notices', 'poxica_child_admin_notice' );