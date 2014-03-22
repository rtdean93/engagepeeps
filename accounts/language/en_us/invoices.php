<?php
/**
 * Language definitions for the Invoices model
 * 
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Recurring invoice pricing periods
$lang['Invoices.getPricingPeriods.day'] = "Day";
$lang['Invoices.getPricingPeriods.week'] = "Week";
$lang['Invoices.getPricingPeriods.month'] = "Month";
$lang['Invoices.getPricingPeriods.year'] = "Year";

// Invoice delivery methods
$lang['Invoices.getDeliveryMethods.email'] = "Email";
$lang['Invoices.getDeliveryMethods.paper'] = "Paper";
$lang['Invoices.getDeliveryMethods.interfax'] = "InterFax";
$lang['Invoices.getDeliveryMethods.postalmethods'] = "PostalMethods";

// Invoice line item descriptions
$lang['Invoices.!line_item.service_created_description'] = "%1\$s - %2\$s"; // %1$s is the name of the package, %2$s is the name of the service
$lang['Invoices.!line_item.service_renew_description'] = "%1\$s - %2\$s (%3\$s - %4\$s)"; // %1$s is the name of the package, %2$s is the name of the service, %3$s is the service's renew date, %4$s is the service's next renew date
$lang['Invoices.!line_item.service_cancel_fee_description'] = "%1\$s - %2\$s Cancelation Fee"; // %1$s is the name of the package, %2$s is the name of the service
$lang['Invoices.!line_item.service_setup_fee_description'] = "%1\$s - %2\$s Setup Fee"; // %1$s is the name of the package, %2$s is the name of the service
$lang['Invoices.!line_item.service_option_renew_description'] = "↳ %1\$s - %2\$s"; // %1$s is the package option label, %2$s is the service option name
$lang['Invoices.!line_item.service_option_setup_fee_description'] = "↳ %1\$s - %2\$s Setup Fee"; // %1$s is the package option label, %2$s is the service option name
$lang['Invoices.!line_item.coupon_line_item_description_amount'] = "Coupon %1\$s"; // %1$s is the coupon code
$lang['Invoices.!line_item.coupon_line_item_description_percent'] = "Coupon %1\$s - %2\$s%%"; // %1$s is the coupon code, %2$s is the coupon discount percentage, the two percent symbols (%%) must both be used together to display a single percent symbol
$lang['Invoices.!line_item.recurring_renew_description'] = "%1\$s (%2\$s - %3\$s)"; // %1$s is the line item description, %2$s is the invoice's renew date, %3$s is the invoice's next renew date


// Statuses
$lang['Invoices.status.active'] = "Active";
$lang['Invoices.status.draft'] = "Draft";
$lang['Invoices.status.void'] = "Void";


// Invoice Delivery errors
$lang['Invoices.!error.invoice_id.exists'] = "Invalid invoice ID.";
$lang['Invoices.!error.invoice_recur_id.exists'] = "Invalid recurring invoice ID.";
$lang['Invoices.!error.method.exists'] = "You must set at least one delivery method.";

$lang['Invoices.!error.delivery.empty'] = "Please enter an invoice delivery method.";
$lang['Invoices.!error.delivery.length'] = "The invoice delivery method length may not exceed 32 characters.";

// Invoice errors
$lang['Invoices.!error.id_format.empty'] = "No ID format set for invoices.";
$lang['Invoices.!error.id_format.length'] = "The ID format for invoices may not exceed 64 characters.";
$lang['Invoices.!error.id_value.valid'] = "Unable to determine invoice ID value.";
$lang['Invoices.!error.id.amount_applied'] = "This invoice may not be updated because an amount has already been applied to it.";
$lang['Invoices.!error.client_id.exists'] = "Invalid client ID.";
$lang['Invoices.!error.date_billed.format'] = "The billed date is in an invalid date format.";
$lang['Invoices.!error.date_due.format'] = "The due date is in an invalid date format.";
$lang['Invoices.!error.date_due.after_billed'] = "The date due must be on or after the date billed.";
$lang['Invoices.!error.date_closed.format'] = "The closed date is in an invalid date format.";
$lang['Invoices.!error.date_autodebit.format'] = "The due date is in an invalid date format.";
$lang['Invoices.!error.status.format'] = "Invalid status.";
$lang['Invoices.!error.currency.length'] = "The currency code must be 3 characters in length.";
$lang['Invoices.!error.delivery.exists'] = "The delivery method given does not exist.";
$lang['Invoices.!error.term.format'] = "The term should be a number.";
$lang['Invoices.!error.term.bounds'] = "The term should be between 1 and 65535.";
$lang['Invoices.!error.period.format'] = "The period is invalid.";
$lang['Invoices.!error.duration.format'] = "The duration is invalid.";
$lang['Invoices.!error.date_renews.format'] = "The recurring invoice renew date must be a valid date format.";
$lang['Invoices.!error.date_last_renewed.format'] = "The last recurring invoice renew date must be a valid date format.";
$lang['Invoices.!error.invoice_id.draft'] = "The given invoice is not a draft invoice, and therefore could not be deleted.";


// Invoice line errors
$lang['Invoices.!error.lines[][id].exists'] = "Invalid line item ID.";
$lang['Invoices.!error.lines[][service_id].exists'] = "Invalid service ID.";
$lang['Invoices.!error.lines[][description].empty'] = "Please enter a line item description.";
$lang['Invoices.!error.lines[][qty].format'] = "The quantity must be a number.";
$lang['Invoices.!error.lines[][qty].minimum'] = "Please enter a quantity of 1 or more.";
$lang['Invoices.!error.lines[][amount].format'] = "The unit cost must be a number.";
$lang['Invoices.!error.lines[][tax].format'] = "Line item tax must be a 'true' or 'false'";
?>