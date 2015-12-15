<?php

namespace ez_sales_reps\includes;

/**
 * Server-side ajax handlers
 */
class ajax {

    public function __construct() {

        add_action( 'wp_ajax_exp2csv', array($this, 'exp2csv_cb'));
        add_action( 'wp_ajax_get_territory', array($this, 'get_territory_cb'));
        add_action( 'wp_ajax_set_territory', array($this, 'set_territory_cb'));
    }

    //Callback for exporting to csv
    public function exp2csv_cb() {

        $data = stripcslashes($_REQUEST['table']);
        file_put_contents('data.csv', $data);

        exit;
    }

    //Callback for getting sales rep territory
    public function get_territory_cb() {
        global $ezsr_data;
        $id = stripcslashes($_REQUEST['ID']);
        foreach ($ezsr_data->reps as $rep) {
            if ( $rep['ID'] == $id) {
                $territory = $rep['territory'];
                echo '<p class="terr_output" data-id="', $rep['ID'] ,'">', $rep['display_name'], '</p><label class="terr_output" for="sel_rep_name">Assigned Territory: </label><input class="terr_output" type="text" name="territory" id="territory" value="', $territory ,'"><small class="terr_output">&nbsp;* use comma separated 5 digit zip codes</small>', submit_button('Update territory');
            }
        }
        exit;
    }

    //Callback for setting sales rep territory
    public function set_territory_cb() {

        //TODO populate values zip chooser
        //TODO update businesses & trx with ajax

        $id = stripcslashes($_REQUEST['ID']);
        $territory = stripcslashes($_REQUEST['territory']);

        //sanitize territory
        if (preg_match('/( *\d{5} *,?)+/',$territory)) {

            //set territory
            update_user_meta($id, 'territory', str_replace(' ', '', $territory));
            echo '<p class="terr_output" id="terr_assigned">Territory has been updated. Refresh page for updated reporting.</p>';
            exit;
        } else {
            echo '<p class="terr_output" id="terr_assigned" style="color: red">Input comma separated 5 digit zip codes, i.e. 11726, 11727</p>';
            exit;
        }
    }
}

?>