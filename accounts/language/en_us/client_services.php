<?php
/**
 * Language definitions for the Client Services controller/views
 * 
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Success messages
$lang['ClientServices.!success.manage.tab_updated'] = "The data was successfully updated.";
$lang['ClientServices.!success.service_canceled'] = "The service was successfully canceled.";
$lang['ClientServices.!success.service_schedule_canceled'] = "The service is scheduled to be canceled at the end of its term.";


// Index
$lang['ClientServices.index.page_title'] = "Client #%1\$s Services"; // %1$s is the client ID number

$lang['ClientServices.index.boxtitle_services'] = "Services";

$lang['ClientServices.index.category_active'] = "Active";
$lang['ClientServices.index.category_pending'] = "Pending";
$lang['ClientServices.index.category_suspended'] = "Suspended";
$lang['ClientServices.index.category_canceled'] = "Canceled";

$lang['ClientServices.index.heading_addons'] = "Add-ons";
$lang['ClientServices.index.heading_status'] = "Status";

$lang['ClientServices.index.heading_package'] = "Package";
$lang['ClientServices.index.heading_label'] = "Label";
$lang['ClientServices.index.heading_term'] = "Term";
$lang['ClientServices.index.heading_datecreated'] = "Date Created";
$lang['ClientServices.index.heading_daterenews'] = "Date Renews";
$lang['ClientServices.index.heading_datesuspended'] = "Date Suspended";
$lang['ClientServices.index.heading_datecanceled'] = "Date Canceled";
$lang['ClientServices.index.heading_options'] = "Options";
$lang['ClientServices.index.option_manage'] = "Manage";

$lang['ClientServices.index.text_never'] = "Never";

$lang['ClientServices.index.no_results'] = "You have no %1\$s Services."; // %1$s is the language for the services category type (e.g. Active, Pending)

$lang['ClientServices.serviceinfo.no_results'] = "This service has no details.";


// Manage
$lang['ClientServices.manage.page_title'] = "Client #%1\$s Manage Service"; // %1$s is the client ID number

$lang['ClientServices.manage.boxtitle_manage'] = "Manage %1\$s - %2\$s";
$lang['ClientServices.manage.tab_service_info'] = "Information";

$lang['ClientServices.manage.heading_service'] = "Service Information";
$lang['ClientServices.manage.text_quantity'] = "%1\$s @"; // %1$s is the quantity value
$lang['ClientServices.manage.text_package_name'] = "%1\$s - %2\$s"; // %1$s is the package name, %2$s is the service label (e.g. domain.com)
$lang['ClientServices.manage.text_price'] = "%1\$s %2\$s"; // %1$s is the package price, %2$s is the pricing term
$lang['ClientServices.manage.text_date_added'] = "Created on %1\$s"; // %1$s is the date the service was created
$lang['ClientServices.manage.text_date_renews'] = "Renews on %1\$s"; // %1$s is the date the service renews
$lang['ClientServices.manage.text_date_renews_never'] = "Never renews";
$lang['ClientServices.manage.text_date_suspended'] = "Suspended on %1\$s"; // %1$s is the date the service was suspended
$lang['ClientServices.manage.text_date_canceled'] = "Scheduled to be canceled on %1\$s"; // %1$s is the date the service is scheduled to be canceled

$lang['ClientServices.manage.cancel_service'] = "Cancel Service";


// Cancel
$lang['ClientServices.cancel.page_title'] = "Client #%1\$s Cancel Service"; // %1$s is the client ID number

$lang['ClientServices.cancel.boxtitle_manage'] = "Cancel %1\$s - %2\$s";
$lang['ClientServices.cancel.heading_cancel'] = "Cancel Service";

$lang['ClientServices.cancel.field_term_date'] = "At End of Term (%1\$s)"; // %1$s is the date the service's term ends
$lang['ClientServices.cancel.field_term'] = "At End of Term";
$lang['ClientServices.cancel.field_now'] = "Immediately";
$lang['ClientServices.cancel.field_cancel_submit'] = "Cancel Service";
$lang['ClientServices.cancel.confirm_cancel'] = "Are you sure you want to cancel this service at the end of its term?";
$lang['ClientServices.cancel.confirm_cancel_now'] = "Are you sure you want to cancel this service?";
$lang['ClientServices.cancel.confirm_cancel_now_fee'] = "Canceling this service immediately will incur a cancellation fee of %1\$s."; // %1$s is the formatted amount of the cancelation fee
$lang['ClientServices.cancel.confirm_cancel_now_fee_tax'] = "Canceling this service immediately will incur a cancellation fee of %1\$s plus tax."; // %1$s is the formatted amount of the cancelation fee
?>