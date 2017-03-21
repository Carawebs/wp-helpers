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
