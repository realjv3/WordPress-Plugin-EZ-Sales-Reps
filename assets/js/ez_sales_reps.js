/**
 * Created by John on 11/9/2015.
 */
jQuery(document).ready(function() {

    var $ = jQuery, trx_dates = $('.trx_datepicker');

    //set date fields to use jQuery UI datepicker
    $.datepicker.setDefaults({ dateFormat: 'yy-mm-dd'});
    trx_dates.datepicker();

    /**
     * Filters businesses and transactions by rep, all trx dates included
     * @param rep string    Sales Rep from change of sales rep selector
     */
    function filter_by_rep(rep) {

        //Show all trx
        var trxrows = $('tr.trxrow');
        trxrows.css('display', '');

        //Show all reps
        if (rep == '--All--') {

            $('tr.bizrow').each(function() {
                $(this).css('display', '');
            });

        } else {

            //Grab data-terr attribute from selected sales rep option tag and split into array of zip codes
            var territory = $('select#rep_select_reporting option:selected').attr('data-terr').split(',');
            //Show rows where data-zip attribute is a value in territory array
            $('tr.bizrow').css('display', '').each(function() {
                if (territory.indexOf($(this).attr('data-zip')) == -1) {
                    $(this).css('display', 'none');
                }
            });
            //Show rows where data-zip attribute is in territory array
            trxrows.css('display', '').each(function() {
                if (territory == '' || territory.indexOf($(this).attr('data-zip')) == -1) {
                    $(this).css('display', 'none');
                }
            });
        }
    }

    /**
     * Filters visible rows by selected date range
     */
    function filter_trx_by_date() {

        //Storing jq objects for date input, trx table rows, from/to in vars
        var trx_rows = $('tr.trxrow'), trx_from = $('input#trx_from').val() , trx_to = $('input#trx_to').val();

        if (trx_to == '') trx_to = '9999-12-31';

        //loop through each table row and display none on rows where data-date attribute is not in trx_from - trx_to range
        trx_rows.each(function(index, row) {

            if ($(row).attr('data-date') < trx_from || $(row).attr('data-date') > trx_to) {
                $(row).css('display', 'none');
            }
        });
    }

    /**
     * Ajax request when 'Export to CSV' buttons are clicked
     * @param target_id string  ID of clicked 'Export to csv' button
     */
    function export2csv(target_id) {

        var data = {};

        if (target_id == 'biz2csv') {
            var bizTable = $('#businesses').table2CSV({delivery:'value'});
            data = {
                'action': 'exp2csv',  //this hooks into wp_ajax_[exp2csv] action so wordpress uses my server-side ajax responder
                'table': bizTable
            };
        } else if (target_id == 'trx2csv') {
           var trxTable = $('#trx').table2CSV({delivery:'value'});
            data = {
                'action': 'exp2csv',
                'table': trxTable
            };
        }

        // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
        // WPURLS.siteurl comes from wp_localize_script() inside enqueue scripts function
        $.post(ajaxurl, data, function() {
            location.href= WPURLS.siteurl + "/wp-admin/data.csv";
        });
    }

    /**
     * Ajax request when rep is selected in Territory section, shows territory for rep
     * @param id int  User ID of selected sales rep in select#rep_select_terr
     */
    function showTerritory(id) {

        var data = {
            'action': 'get_territory',  //this hooks into wp_ajax_[territory] action so wordpress uses my server-side ajax responder
            'ID': id
        };

        // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
        $.post(ajaxurl, data, function(resp) {
            $('#update_terr > *').remove();
            $('#update_terr').append(resp);
        });
    }

    /**
     * Ajax request when 'Update territory' button is clicked
     * @param id int - id of sales rep
     * @param territory string - comma delimited string of 5 digit zip codes
     */
    function setTerritory(id, territory) {
        var data = {
            'action': 'set_territory',  //this hooks into wp_ajax_[territory] action so wordpress uses my server-side ajax responder
            'territory': territory,
            'ID': id
        };

        // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
        $.post(ajaxurl, data, function(resp) {
            $('#terr_assigned').remove();
            $('#update_terr').append(resp);
        });
    }

    /**
     * Event listeners
     */

    //Listen for change on territory section rep select
    $('#rep_select_terr').change(function(event) {
        var id = $(event.target).find(':selected').attr('data-id');
        showTerritory(id);
    });

    //Listen for clicks on 'Assign Territory' button
    $('body').on('click', 'input#submit' ,function() {
        var territory = $('#territory').val();
        var id = $('p.terr_output').attr('data-id');
        setTerritory(id, territory);
    });

    //Listen for change on select#rep_select_reporting
    $('#rep_select_reporting').change(function(event) {

        //Reset date inputs
        trx_dates.val('');

        var rep = $(event.target).find(':selected').val();
        filter_by_rep(rep);
    });

    //Listen for change on input#trx_from and input#trx_to
    $(trx_dates).change(function() {
        var rep = $('select#rep_select_reporting').val();
        filter_by_rep(rep);
        filter_trx_by_date();
    });

    //Listen for clicks on 'Export as csv' buttons
    $('.exp2csv').click(function(event) {
        var target_id = $(event.target).attr('id');
        export2csv(target_id);
    });
});
