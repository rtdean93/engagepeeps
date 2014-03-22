<?php
/**
 * Language definitions for the Admin Company General settings controller/views
 * 
 * @package blesta
 * @subpackage blesta.language.en_us
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

// Success messages
$lang['AdminCompanyGeneral.!success.themes_updated'] = "The theme has been successfully updated.";
$lang['AdminCompanyGeneral.!success.localization_updated'] = "The localization settings have been successfully updated.";
$lang['AdminCompanyGeneral.!success.encryption_updated'] = "The encryption settings have been successfully updated.";
$lang['AdminCompanyGeneral.!success.theme_deleted'] = "The theme \"%1\$s\" has been successfully deleted."; // %1$s is the name of the theme
$lang['AdminCompanyGeneral.!success.theme_updated'] = "The theme \"%1\$s\" has been successfully updated."; // %1$s is the name of the theme
$lang['AdminCompanyGeneral.!success.theme_added'] = "The theme \"%1\$s\" has been successfully added."; // %1$s is the name of the theme
$lang['AdminCompanyGeneral.!success.contact_type_added'] = "The contact type \"%1\$s\" has been successfully added."; // %1$s is the name of the contact type
$lang['AdminCompanyGeneral.!success.contact_type_updated'] = "The contact type \"%1\$s\" has been successfully updated."; // %1$s is the name of the contact type
$lang['AdminCompanyGeneral.!success.contact_type_deleted'] = "The contact type \"%1\$s\" has been successfully deleted."; // %1$s is the name of the contact type

$lang['AdminCompanyGeneral.!success.language_installed'] = "The language %1\$s has been successfully installed."; // %1$s is the name of the language
$lang['AdminCompanyGeneral.!success.language_uninstalled'] = "The language %1\$s has been successfully uninstalled."; // %1$s is the name of the language


// Localization
$lang['AdminCompanyGeneral.localization.page_title'] = "Settings > Company > General > Localization";
$lang['AdminCompanyGeneral.localization.tz_format'] = "(UTC %1\$s) %2\$s"; // %1$s is the UTC offset, %2$s is the timezone name

$lang['AdminCompanyGeneral.localization.text_language'] = "Default Language";
$lang['AdminCompanyGeneral.localization.text_setlanguage'] = "Client may set Language";
$lang['AdminCompanyGeneral.localization.text_calendar'] = "Calendar Start Day";
$lang['AdminCompanyGeneral.localization.text_sunday'] = "Sunday";
$lang['AdminCompanyGeneral.localization.text_monday'] = "Monday";
$lang['AdminCompanyGeneral.localization.text_timezone'] = "Timezone";
$lang['AdminCompanyGeneral.localization.text_dateformat'] = "Date Format";
$lang['AdminCompanyGeneral.localization.text_datetimeformat'] = "Date Time Format";
$lang['AdminCompanyGeneral.localization.text_country'] = "Default Country";
$lang['AdminCompanyGeneral.localization.text_localizationsubmit'] = "Update Settings";


// Internationalization
$lang['AdminCompanyGeneral.!notice.international_languages'] = "A crowdsourced translation project exists at translate.blesta.com. You may contribute to, and download language translations there. To install, unzip the contents of the file to your Blesta installation directory. Then, refresh this page, and click the Install link.";
$lang['AdminCompanyGeneral.international.page_title'] = "Settings > Company > General > Internationalization";
$lang['AdminCompanyGeneral.international.boxtitle_international'] = "Internationalization";

$lang['AdminCompanyGeneral.international.text_language'] = "Language";
$lang['AdminCompanyGeneral.international.text_iso'] = "ISO 639-1, 3166-1";
$lang['AdminCompanyGeneral.international.text_options'] = "Options";

$lang['AdminCompanyGeneral.international.option_install'] = "Install";
$lang['AdminCompanyGeneral.international.option_uninstall'] = "Uninstall";

$lang['AdminCompanyGeneral.international.confirm_install'] = "Are you sure you want to install the language %1\$s?"; // %1$s is the name of the language
$lang['AdminCompanyGeneral.international.confirm_uninstall'] = "Are you sure you want to uninstall the language %1\$s? This language will be uninstalled and all email templates in this language will be permanently deleted."; // %1$s is the name of the language


// Themes
$lang['AdminCompanyGeneral.themes.page_title'] = "Settings > Company > General > Themes";
$lang['AdminCompanyGeneral.themes.boxtitle_themes'] = "Themes";
$lang['AdminCompanyGeneral.themes.categorylink_addtheme'] = "Add Theme";

$lang['AdminCompanyGeneral.themes.field_themessubmit'] = "Select Theme";

$lang['AdminCompanyGeneral.themes.heading_color'] = "Color Scheme";
$lang['AdminCompanyGeneral.themes.heading_name'] = "Name";
$lang['AdminCompanyGeneral.themes.heading_options'] = "Options";

$lang['AdminCompanyGeneral.themes.option_edit'] = "Edit";
$lang['AdminCompanyGeneral.themes.option_delete'] = "Delete";

$lang['AdminCompanyGeneral.themes.no_results'] = "There are no themes of this type.";

$lang['AdminCompanyGeneral.themes.confirm_deletetheme'] = "Are you sure you want to delete this theme?";


// Add/Edit Theme tool tips
$lang['AdminCompanyGeneral.!theme.tooltip_theme_header_bg_color'] = "Sets the header background color gradient.";
$lang['AdminCompanyGeneral.!theme.tooltip_theme_header_text_color'] = "Sets the color of text/links in the header.";
$lang['AdminCompanyGeneral.!theme.tooltip_theme_navigation_background_color'] = "Sets the navigation menu's background color.";
$lang['AdminCompanyGeneral.!theme.tooltip_theme_navigation_text_color'] = "Sets the navigation menu's text color.";
$lang['AdminCompanyGeneral.!theme.tooltip_theme_navigation_text_hover_color'] = "Sets the navigation and sub-navigation menus' text color on hover.";
$lang['AdminCompanyGeneral.!theme.tooltip_theme_subnavigation_bg_color'] = "Sets the header subnavigation background color gradient.";
$lang['AdminCompanyGeneral.!theme.tooltip_theme_subnavigation_text_color'] = "Sets the header subnavigation text color.";
$lang['AdminCompanyGeneral.!theme.tooltip_theme_subnavigation_text_active_color'] = "Sets the header subnavigation text color when active.";
$lang['AdminCompanyGeneral.!theme.tooltip_theme_widget_heading_bg_color'] = "Sets the widget-box heading background color gradient.";
$lang['AdminCompanyGeneral.!theme.tooltip_theme_widget_icon_heading_bg_color'] = "Sets the widget-box heading icon background color gradient.";
$lang['AdminCompanyGeneral.!theme.tooltip_theme_box_text_color'] = "Sets the text color in widget-box headings.";
$lang['AdminCompanyGeneral.!theme.tooltip_theme_text_shadow'] = "Sets the color of text shadows.";
$lang['AdminCompanyGeneral.!theme.tooltip_theme_actions_text_color'] = "Sets the color of links.";
$lang['AdminCompanyGeneral.!theme.tooltip_theme_highlight_bg_color'] = "Sets the highlight (mouse-over) color for pagination and table rows.";
$lang['AdminCompanyGeneral.!theme.tooltip_logo_url'] = "The full path (URL) to the header logo image.";

$lang['AdminCompanyGeneral.!theme.tooltip_theme_page_title_background_color'] = "Sets the page title background color gradient.";
$lang['AdminCompanyGeneral.!theme.tooltip_theme_page_title_text_color'] = "Sets the page title text color.";
$lang['AdminCompanyGeneral.!theme.tooltip_theme_page_title_button_background_color'] = "Sets the page title buttons' background color gradient.";
$lang['AdminCompanyGeneral.!theme.tooltip_theme_navigation_text_active_color'] = "Sets the navigation menu's text color when active.";
$lang['AdminCompanyGeneral.!theme.tooltip_theme_page_background_color'] = "Sets the background color of the content area.";
$lang['AdminCompanyGeneral.!theme.tooltip_theme_link_color'] = "Sets the color of links.";
$lang['AdminCompanyGeneral.!theme.tooltip_theme_link_settings_color'] = "Sets the color of links in the settings section of the sidebar.";


// Add theme
$lang['AdminCompanyGeneral.addtheme.page_title'] = "Settings > Company > Themes > New Theme";
$lang['AdminCompanyGeneral.addtheme.boxtitle_addtheme'] = "New Theme";

$lang['AdminCompanyGeneral.addtheme.field_name'] = "Name";
$lang['AdminCompanyGeneral.addtheme.field_addthemesubmit'] = "Create Theme";

// staff theme options
$lang['AdminCompanyGeneral.addtheme.field_theme_header_text_color'] = "Header Text Color";
$lang['AdminCompanyGeneral.addtheme.field_theme_navigation_background_color'] = "Navigation Background Color";
$lang['AdminCompanyGeneral.addtheme.field_theme_navigation_text_hover_color'] = "Navigation Text Hover Color";
$lang['AdminCompanyGeneral.addtheme.field_theme_subnavigation_bg_color'] = "Subnavigation Background Color";
$lang['AdminCompanyGeneral.addtheme.field_theme_subnavigation_text_color'] = "Subnavigation Text Color";
$lang['AdminCompanyGeneral.addtheme.field_theme_subnavigation_text_active_color'] = "Subnavigation Text Active Color";
$lang['AdminCompanyGeneral.addtheme.field_theme_widget_heading_bg_color'] = "Widget Box Heading Background Color";
$lang['AdminCompanyGeneral.addtheme.field_theme_widget_icon_heading_bg_color'] = "Widget Box Heading Icon Background Color";
$lang['AdminCompanyGeneral.addtheme.field_theme_box_text_color'] = "Box Text Color";
$lang['AdminCompanyGeneral.addtheme.field_theme_text_shadow'] = "Text Shadow Color";
$lang['AdminCompanyGeneral.addtheme.field_theme_actions_text_color'] = "Link Text Color";
$lang['AdminCompanyGeneral.addtheme.field_theme_highlight_bg_color'] = "Highlight Background Color";

// staff/client theme shared options
$lang['AdminCompanyGeneral.addtheme.field_theme_header_bg_color'] = "Header Background Color";
$lang['AdminCompanyGeneral.addtheme.field_theme_navigation_text_color'] = "Navigation Text Color";
$lang['AdminCompanyGeneral.addtheme.field_logo_url'] = "Header Logo";

// client options
$lang['AdminCompanyGeneral.addtheme.field_theme_page_title_background_color'] = "Page Title Background Color";
$lang['AdminCompanyGeneral.addtheme.field_theme_page_title_text_color'] = "Page Title Text Color";
$lang['AdminCompanyGeneral.addtheme.field_theme_page_title_button_background_color'] = "Page Title Button Background Color";
$lang['AdminCompanyGeneral.addtheme.field_theme_navigation_text_active_color'] = "Navigation Text Active Color";
$lang['AdminCompanyGeneral.addtheme.field_theme_page_background_color'] = "Page Background Color";
$lang['AdminCompanyGeneral.addtheme.field_theme_link_color'] = "Link Text Color";
$lang['AdminCompanyGeneral.addtheme.field_theme_link_settings_color'] = "Settings Link Text Color";


// Edit theme
$lang['AdminCompanyGeneral.edittheme.page_title'] = "Settings > Company > Themes > Edit Theme";
$lang['AdminCompanyGeneral.edittheme.boxtitle_edittheme'] = "Edit Theme";

$lang['AdminCompanyGeneral.edittheme.field_name'] = "Name";
$lang['AdminCompanyGeneral.edittheme.field_editthemesubmit'] = "Update Theme";

// staff theme options
$lang['AdminCompanyGeneral.edittheme.field_theme_header_text_color'] = "Header Text Color";
$lang['AdminCompanyGeneral.edittheme.field_theme_navigation_text_hover_color'] = "Navigation Text Hover Color";
$lang['AdminCompanyGeneral.edittheme.field_theme_subnavigation_bg_color'] = "Subnavigation Background Color";
$lang['AdminCompanyGeneral.edittheme.field_theme_subnavigation_text_color'] = "Subnavigation Text Color";
$lang['AdminCompanyGeneral.edittheme.field_theme_subnavigation_text_active_color'] = "Subnavigation Text Active Color";
$lang['AdminCompanyGeneral.edittheme.field_theme_widget_heading_bg_color'] = "Widget Box Heading Background Color";
$lang['AdminCompanyGeneral.edittheme.field_theme_widget_icon_heading_bg_color'] = "Widget Box Heading Icon Background Color";
$lang['AdminCompanyGeneral.edittheme.field_theme_box_text_color'] = "Box Text Color";
$lang['AdminCompanyGeneral.edittheme.field_theme_text_shadow'] = "Text Shadow Color";
$lang['AdminCompanyGeneral.edittheme.field_theme_actions_text_color'] = "Link Text Color";
$lang['AdminCompanyGeneral.edittheme.field_theme_highlight_bg_color'] = "Highlight Background Color";

// staff/client theme shared options
$lang['AdminCompanyGeneral.edittheme.field_theme_header_bg_color'] = "Header Background Color";
$lang['AdminCompanyGeneral.edittheme.field_theme_navigation_background_color'] = "Navigation Background Color";
$lang['AdminCompanyGeneral.edittheme.field_theme_navigation_text_color'] = "Navigation Text Color";
$lang['AdminCompanyGeneral.edittheme.field_logo_url'] = "Header Logo";

// client options
$lang['AdminCompanyGeneral.edittheme.field_theme_page_title_background_color'] = "Page Title Background Color";
$lang['AdminCompanyGeneral.edittheme.field_theme_page_title_text_color'] = "Page Title Text Color";
$lang['AdminCompanyGeneral.edittheme.field_theme_page_title_button_background_color'] = "Page Title Button Background Color";
$lang['AdminCompanyGeneral.edittheme.field_theme_navigation_text_active_color'] = "Navigation Text Active Color";
$lang['AdminCompanyGeneral.edittheme.field_theme_page_background_color'] = "Page Background Color";
$lang['AdminCompanyGeneral.edittheme.field_theme_link_color'] = "Link Text Color";
$lang['AdminCompanyGeneral.edittheme.field_theme_link_settings_color'] = "Settings Link Text Color";


// Encryption
$lang['AdminCompanyGeneral.encryption.page_title'] = "Settings > Company > General > Encryption";
$lang['AdminCompanyGeneral.!notice.passphrase'] = "WARNING: Setting a passphrase will prevent locally stored payment accounts from being automatically processed. You will be required to manually batch payments by entering your passphrase. For more information regarding this feature please consult the manual.";
$lang['AdminCompanyGeneral.!notice.passphrase_set'] = "WARNING: A passphrase has been set. You are required to manually batch payments with your passphrase. Changing your passphrase to a blank passphrase will remove this requirement.";

$lang['AdminCompanyGeneral.encryption.boxtitle_encryption'] = "Encryption";

$lang['AdminCompanyGeneral.encryption.field_current_passphrase'] = "Current Private Key Passphrase";
$lang['AdminCompanyGeneral.encryption.field_private_key_passphrase'] = "New Private Key Passphrase";
$lang['AdminCompanyGeneral.encryption.field_confirm_new_passphrase'] = "Confirm Private Key Passphrase";
$lang['AdminCompanyGeneral.encryption.field_agree'] = "I have saved this passphrase to a safe location";

$lang['AdminCompanyGeneral.encryption.field_encryptionsubmit'] = "Update Passphrase";


// Contact Types
$lang['AdminCompanyGeneral.contacttypes.page_title'] = "Settings > Company > General > Contact Types";
$lang['AdminCompanyGeneral.contacttypes.categorylink_addtype'] = "Create Contact Type";
$lang['AdminCompanyGeneral.contacttypes.boxtitle_types'] = "Contact Types";

$lang['AdminCompanyGeneral.contacttypes.heading_name'] = "Name";
$lang['AdminCompanyGeneral.contacttypes.heading_define'] = "Uses Language Definition";
$lang['AdminCompanyGeneral.contacttypes.heading_options'] = "Options";

$lang['AdminCompanyGeneral.contacttypes.text_yes'] = "Yes";
$lang['AdminCompanyGeneral.contacttypes.text_no'] = "No";
$lang['AdminCompanyGeneral.contacttypes.option_edit'] = "Edit";
$lang['AdminCompanyGeneral.contacttypes.option_delete'] = "Delete";

$lang['AdminCompanyGeneral.contacttypes.modal_delete'] = "Deleting this contact type will cause all contacts assigned to this type to be placed into the default \"Billing\" type. Are you sure you want to delete this contact type?";

$lang['AdminCompanyGeneral.contacttypes.no_results'] = "There are no Contact Types.";

$lang['AdminCompanyGeneral.!contacttypes.is_lang'] = "Only check this box if you have added a language definition for this contact type in the custom language file.";


// Add Contact Type
$lang['AdminCompanyGeneral.addcontacttype.page_title'] = "Settings > Company > General > Create Contact Type";
$lang['AdminCompanyGeneral.addcontacttype.boxtitle_addcontacttype'] = "Create Contact Type";

$lang['AdminCompanyGeneral.addcontacttype.field_name'] = "Name";
$lang['AdminCompanyGeneral.addcontacttype.field_is_lang'] = "Use Language Definition";
$lang['AdminCompanyGeneral.addcontacttype.field_contacttypesubmit'] = "Create Contact Type";


// Edit Contact Type
$lang['AdminCompanyGeneral.editcontacttype.page_title'] = "Settings > Company > General > Edit Contact Type";
$lang['AdminCompanyGeneral.editcontacttype.boxtitle_editcontacttype'] = "Edit Contact Type";

$lang['AdminCompanyGeneral.editcontacttype.field_name'] = "Name";
$lang['AdminCompanyGeneral.editcontacttype.field_is_lang'] = "Use Language Definition";
$lang['AdminCompanyGeneral.editcontacttype.field_contacttypesubmit'] = "Edit Contact Type";
?>