<?php

namespace ez_sales_reps\includes;

/**
 * Loads and renders Users\E-Z Sales Representatives admin page
 */
class admin_page {

    public $rep_rows = '';
    public $biz_rows = '';
    public $trx_rows = '';
    private $data;

    /**
     * @param data $data - instance contains rep, business and trx data
     * for dynamic output
     */
    public function __construct(data $data) {

        $this->data = $data;
        $this->render_reps_biz_trx();
        add_action('admin_menu', array($this, 'add_users_page'));
    }

    /**
     * Gets sales rep, businesses and transaction data from $data object and creates html elements
     * for use in page_html()
     */
    private function render_reps_biz_trx() {

        //make sales rep <option>s
        foreach ((array)$this->data->reps as $rep) {
            $this->rep_rows .= '<option class="rep_row" data-id="' . $rep['ID'] . '" data-terr="'. $rep['territory'] . '">' . $rep['display_name'] . '</option>';
        }

        //make businesses <tr>s
        foreach ($this->data->bizs as $business) {

            $row = '<tr class= "bizrow" data-zip="'. $business['zip'] . '"><td>'. $business['biz_name']. '</td>';
            $row .= '<td class= "bizrow">'. $business['display_name']. '</td>';
            $row .= '<td class= "bizrow">'. $business['addr1']. '</td>';
            $row .= '<td class= "bizrow">'. $business['city']. '</td>';
            $row .= '<td class= "bizrow">'. $business['thestate']. '</td>';
            $row .= '<td class= "bizrow">'. $business['zip']. '</td>';
            $row .= '<td class= "bizrow">'. (($business['user_status'] == 0) ? 'active' : 'inactive') . '</td>';
            $row .= '<td class= "bizrow">'. (($business['mrc'] == null) ? '$0.00' : $business['mrc']) . '</td></tr>';
            $this->biz_rows .= $row;
        }

        //make transaction <tr>s
        foreach ((array)$this->data->trxs as $trx) {

            $row = '<tr class= "trxrow" data-date="' . $trx['payment_date'] . '" data-zip="'. $trx['zip'] . '">';
            $row .= '<td>'. $trx['user_name']. '<br>'. $trx['pay_email']. '<br> ID: '. $trx['trans_id']. '</td>';
            $row .= '<td>'. $trx['title'] . ' <br>'.(($trx['pkg_type'] == 2) ? 'Recurring' : 'Single').'</td>';
            $row .= '<td>'. $trx['payable_amt']. '</td>';
            $row .= '<td>'. $trx['payment_method']. '</td>';
            $row .= '<td>'. $trx['payment_date']. '</td>';
            $row .= '<td>'. (($trx['status'] == 0) ? '<p style="color: darkorange">Pending</p>' : '<p style="color: green">Approved</p>'). '</td></tr>';
            $this->trx_rows .= $row;
        }
    }

    /**
     * Add users\E-Z Sales Representatives page to wordpress backend
     * https://codex.wordpress.org/Function_Reference/add_users_page
     */
    public function add_users_page() {
        add_users_page( __('E-Z Sales Representatives'), __('E-Z Sales Representatives'), 'manage_options', 'ez_sales_reps', array($this, 'page_html') );
    }

    /**
     * The callback function to be called to output the content for ez_sales_reps page.
     */
    public function page_html() {
        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }

        ?>
        <div class="wrap">
            <h2><?php _e('E-Z Sales Representatives'); ?></h2>
            <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
                <div class="panel">
                    <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                        <div class="panel-heading" role="tab" id="headingOne">
                            <h3 class="panel-title">
                                <?php _e('E-Z Sales Rep Registration'); ?>
                            </h3>
                        </div>
                    </a>
                    <div id="collapseOne" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingOne">
                        <div class="panel-body">
                            <?php gravity_form(3); ?>
                        </div>
                    </div>
                </div>
                <div class="panel">
                    <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseThree" aria-expanded="true" aria-controls="collapseOne">
                        <div class="panel-heading" role="tab" id="headingOne">
                            <h3 class="panel-title">
                                <?php _e('E-Z Sales Rep Territory'); ?>
                            </h3>
                        </div>
                    </a>
                    <div id="collapseThree" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingOne">
                        <div class="panel-body">
                            <label for="rep_select_terr">E-Z Sales Rep:</label>
                            <select class="form-control" id="rep_select_terr" style="display: inline; width: 150px;">
                                <option>--Select--</option>
                                <?php  echo $this->rep_rows; ?>
                            </select>
                            <section id="update_terr">

                            </section>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">
                        <div class="panel-heading" role="tab" id="headingTwo">
                            <h3 class="panel-title">
                                <?php _e('Reporting'); ?>
                            </h3>
                        </div>
                    </a>
                    <div id="collapseTwo" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingTwo">
                        <label for="rep_select_reporting" style="margin: 20px">E-Z Sales Rep:</label>
                        <select class="form-control" id="rep_select_reporting" style="display: inline; width: 150px;">
                            <option>--All--</option>
                            <?php  echo $this->rep_rows; ?>
                        </select>
                        <div class="panel-body">
                            <button class="btn btn-primary btn-sm exp2csv" id="biz2csv" type="button" style="float:right">Export as CSV</button>
                            <h4>Registered Businesses by Sales Rep</h4>
                            <table class="table table-striped table-bordered table-hover table-condensed" id="businesses">
                                <thead>
                                <tr>
                                    <th>Business Name</th>
                                    <th>Contact</th>
                                    <th>Address</th>
                                    <th>City</th>
                                    <th>State</th>
                                    <th>ZIP</th>
                                    <th>Status</th>
                                    <th>MRC</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php echo $this->biz_rows; ?>
                                </tbody>
                            </table>
                            <button class="btn btn-primary btn-sm exp2csv" id="trx2csv" type="button" style="float:right; margin-bottom: 8px;">Export as CSV</button>
                            <h4 style="display: inline;">Transactions by Sales Rep</h4>
                            <fieldset style="display: inline; margin-left: 50px;">
                                <label for="trx_from">From:</label><input id="trx_from" class="trx_datepicker" type="text">
                                <label for="trx_to">To:</label><input id="trx_to" class="trx_datepicker" type="text">
                            </fieldset>
                            <table class="table table-striped table-bordered table-hover table-condensed" id="trx">
                                <thead>
                                <tr>
                                    <th>User / Email / Order ID</th>
                                    <th>Price Package</th>
                                    <th>Amount</th>
                                    <th>Payment Method</th>
                                    <th>Paid On</th>
                                    <th>Status</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php echo $this->trx_rows; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php
    }

}