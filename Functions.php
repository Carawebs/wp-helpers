<?php

namespace Carawebs\Helpers;

//use Carawebs\Helpers\NavWalker;

/**
* This class contains methods that override and amend basic WordPress functionality.
*
* The class methods which run are controlled by passing in an arguments array.
*/
class Functions {

    public function __construct( array $args = [] ) {
        $this->set_args( $args );
        $this->execute_methods();
    }

    /**
    * Set arguments - these determine which methods will run.
    *
    * Arguments passed in will override the defaults.
    *
    * @param array $args Array of arguments in the format {method_name}=>boolean
    */
    public function set_args( $args ) {
        $defaults = [
            'kill_emojis' => true,
            'remove_comments' => true,
            'page_excerpts' => true,
            'blog_page_content' => true,
            'setup_nav' => true,
            'nav_classes' => true,
            'soil_cpt_menu_classes' => true,
            'bs4_menu_classes' => true,
            'title_case_archive_titles' => true,
            'extra_editor_privileges' => false,
        ];
        $this->args = array_merge( $defaults, $args );
    }

    /**
    * Run class methods if the argument value is true.
    *
    * The $args property for this class is an associative array that consists of
    * key-value pairings in the format {method_name} => boolean. This method
    * checks the value for each element of the $this->args array - if true,
    * the method corresponding to the element key is executed.
    *
    * @return void
    */
    public function execute_methods() {
        foreach ($this->args as $key => $value) {
            if ( true === $value ) {
                $this->$key();
            }
        }
    }

    /**
    * Set wp_nav_menu defaults.
    *
    * Remove the container. Remove the id on nav menu items. Specify a custom
    * Walker class from this library.
    *
    * If the soil navigation has been selected - e.g. the theme contains
    * `add_theme_support('soil-nav-walker');`, do nothing.
    */
    public function setup_nav() {
        global $_wp_theme_features;
        if (true === $_wp_theme_features['soil-nav-walker']) return;

        add_filter('wp_nav_menu_args', function($args = '') {
            $nav_menu_args = [];
            $nav_menu_args['container'] = false;

            // If the 'items_wrap' element is null, set a basic wrapper
            if (!$args['items_wrap']) {
                $nav_menu_args['items_wrap'] = '<ul class="%2$s">%3$s</ul>';
            }

            if (!$args['walker']) {
                $nav_menu_args['walker'] = new NavWalker();
            }

            return array_merge($args, $nav_menu_args);
        });

        // No item ids
        add_filter( 'nav_menu_item_id', '__return_null' );
    }

    /**
    * Amend menu CSS.
    *
    * Remove the id="" on nav menu items. Return 'menu-slug' for nav menu classes.
    * Better naming of active element CSS class.
    * @return void
    */
    public function nav_classes()
    {
        add_filter( 'nav_menu_css_class', function($classes, $item) {
            $slug = sanitize_title($item->title);
            $classes = preg_replace('/(current(-menu-|[-_]page[-_])(item|parent|ancestor))/', 'active', $classes);
            $classes = preg_replace('/^((menu|page)[-_\w+]+)+/', '', $classes);
            $classes[] = 'menu-' . $slug;
            $classes = array_unique($classes);
            error_log(print_r($classes, true));
            $filteredClasses = [];
            foreach ($classes as $class) {
                if (empty($class)) continue;
                $filteredClasses[] = trim($class);
            }
            error_log(print_r($filteredClasses, true));
            return $filteredClasses;
        }, 10, 2 );
    }

    /**
    * Get rid of the emoji mess.
    *
    * @return void
    */
    public function kill_emojis(){
        add_action( 'init', function() {
            remove_action( 'admin_print_styles', 'print_emoji_styles' );
            remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
            remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
            remove_action( 'wp_print_styles', 'print_emoji_styles' );
            remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
            remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
            remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
        });

        add_filter( 'tiny_mce_plugins', function( $plugins ) {
            if ( is_array( $plugins ) ) {
                return array_diff( $plugins, array( 'wpemoji' ) );
            } else {
                return array();
            }
        });
    }

    /**
    * Disable Comments.
    *
    * @return void
    */
    public function remove_comments() {

        // Remove from admin menu
        add_action( 'admin_menu', function () {
            remove_menu_page( 'edit-comments.php' );
        });

        // Remove from post and pages
        add_action( 'init', function () {
            remove_post_type_support( 'post', 'comments' );
            remove_post_type_support( 'page', 'comments' );
        }, 100 );

        // Remove from admin bar
        add_action( 'wp_before_admin_bar_render', function () {
            global $wp_admin_bar;
            $wp_admin_bar->remove_menu( 'comments' );
        });

    }

    /**
    * Allow custom excerpts on WordPress Pages.
    *
    * @return void
    */
    public function page_excerpts() {
        add_action( 'init', function () {
            add_post_type_support( 'page', array( 'excerpt' ));
        });
    }

    /**
    * Add the wp-editor back into WordPress after it was removed in 4.2.2.
    *
    * @see http://dev-notes.eu/2016/05/wordpress-content-on-posts-for-pages/
    * @param $post
    * @return void
    */
    public function blog_page_content() {
        add_action( 'edit_form_after_title', function( $post ) {
            if( $post->ID != get_option( 'page_for_posts' ) ) { return; }
            remove_action( 'edit_form_after_title', '_wp_posts_page_notice' );
            add_post_type_support( 'page', 'editor' );
        }, 0 );
    }

    /**
    * Amend menu classes for roots/soil menu to denote parent for Custom Post Types.
    *
    * This is useful if your CPT "Archive" is actually a 'page' with a custom loop
    * embedded. You must define two filters for this to work:
    * - 'carawebs/amend-menu-cpts-target-cpts'
    * - 'carawebs/amend-menu-cpts-target-locations'
    * See the readme of this package for more information.
    *
    * @TODO Allow custom menus in widgets to be targeted - these don't have a
    * theme location.
    *
    * @return void
    */
    public function soil_cpt_menu_classes() {
        $cpts = apply_filters('carawebs/amend-menu-cpts-target-cpts', []);
        $targetMenuLocations = apply_filters('carawebs/amend-menu-cpts-target-locations', []);

        if (empty($cpts)) return;
        foreach ($cpts as $cpt_slug => $menu_item) {
            add_filter('nav_menu_css_class', function($classes, $item, $args) use ($cpt_slug, $menu_item, $targetMenuLocations) {
                // Specify the menu to target
                if (!in_array($args->theme_location, $targetMenuLocations)) {
                    return $classes;
                }

                // On single CPT remove active class, amend specified parent as active.
                if (is_singular($cpt_slug)) {
                    $classes = str_replace( 'active', '', $classes );
                    $classes = str_replace( 'menu-'.$menu_item['class'], 'menu-'.$menu_item['class'].' active', $classes );
                }
                return $classes;
            }, 100, 3 );
        }
    }

    /**
    * Add the proper Bootstrap 4 nav classes to <li> and <a> elements.
    *
    * @return [type] [description]
    */
    public function bs4_menu_classes() {
        add_filter( 'nav_menu_link_attributes', function($classes) {
            $classes['class'] = "nav-link";
            return $classes;
        }, 100, 2 );
        add_filter( 'nav_menu_css_class', function( $classes ) {
            $classes[] = "nav-item";
            return $classes;
        }, 10, 1);
    }

    /**
    * Simple function to Title Case archive titles.
    * @return void
    */
    public function title_case_archive_titles() {
        add_filter( 'get_the_archive_title', function( $title ) {
            return ucwords($title);
        });
    }

    /**
     * Allow editors to control widgets and menus.
     * @return array Capabilities
     */
    public function extra_editor_privileges()
    {
        add_filter('user_has_cap', function($caps) {
            if(!empty( $caps['edit_pages'])) {
                $caps['edit_theme_options'] = true;
            }
            return $caps;
        });
    }
}
