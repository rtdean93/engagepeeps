<?php
/**
 * Language definitions for the Admin Clients controller/views
 * 
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Success messages
$lang['AdminClients.!success.note_added'] = "The note has been successfully created.";
$lang['AdminClients.!success.note_updated'] = "The note has been successfully updated.";
$lang['AdminClients.!success.note_deleted'] = "The note has been successfully deleted.";

$lang['AdminClients.!success.client_added'] = "The client has been successfully created.";
$lang['AdminClients.!success.client_updated'] = "The client has been successfully updated.";
$lang['AdminClients.!success.client_deleted'] = "The client has been successfully deleted.";

$lang['AdminClients.!success.contact_added'] = "The contact has been successfully created.";
$lang['AdminClients.!success.contact_updated'] = "The contact has been successfully updated.";

$lang['AdminClients.!success.invoice_added'] = "Invoice #%1\$s was successfully created."; // %1$s is the invoice number
$lang['AdminClients.!success.invoice_updated'] = "Invoice #%1\$s was successfully updated."; // %1$s is the invoice number
$lang['AdminClients.!success.draftinvoice_added'] = "Draft Invoice #%1\$s was successfully created."; // %1$s is the invoice number
$lang['AdminClients.!success.draftinvoice_updated'] = "Draft Invoice #%1\$s was successfully updated."; // %1$s is the draft invoice number
$lang['AdminClients.!success.draftinvoice_created'] = "Draft Invoice #%1\$s was successfully created as Invoice #%2\$s."; // %1$s is the draft invoice number, %2$s is the invoice number

$lang['AdminClients.!success.addccaccount_added'] = "The credit card account has been created successfully.";
$lang['AdminClients.!success.editccaccount_updated'] = "The credit card account has been edited successfully.";

$lang['AdminClients.!success.addachaccount_added'] = "The ACH account has been created successfully.";
$lang['AdminClients.!success.editachaccount_updated'] = "The ACH account has been edited successfully.";

$lang['AdminClients.!success.deleteccaccount_deleted'] = "The payment account has been successfully removed.";
$lang['AdminClients.!success.deleteachaccount_deleted'] = "The payment account has been successfully removed.";

$lang['AdminClients.!success.accounts_updated'] = "The payment account to use for auto-debiting has been updated.";
$lang['AdminClients.!success.accounts_deleted'] = "The payment account to use for auto-debiting has been removed.";

$lang['AdminClients.!success.recurinvoice_deleted'] = "The recurring invoice has been successfully removed.";
$lang['AdminClients.!success.recurinvoice_updated'] = "The recurring invoice has been successfully updated.";

$lang['AdminClients.!success.edittransaction_updated'] = "The transaction was successfully updated.";

$lang['AdminClients.!success.transaction_unapplied'] = "The transaction was successfully unapplied from the invoice.";

$lang['AdminClients.!success.invoices_queued'] = "The selected invoices were successfully queued for delivery.";
$lang['AdminClients.!success.invoices_delivered'] = "The selected invoices were successfully delivered.";

$lang['AdminClients.!success.makepayment_processed'] = "The payment was successfully processed for %1\$s. Transaction Number: %2\$s"; // %1$s is the payment amount, %2$s is the transaction number
$lang['AdminClients.!success.recordpayment_processed'] = "The payment was successfully recorded for %1\$s."; // %1$s is the payment amount

$lang['AdminClients.!success.deletedraftinvoice_deleted'] = "The draft invoice was successfully deleted!";

$lang['AdminClients.!success.email_sent'] = "The email was successfully sent.";

$lang['AdminClients.!success.contact_deleted'] = "The contact %1\$s %2\$s was successfully deleted!"; // %1$s is the contact's first name, %2$s is the contact's last name

$lang['AdminClients.!success.service_added'] = "The service was successfully added.";
$lang['AdminClients.!success.service_edited'] = "The service was successfully updated.";
$lang['AdminClients.!success.service_deleted'] = "The service was successfully deleted.";

$lang['AdminClients.!success.packages_restricted'] = "Restricted package access has been updated for this client.";

$lang['AdminClients.!success.service_tab'] = "The data was successfully updated.";


// Error messages
$lang['AdminClients.!error.invoices_emailinvalid'] = "The email address is invalid.";
$lang['AdminClients.!error.pay_with.required'] = "You must select a payment method.";

// Notice messages
$lang['AdminClients.!notice.invoice_tax_rules_differ'] = "WARNING: This invoice contains tax rules that are no longer in effect. Adding or removing a line item or updating the quantity, unit cost, or tax status will apply the most recent tax rules to this invoice.";
$lang['AdminClients.!notice.payment_type'] = "WARNING: %1\$s payment types are currently not enabled. To enable them, update the <a href=\"%2\$s\">%3\$s</a>."; // %1$s is the account type (CC, ACH) language, %2$s is the URI to the accepted type settings, %3$s is the language to use as text for the link
$lang['AdminClients.!notice.transactions_already_applied'] = "WARNING: Modifying this transaction's status will remove this transaction from all currently applied invoices.";


// Index
$lang['AdminClients.index.page_title'] = "Browse Clients";
$lang['AdminClients.index.boxtitle_browseclients'] = "Browse Clients";

$lang['AdminClients.index.heading_client'] = "Client ID";
$lang['AdminClients.index.heading_group'] = "Group";
$lang['AdminClients.index.heading_name'] = "Name";
$lang['AdminClients.index.heading_company'] = "Company";
$lang['AdminClients.index.heading_email'] = "Email";

$lang['AdminClients.index.category_active'] = "Active";
$lang['AdminClients.index.category_inactive'] = "Inactive";
$lang['AdminClients.index.category_fraud'] = "Fraud";

$lang['AdminClients.index.categorylink_clientsadd'] = "Add Client";

$lang['AdminClients.index.no_results'] = "There are no clients with this status.";


// Add
$lang['AdminClients.add.page_title'] = "Clients Create New Client";
$lang['AdminClients.add.boxtitle_newclient'] = "New Client";

$lang['AdminClients.add.heading_contact'] = "Contact Information";
$lang['AdminClients.add.heading_billing'] = "Billing Information";
$lang['AdminClients.add.heading_authentication'] = "Authentication";
$lang['AdminClients.add.heading_settings'] = "Additional Settings";

$lang['AdminClients.add.field_firstname'] = "First Name";
$lang['AdminClients.add.field_lastname'] = "Last Name";
$lang['AdminClients.add.field_company'] = "Company/Org.";
$lang['AdminClients.add.field_address1'] = "Address 1";
$lang['AdminClients.add.field_address2'] = "Address 2";
$lang['AdminClients.add.field_city'] = "City";
$lang['AdminClients.add.field_state'] = "State/Province";
$lang['AdminClients.add.field_zip'] = "Zip/Postal Code";
$lang['AdminClients.add.field_country'] = "Country";
$lang['AdminClients.add.field_email'] = "Email";

$lang['AdminClients.add.field_username_type_email'] = "Use email as username";
$lang['AdminClients.add.field_username_type_username'] = "Specify a username";
$lang['AdminClients.add.field_username'] = "Username";
$lang['AdminClients.add.field_newpassword'] = "Password";
$lang['AdminClients.add.text_generate_password'] = "Generate Password";

$lang['AdminClients.add.field_taxexempt'] = "Tax Exempt";
$lang['AdminClients.add.field_taxid'] = "Tax ID/VATIN";
$lang['AdminClients.add.field_preferredcurrency'] = "Preferred Currency";

$lang['AdminClients.add.field_language'] = "Language";
$lang['AdminClients.add.field_clientgroup'] = "Client Group";
$lang['AdminClients.add.field_send_registration_email'] = "Send Account Registration Email";

$lang['AdminClients.add.field_clientsubmit'] = "Create Client";


// Accounts
$lang['AdminClients.accounts.page_title'] = "Client #%1\$s Payment Accounts"; // %1$s is the client ID number
$lang['AdminClients.accounts.boxtitle_accounts'] = "Payment Accounts";

$lang['AdminClients.accounts.categorylink_ach'] = "Add ACH Account";
$lang['AdminClients.accounts.categorylink_cc'] = "Add CC Account";

$lang['AdminClients.accounts.text_name'] = "Name";
$lang['AdminClients.accounts.text_type'] = "Type";
$lang['AdminClients.accounts.text_last4'] = "Last 4";
$lang['AdminClients.accounts.text_options'] = "Options";

$lang['AdminClients.accounts.confirm_delete'] = "Really delete this payment account?";

$lang['AdminClients.accounts.option_edit'] = "Edit";
$lang['AdminClients.accounts.option_delete'] = "Delete";

$lang['AdminClients.accounts.type_cc'] = "%1\$s - %2\$s"; // %1$s is the account type (Credit Card) and %2$s is the type of account (MasterCard, Visa, etc.)
$lang['AdminClients.accounts.type_ach'] = "%1\$s - %2\$s"; // %1$s is the account type (ACH) and %2$s is the type of account (Checking or Savings)

$lang['AdminClients.accounts.field_accountsubmit'] = "Use for Auto Debit";

$lang['AdminClients.accounts.no_results'] = "There are no ACH or CC accounts.";


// Add Credit Card Account
$lang['AdminClients.addccaccount.page_title'] = "Client #%1\$s Add Credit Card Account"; // %1$s is the client ID number
$lang['AdminClients.addccaccount.boxtitle_addccaccount'] = "Add Credit Card Account";
$lang['AdminClients.addccaccount.field_accountsubmit'] = "Create Account";
$lang['AdminClients.addccaccount.text_none'] = "None";

$lang['AdminClients.addCcAccount.text_cc'] = "Credit Card";


// Add ACH account
$lang['AdminClients.addachaccount.page_title'] = "Client #%1\$s Add ACH Account"; // %1$s is the client ID number
$lang['AdminClients.addachaccount.boxtitle_addachaccount'] = "Add ACH Account";
$lang['AdminClients.addachaccount.field_accountsubmit'] = "Create Account";

$lang['AdminClients.addAchAccount.text_ach'] = "ACH";


// Edit CC account
$lang['AdminClients.editccaccount.page_title'] = "Client #%1\$s Edit Credit Card Account"; // %1$s is the client ID number
$lang['AdminClients.editccaccount.boxtitle_editccaccount'] = "Edit Credit Card Account";
$lang['AdminClients.editccaccount.field_accountsubmit'] = "Edit Account";


// Edit ACH account
$lang['AdminClients.editachaccount.page_title'] = "Client #%1\$s Edit ACH Account"; // %1$s is the client ID number
$lang['AdminClients.editachaccount.boxtitle_editachaccount'] = "Edit ACH Account";
$lang['AdminClients.editachaccount.field_accountsubmit'] = "Edit Account";


// Add Contact
$lang['AdminClients.addcontact.page_title'] = "Client #%1\$s New Contact"; // %1$s is the client ID number
$lang['AdminClients.addcontact.boxtitle_newcontact'] = "New Contact";

$lang['AdminClients.addcontact.heading_contact'] = "Contact Information";
$lang['AdminClients.addcontact.heading_settings'] = "Additional Settings";

$lang['AdminClients.addcontact.field_firstname'] = "First Name";
$lang['AdminClients.addcontact.field_lastname'] = "Last Name";
$lang['AdminClients.addcontact.field_company'] = "Company/Org.";
$lang['AdminClients.addcontact.field_title'] = "Title";
$lang['AdminClients.addcontact.field_address1'] = "Address 1";
$lang['AdminClients.addcontact.field_address2'] = "Address 2";
$lang['AdminClients.addcontact.field_city'] = "City";
$lang['AdminClients.addcontact.field_state'] = "State/Province";
$lang['AdminClients.addcontact.field_zip'] = "Zip/Postal Code";
$lang['AdminClients.addcontact.field_country'] = "Country";
$lang['AdminClients.addcontact.field_email'] = "Email";

$lang['AdminClients.addcontact.field_accounttype'] = "Account Type";

$lang['AdminClients.addcontact.field_contactsubmit'] = "Create Contact";


// Create Invoice
$lang['AdminClients.createinvoice.page_title'] = "Client #%1\$s Create Invoice"; // %1$s is the client ID number
$lang['AdminClients.createinvoice.boxtitle_createinvoice'] = "Create Invoice";

$lang['AdminClients.createinvoice.heading_description'] = "Description";
$lang['AdminClients.createinvoice.heading_quantity'] = "Quantity";
$lang['AdminClients.createinvoice.heading_unitcost'] = "Unit Cost";
$lang['AdminClients.createinvoice.heading_tax'] = "Tax";
$lang['AdminClients.createinvoice.heading_options'] = "Options";

$lang['AdminClients.createinvoice.heading_term'] = "Term";
$lang['AdminClients.createinvoice.heading_period'] = "Period";
$lang['AdminClients.createinvoice.heading_duration'] = "Duration";
$lang['AdminClients.createinvoice.heading_nextbilldate'] = "Next Renew Date";

$lang['AdminClients.createinvoice.option_add'] = "Add";
$lang['AdminClients.createinvoice.option_delete'] = "Delete";

$lang['AdminClients.createinvoice.field_datebilled'] = "Date Billed:";
$lang['AdminClients.createinvoice.field_datedue'] = "Date Due:";
$lang['AdminClients.createinvoice.field_invoicedelivery'] = "Invoice Delivery:";

$lang['AdminClients.createinvoice.field_currency'] = "Currency";

$lang['AdminClients.createinvoice.field_duration_indefinitely'] = "Indefinitely";
$lang['AdminClients.createinvoice.field_duration_times'] = "number of times";
$lang['AdminClients.createinvoice.field_notepublic'] = "Public Note:";
$lang['AdminClients.createinvoice.field_noteprivate'] = "Private Note:";

$lang['AdminClients.createinvoice.field_invoicesubmit'] = "Create";
$lang['AdminClients.createinvoice.field_invoicedraft'] = "Save as Draft";

$lang['AdminClients.createinvoice.section_recurringinvoice'] = "Recurring Invoice";
$lang['AdminClients.createinvoice.section_notes'] = "Notes";

$lang['AdminClients.createinvoice.price_subtotal'] = "Sub Total:";

$lang['AdminClients.createinvoice.auto_save_saving'] = "Saving as draft...";
$lang['AdminClients.createinvoice.auto_save_saved'] = "Draft saved";
$lang['AdminClients.createinvoice.auto_save_error'] = "The draft could not be auto-saved";


// Edit
$lang['AdminClients.edit.page_title'] = "Client #%1\$s Modify Client"; // %1$s is the client ID number
$lang['AdminClients.edit.boxtitle_editclient'] = "Modify Client";

$lang['AdminClients.edit.heading_contact'] = "Contact Information";
$lang['AdminClients.edit.heading_billing'] = "Billing Information";
$lang['AdminClients.edit.heading_authentication'] = "Authentication";
$lang['AdminClients.edit.heading_settings'] = "Additional Settings";

$lang['AdminClients.edit.field_firstname'] = "First Name";
$lang['AdminClients.edit.field_lastname'] = "Last Name";
$lang['AdminClients.edit.field_company'] = "Company/Org.";
$lang['AdminClients.edit.field_address1'] = "Address 1";
$lang['AdminClients.edit.field_address2'] = "Address 2";
$lang['AdminClients.edit.field_city'] = "City";
$lang['AdminClients.edit.field_state'] = "State/Province";
$lang['AdminClients.edit.field_zip'] = "Zip/Postal Code";
$lang['AdminClients.edit.field_country'] = "Country";
$lang['AdminClients.edit.field_email'] = "Email";

$lang['AdminClients.edit.field_username_type_email'] = "Use email as username";
$lang['AdminClients.edit.field_username_type_username'] = "Specify a username";
$lang['AdminClients.edit.field_username'] = "Username";
$lang['AdminClients.edit.field_newpassword'] = "Password";

$lang['AdminClients.edit.field_taxexempt'] = "Tax Exempt";
$lang['AdminClients.edit.field_taxid'] = "Tax ID/VATIN";
$lang['AdminClients.edit.field_preferredcurrency'] = "Preferred Currency";
$lang['AdminClients.edit.field_invoiceaddress'] = "Address Invoices To";

$lang['AdminClients.edit.field_language'] = "Language";
$lang['AdminClients.edit.field_clientgroup'] = "Client Group";

$lang['AdminClients.edit.field_clientsubmit'] = "Modify Client";


// Account ACH Info
$lang['AdminClients.accountachinfo.heading_account'] = "Bank Account Information";
$lang['AdminClients.accountachinfo.field_type'] = "Type";
$lang['AdminClients.accountachinfo.field_accountnum'] = "Account Number";
$lang['AdminClients.accountachinfo.field_routingnum'] = "Routing Number";
$lang['AdminClients.accountachinfo.field_savedetails'] = "Save Account";

$lang['AdminClients.accountachinfo.text_showaccount'] = "Show Account";


// Account CC Info
$lang['AdminClients.accountccinfo.heading_cc'] = "Credit Card Information";
$lang['AdminClients.accountccinfo.field_number'] = "Number";
$lang['AdminClients.accountccinfo.field_security'] = "Security Code";
$lang['AdminClients.accountccinfo.field_expiration'] = "Expiration Date";
$lang['AdminClients.accountccinfo.field_savedetails'] = "Save Account";

$lang['AdminClients.accountccinfo.text_showcard'] = "Show Card";
$lang['AdminClients.accountccinfo.tooltip_code'] = "The 3 or 4 digit security code, usually found on the back of the card.";


// Account Contact Info
$lang['AdminClients.accountcontactinfo.heading_contact'] = "Contact Information";

$lang['AdminClients.accountcontactinfo.field_contact_id'] = "Copy Contact Information From";
$lang['AdminClients.accountcontactinfo.field_first_name'] = "First Name";
$lang['AdminClients.accountcontactinfo.field_last_name'] = "Last Name";
$lang['AdminClients.accountcontactinfo.field_address1'] = "Address 1";
$lang['AdminClients.accountcontactinfo.field_address2'] = "Address 2";
$lang['AdminClients.accountcontactinfo.field_city'] = "City";
$lang['AdminClients.accountcontactinfo.field_country'] = "Country";
$lang['AdminClients.accountcontactinfo.field_state'] = "State";
$lang['AdminClients.accountcontactinfo.field_zip'] = "Zip/Postal Code";


// Phones partial
$lang['AdminClients.phones.categorylink_number'] = "Add Additional Number";
$lang['AdminClients.phones.rowheading_number'] = "Phone Numbers";
$lang['AdminClients.phones.text_remove'] = "Remove";

$lang['AdminClients.phones.field_phonetype'] = "Type";
$lang['AdminClients.phones.field_phonelocation'] = "Location";
$lang['AdminClients.phones.field_phonenumber'] = "Number";


// Edit Contact
$lang['AdminClients.editcontact.page_title'] = "Client #%1\$s Modify Contact"; // %1$s is the client ID number
$lang['AdminClients.editcontact.boxtitle_editcontact'] = "Modify Contact";

$lang['AdminClients.editcontact.heading_contact'] = "Contact Information";
$lang['AdminClients.editcontact.heading_settings'] = "Additional Settings";

$lang['AdminClients.editcontact.field_firstname'] = "First Name";
$lang['AdminClients.editcontact.field_lastname'] = "Last Name";
$lang['AdminClients.editcontact.field_company'] = "Company/Org.";
$lang['AdminClients.editcontact.field_address1'] = "Address 1";
$lang['AdminClients.editcontact.field_address2'] = "Address 2";
$lang['AdminClients.editcontact.field_city'] = "City";
$lang['AdminClients.editcontact.field_state'] = "State/Province";
$lang['AdminClients.editcontact.field_zip'] = "Zip/Postal Code";
$lang['AdminClients.editcontact.field_country'] = "Country";
$lang['AdminClients.editcontact.field_email'] = "Email";

$lang['AdminClients.editcontact.field_contacttype'] = "Contact Type";

$lang['AdminClients.editcontact.field_contactsubmit'] = "Modify Contact";
$lang['AdminClients.editcontact.field_deletecontact'] = "Delete Contact";

$lang['AdminClients.editcontact.confirm_delete'] = "Are you sure you want to delete this contact?";


// Edit Invoice
$lang['AdminClients.editinvoice.page_title'] = "Client #%1\$s Edit Invoice #%2\$s"; // %1$s is the client ID number, %2$s is the invoice number
$lang['AdminClients.editinvoice.boxtitle_editinvoice'] = "Edit Invoice [%1\$s]"; // %1$s is the invoice number
$lang['AdminClients.editinvoice.boxtitle_editdraft'] = "Edit Invoice Draft [%1\$s]"; // %1$s is the invoice number

$lang['AdminClients.editinvoice.heading_description'] = "Description";
$lang['AdminClients.editinvoice.heading_quantity'] = "Quantity";
$lang['AdminClients.editinvoice.heading_unitcost'] = "Unit Cost";
$lang['AdminClients.editinvoice.heading_tax'] = "Tax";
$lang['AdminClients.editinvoice.heading_options'] = "Options";

$lang['AdminClients.editinvoice.heading_term'] = "Term";
$lang['AdminClients.editinvoice.heading_period'] = "Period";
$lang['AdminClients.editinvoice.heading_duration'] = "Duration";
$lang['AdminClients.editinvoice.heading_nextbilldate'] = "Next Renew Date";

$lang['AdminClients.editinvoice.option_add'] = "Add";
$lang['AdminClients.editinvoice.option_delete'] = "Delete";

$lang['AdminClients.editinvoice.field_datebilled'] = "Date Billed:";
$lang['AdminClients.editinvoice.field_datedue'] = "Date Due:";
$lang['AdminClients.editinvoice.field_invoicedelivery'] = "Invoice Delivery:";

$lang['AdminClients.editinvoice.field_currency'] = "Currency";

$lang['AdminClients.editinvoice.field_duration_indefinitely'] = "Indefinitely";
$lang['AdminClients.editinvoice.field_duration_times'] = "number of times";
$lang['AdminClients.editinvoice.field_notepublic'] = "Public Note:";
$lang['AdminClients.editinvoice.field_noteprivate'] = "Private Note:";

$lang['AdminClients.editinvoice.field_invoicesubmit'] = "Modify Invoice";
$lang['AdminClients.editinvoice.field_invoicedvoid'] = "Void Invoice";
$lang['AdminClients.editinvoice.field_invoicecreate'] = "Create";
$lang['AdminClients.editinvoice.field_invoicesavedraft'] = "Save Draft";
$lang['AdminClients.editinvoice.field_invoiceunvoid'] = "Unvoid and Modify";
$lang['AdminClients.editinvoice.field_invoicedelete'] = "Delete Invoice";

$lang['AdminClients.editinvoice.section_recurringinvoice'] = "Recurring Invoice";
$lang['AdminClients.editinvoice.section_notes'] = "Notes";

$lang['AdminClients.editinvoice.price_subtotal'] = "Sub Total:";

$lang['AdminClients.editinvoice.auto_save_saving'] = "Saving draft...";
$lang['AdminClients.editinvoice.auto_save_saved'] = "Draft saved";
$lang['AdminClients.editinvoice.auto_save_error'] = "The draft could not be auto-saved";


// Edit Recurring Invoice
$lang['AdminClients.editrecurinvoice.page_title'] = "Client #%1\$s Edit Recurring Invoice #%2\$s"; // %1$s is the client ID number, %2$s is the recurring invoice number
$lang['AdminClients.editrecurinvoice.boxtitle_editinvoice'] = "Edit Recurring Invoice [%1\$s]"; // %1$s is the recurring invoice number
$lang['AdminClients.editinvoice.confirm_deleterecur'] = "This will permanently remove this recurring invoice. No further invoices will be generated using this recurring invoice. Are you sure you want to delete it?";


// Edit Transaction
$lang['AdminClients.edittransaction.page_title'] = "Client #%1\$s Edit Transaction"; // %1$s is the client ID number
$lang['AdminClients.edittransaction.boxtitle_edittransaction'] = "Edit Transaction";
$lang['AdminClients.edittransaction.heading_type'] = "Type";
$lang['AdminClients.edittransaction.heading_amount'] = "Amount";
$lang['AdminClients.edittransaction.heading_credited'] = "Credited";
$lang['AdminClients.edittransaction.heading_applied'] = "Applied";
$lang['AdminClients.edittransaction.heading_number'] = "Number";
$lang['AdminClients.edittransaction.heading_status'] = "Status";
$lang['AdminClients.edittransaction.heading_date'] = "Date";
$lang['AdminClients.edittransaction.subheading_invoice'] = "Invoice #";
$lang['AdminClients.edittransaction.subheading_amount'] = "Amount";
$lang['AdminClients.edittransaction.subheading_appliedon'] = "Applied On";
$lang['AdminClients.edittransaction.subheading_options'] = "Options";
$lang['AdminClients.edittransaction.field_status'] = "Status";
$lang['AdminClients.edittransaction.field_processremotely'] = "Process this status change with the payment gateway (%1\$s)"; // %1$s is the name of the remote gateway
$lang['AdminClients.edittransaction.field_submit'] = "Modify Transaction";
$lang['AdminClients.edittransaction.applied_no_results'] = "This transaction has not been applied to any invoices.";
$lang['AdminClients.edittransaction.option_unapply'] = "Unapply";
$lang['AdminClients.edittransaction.confirm_unapply'] = "Really unapply this transaction from the selected invoice?";


// Email
$lang['AdminClients.email.page_title'] = "Client #%1\$s Email Client"; // %1$s is the client ID number
$lang['AdminClients.email.boxtitle_emailclient'] = "Email Client";

$lang['AdminClients.email.field_recipient'] = "Recipient";
$lang['AdminClients.email.field_fromname'] = "From Name";
$lang['AdminClients.email.field_from'] = "From Email";
$lang['AdminClients.email.field_subject'] = "Subject";
$lang['AdminClients.email.field_message'] = "Text";
$lang['AdminClients.email.field_messagehtml'] = "HTML";

$lang['AdminClients.email.field_emailsubmit'] = "Send Email";


// Emails (mail log)
$lang['AdminClients.emails.page_title'] = "Client #%1\$s Mail Log"; // %1$s is the client ID number
$lang['AdminClients.emails.boxtitle_maillog'] = "Mail Log";

$lang['AdminClients.emails.heading_date'] = "Date";
$lang['AdminClients.emails.heading_subject'] = "Subject";
$lang['AdminClients.emails.heading_summary'] = "Summary";

$lang['AdminClients.emails.text_to'] = "To";
$lang['AdminClients.emails.text_cc'] = "CC";
$lang['AdminClients.emails.text_from'] = "From";
$lang['AdminClients.emails.text_resend'] = "Resend";

$lang['AdminClients.emails.no_results'] = "There are no emails.";


// Invoices
$lang['AdminClients.invoices.page_title'] = "Client #%1\$s Invoices"; // %1$s is the client ID number
$lang['AdminClients.invoices.boxtitle_invoices'] = "Invoices";

$lang['AdminClients.invoices.heading_invoice'] = "Invoice #";
$lang['AdminClients.invoices.heading_recurinvoice'] = "Recurring #";
$lang['AdminClients.invoices.heading_amount'] = "Amount";
$lang['AdminClients.invoices.heading_paid'] = "Paid";
$lang['AdminClients.invoices.heading_due'] = "Due";
$lang['AdminClients.invoices.heading_dateclosed'] = "Date Closed";
$lang['AdminClients.invoices.heading_datebilled'] = "Date Billed";
$lang['AdminClients.invoices.heading_datedue'] = "Date Due";
$lang['AdminClients.invoices.heading_status'] = "Status";
$lang['AdminClients.invoices.heading_options'] = "Options";
$lang['AdminClients.invoices.heading_term'] = "Term";
$lang['AdminClients.invoices.heading_duration'] = "Duration";
$lang['AdminClients.invoices.heading_count'] = "Count";

$lang['AdminClients.invoices.category_open'] = "Open";
$lang['AdminClients.invoices.category_drafts'] = "Drafts";
$lang['AdminClients.invoices.category_closed'] = "Closed";
$lang['AdminClients.invoices.category_voided'] = "Voided";
$lang['AdminClients.invoices.category_recurring'] = "Recurring";
$lang['AdminClients.invoices.category_pending'] = "Pending";

$lang['AdminClients.invoices.categorylink_createinvoice'] = "Create Invoice";

$lang['AdminClients.invoices.headingexpand_paymenttype'] = "Payment Type";
$lang['AdminClients.invoices.headingexpand_amount'] = "Amount";
$lang['AdminClients.invoices.headingexpand_applied'] = "Applied";
$lang['AdminClients.invoices.headingexpand_appliedon'] = "Applied On";
$lang['AdminClients.invoices.headingexpand_options'] = "Options";

$lang['AdminClients.invoices.status_sent'] = "Sent";
$lang['AdminClients.invoices.status_unsent'] = "Unsent";

$lang['AdminClients.invoices.option_edit'] = "Edit";
$lang['AdminClients.invoices.option_view'] = "View";
$lang['AdminClients.invoices.option_pay'] = "Pay";
$lang['AdminClients.invoices.option_delete'] = "Delete";

$lang['AdminClients.invoices.confirm_delete'] = "Are you sure you want to delete this draft invoice?";

$lang['AdminClients.invoices.optionexpand_edit'] = "Edit";

$lang['AdminClients.invoices.method_deliverselected'] = "Deliver via %1\$s"; // %1$s is the invoice delivery method (e.g. Email, PostalMethods)

$lang['AdminClients.invoices.field_invoicesubmit'] = "Submit";
$lang['AdminClients.invoices.no_results'] = "There are no invoices with this status.";
$lang['AdminClients.invoices.applied_no_results'] = "This invoice has no transactions applied to it.";

$lang['AdminClients.invoices.subtotal_w_tax'] = "%1\$s +tax"; // %1$s is the sub total amount
$lang['AdminClients.invoices.term_day'] = "%1\$s day"; // %1$s is the term (an integer)
$lang['AdminClients.invoices.term_week'] = "%1\$s week"; // %1$s is the term (an integer)
$lang['AdminClients.invoices.term_month'] = "%1\$s month"; // %1$s is the term (an integer)
$lang['AdminClients.invoices.term_year'] = "%1\$s year"; // %1$s is the term (an integer)
$lang['AdminClients.invoices.term_day_plural'] = "%1\$s days"; // %1$s is the term (an integer)
$lang['AdminClients.invoices.term_week_plural'] = "%1\$s weeks"; // %1$s is the term (an integer)
$lang['AdminClients.invoices.term_month_plural'] = "%1\$s months"; // %1$s is the term (an integer)
$lang['AdminClients.invoices.term_year_plural'] = "%1\$s years"; // %1$s is the term (an integer)

$lang['AdmineClients.invoices.duration_finite'] = "%1\$s times"; // %1$s is the number of times the recurring invoice will be created
$lang['AdmineClients.invoices.duration_infinite'] = "∞";


// Merge
$lang['AdminClients.merge.page_title'] = "Client #%1\$s Merge Clients"; // %1$s is the client ID number
$lang['AdminClients.merge.boxtitle_merge'] = "Merge Clients";

$lang['AdminClients.merge.field_clientid'] = "Client ID to Merge";
$lang['AdminClients.merge.field_mergefrom'] = "Merge Client From";
$lang['AdminClients.merge.field_btoa'] = "B to A";
$lang['AdminClients.merge.field_atob'] = "A to B";
$lang['AdminClients.merge.field_password'] = "Admin Password";

$lang['AdminClients.merge.field_mergesubmit'] = "Merge Clients";


// Sticky Notes
$lang['AdminClients.stickynotes.date_separator'] = ":";
$lang['AdminClients.stickynotes.text_unstick'] = "Unstick";
$lang['AdminClients.stickynotes.text_more'] = "Show More";
$lang['AdminClients.stickynotes.text_less'] = "Show Less";


// Notes
$lang['AdminClients.notes.page_title'] = "Client #%1\$s Notes"; // %1$s is the client ID number
$lang['AdminClients.notes.boxtitle_notes'] = "Notes";

$lang['AdminClients.notes.heading_title'] = "Summary";
$lang['AdminClients.notes.heading_dateupdated'] = "Date Updated";
$lang['AdminClients.notes.heading_options'] = "Options";

$lang['AdminClients.notes.heading_staff'] = "Created by";
$lang['AdminClients.notes.heading_dateadded'] = "Date Added";
$lang['AdminClients.notes.by_system'] = "System";

$lang['AdminClients.notes.categorylink_create'] = "Create Note";

$lang['AdminClients.notes.option_edit'] = "Edit";
$lang['AdminClients.notes.option_delete'] = "Delete";

$lang['AdminClients.notes.confirm_delete'] = "Are you sure you want to delete this note?";

$lang['AdminClients.notes.no_results'] = "There are no notes.";

$lang['AdminClients.!notes.stickied'] = "Check this box to display this note on the client profile page.";


// Add Note
$lang['AdminClients.addnote.page_title'] = "Client #%1\$s Create Note"; // %1$s is the client ID number
$lang['AdminClients.addnote.boxtitle_createnote'] = "Create Note";

$lang['AdminClients.addnote.field_title'] = "Summary";
$lang['AdminClients.addnote.field_description'] = "Details";
$lang['AdminClients.addnote.field_stickied'] = "Sticky this Note";

$lang['AdminClients.addnote.field_notesubmit'] = "Create Note";


// Edit Note
$lang['AdminClients.editnote.page_title'] = "Client #%1\$s Edit Note"; // %1$s is the client ID number
$lang['AdminClients.editnote.boxtitle_editnote'] = "Edit Note";

$lang['AdminClients.editnote.field_title'] = "Summary";
$lang['AdminClients.editnote.field_description'] = "Details";
$lang['AdminClients.editnote.field_stickied'] = "Sticky this Note";

$lang['AdminClients.editnote.field_notesubmit'] = "Edit Note";


// Create Service
$lang['AdminClients.addservice.status.active'] = "Active";
$lang['AdminClients.addservice.status.inactive'] = "Inactive";
$lang['AdminClients.addservice.status.restricted'] = "Restricted";
$lang['AdminClients.addservice.page_title'] = "Client #%1\$s Add Service"; // %1$s is the client ID number
$lang['AdminClients.addservice.boxtitle_addservice'] = "Add Service: %1\$s"; // %1\$s is the name of the package being used to add the service
$lang['AdminClients.addservice.field_package'] = "Package";

$lang['AdminClients.addservice.field_continue'] = "Continue";

$lang['AdminClients.addservice.term_onetime'] = "Onetime - %3\$s";
$lang['AdminClients.addservice.term'] = "%1\$s %2\$s - %3\$s";

$lang['AdminClients.addservice_basic.basic_heading'] = "Basic Options";
$lang['AdminClients.addservice_basic.field_invoice_method'] = "Invoice Method";
$lang['AdminClients.addservice_basic.field_invoice_method_create'] = "Create Invoice";
$lang['AdminClients.addservice_basic.field_invoice_method_append'] = "Append to Invoice";
$lang['AdminClients.addservice_basic.field_invoice_method_dont'] = "Do Not Invoice";
$lang['AdminClients.addservice_basic.field_term'] = "Term";
$lang['AdminClients.addservice_basic.field_status'] = "Status";
$lang['AdminClients.addservice_basic.field_notify_order'] = "Send order confirmation email";
$lang['AdminClients.addservice_basic.field_use_module'] = "Provision using the %1\$s module"; // %1$s is the name of the module the service is being created for
$lang['AdminClients.addservice_basic.module_heading'] = "%1\$s Options"; // %1$s is the name of the module options are being displayed for
$lang['AdminClients.addservice_basic.addons_heading'] = "Add-ons";
$lang['AdminClients.addservice_basic.field_default_addon'] = "Default";
$lang['AdminClients.addservice_basic.field_continue'] = "Continue";

$lang['AdminClients.addservice_addon.module_heading'] = "%1\$s Options"; // %1$s is the name of the module options are being displayed for
$lang['AdminClients.addservice_addon.basic_heading'] = "Basic Options";
$lang['AdminClients.addservice_addon.field_term'] = "Term";

$lang['AdminClients.addservice_confirm.field_invoice_method'] = "Invoice Method:";
$lang['AdminClients.addservice_confirm.field_invoice_method_create'] = "Create Invoice";
$lang['AdminClients.addservice_confirm.field_invoice_method_append'] = "Append to Invoice %1\$s";
$lang['AdminClients.addservice_confirm.field_invoice_method_none'] = "Do Not Invoice";
$lang['AdminClients.addservice_confirm.field_notify_order'] = "Send Order Confirmation Email:";
$lang['AdminClients.addservice_confirm.field_notify_order_true'] = "Yes";
$lang['AdminClients.addservice_confirm.field_notify_order_false'] = "No";
$lang['AdminClients.addservice_confirm.field_status'] = "Status:";
$lang['AdminClients.addservice_confirm.package'] = "Package";
$lang['AdminClients.addservice_confirm.qty'] = "Quantity";
$lang['AdminClients.addservice_confirm.term'] = "Term";
$lang['AdminClients.addservice_confirm.subtotal'] = "Sub Total:";
$lang['AdminClients.addservice_confirm.setup_fee'] = "Setup Fee:";
$lang['AdminClients.addservice_confirm.discount'] = "Discount:";
$lang['AdminClients.addservice_confirm.field_update_coupon'] = "Update";
$lang['AdminClients.addservice_confirm.field_add'] = "Add Service";
$lang['AdminClients.addservice_confirm.field_edit'] = "Edit";

// Edit Service
$lang['AdminClients.editservice.page_title'] = "Client #%1\$s Manage Service"; // %1$s is the client ID number
$lang['AdminClients.editservice.boxtitle_editservice'] = "Manage Service: %1\$s - %2\$s"; // %1$s is the name of the package, %2$s is the name of the service
$lang['AdminClients.editservice.tab_basic'] = "Basic Options";

$lang['AdminClients.editservice.service_heading'] = "Service Information";
$lang['AdminClients.editservice.text_package_name'] = "Package Name:";
$lang['AdminClients.editservice.text_label'] = "Label:";
$lang['AdminClients.editservice.text_qty'] = "Quantity:";
$lang['AdminClients.editservice.text_term'] = "Term:";
$lang['AdminClients.editservice.text_date_added'] = "Date Created:";
$lang['AdminClients.editservice.text_date_renews'] = "Date Renews:";
$lang['AdminClients.editservice.text_date_suspended'] = "Date Suspended:";
$lang['AdminClients.editservice.text_date_canceled'] = "Scheduled Cancellation Date:";

$lang['AdminClients.editservice.action_heading'] = "Actions";
$lang['AdminClients.editservice.package_heading'] = "Upgrade/Downgrade";
$lang['AdminClients.editservice.module_heading'] = "%1\$s Options"; // %1$s is the name of the module options are being displayed for
$lang['AdminClients.editservice.addon_heading'] = "Available Add-ons";
$lang['AdminClients.editservice.field_use_module'] = "Use module";
$lang['AdminClients.editservice.field_notify_order'] = "Send order confirmation email"; 
$lang['AdminClients.editservice.field_module_save'] = "Save";
$lang['AdminClients.editservice.field_module_activate'] = "Activate";
$lang['AdminClients.editservice.field_add_addon'] = "Add Service";

$lang['AdminClients.editservice.action.field_action'] = "Action";
$lang['AdminClients.editservice.action.field_cancel_term'] = "End of Term"; // %1$s is the date the service next renews
$lang['AdminClients.editservice.action.field_cancel_term_date'] = "End of Term (%1\$s)"; // %1$s is the date the service next renews
$lang['AdminClients.editservice.action.field_cancel_date'] = "Specific Date";
$lang['AdminClients.editservice.action.field_cancel_none'] = "Do not cancel";
$lang['AdminClients.editservice.action.field_date_renews'] = "Date Renews";

$lang['AdminClients.editservice.package.field_pricing'] = "Package/Term";

// Service info
$lang['AdminClients.serviceinfo.no_results'] = "This service has no details.";

// Make Payment
$lang['AdminClients.makepayment.page_title'] = "Client #%1\$s Make Payment"; // %1$s is the client ID number
$lang['AdminClients.makepayment.boxtitle_makepayment'] = "Make Payment";
$lang['AdminClients.makepayment.heading_paymentaccount'] = "Funding";
$lang['AdminClients.makepayment.field_paymentaccount'] = "%1\$s %2\$s - %3\$s x%4\$s"; // %1$s is the account first name, %2$s is the account last name, %3$s is the account type (card type or bank account type), %4$s is the last 4 of the account
$lang['AdminClients.makepayment.field_paymentaccount_autodebit'] = "(Auto Debit) %1\$s %2\$s - %3\$s x%4\$s"; // %1$s is the account first name, %2$s is the account last name, %3$s is the account type (card type or bank account type), %4$s is the last 4 of the account
$lang['AdminClients.makepayment.field_paymentaccount_ach'] = "ACH Accounts";
$lang['AdminClients.makepayment.field_paymentaccount_cc'] = "Credit Card Accounts";
$lang['AdminClients.makepayment.field_submit'] = "Continue";
$lang['AdminClients.makepayment.field_useaccount'] = "Use Payment Account";
$lang['AdminClients.makepayment.field_newdetails'] = "New Payment Details";
$lang['AdminClients.makepayment.boxtitle_makepaymentamount'] = "Make Payment";
$lang['AdminClients.makepayment.record_invoice'] = "Record Payment for Invoice #%1\$s, instead"; // %1$s is the invoice number

$lang['AdminClients.makepaymentamount.heading_invoices'] = "Invoice Selection";
$lang['AdminClients.makepaymentamount.field_submit'] = "Review and Confirm";
$lang['AdminClients.makepaymentamount.field_receipt'] = "Email Receipt";
$lang['AdminClients.makepaymentamount.field_credit'] = "Other Payment Amount";
$lang['AdminClients.makepaymentamount.field_currency'] = "Currency";
$lang['AdminClients.makepaymentamount.text_amount'] = "Amount to Pay";
$lang['AdminClients.makepaymentamount.text_due'] = "Amount Due";
$lang['AdminClients.makepaymentamount.text_invoice'] = "Invoice #";
$lang['AdminClients.makepaymentamount.text_datedue'] = "Date Due";
$lang['AdminClients.makepaymentamount.no_results'] = "There are no invoices in this currency.";

$lang['AdminClients.makepaymentconfirm.boxtitle_makepaymentconfirm'] = "Confirm Payment";
$lang['AdminClients.makepaymentconfirm.field_submit'] = "Submit Payment";
$lang['AdminClients.makepaymentconfirm.field_edit'] = "Edit Payment";
$lang['AdminClients.makepaymentconfirm.text_amount'] = "Amount to Apply";
$lang['AdminClients.makepaymentconfirm.text_due'] = "Amount Due";
$lang['AdminClients.makepaymentconfirm.text_invoice'] = "Invoice #";
$lang['AdminClients.makepaymentconfirm.text_datedue'] = "Date Due";
$lang['AdminClients.makepaymentconfirm.account_info'] = "%1\$s (%2\$s) ending in %3\$s"; // %1$s is the account type (Credit Card of ACH), %2$s is the type (Savings, Checking, MasterCard, etc.) and %3$s is the last 4 digits of the account
$lang['AdminClients.makepaymentconfirm.account_exp'] = "expires %1\$s"; // %1$s is the date the credit card expires
$lang['AdminClients.makepaymentconfirm.total'] = "Total:";
$lang['AdminClients.makepaymentconfirm.payment_details'] = "Payment Details";
$lang['AdminClients.makepaymentconfirm.email_receipt'] = "Email Receipt";
$lang['AdminClients.makepaymentconfirm.email_receipt_yes'] = "Yes";
$lang['AdminClients.makepaymentconfirm.email_receipt_no'] = "No";
$lang['AdminClients.makepaymentconfirm.trans_info_detailed'] = "%1\$s Payment #%2\$s"; // %1$s is the payment type, %2$s is the transaction number
$lang['AdminClients.makepaymentconfirm.trans_info'] = "%1\$s Payment"; // %1$s is the payment type


// Record Payment
$lang['AdminClients.recordpayment.page_title'] = "Client #%1\$s Record Payment"; // %1$s is the client ID number
$lang['AdminClients.recordpayment.boxtitle_recordpayment'] = "Record Payment";
$lang['AdminClients.recordpayment.field_receipt'] = "Email Receipt";
$lang['AdminClients.recordpayment.field_amount'] = "Payment Amount";
$lang['AdminClients.recordpayment.field_currency'] = "Currency";
$lang['AdminClients.recordpayment.field_datereceived'] = "Date Received";
$lang['AdminClients.recordpayment.field_transaction_id'] = "Check/ID #";
$lang['AdminClients.recordpayment.field_transactiontype'] = "Payment Type";
$lang['AdminClients.recordpayment.field_gateway_id'] = "Payment Gateway";
$lang['AdminClients.recordpayment.field_submit'] = "Continue";
$lang['AdminClients.recordpayment.text_datereceived'] = "Set Date Received";
$lang['AdminClients.recordpayment.gateway_none'] = "None";

$lang['AdminClients.recordpaymentconfirm.boxtitle_recordconfirm'] = "Confirm Payment";
$lang['AdminClients.recordpaymentconfirm.total'] = "Total:";
$lang['AdminClients.recordpaymentconfirm.payment_details'] = "Payment Details";
$lang['AdminClients.recordpaymentconfirm.email_receipt'] = "Email Receipt";
$lang['AdminClients.recordpaymentconfirm.email_receipt_yes'] = "Yes";
$lang['AdminClients.recordpaymentconfirm.email_receipt_no'] = "No";
$lang['AdminClients.recordpaymentconfirm.trans_info_detailed'] = "%1\$s Payment #%2\$s"; // %1$s is the payment type, %2$s is the transaction number
$lang['AdminClients.recordpaymentconfirm.trans_info'] = "%1\$s Payment"; // %1$s is the payment type
$lang['AdminClients.recordpaymentconfirm.field_submit'] = "Submit Payment";
$lang['AdminClients.recordpaymentconfirm.field_edit'] = "Edit Payment";


// Set Contact Info
$lang['AdminClients.setcontactview.text_none'] = "None";


// Services
$lang['AdminClients.services.page_title'] = "Client #%1\$s Services"; // %1$s is the client ID number
$lang['AdminClients.services.boxtitle_services'] = "Services";

$lang['AdminClients.services.heading_addons'] = "Add-ons";
$lang['AdminClients.services.heading_status'] = "Status";

$lang['AdminClients.services.heading_package'] = "Package";
$lang['AdminClients.services.heading_label'] = "Label";
$lang['AdminClients.services.heading_term'] = "Term";
$lang['AdminClients.services.heading_datecreated'] = "Date Created";
$lang['AdminClients.services.heading_daterenews'] = "Date Renews";
$lang['AdminClients.services.heading_datesuspended'] = "Date Suspended";
$lang['AdminClients.services.heading_datecanceled'] = "Date Canceled";
$lang['AdminClients.services.heading_options'] = "Options";

$lang['AdminClients.services.category_active'] = "Active";
$lang['AdminClients.services.category_pending'] = "Pending";
$lang['AdminClients.services.category_suspended'] = "Suspended";
$lang['AdminClients.services.category_canceled'] = "Canceled";

$lang['AdminClients.services.categorylink_newservice'] = "New Service";

$lang['AdminClients.services.option_manage'] = "Manage";
$lang['AdminClients.services.option_delete'] = "Delete";
$lang['AdminClients.services.confirm_delete'] = "Are you sure you want to delete this service?";
$lang['AdminClients.services.no_results'] = "There are no services with this status.";

$lang['AdminClients.services.text_never'] = "Never";


// Transactions
$lang['AdminClients.transactions.page_title'] = "Client #%1\$s Transactions"; // %1$s is the client ID number
$lang['AdminClients.transactions.boxtitle_transactions'] = "Transactions";

$lang['AdminClients.transactions.heading_type'] = "Type";
$lang['AdminClients.transactions.heading_amount'] = "Amount";
$lang['AdminClients.transactions.heading_credited'] = "Credited";
$lang['AdminClients.transactions.heading_applied'] = "Applied";
$lang['AdminClients.transactions.heading_number'] = "Number";
$lang['AdminClients.transactions.heading_status'] = "Status";
$lang['AdminClients.transactions.heading_date'] = "Date";
$lang['AdminClients.transactions.heading_options'] = "Options";

$lang['AdminClients.transactions.headingexpand_invoice'] = "Invoice";
$lang['AdminClients.transactions.headingexpand_amount'] = "Amount";
$lang['AdminClients.transactions.headingexpand_appliedon'] = "Applied On";
$lang['AdminClients.transactions.headingexpand_options'] = "Options";

$lang['AdminClients.transactions.category_approved'] = "Approved";
$lang['AdminClients.transactions.category_declined'] = "Declined";
$lang['AdminClients.transactions.category_voided'] = "Voided";
$lang['AdminClients.transactions.category_error'] = "Error";
$lang['AdminClients.transactions.category_pending'] = "Pending";
$lang['AdminClients.transactions.category_refunded'] = "Refunded";
$lang['AdminClients.transactions.category_returned'] = "Returned";

$lang['AdminClients.transactions.option_edit'] = "Edit";
$lang['AdminClients.transactions.no_results'] = "There are no transactions with this status.";
$lang['AdminClients.transactions.applied_no_results'] = "This transaction has not been applied to any invoices.";


// View
$lang['AdminClients.view.page_title'] = "Client #%1\$s Profile"; // %1$s is the client ID number
$lang['AdminClients.view.boxtitle_client'] = "Client #%1\$s"; // %1$s is the client ID number
$lang['AdminClients.view.boxtitle_contacts'] = "Contacts";

$lang['AdminClients.view.status_link'] = "(Click to change)";

$lang['AdminClients.view.link_vcard'] = "vCard";
$lang['AdminClients.view.link_notes'] = "Notes";
$lang['AdminClients.view.link_editclient'] = "Edit";

$lang['AdminClients.view.setting_memberof'] = "Member of";
$lang['AdminClients.view.setting_invoicemethod'] = "Invoice Method";
$lang['AdminClients.view.setting_autodebit'] = "Auto Debit";
$lang['AdminClients.view.setting_autosuspension'] = "Auto Suspension";
$lang['AdminClients.view.setting_sublogin'] = "Sub Login";

$lang['AdminClients.view.setting_enabled'] = "Enabled";
$lang['AdminClients.view.setting_disabled'] = "Disabled";

$lang['AdminClients.view.member_since'] = "Member since %1\$s"; // %1$s is the date the user signed up
$lang['AdminClients.view.member_last_seen'] = "Last seen %1\$s from"; // %1$s is the date the user last logged in, NOTE: this phrase is expected to be followed by an IP address
$lang['AdminClients.view.member_last_seen_never'] = "Last seen never";
$lang['AdminClients.view.tooltip_activity'] = "Last Activity";
$lang['AdminClients.view.tooltip_location'] = "Location";
$lang['AdminClients.view.tooltip_last_activity_now'] = "Just Now";
$lang['AdminClients.view.tooltip_last_activity_minute'] = "1 minute ago";
$lang['AdminClients.view.tooltip_last_activity_minutes'] = "%1\$s minutes ago"; // %1$s is the number of minutes since the user's last activity

$lang['AdminClients.view.actions_title'] = "Account Actions";
$lang['AdminClients.view.actionlink_createinvoice'] = "Create Invoice";
$lang['AdminClients.view.actionlink_addservice'] = "Add Service";
$lang['AdminClients.view.actionlink_addcontact'] = "Add Contact";
$lang['AdminClients.view.actionlink_makepayment'] = "Make Payment";
$lang['AdminClients.view.actionlink_recordpayment'] = "Record Payment";
$lang['AdminClients.view.actionlink_manageaccount'] = "Payment Accounts";
$lang['AdminClients.view.actionlink_addnote'] = "Add Note";
$lang['AdminClients.view.actionlink_setpackages'] = "Set Packages";
$lang['AdminClients.view.actionlink_viewmaillog'] = "View Mail Log";
$lang['AdminClients.view.actionlink_mergeclient'] = "Merge Client";
$lang['AdminClients.view.actionlink_deleteclient'] = "Delete Client";
$lang['AdminClients.view.actionlink_emailclient'] = "Email Client";
$lang['AdminClients.view.actionlink_login'] = "Login as Client";

$lang['AdminClients.view.actionlink_more'] = "Show All Actions";
$lang['AdminClients.view.actionlink_less'] = "Show Common Actions";

$lang['AdminClients.view.confirm_delete'] = "Are you sure you want to delete this client?";

$lang['AdminClients.view.contact_type'] = "(%1\$s)"; // %1$s is the client contact type (e.g. Billing)


// Get currency amounts partial
$lang['AdminClients.getcurrencyamounts.text_total_credits'] = "Total Credit";
$lang['AdminClients.getcurrencyamounts.text_total_due'] = "Total Due";


// Show card
$lang['AdminClients.showcard.modal_title'] = "Show Card";
$lang['AdminClients.showcard.field_passphrase'] = "Passphrase";
$lang['AdminClients.showcard.field_staff_password'] = "Your login password";
$lang['AdminClients.showcard.field_submit'] = "Submit";
$lang['AdminClients.showcard.!error.passphrase'] = "Passphrase is invalid.";
$lang['AdminClients.showcard.!error.password'] = "Password is invalid.";

// Show account
$lang['AdminClients.showaccount.modal_title'] = "Show Account";
$lang['AdminClients.showaccount.field_passphrase'] = "Passphrase";
$lang['AdminClients.showaccount.field_staff_password'] = "Your login password";
$lang['AdminClients.showaccount.field_submit'] = "Submit";
$lang['AdminClients.showaccount.!error.passphrase'] = "Passphrase is invalid.";
$lang['AdminClients.showaccount.!error.password'] = "Password is invalid.";

// Packages (Restricted)
$lang['AdminClients.packages.page_title'] = "Client #%1\$s Restricted Packages"; // %1$s is the client ID number
$lang['AdminClients.packages.boxtitle_packages'] = "Restricted Packages";

$lang['AdminClients.packages.text_name'] = "Name";
$lang['AdminClients.packages.text_module'] = "Module";

$lang['AdminClients.packages.field_packagesubmit'] = "Save Restricted Package Access";

$lang['AdminClients.packages.no_results'] = "There are no restricted packages.";
?>