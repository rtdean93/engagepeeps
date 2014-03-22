<?php
/**
 * Language definitions for the Client Contacts controller/views
 * 
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Success messages
$lang['ClientContacts.!success.contact_deleted'] = "The contact %1\$s %2\$s was successfully deleted!"; // %1$s is the contact's first name, %2$s is the contact's last name
$lang['ClientContacts.!success.contact_updated'] = "The contact was successfully updated!";
$lang['ClientContacts.!success.contact_added'] = "The contact was successfully created!";


// Index
$lang['ClientContacts.index.page_title'] = "Client #%1\$s Contacts"; // %1$s is the client ID number

$lang['ClientContacts.index.create_contact'] = "Add Contact";

$lang['ClientContacts.index.boxtitle_contacts'] = "Contacts";
$lang['ClientContacts.index.heading_name'] = "Name";
$lang['ClientContacts.index.heading_email'] = "Email";
$lang['ClientContacts.index.heading_type'] = "Type";
$lang['ClientContacts.index.heading_options'] = "Options";
$lang['ClientContacts.index.option_edit'] = "Edit";
$lang['ClientContacts.index.option_delete'] = "Delete";

$lang['ClientContacts.index.confirm_delete'] = "Are you sure you want to delete this contact?";

$lang['ClientContacts.index.no_results'] = "You have no contacts. To add your first contact, click the Add Contact button above.";


// Add contact
$lang['ClientContacts.add.page_title'] = "Client #%1\$s Add Contact"; // %1$s is the client ID number
$lang['ClientContacts.add.boxtitle_create'] = "Add Contact";

$lang['ClientContacts.add.heading_settings'] = "Additional Settings";
$lang['ClientContacts.add.field_contact_type'] = "Contact Type";
$lang['ClientContacts.add.field_addsubmit'] = "Create Contact";


// Edit contact
$lang['ClientContacts.edit.page_title'] = "Client #%1\$s Edit Contact"; // %1$s is the client ID number
$lang['ClientContacts.edit.boxtitle_edit'] = "Edit Contact";

$lang['ClientContacts.edit.heading_settings'] = "Additional Settings";
$lang['ClientContacts.edit.field_contact_type'] = "Contact Type";
$lang['ClientContacts.edit.field_editsubmit'] = "Update Contact";


// Set Contact View
$lang['ClientContacts.setcontactview.text_none'] = "None";


// Contact Info partial
$lang['ClientContacts.contact_info.heading_contact'] = "Contact Information";

$lang['ClientContacts.contact_info.field_contact_id'] = "Copy Contact Information From";
$lang['ClientContacts.contact_info.field_first_name'] = "First Name";
$lang['ClientContacts.contact_info.field_last_name'] = "Last Name";
$lang['ClientContacts.contact_info.field_company'] = "Company";
$lang['ClientContacts.contact_info.field_address1'] = "Address 1";
$lang['ClientContacts.contact_info.field_address2'] = "Address 2";
$lang['ClientContacts.contact_info.field_city'] = "City";
$lang['ClientContacts.contact_info.field_country'] = "Country";
$lang['ClientContacts.contact_info.field_state'] = "State";
$lang['ClientContacts.contact_info.field_zip'] = "Zip/Postal Code";
$lang['ClientContacts.contact_info.field_email'] = "Email";


// Phone Number partial
$lang['ClientContacts.phone_numbers.heading_phone'] = "Phone Numbers";
$lang['ClientContacts.phone_numbers.categorylink_number'] = "Add Additional Number";

$lang['ClientContacts.phone_numbers.field_phonetype'] = "Type";
$lang['ClientContacts.phone_numbers.field_phonelocation'] = "Location";
$lang['ClientContacts.phone_numbers.field_phonenumber'] = "Number";
$lang['ClientContacts.phone_numbers.text_remove'] = "Remove";
?>