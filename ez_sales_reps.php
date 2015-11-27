<?php
/**
 * Plugin Name: E-Z Sales Representatives
 * Description: User admin page for managing E-Z Sales Representatives
 * Version: 1.0
 * Author: John Verity
 * Author URI: http://realjv3.com
 */

namespace ez_sales_reps;

require_once 'etc/settings.php';

//Load dependencies
new includes\dep_loader(file_get_contents(dirname(__FILE__).'/etc/deps.json'));

//Instantiate sales rep/businesses/trx data object
$ezsr_data = new includes\data();

//Load E-Z Sales Representatives page under wp-admin\Users
new includes\admin_page($ezsr_data);

//Register server-side ajax handlers
new includes\ajax();

?>