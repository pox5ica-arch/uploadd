<?php
/**
 * Poxica Theme Customizer Defaults
 * Modern Dark Color Palette for 2025
 *
 * @package poxica-theme
 */

if ( ! function_exists( 'poxica_theme_get_option_defaults' ) ) {

	/**
	 * Modern dark color palette with vibrant accents
	 * Following 2025 design trends: Deep charcoal, electric blue, neon green accents
	 */
	function poxica_theme_get_option_defaults() {
		$defaults = array(

			// Top Bar - Deep charcoal background
			'shoptimizer_layout_top_bar_display'           						=> 'enable',
			'shoptimizer_layout_top_bar_mobile'            						=> 'hide',

			'shoptimizer_layout_top_bar_background'        						=> '#1a1a1f', // Deep charcoal
			'shoptimizer_layout_top_bar_text'              						=> '#e4e4e7', // Light gray text
			'shoptimizer_layout_top_bar_border'            						=> '#27272a', // Darker border

			// Layout.
			'shoptimizer_layout_wrapper'										=> 'no',

			// Sidebars.
			'shoptimizer_layout_woocommerce_sidebar'       						=> 'left-woocommerce-sidebar',
			'shoptimizer_wc_product_category_widget_toggle'						=> 'disable',
			'shoptimizer_layout_archives_sidebar'          						=> 'right-archives-sidebar',
			'shoptimizer_layout_post_sidebar'              						=> 'right-post-sidebar',
			'shoptimizer_layout_page_sidebar'              						=> 'right-page-sidebar',

			// Header - Dark elegant background
			'shoptimizer_header_layout'											=> 'default',
			'shoptimizer_header_layout_container'								=> 'contained',
			'shoptimizer_header_bg_color'                  						=> '#09090b', // Pure dark
			'shoptimizer_header_border_color'              						=> '#27272a', // Subtle border
			'shoptimizer_layout_search_display'            						=> 'enable',
			'shoptimizer_layout_search_display_type'							=> 'default',
			'shoptimizer_mobile_hamburger'                 						=> '#f4f4f5', // Light gray
			'shoptimizer_mobile_cart_color'										=> '#22d3ee', // Cyan accent
			'shoptimizer_mobile_bg'												=> '#18181b', // Dark mobile
			'shoptimizer_mobile_divider_line'              						=> '#3f3f46', // Gray divider
			'shoptimizer_mobile_color'                     						=> '#f4f4f5', // Light text
			'shoptimizer_sticky_mobile_header'			   						=> 'enable',
			'shoptimizer_search_mobile'											=> 'enable',
			'shoptimizer_search_mobile_position'								=> 'within-navigation',
			'shoptimizer_mobile_myaccount'										=> 'disable',
			'shoptimizer_tagline_display'				  						=> false,

			'shoptimizer_menu_display_description'         						=> true,

			'shoptimizer_layout_woocommerce_cart_icon'     						=> 'basket',

			'shoptimizer_layout_myaccount_display'								=> 'disable',

			'shoptimizer_layout_search_title'              						=> 'Search',

			'shoptimizer_cart_title'											=> 'Your Cart',
			'shoptimizer_cart_below_text'										=> '',
			'shoptimizer_sidebar_hide_cart_link'								=> false,
			'shoptimizer_minicart_quantity'										=> false,

			// Navigation - Rich dark with electric accents
			'shoptimizer_navigation_bg_color'              						=> '#18181b', // Rich dark
			'shoptimizer_navigation_border_color'		   						=> '',
			'shoptimizer_secondary_navigation_color'       						=> '#3f3f46', // Medium gray
			'shoptimizer_navigation_color'                 						=> '#f4f4f5', // Clean white text
			'shoptimizer_navigation_color_header_4'        						=> '#f4f4f5', // Consistent light
			'shoptimizer_navigation_color_hover'           						=> '#22d3ee', // Electric cyan hover
			'shoptimizer_menu_hover_intent'										=> false,

			// Navigation Dropdowns - Premium dark
			'shoptimizer_navigation_dropdown_background'   						=> '#27272a', // Elevated dark
			'shoptimizer_navigation_dropdown_color'        						=> '#e4e4e7', // Light gray text
			'shoptimizer_navigation_dropdown_hover_color'  						=> '#06b6d4', // Cyan hover

			// Navigation Cart - Vibrant accents
			'shoptimizer_cart_color'                       						=> '#f4f4f5', // White text
			'shoptimizer_cart_hover_color'                 						=> '#22d3ee', // Cyan hover
			'shoptimizer_cart_icon_color'                  						=> '#22d3ee', // Electric cyan
			'shoptimizer_cart_bubble_background_color'							=> '#dc2626', // Red notification
			'shoptimizer_cart_bubble_border_color'								=> '#dc2626', // Red border

			// Sticky Header.
			'shoptimizer_sticky_header'                    						=> 'enable',
			'shoptimizer_logo_mark_image'                  						=> '',

			// Below Header - Vibrant accent section
			'shoptimizer_below_header_bg'                  						=> '#0ea5e9', // Electric blue
			'shoptimizer_below_header_text'                						=> '#ffffff', // Pure white

			// General
			'shoptimizer_layout_woocommerce_breadcrumbs_type' 					=> 'default',

			// Mobile products per row
			'shoptimizer_layout_woocommerce_products_per_row_mobile'			=> '2-mobile',

			// Demo store
			'shoptimizer_layout_woocommerce_demo_store_display'					=> 'show',

			// Floating button
			'shoptimizer_layout_floating_button_display'						=> 'enable',
			'shoptimizer_layout_floating_button_background'						=> '#22d3ee', // Cyan button
			'shoptimizer_layout_floating_button_text'							=> '#ffffff', // White text
			'shoptimizer_layout_floating_button_text_content'					=> 'New Products',

			// Checkout Optimizations.
			'shoptimizer_checkout_sliding_bar_background'						=> '#27272a', // Dark bar
			'shoptimizer_checkout_sliding_bar_background_mobile'				=> '#18181b', // Darker mobile
			'shoptimizer_checkout_sliding_bar_color'							=> '#e4e4e7', // Light text

			// Buttons - Modern accent colors
			'shoptimizer_button_background_color'        						=> '#22d3ee', // Primary cyan
			'shoptimizer_button_text_color'              						=> '#ffffff', // White text
			'shoptimizer_button_alt_background_color'    						=> '#16a34a', // Success green
			'shoptimizer_button_alt_text_color'          						=> '#ffffff', // White text

			// Typography colors
			'shoptimizer_color_text_color'               						=> '#f4f4f5', // Light text
			'shoptimizer_color_heading_color'            						=> '#ffffff', // Pure white headings
			'shoptimizer_color_link_color'               						=> '#22d3ee', // Cyan links
			'shoptimizer_color_link_hover_color'         						=> '#06b6d4', // Darker cyan hover

			// Background colors
			'shoptimizer_layout_body_bg_color'            						=> '#09090b', // Deep black body
			'shoptimizer_layout_content_bg_color'         						=> '#18181b', // Dark content

			// WooCommerce specific colors
			'shoptimizer_sale_flash_bg'                  						=> '#dc2626', // Red sale
			'shoptimizer_sale_flash_text'                						=> '#ffffff', // White text
			'shoptimizer_price_color'                    						=> '#22d3ee', // Cyan price
			'shoptimizer_sale_price_color'               						=> '#16a34a', // Green sale price

			// Star ratings
			'shoptimizer_star_rating_color'              						=> '#facc15', // Golden yellow

			// Forms
			'shoptimizer_form_bg_color'                  						=> '#27272a', // Dark form bg
			'shoptimizer_form_border_color'              						=> '#3f3f46', // Gray border
			'shoptimizer_form_text_color'                						=> '#f4f4f5', // Light text

			// Additional modern elements
			'shoptimizer_notice_success_bg'              						=> '#166534', // Dark green
			'shoptimizer_notice_error_bg'                						=> '#991b1b', // Dark red
			'shoptimizer_notice_info_bg'                 						=> '#1e40af', // Dark blue

		);

		return apply_filters( 'poxica_theme_option_defaults', $defaults );

	}
}

/**
 * Backward compatibility function
 */
if ( ! function_exists( 'shoptimizer_get_option_defaults' ) ) {
	function shoptimizer_get_option_defaults() {
		return poxica_theme_get_option_defaults();
	}
}
