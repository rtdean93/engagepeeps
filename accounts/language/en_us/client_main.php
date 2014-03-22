<?php
/**
 * Language definitions for the Client Main controller/views
 * 
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Success messages
$lang['ClientMain.!success.client_updated'] = "Your account information has been successfully updated.";
$lang['ClientMain.!success.invoice_method_updated'] = "Your invoice method has been successfully updated. All future invoices will be delivered to you via %1\$s."; // %1$s is the clients invoice method (e.g. Email, Paper, etc.)


// Info message
$lang['ClientMain.!info.invoice_due_title'] = "Welcome back, %1\$s!"; // %1$s is the client's first name
$lang['ClientMain.!info.invoice_due_button'] = "Make Payment";
$lang['ClientMain.!info.invoice_due_text'] = "Your account has an outstanding balance of %1\$s. Please make a payment at your earliest convenience."; // %1$s is the total amount due for this client
$lang['ClientMain.!info.invoice_due_other_currencies'] = "You have an outstanding balance in other currencies as well.";
$lang['ClientMain.!info.invoice_method_current'] = "You are currently receiving invoices by %1\$s. Your invoice method is the method that we deliver new invoices to you. The following options are available:"; // %1$s is the clients invoice method (e.g. Email, Paper, etc.)
$lang['ClientMain.!info.invoice_method_email'] = "If %1\$s is selected, we will automatically email your invoice to the address we have on file."; // %1$s is the language set as the invoice method for Email
$lang['ClientMain.!info.invoice_method_paper'] = "If %1\$s is selected, we will print and mail your invoice to the address we have on file."; // %1$s is the language set as the invoice method for Paper
$lang['ClientMain.!info.invoice_method_interfax'] = "If %1\$s is selected, we will automatically fax your invoice to the fax number we have on file. If selecting this option, please ensure that we have a valid fax number on file."; // %1$s is the language set as the invoice method for Interfax
$lang['ClientMain.!info.invoice_method_postalmethods'] = "If %1\$s is selected, we will automatically print and mail your invoice to the address we have on file."; // %1$s is the language set as the invoice method for PostalMethods


// Index
$lang['ClientMain.index.page_title'] = "Client %1\$s"; // %1$s is the client ID number


// Myinfo
$lang['ClientMain.myinfo.page_title'] = "Client %1\$s"; // %1$s is the client ID number

$lang['ClientMain.myinfo.boxtitle_client'] = "My Information";
$lang['ClientMain.myinfo.setting_invoices'] = "You are currently receiving invoices by %1\$s."; // %1$s is the clients invoice method (e.g. Email, Paper, etc.)
$lang['ClientMain.myinfo.setting_autodebit_disabled'] = "Your account is not set up for automatic payment.";
$lang['ClientMain.myinfo.setting_autodebit_enabled'] = "Your account is set up for automatic payment.";
$lang['ClientMain.myinfo.setting_autodebit_cc_zero_days'] = "We'll charge your %1\$s ending in x%2\$s on the day payment is due."; // %1$s is the type of credit card (e.g. "Visa"), %2$s is the last 4 of the credit card number
$lang['ClientMain.myinfo.setting_autodebit_ach_zero_days'] = "We'll charge your %1\$s Account ending in x%2\$s on the day payment is due."; // %1$s is the type of payment account to charge (e.g. "Checking"), %2$s is the last 4 of the account number
$lang['ClientMain.myinfo.setting_autodebit_cc_one_day'] = "We'll charge your %1\$s ending in x%2\$s the day before payment is due."; // %1$s is the type of credit card (e.g. "Visa"), %2$s is the last 4 of the credit card number
$lang['ClientMain.myinfo.setting_autodebit_ach_one_day'] = "We'll charge your %1\$s Account ending in x%2\$s the day before payment is due."; // %1$s is the type of payment account to charge (e.g. "Checking"), %2$s is the last 4 of the account number
$lang['ClientMain.myinfo.setting_autodebit_cc_days'] = "We'll charge your %1\$s ending in x%2\$s %3\$s days before payment is due."; // %1$s is the type of credit card (e.g. "Visa"), %2$s is the last 4 of the credit card number, %3$s is the plural number of days (two or more) before a payment is due when the client's payment account will be charged
$lang['ClientMain.myinfo.setting_autodebit_ach_days'] = "We'll charge your %1\$s Account ending in x%2\$s %3\$s days before payment is due."; // %1$s is the type of payment account to charge (e.g. "Checking"), %2$s is the last 4 of the account number, %3$s is the plural number of days (two or more) before a payment is due when the client's payment account will be charged


$lang['ClientMain.myinfo.setting_change'] = "Change this?";

$lang['ClientMain.myinfo.link_editclient'] = "Change";

$lang['ClientMain.myinfo.actions_title'] = "Actions";
$lang['ClientMain.myinfo.actionlink_makepayment'] = "Make Payment";
$lang['ClientMain.myinfo.actionlink_addaccount'] = "Add Payment Account";
$lang['ClientMain.myinfo.actionlink_addcontact'] = "Add Contact";
$lang['ClientMain.myinfo.actionlink_openticket'] = "Open Ticket";

$lang['ClientMain.myinfo.client_id'] = "Client ID: %1\$s"; // %1$s is the client ID


// Edit Client
$lang['ClientMain.edit.page_title'] = "Client %1\$s Edit My Information"; // %1$s is the client ID number
$lang['ClientMain.edit.boxtitle_edit'] = "Edit My Information";

$lang['ClientMain.edit.heading_billing'] = "Billing Information";
$lang['ClientMain.edit.field_taxid'] = "Tax ID/VATIN";
$lang['ClientMain.edit.field_default_currency'] = "Preferred Currency";
$lang['ClientMain.edit.field_invoiceaddress'] = "Address Invoices To";

$lang['ClientMain.edit.heading_settings'] = "Additional Settings";
$lang['ClientMain.edit.field_language'] = "Language";

$lang['ClientMain.edit.link_change_password'] = "Change Password?";
$lang['ClientMain.edit.heading_password'] = "Login Details";
$lang['ClientMain.edit.field_username'] = "Username";
$lang['ClientMain.edit.field_new_password'] = "New Password";
$lang['ClientMain.edit.field_confirm_password'] = "Confirm Password";
$lang['ClientMain.edit.field_current_password'] = "Current Password";

$lang['ClientMain.edit.field_editsubmit'] = "Update My Information";


// Invoice Method
$lang['ClientMain.invoicemethod.page_title'] = "Client %1\$s Invoice Method"; // %1$s is the client ID number

$lang['ClientMain.invoicemethod.boxtitle_inv_method'] = "Invoice Method";
$lang['Clientmain.invoicemethod.field_methodsubmit'] = "Update";


// Currency amounts
$lang['ClientMain.getcurrencyamounts.text_total_credits'] = "Total Credit";
?>