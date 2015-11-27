<?php
/**
 * Author: John
 * Date: 11/19/2015
 * Time: 4:16 PM
 * This file sets autoloader and some gravity forms hooks/filters, action to control display of weekly planner posts
 */

namespace ez_sales_reps\etc;

function autoloader($class) {
    $class = str_replace( "\\", "/", strtolower($class));

    $path = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $class . '.php';

    if(file_exists($path)) {
        require_once $path;
    }
}

spl_autoload_register('\ez_sales_reps\etc\autoloader');

//Sales Rep for the assigned zip code receives an email notification that there is a pending registration to review for approval
function sales_rep_notification( $notification, $form, $entry ) {

    if ( $notification['name'] == 'Admin Notification' ) {

        //get business zip code
        $zip = rgar($entry, '2.5');

        //get sales rep for that territory's email address
        global $wpdb;
        $rep_id = $wpdb->get_var("SELECT user_id FROM wp_usermeta where meta_value LIKE ($zip) AND meta_key = 'territory'");
        $rep_info = get_user_by('id', $rep_id);

        // toType can be routing or email
        $notification['toType'] = 'email';
        //notify sales rep
        $notification['to'] = $rep_info->user_email;
        $notification['message'] = ' A new business has registered in your sales territory. Please review pending registration for approval.'. $notification['message'];
    }

    return $notification;
}

add_filter( 'gform_notification_2', '\ez_sales_reps\etc\sales_rep_notification', 10, 3 );

//Once approved the business owner will receive an email notification welcoming them to E-Z Save and login instructions
function business_owner_notification($user_id) {
    wp_new_user_notification($user_id, null, 'both');
}

add_action("gform_user_registered", '\ez_sales_reps\etc\business_owner_notification', 10, 4);

function display_weekly_planner_posts() {

    //find posts with package type weekly planner
    global $wpdb;
    $weekly_planner_post_ids = $wpdb->get_results("SELECT post_id FROM `wp_postmeta` WHERE meta_key = 'package_select' and meta_value = 19");

    //if weekly planner posts day != current weekday then set post to draft
    foreach ($weekly_planner_post_ids as $id) {
        if (trim(get_post_meta($id->post_id, 'weekly_planner_day', true)) == date('l')) {
            wp_update_post( array('ID' => $id->post_id, 'post_status' => 'publish'));
        } else {
            wp_update_post( array('ID' => $id->post_id, 'post_status' => 'private'));
        }
    }
}
add_action('wp', 'ez_sales_reps\etc\display_weekly_planner_posts');


?>