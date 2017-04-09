WordPress Helpers
==================
Odds and sods that are useful on most projects.

This library is in development and I may introduce breaking changes.

~~~php
// Within theme
/**
* Run themehelper setup
*/
add_action('after_setup_theme', function() {

    // Apply Themehelper functions - basic housekeeping
    if (class_exists('\Carawebs\Helpers\Functions')) {
        $args = [];
        new \Carawebs\Helpers\Functions( $args );
    }

});
~~~

## Menu Adjustment for Custom Post Type Views

Define the CPTs, menus and menu items to target from within the active theme.
Use the format `$keyvals['custom-post-type-slug'] => ['class'=>'menu-class-of-parent', 'text'=>'Text val of parent menu item'];`
~~~php
// Define the custom post type view and the required parent menu item:
add_filter( 'carawebs/amend-menu-cpts-target-cpts', function($keyvals) {
    $keyvals['service'] = ['class' => 'services', 'text' => 'Services'];
    return $keyvals;
});
~~~

Define an array of menu locations for which the amendments should apply using the 'carawebs/amend-menu-cpts-target-locations' hook:

~~~php
add_filter( 'carawebs/amend-menu-cpts-target-locations', function() {
    return ['primary_navigation'];
});
~~~
