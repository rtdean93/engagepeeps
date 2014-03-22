<?php
/**
 * Language definitions for the Client Accounts controller/views
 * 
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Success messages
$lang['ClientAccounts.!success.defaultaccount_updated'] = "The default payment account has been updated.";
$lang['ClientAccounts.!success.defaultaccount_deleted'] = "The default payment account has been removed.";
$lang['ClientAccounts.!success.account_created'] = "The payment account has been successfully created.";
$lang['ClientAccounts.!success.ccaccount_deleted'] = "The payment account has been successfully deleted.";
$lang['ClientAccounts.!success.ccaccount_updated'] = "The payment account has been successfully updated.";
$lang['ClientAccounts.!success.achaccount_deleted'] = "The payment account has been successfully deleted.";
$lang['ClientAccounts.!success.achaccount_updated'] = "The payment account has been successfully updated.";


// Error messages
$lang['ClientAccounts.!error.account_invalid'] = "The payment account type provided is disabled or invalid.";
$lang['ClientAccounts.!error.payment_account_type_invalid'] = "Invalid payment account type.";


// Notice messages
$lang['ClientAccounts.!notice.disabled'] = "The ability to add payment accounts is currently disabled.";
$lang['ClientAccounts.!notice.ach_disabled'] = "ACH payment accounts are currently disabled and may not be updated.";
$lang['ClientAccounts.!notice.cc_disabled'] = "Credit card payment accounts are currently disabled and may not be updated.";
$lang['ClientAccounts.!notice.reenable_autodebit'] = "Automatic payments have been specifically disabled for this account, please contact a staff member to reactivate. You may select a payment account as your default, but it will not be charged automatically until it has been reactivated by staff.";


// Info messages
$lang['ClientAccounts.!info.account_info'] = "Payment accounts allow you to keep payment information on file and may be used to pay invoices. A payment account that is selected as the default account will be used for automatic recurring payments.";


// Index
$lang['ClientAccounts.index.page_title'] = "Client #%1\$s Accounts"; // %1$s is the client ID number

$lang['ClientAccounts.index.create_account'] = "Add Payment Account";

$lang['ClientAccounts.index.boxtitle_accounts'] = "Payment Accounts";
$lang['ClientAccounts.index.heading_name'] = "Name";
$lang['ClientAccounts.index.heading_type'] = "Type";
$lang['ClientAccounts.index.heading_last4'] = "Last 4";
$lang['ClientAccounts.index.heading_options'] = "Options";
$lang['ClientAccounts.index.option_edit'] = "Edit";
$lang['ClientAccounts.index.option_delete'] = "Delete";

$lang['ClientAccounts.index.type_cc'] = "%1\$s - %2\$s"; // %1$s is the account type (Credit Card) and %2$s is the type of account (MasterCard, Visa, etc.)
$lang['ClientAccounts.index.type_ach'] = "%1\$s - %2\$s"; // %1$s is the account type (ACH) and %2$s is the type of account (Checking or Savings)

$lang['ClientAccounts.index.confirm_delete'] = "Are you sure you want to delete this payment account?";

$lang['ClientAccounts.index.field_accountsubmit'] = "Set as Default Payment Account";
$lang['ClientAccounts.index.no_results'] = "You have no payment accounts.";


// Add
$lang['ClientAccounts.add.page_title'] = "Client #%1\$s Add Payment Account"; // %1$s is the client ID number
$lang['ClientAccounts.add.heading_account_type'] = "Account Type";
$lang['ClientAccounts.add.field_cc_type'] = "Credit Card";
$lang['ClientAccounts.add.field_ach_type'] = "ACH";
$lang['ClientAccounts.add.boxtitle_create'] = "Add Payment Account";
$lang['ClientAccounts.add.field_nextsubmit'] = "Continue";
$lang['ClientAccounts.add.field_accountsubmit'] = "Create Account";


// Edit ACH
$lang['ClientAccounts.editach.page_title'] = "Client #%1\$s Edit ACH Account"; // %1$s is the client ID number
$lang['ClientAccounts.editach.boxtitle_editach'] = "Edit ACH Account";
$lang['ClientAccounts.editach.field_accountsubmit'] = "Edit Account";


// Edit CC
$lang['ClientAccounts.editcc.page_title'] = "Client #%1\$s Edit Credit Card Account"; // %1$s is the client ID number
$lang['ClientAccounts.editcc.boxtitle_editcc'] = "Edit Credit Card Account";
$lang['ClientAccounts.editcc.field_accountsubmit'] = "Edit Account";


// Set Contact view
$lang['ClientAccounts.setcontactview.text_none'] = "None";


// Account CC info
$lang['ClientAccounts.cc_info.heading_cc'] = "Credit Card Information";
$lang['ClientAccounts.cc_info.field_number'] = "Number";
$lang['ClientAccounts.cc_info.field_security'] = "Security Code";
$lang['ClientAccounts.cc_info.field_expiration'] = "Expiration Date";
$lang['ClientAccounts.cc_info.field_savedetails'] = "Save Account";
$lang['ClientAccounts.cc_info.tooltip_code'] = "The 3 or 4 digit security code, usually found on the back of the card.";


// Account ACH info
$lang['ClientAccounts.ach_info.heading_ach'] = "Bank Account Information";
$lang['ClientAccounts.ach_info.field_type'] = "Type";
$lang['ClientAccounts.ach_info.field_accountnum'] = "Account Number";
$lang['ClientAccounts.ach_info.field_routingnum'] = "Routing Number";
$lang['ClientAccounts.ach_info.field_savedetails'] = "Save Account";
?>