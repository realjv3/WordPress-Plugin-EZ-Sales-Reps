/**
 * Created by John on 12/11/2015.
 */
//update total if additional cardholders are input
gform.addFilter('gform_product_total', function(total, formId){

    //only apply logic to form ID 1
    if(formId != 1)
        return total;

    //get 'additional cardholder' rows
    var addtlCardRows = jQuery('li#field_1_16 tr[class^="gfield_list_"]');
    addtlCardRows = jQuery.makeArray(addtlCardRows);

    //filter out rows that don't have each field filled out
    var filledOutCardRows = addtlCardRows.filter(function(elem) {

        if (jQuery(elem).find('input[value=""]').length == 0) {
            return elem;
        }
    });

    return total + filledOutCardRows.length * 8;
});