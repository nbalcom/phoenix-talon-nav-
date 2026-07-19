<?php
/**
 * Plugin Name: Phoenix Talon Nav
 * Plugin URI: https://natebal.com/lab/code/phoenix-talon-nav/
 * Description: A high-performance, isolated mobile navigation plugin engineered for the modern web. Features hardware-accelerated drawer mechanics, autonomous AEO schema, and strict ARIA compliance.
 * Version: 1.1.0
 * Author: Nate Balcom
 * Author URI: https://natebal.com
 * https://natebal.com/lab/code/phoenix-talon-nav/
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ==========================================================================
// 1. ADMIN OPTIONS DASHBOARD CORE MATRIX
// ==========================================================================
add_action( 'admin_menu', 'phx_talon_nav_menu' );
function phx_talon_nav_menu() {
    add_options_page(
        'Phoenix Talon Nav Config',
        'Talon Nav Settings',
        'manage_options',
        'phoenix-talon-nav',
        'phx_talon_nav_settings_page'
    );
}

add_action( 'admin_init', 'phx_talon_nav_settings_init' );
function phx_talon_nav_settings_init() {
    register_setting( 'phx_talon_nav_group', 'phx_menu_location' );
    register_setting( 'phx_talon_nav_group', 'phx_hamburger_selector' );
    register_setting( 'phx_talon_nav_group', 'phx_hide_theme_nav' );
    register_setting( 'phx_talon_nav_group', 'phx_drawer_bg' );
}

function phx_talon_nav_settings_page() {
    ?>
    <div class="wrap">
        <h1>Phoenix Talon Nav Controls</h1>
        <form method="post" action="options.php">
            <?php settings_fields( 'phx_talon_nav_group' ); ?>
            <?php do_settings_sections( 'phx_talon_nav_group' ); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Target Menu Location Mapping</th>
                    <td>
                        <select name="phx_menu_location">
                            <?php 
                            $menus = wp_get_nav_menus();
                            $locations = get_nav_menu_locations();
                            $current = get_option('phx_menu_location', 'primary');
                            
                            // Let the user pick explicit custom menu definitions if location mappings differ
                            echo '<option value="primary" '.selected($current, 'primary', false).'>Default Location (Primary)</option>';
                            foreach($menus as $menu) {
                                echo '<option value="menu_id_'.$menu->term_id.'" '.selected($current, 'menu_id_'.$menu->term_id, false).'>Menu: '.$menu->name.'</option>';
                            }
                            ?>
                        </select>
                        <p class="description">Select the primary menu hierarchy asset to load into the AEO system and mobile drawer.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Custom Theme Hamburger Selector</th>
                    <td>
                        <input type="text" name="phx_hamburger_selector" value="<?php echo esc_attr(get_option('phx_hamburger_selector', '.phx-menu-toggle')); ?>" class="regular-text" />
                        <p class="description">Provide the CSS class or #ID selector of your header's hamburger button (e.g., <code>.menu-toggle</code> or <code>#hamburger</code>).</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Hide Theme Navigation (Mobile)</th>
                    <td>
                        <input type="text" name="phx_hide_theme_nav" value="<?php echo esc_attr(get_option('phx_hide_theme_nav', '.main-navigation')); ?>" class="regular-text" />
                        <p class="description">CSS container class targeting the existing theme mobile navigation selector you wish to suppress.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Custom Mobile Drawer Background</th>
                    <td>
                        <input type="text" name="phx_drawer_bg" value="<?php echo esc_attr(get_option('phx_drawer_bg', '')); ?>" placeholder="e.g. #0b0f19 or inherit" class="regular-text" />
                        <p class="description">Leave blank to cleanly inherit the global theme stylesheet background settings dynamically.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Helper calculation engine to resolve the active menu asset cleanly across platforms
function phx_talon_get_active_menu_items() {
    $target = get_option('phx_menu_location', 'primary');
    
    if ( strpos($target, 'menu_id_') === 0 ) {
        $menu_id = (int) str_replace('menu_id_', '', $target);
        return wp_get_nav_menu_items($menu_id);
    }
    
    $locations = get_nav_menu_locations();
    if ( isset( $locations[$target] ) ) {
        return wp_get_nav_menu_items( $locations[$target] );
    }
    
    // Universal Fallback Loop: Pull the first available populated menu entity if mapping configuration defaults
    $menus = wp_get_nav_menus();
    if ( ! empty( $menus ) ) {
        return wp_get_nav_menu_items( $menus[0]->term_id );
    }
    
    return false;
}

// ==========================================================================
// 2. ASSETS ENQUEUE & OPTIMIZATION PIPELINE
// ==========================================================================
add_action( 'wp_enqueue_scripts', 'phoenix_talon_nav_assets', 999 );
function phoenix_talon_nav_assets() {
    wp_enqueue_style( 'phoenix-talon-nav-css', plugin_dir_url( __FILE__ ) . 'assets/nav.css', array(), '1.1.0' );
    wp_enqueue_script( 'phoenix-talon-nav-js', plugin_dir_url( __FILE__ ) . 'assets/nav.js', array(), '1.1.0', true );

    // Inject active dashboard configurations dynamically straight into the DOM environment
    $hamburger_selector = get_option('phx_hamburger_selector', '.phx-menu-toggle');
    wp_localize_script( 'phoenix-talon-nav-js', 'phxTalonConfig', array(
        'hamburgerSelector' => $hamburger_selector
    ));
    
    // Inline dynamic style overrides based on dashboard option profiles
    $drawer_bg = get_option('phx_drawer_bg');
    $suppress_target = get_option('phx_hide_theme_nav', '.main-navigation');
    
    $custom_css = "";
    if ( ! empty( $suppress_target ) ) {
        $custom_css .= "@media (max-width: 768px) { " . esc_html($suppress_target) . " { display: none !important; } } ";
    }
    if ( ! empty( $drawer_bg ) ) {
        $custom_css .= ".phx-drawer, .phx-mobile-menu .sub-menu { background-color: " . esc_html($drawer_bg) . " !important; }";
    }
    
    if ( ! empty( $custom_css ) ) {
        wp_add_inline_style( 'phoenix-talon-nav-css', $custom_css );
    }
}

// ==========================================================================
// 3. CANVAS INJECTION AND PORTAL PARSING
// ==========================================================================
add_action( 'wp_footer', 'phoenix_talon_nav_canvas_injection' );
function phoenix_talon_nav_canvas_injection() {
    $menu_items = phx_talon_get_active_menu_items();
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
                if ( $menu_items ) {
                    // Re-render the parsed location using fallback array parameters
                    $target = get_option('phx_menu_location', 'primary');
                    $nav_menu_args = array(
                        'container'  => false,
                        'menu_class' => 'phx-mobile-menu',
                        'depth'      => 4,
                    );
                    
                    if ( strpos($target, 'menu_id_') === 0 ) {
                        $nav_menu_args['menu'] = (int) str_replace('menu_id_', '', $target);
                    } else {
                        $nav_menu_args['theme_location'] = $target;
                    }
                    
                    wp_nav_menu( $nav_menu_args );
                }
                ?>
            </div>
        </div>
    </div>
    <?php
}

// ==========================================================================
// 4. SEO & ARIA MAPPING INTERCEPTOR ENGINE
// ==========================================================================
add_filter( 'nav_menu_link_attributes', 'phoenix_talon_nav_seo_attributes', 10, 3 );
function phoenix_talon_nav_seo_attributes( $atts, $item, $args ) {
    $atts['itemprop'] = 'url';
    
    if ( empty( $atts['title'] ) ) {
        $atts['title'] = strip_tags( $item->title );
    }

    if ( in_array( 'menu-item-has-children', $item->classes ) ) {
        $atts['aria-haspopup'] = 'true';
    }

    return $atts;
}

// ==========================================================================
// 5. DATA GRAPH / AEO JSON-LD GENERATOR
// ==========================================================================
add_action( 'wp_footer', 'phoenix_talon_nav_json_ld', 20 );
function phoenix_talon_nav_json_ld() {
    $menu_items = phx_talon_get_active_menu_items();
    if ( ! $menu_items ) return;

    $schema = array(
        "@context" => "https://schema.org",
        "@type"    => "ItemList",
        "itemListElement" => array()
    );
    
    $position = 1;
    foreach ( $menu_items as $item ) {
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
    
    echo "\n<script type=\"application/ld+json\">\n" . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . "\n</script>\n";
}