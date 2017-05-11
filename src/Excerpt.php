<?php

namespace Carawebs\Helpers;

class Excerpt {

    /**
    *
    * @see http://codex.wordpress.org/Template_Tags/the_content#Alternative_Usage
    * @param  [type] $words [description]
    * @return [type]        [description]
    */
    public static function content( $post, $word_count = NULL )
    {
        $post_object = !is_object($post) ? get_post( $post ) : $post;

        // Modify get_the_content with the filters attached to the_content - so html tags are maintained
        $content = apply_filters( 'the_content', $post_object->post_content );

        // Split $content into an array of strings, seperator is a space, so it's an array of words
        $words = explode( " ", $content );

        // Rebuild the array of words into a string, seperated by spaces. Array_splice
        // removes elements from $words array.
        $limited_content = implode( " ", array_splice( $words, 0, $word_count ) );

        // Strip tags, but keep <p> tags
        $stripped_limited_content = strip_tags( $limited_content, '<p>' ) . "&hellip;";

        return $stripped_limited_content;
    }

    public static function customExcerpt($wordCount = 25, $postID = NULL)
    {
        $postID = !empty($postID) ? $postID : get_the_ID();
        $postObject = get_post( $postID );

        $excerpt = ! empty ( $postObject->post_excerpt )
            ? $postObject->post_excerpt
            : self::rawContent($postObject, $wordCount);

        return $excerpt;
    }

    public static function rawContent( $post, $wordCount = NULL )
    {
        $post_object = !is_object($post) ? get_post($post) : $post;

        // Modify get_the_content with the filters attached to the_content - so html tags are maintained
        $content = $post_object->post_content;
        // Split $content into an array of strings, seperator is a space, so it's an array of words
        $words = explode( " ", $content );
        // Rebuild the array of words into a string, seperated by spaces.
        $limited_content = implode( " ", array_splice( $words, 0, $wordCount ) );

        return $limited_content . "&hellip;";
    }

    /**
    * Return a post excerpt if one is available, else return trimmed content.
    *
    * Can be used outside the loop - just pass in the ID of the post for which you
    * need an excerpt.
    *
    * @param  string|int $post_ID    ID of the post
    * @param  string|int $word_count Number of words to display
    * @return string                 Excerpt
    */
    public static function excerpt_or_content( $post_ID, $word_count = NULL )
    {
        $post_object = get_post( $post_ID );

        $excerpt = ! empty ( $post_object->post_excerpt )
        ? $post_object->post_excerpt
        : $post_object->post_content ?? NULL;

        // Modify get_the_content with the filters attached to the_content - so html tags are maintained.
        $content = apply_filters( 'the_content', $excerpt );

        if( ! empty( $word_count ) ) {

            // Split $content into an array of strings. Seperator is a space, so it's an array of words
            $words = explode( " ", $content );

            // Rebuild the array of words into a string, seperated by spaces.
            // Array_splice removes elements from $words array.
            $content = implode( " ", array_splice( $words, 0, $word_count ) );

        }

        // Strip tags, but keep specified tags.
        $stripped_limited_content = strip_tags( $content, '<p><blockquote>' );

        // Append an ellipsis if we've used truncated content.
        // If using the manual excerpt, it's up to the editor to not
        // exceed the word limit.
        $stripped_limited_content = empty ( $post_object->post_excerpt )
        ? $stripped_limited_content . "&hellip;"
        : $stripped_limited_content;

        return $stripped_limited_content;
    }
}
