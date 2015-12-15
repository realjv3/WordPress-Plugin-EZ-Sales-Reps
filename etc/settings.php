<?php
/**
 * Author: John
 * Date: 11/19/2015
 * Time: 4:16 PM
 * This file sets autoloader, sales rep/biz owner notifications (GF), action to control display of weekly planner posts,
 * 'Business Starter' offer edits monetization (woocommerce), addt'l cardholder monetization (GF paypal add-on), age verification,
 * restrict wp-admin access, and dashboard author tabs.
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

    //if weekly planner posts day != current weekday then set post to private
    foreach ($weekly_planner_post_ids as $id) {
        if (trim(get_post_meta($id->post_id, 'weekly_planner_day', true)) == date('l')) {
            wp_update_post( array('ID' => $id->post_id, 'post_status' => 'publish'));
        } else {
            wp_update_post( array('ID' => $id->post_id, 'post_status' => 'private'));
        }
    }
}
add_action('wp', 'ez_sales_reps\etc\display_weekly_planner_posts');

function biz_starter_offer_edits() {

    //before offer edit screen loads
    if ($_GET['action'] == 'edit' && $_SERVER['REDIRECT_URL'] == '/submit-listing/' || $_GET['page'] == 'success' && $_GET['action'] == 'edit') {
        global $wpdb;
        $package = $wpdb->get_var("SELECT `meta_value` FROM `wp_postmeta` WHERE `post_id` = $_GET[pid] AND `meta_key` = 'package_select'");
        $_SESSION['edits'] = $_GET['pid'];
        //check if offer's selected package is Business Starter
        if ($package == 415) {

            $edits = get_post_meta($_GET['pid'], 'biz_starter_edits', true);

            //after business starter post is edited successfully
            if ($_GET['page'] == 'success' && $_GET['action'] == 'edit') {

                //decrement edits post meta field
                update_post_meta($_GET['pid'], 'biz_starter_edits', --$edits);
                return;
            }

            //if user has no edits left, direct to product page to purchase 'changes' package
            if ($edits < 1) {
                ob_start();
                header("Location: http://sobez.wpengine.com/business-starter-offer-changes");
                exit();
            }
        }
    }
}
add_action('init', 'ez_sales_reps\etc\biz_starter_offer_edits');

//Woocommerce skip cart and go straight to checkout
function redirect_to_checkout() {
    return WC()->cart->get_checkout_url();
}
add_filter ('add_to_cart_redirect', 'ez_sales_reps\etc\redirect_to_checkout');

//once more edits have been bought, update available edits for that biz starter offer
function set_biz_starter_offer_edits($order_id) {

    $order = new \WC_Order($order_id);
    $items = $order->get_items();
    $items = array_shift($items);
    switch ($items['product_id']) {
        case 685:
            $edits = $items['qty'] * 1;
            break;
        case 686:
            $edits = $items['qty'] * 5;
            break;
        case 687:
            $edits = $items['qty'] * 11;
            break;
        case 688:
            $edits = $items['qty'] * 25;
            break;
    }
    update_post_meta($_SESSION['edits'], 'biz_starter_edits', $edits);
    $_SESSION['edits'] = '';
}
add_action('woocommerce_thankyou', 'ez_sales_reps\etc\set_biz_starter_offer_edits');


//validate member registration minimum age 18
add_filter('gform_field_validation_1_6', 'ez_sales_reps\etc\verify_minimum_age', 10, 4);
function verify_minimum_age($result, $value, $form, $field) {

    // date of birth is submitted in field 6 in the format YYYY-MM-DD
    $dob = implode('-', $value);

    // this the minimum age requirement we are validating
    $minimum_age = 18;

    // calculate age in years like a human, not a computer, based on the same birth date every year
    $age = date('Y') - substr($dob, 0, 4);
    if (strtotime(date('Y-m-d')) - strtotime(date('Y') . substr($dob, 4, 6)) < 0){
        $age--;
    }

    // is $age less than the $minimum_age?
    if( $age < $minimum_age ){

        // set the form validation to false if age is less than the minimum age
        $result['is_valid'] = false;
        $result['message'] = "Sorry, you must be at least $minimum_age years of age to join. You're $age years old.";
    }
    // assign modified $form object back to the validation result
    return $result;
}

//upon submission of cardholder registration form, charge $8/extra cardholder
add_filter( 'gform_product_info_1', 'ez_sales_reps\etc\calculate_total', 10, 3 );
function calculate_total( $product_info, $form, $lead ) {

    $addtl_cards = unserialize($lead[16]);
    $count = count($addtl_cards);
    //if one or more addt'l cardholder input, charge $count x $8.00, will result in paypal login
    if ($count > 0) {

        foreach($product_info['products'] as $key=>$product){

            if ($product['name'] == 'Additional cardholders') {
                $product_info['products'][$key]['quantity'] = $count;
                $product_info['products'][$key]['price'] = '$8.00';
                return $product_info;
            }
        }
    }
    return $product_info;
}

//fixes front-end total display if additional cardholders are input
add_action('gform_enqueue_scripts_1', 'ez_sales_reps\etc\display_total', 10, 2);
function display_total($form, $is_ajax) {
    wp_enqueue_script('display_total', plugin_dir_url(__DIR__) . 'assets/js/update_total.js');
}

/**
 * Restrict access to the administration screens.
 *
 * Only administrators will be allowed to access the admin screens,
 * all other users will be automatically redirected to the front of
 * the site instead.
 *
 * We do allow access for Ajax requests though, since these may be
 * initiated from the front end of the site by non-admin users.
 */
function restrict_admin_with_redirect() {

    if (!current_user_can('manage_options') && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )) {
        wp_redirect( site_url() );
        exit;
    }
}
add_action( 'admin_init', 'ez_sales_reps\etc\restrict_admin_with_redirect');

//removes 'Listing' tab from author page, removed cbos by deleting custom post type,
//commented out plugins\Tevolution\tmplconnector\monetize\templatic-registration\registration_functions.php:155 to remove 'Posts' tab
remove_action('tmpl_before_author_page_posttype_tab', 'tmpl_before_author_page_posttype_tab_return');

//leave the Toolbar available in the Dashboard but hide it on all front facing page
add_filter('show_admin_bar', '__return_false');

?>