<?php
namespace Carawebs\Helpers;

/**
* Cleaner walker for wp_nav_menu()
*
* Walker_Nav_Menu (WordPress default) example output:
*   <li id="menu-item-8" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-8"><a href="/">Home</a></li>
*   <li id="menu-item-9" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-9"><a href="/sample-page/">Sample Page</a></l
*
* NavWalker example output:
*   <li class="menu-home"><a href="/">Home</a></li>
*   <li class="menu-sample-page"><a href="/sample-page/">Sample Page</a></li>
*/
class NavWalker extends \Walker_Nav_Menu {

    function check_current( $classes ) {
        return preg_match('/(current[-_])|active|dropdown/', $classes);
    }

    function start_lvl( &$output, $depth = 0, $args = array() ) {
        $output .= "\n<ul class=\"dropdown-menu\">\n";
    }

    function start_el(&$output, $item, $depth = 0, $args = [], $id = 0) {
        $item_html = '';
        parent::start_el($item_html, $item, $depth, $args);

        if ( $item->is_dropdown && ( $depth === 0 ) ) {
            $item_html = str_replace('<a', '<a class="nav-link dropdown-toggle" data-toggle="dropdown" data-target="#"', $item_html);
            $item_html = str_replace('</a>', ' <b class="caret"></b></a>', $item_html);
        }
        elseif (stristr($item_html, 'li class="divider')) {
            $item_html = preg_replace('/<a[^>]*>.*?<\/a>/iU', '', $item_html);
        }
        elseif (stristr($item_html, 'li class="dropdown-header')) {
            $item_html = preg_replace('/<a[^>]*>(.*)<\/a>/iU', '$1', $item_html);
        }

        $item_html = apply_filters('carawebs/wp_nav_menu_item', $item_html);
        $output .= $item_html;
    }

    function display_element( $element, &$children_elements, $max_depth, $depth = 0, $args, &$output ) {
        $element->is_dropdown = ( ( !empty( $children_elements[$element->ID] ) && ( ( $depth + 1 ) < $max_depth || ( $max_depth === 0 ) ) ) );
        if ( $element->is_dropdown ) {
            $element->classes[] = 'dropdown';
        }
        parent::display_element($element, $children_elements, $max_depth, $depth, $args, $output);
    }
}
