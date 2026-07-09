<?php
/**
 * Plugin Name: Phoenix Talon Nav
 * Plugin URI: https://natebal.com/lab/code/phoenix-talon-nav/
 * Description: A high-performance, isolated mobile navigation plugin engineered for the modern web. Features hardware-accelerated drawer mechanics, autonomous AEO schema, and strict ARIA compliance.
 * Version: 1.0.2
 * Author: Nate Balcom
 * Author URI: https://natebal.com
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Enqueue isolated stylesheet and script
add_action( 'wp_enqueue_scripts', 'phoenix_talon_nav_assets', 999 );
function phoenix_talon_nav_assets() {
    wp_enqueue_style( 'phoenix-talon-nav-css', plugin_dir_url( __FILE__ ) . 'assets/nav.css', array(), '1.0.2' );
    wp_enqueue_script( 'phoenix-talon-nav-js', plugin_dir_url( __FILE__ ) . 'assets/nav.js', array(), '1.0.2', true );
}

// 2. Inject the Mobile Canvas Portal directly into the site footer
add_action( 'wp_footer', 'phoenix_talon_nav_canvas_injection' );
function phoenix_talon_nav_canvas_injection() {
    ?>
    <div id="phx-mobile-portal" class="phx-hidden">
        <div class="phx-drawer-overlay" id="phx-drawer-overlay"></div>
        <div class="phx-drawer" id="phx-drawer">
            <div class="phx-drawer-header">
                <button id="phx-drawer-close" aria-label="Close Menu">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="phx-drawer-content">
                <?php
                // Pulls the exact same menu you build in Appearance > Menus
                if ( has_nav_menu( 'primary' ) ) {
                    wp_nav_menu( array(
                        'theme_location' => 'primary',
                        'container'      => false,
                        'menu_class'     => 'phx-mobile-menu',
                        'depth'          => 4,
                    ) );
                }
                ?>
            </div>
        </div>
    </div>
    <?php
}

// 3. SEO & ARIA Link Interceptor Engine
add_filter( 'nav_menu_link_attributes', 'phoenix_talon_nav_seo_attributes', 10, 3 );
function phoenix_talon_nav_seo_attributes( $atts, $item, $args ) {
    
    // Inject Schema.org data mapping for Site Navigation
    $atts['itemprop'] = 'url';
    
    // Auto-generate Title tags for AEO context if they are left blank
    if ( empty( $atts['title'] ) ) {
        $atts['title'] = strip_tags( $item->title );
    }

    // Add static ARIA roles for parent drop-down triggers (Desktop & Mobile context)
    if ( in_array( 'menu-item-has-children', $item->classes ) ) {
        $atts['aria-haspopup'] = 'true';
    }

    return $atts;
}

// 4. AEO JSON-LD Schema Generator
add_action( 'wp_footer', 'phoenix_talon_nav_json_ld', 20 );
function phoenix_talon_nav_json_ld() {
    $locations = get_nav_menu_locations();
    if ( ! isset( $locations['primary'] ) ) return;
    
    $menu = wp_get_nav_menu_object( $locations['primary'] );
    if ( ! $menu ) return;
    
    $menu_items = wp_get_nav_menu_items( $menu->term_id );
    if ( ! $menu_items ) return;

    $schema = array(
        "@context" => "https://schema.org",
        "@type"    => "ItemList",
        "itemListElement" => array()
    );
    
    $position = 1;
    foreach ( $menu_items as $item ) {
        // Only map top-level parent links to keep the schema clean and focused
        if ( $item->menu_item_parent == 0 ) { 
            $schema['itemListElement'][] = array(
                "@type"    => "SiteNavigationElement",
                "position" => $position,
                "name"     => strip_tags( $item->title ),
                "url"      => $item->url
            );
            $position++;
        }
    }
    
    echo "\n";
    echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . "</script>\n";
}
