<?php

namespace ez_sales_reps\includes;

/**
 * Sets required data for sales reps, businesses, and transactions
 */
class data {

    public $reps;
    public $bizs;
    public $trxs;

    public function __construct() {
        $this->set_data();
    }

    private function set_data() {

        global $wpdb;

        //Set sales rep data
        $ez_sales_reps = get_users(array('role' => 'Contributor'));
        foreach ($ez_sales_reps as $ez_sales_rep) {
            $territory = get_user_meta($ez_sales_rep->ID, 'territory', true);
            $this->reps[] = array('ID' => $ez_sales_rep->ID, 'display_name' => $ez_sales_rep->data->display_name, 'user_email' => $ez_sales_rep->data->user_email, 'territory' => $territory);
        }

        //Set business data
        $businesses = get_users(array('role' => 'biz', 'fields' => array('ID', 'display_name', 'user_status')));
        foreach ($businesses as $business) {

            $mrc = $wpdb->get_var("SELECT CONCAT('$', FORMAT(SUM(payable_amt), 2)) FROM wp_transactions WHERE user_id = $business->ID");
            $usermeta = get_user_meta($business->ID);

            $this->bizs[] = array (
                'display_name' => $business->display_name,
                'zip' => $usermeta['zip'][0],
                'biz_name' => $usermeta['biz_name'][0],
                'addr1' => $usermeta['addr1'][0],
                'city' => $usermeta['city'][0],
                'thestate' => $usermeta['thestate'][0],
                'user_status' => $business->user_status,
                'mrc' => $mrc
            );
        }

        //Set transaction data
        $trxs = $wpdb->get_results("SELECT trans_id, user_id, post_id, user_name, post_title, status, payment_method, CONCAT('$', FORMAT(payable_amt, 2)) AS payable_amt, pay_email, CAST(payment_date AS DATE) AS payment_date, package_id FROM wp_transactions WHERE payable_amt > 0 ORDER BY  payment_date DESC, trans_id DESC");
        foreach ($trxs as $trx) {

            $this->trxs[] = array (
                'trans_id' => $trx->trans_id,
                'payment_date' => $trx->payment_date,
                'zip' => get_user_meta($trx->user_id, 'zip', true),
                'pkg_type' => get_post_meta($trx->package_id, 'package_type', true),
                'user_name' => $trx->user_name,
                'pay_email' => $trx->pay_email,
                'title' => get_the_title($trx->package_id),
                'payable_amt' => $trx->payable_amt,
                'payment_method' => $trx->payment_method,
                'status' => $trx->status,
            );
        }
    }
}