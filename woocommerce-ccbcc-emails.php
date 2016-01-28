<?php
/*
Plugin Name: WooCommerce Cc/Bcc Order Emails
Plugin URI:  http://URI_Of_Page_Describing_Plugin_and_Updates
Description: This describes my plugin in a short sentence
Version:     1.5
Author:      Nate Finch
Author URI:  http://URI_Of_The_Plugin_Author
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
Text Domain: my-toolset
*/

//Need to sparate input 1 and 2, input 2 retrieves from input 1. Check phpMyAdmin database.



/*
 * Adds Custom Meta Box for Cc/Bcc Emails
 * Codex Reference: https://codex.wordpress.org/Function_Reference/add_meta_box
 -------------------------------------------*/

/**
 * Adds a box to the main column on the Post and Page edit screens.
 */
function ccbcc_add_meta_box() {

    $screens = array( 'shop_order' );

    foreach ( $screens as $screen ) {

        add_meta_box(
            'ccbcc_sectionid',
            __( 'Cc/Bcc Emails ', 'ccbcc_textdomain' ),
            'ccbcc_meta_box_callback',
            $screen,
            'side',
            'core'
        );
    }
}
add_action( 'add_meta_boxes', 'ccbcc_add_meta_box' );

/**
 * Prints the box content.
 *
 * @param WP_Post $post The object for the current post/page.
 */
function ccbcc_meta_box_callback( $post ) {

    // Add a nonce field so we can check for it later.
    wp_nonce_field( 'ccbcc_save_meta_box_data', 'ccbcc_meta_box_nonce' );

    /*
	 * Use get_post_meta() to retrieve an existing value
	 * from the database and use the value for the form.
	 */
    $value = get_post_meta( $post->ID, '_ccbcc_cc_emails', true );
		
    echo '<label for="ccbcc_cc_field">';
    _e( 'Add any emails you want to Cc here, coma separated:', 'ccbcc_textdomain' );
    echo '</label> ';
    echo '<input type="text" id="ccbcc_cc_field" name="ccbcc_cc_field" value="' . esc_attr( $value ) . '" size="25" /><br/>';
		
    $value2 = get_post_meta( $post->ID, '_ccbcc_bcc_emails', true );
		
    echo '<label for="ccbcc_bcc_field">';
    _e( 'Add any emails you want to Bcc here, coma separated:', 'ccbcc_textdomain' );
    echo '</label> ';
    echo '<input type="text" id="ccbcc_bcc_field" name="ccbcc_bcc_field" value="' . esc_attr( $value2 ) . '" size="25" />';
}

/**
 * When the post is saved, saves our custom data.
 *
 * @param int $post_id The ID of the post being saved.
 */
    function ccbcc_save_meta_box_data( $post_id ) {

        /*
         * We need to verify this came from our screen and with proper authorization,
         * because the save_post action can be triggered at other times.
         */

        // Check if our nonce is set.
        if ( ! isset( $_POST['ccbcc_meta_box_nonce'] ) ) {
            return;
        }

        // Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $_POST['ccbcc_meta_box_nonce'], 'ccbcc_save_meta_box_data' ) ) {
            return;
        }

        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check the user's permissions.
        if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {

            if ( ! current_user_can( 'edit_page', $post_id ) ) {
                return;
            }

        } else {

            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return;
            }
        }

        /* OK, it's safe for us to save the data now. */

        // Make sure that it is set.
        if ( ! isset( $_POST['ccbcc_cc_field'] ) ) {
            return;
        }
        if ( ! isset( $_POST['ccbcc_bcc_field'] ) ) {
            return;
        }

        // Sanitize user input.
        $cc_data = sanitize_text_field( $_POST['ccbcc_cc_field'] );
        $bcc_data = sanitize_text_field( $_POST['ccbcc_bcc_field'] );

        // Update the meta field in the database.
        update_post_meta( $post_id, '_ccbcc_cc_emails', $cc_data );
        update_post_meta( $post_id, '_ccbcc_bcc_emails', $bcc_data );
    }
    add_action( 'save_post', 'ccbcc_save_meta_box_data' );

/*
 * End Custom Meta Box for Slider Hyperlink
 -------------------------------------------*/


/*
 * Add Cc and Bcc to WooCommerce Emails
 * https://docs.woothemes.com/wc-apidocs/source-class-WC_Email.html#269
 * https://github.com/woothemes/woocommerce/issues/6978
 -------------------------------------------*/

add_filter( 'woocommerce_email_headers', 'add_cc_and_bcc_headers', 10, 2);

function add_cc_and_bcc_headers($headers, $id, $object) {
    
		$postID = get_the_ID();
		$postMeta	= get_post_meta( $postID , '', true);
		$emailCC = $postMeta['_ccbcc_cc_emails'][0];
		$emailBCC = $postMeta['_ccbcc_bcc_emails'][0];
		$headers .= 'Cc: ' . $emailCC . "\r\n";
    $headers .= 'Bcc: ' . $emailBCC;
    return $headers;
}
