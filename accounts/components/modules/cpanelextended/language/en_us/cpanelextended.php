<?php
/**
 * en_us language for the cPanel Extended module
 */

// Basics
$lang['Cpe.name'] = "cPanel Extended";
$lang['Cpe.module_row'] = "Server";
$lang['Cpe.module_row_plural'] = "Servers";
$lang['Cpe.module_group'] = "Server Group";

// Sections
$lang['Cpe.stats'] = "Statistics";
$lang['Cpe.ftp']   = "FTP Accounts";
$lang['Cpe.databases']   = "Databases";
$lang['Cpe.emails']   = "Emails";
$lang['Cpe.subdomains']   = "Subdomains";
$lang['Cpe.parkeddomains']   = "Parked Domains";
$lang['Cpe.addondomains']   = "Addon Domains";
$lang['Cpe.cron']   = "Cron";
$lang['Cpe.ssl']   = "SSL Management";
$lang['Cpe.loginto']   = "Log In To";

// Section titles
$lang['Cpe.titles.stats'] = "Statistics For The Account";
$lang['Cpe.titles.ftp'] = "FTP Accounts Management";
$lang['Cpe.titles.databases'] = "MySQL Databases Management";
$lang['Cpe.titles.emails'] = "Email Accounts Management";
$lang['Cpe.titles.subdomains']   = "Subdomains";
$lang['Cpe.titles.parkeddomains']   = "Parked Domains";
$lang['Cpe.titles.addondomains']   = "Addon Domains";
$lang['Cpe.titles.cron']   = "Cron Jobs Management";
$lang['Cpe.titles.ssl']   = "SSL Management";
$lang['Cpe.titles.loginto']   = "Log In To";

// Section descriptions
$lang['Cpe.description.ftp'] = "FTP accounts allow you to access your website's files through a protocol called FTP. You will need a third-party FTP program to access your files. You can log into via FTP by entering.";
$lang['Cpe.description.databases'] = "MySQL Databases allow you to store lots of information in an easy to access manner. The databases themselves are not easily read by humans. MySQL databases are required by many web applications including some bulletin boards, content management systems, and others. To use a database, you'll need to create it. Only MySQL Users (different than mail or other users) that have privileges to access a database can read from or write to that database.";
$lang['Cpe.description.emails'] = "In this area you can manage the email accounts associated with your domains.";
$lang['Cpe.description.subdomains'] = "Subdomains are URLs for different sections of your website. They use your main domain name and a prefix. For example, if your domain is hosting.com, a sub-domain of your domain might be support.hosting.com.
Subdomains are relative to your account's home directory.";
$lang['Cpe.description.addondomains'] = "An addon domain allows visitors to reach a subdomain of your site by typing the addon domain's URL into a browser. This means that you can host additional domains from your account, if allowed by your hosting provider. Addon Domains are relative to your account's home directory.";
$lang['Cpe.description.parkeddomains'] = "Parked Domains (Domain pointers) allow you to 'point' or 'park' additional domain names to your existing hosting account. This will allow users to also reach your website when entering the 'parked' or 'pointed' domain into their browsers.";
$lang['Cpe.description.cron'] = "Cron jobs allow you to automate certain commands or scripts on your site. You can set a command or script to run at a specific time every day, week, etc. For example, you could set a cron job to delete temporary files every week to free up disk space.<br><br><strong style=\"color: red;\">Warning:</strong> You need to have a good knowledge of Linux commands before you can use cron jobs effectively. Check your script with your hosting administrator before adding a cron job.";
$lang['Cpe.description.sslkeys'] = "SSL Certificates have a key pair: a public and a private key. These keys work together to establish an encrypted connection. You can generate the secure key, or you can import the private key.";
$lang['Cpe.description.sslcsr'] = "Certificate Signing Request is a message sent from an applicant to a certificate authority in order to apply for a digital identity certificate. Before you can order your SSL Certificates, you must first generate a CSR (Certificate Signing Request) on your server.";
$lang['Cpe.description.sslcrt'] = "For SSL to work a valid signed SSL certificate is required. The certificate contains subject, which is the identity of the certificate/website owner. You can generate the certificate key, or you can import them.";

// Section subtitles
$lang['Cpe.subtitles.ftp.create'] = "Add FTP Accounts";
$lang['Cpe.subtitles.ftp.list'] = "List FTP Accounts";
$lang['Cpe.subtitles.email.create'] = "Add Email Accounts";
$lang['Cpe.subtitles.email.list'] = "List Emails Accounts";
$lang['Cpe.subtitles.db.create'] = "Create Database";
$lang['Cpe.subtitles.db.dblist'] = "Current Databases List";
$lang['Cpe.subtitles.db.usercreate'] = "Add New MySQL User";
$lang['Cpe.subtitles.db.userlist'] = "Current Users List";
$lang['Cpe.subtitles.db.addusertodb'] = "Add User To Database";
$lang['Cpe.subtitles.subdomain.create'] = "Create Subdomain";
$lang['Cpe.subtitles.subdomain.list'] = "Subdomains List";
$lang['Cpe.subtitles.parkeddomain.create'] = "Create a New Parked Domain";
$lang['Cpe.subtitles.parkeddomain.list'] = "List Parked Domains";
$lang['Cpe.subtitles.addondomain.create'] = "Create a New Addon Domain";
$lang['Cpe.subtitles.addondomain.list'] = "List Addon Domains";
$lang['Cpe.subtitles.cron.create'] = "Create New Cron Job";
$lang['Cpe.subtitles.cron.list'] = "List Cron Jobs";
$lang['Cpe.subtitles.ssl.keys'] = "Private Keys";
$lang['Cpe.subtitles.ssl.crt'] = "SSL Certificates";
$lang['Cpe.subtitles.ssl.csr'] = "Generate SSL Certificate Signing Requests";

// Button labels
$lang['Cpe.button.save'] = "Save Changes";
$lang['Cpe.button.createaccount'] = "Create Account";
$lang['Cpe.button.changepassword'] = "Change Password";
$lang['Cpe.button.edit'] = "Edit";
$lang['Cpe.button.delete'] = "Delete";
$lang['Cpe.button.changequota'] = "Change Quota";
$lang['Cpe.button.generatepassword'] = "Generate Password";
$lang['Cpe.button.addusertodb'] = "Add User To Database";
$lang['Cpe.button.createuser'] = "Create User";
$lang['Cpe.button.createdb'] = "Create Database";
$lang['Cpe.button.createacct'] = "Create Account";
$lang['Cpe.button.createsubdomain'] = "Create Subdomain";
$lang['Cpe.button.createparkeddomain'] = "Create Parked Domain";
$lang['Cpe.button.createaddondomain'] = "Create Addon Domain";
$lang['Cpe.button.createcronjob'] = "Create Cron Job";
$lang['Cpe.button.redirectto'] = "Redirect To";
$lang['Cpe.button.view'] = "View";
$lang['Cpe.button.uploadkey'] = "Upload Your Private Keys";
$lang['Cpe.button.generatekey'] = "Generate Your Private Keys";
$lang['Cpe.button.generatecsr'] = "Generate SSL Certificate Signing Requests";
$lang['Cpe.button.generatecrt'] = "Generate Certificates";
$lang['Cpe.button.uploadcrt'] = "Upload Certificates";
$lang['Cpe.button.generate'] = "Generate";
$lang['Cpe.button.upload'] = "Upload";
$lang['Cpe.button.install'] = "Install";
$lang['Cpe.button.deleteacct'] = "Delete Account";
$lang['Cpe.button.destroyacct'] = "Delete Account and Files";
$lang['Cpe.button.deletedbuser'] = "Delete Database User";
$lang['Cpe.button.deletedb'] = "Delete Database";
$lang['Cpe.button.deleteemail'] = "Delete Email Account";
$lang['Cpe.button.deletecron'] = "Delete Cron Job";
$lang['Cpe.button.redirect'] = "Redirect Subdomain";
$lang['Cpe.button.deleteredirect'] = "Delete Redirection";
$lang['Cpe.button.deletesubdomain'] = "Delete Subdomain";
$lang['Cpe.button.deleteparked'] = "Delete Parked Domain";

// Labels
$lang['Cpe.label.username'] = "Username";
$lang['Cpe.label.password'] = "Password";
$lang['Cpe.label.passwordconfirm'] = "Confirm Password";
$lang['Cpe.label.directory'] = "Directory";
$lang['Cpe.label.quota'] = "Quota";
$lang['Cpe.label.privileges'] = "Privileges";
$lang['Cpe.label.dbname'] = "Database Name";
$lang['Cpe.label.subdomain'] = "Subdomain";
$lang['Cpe.label.docroot'] = "Document Root";
$lang['Cpe.label.domain'] = "Domain Name";
$lang['Cpe.label.domainuser'] = "Subdomain FTP/Username";
$lang['Cpe.label.cronline'] = "Cron Line ID";
$lang['Cpe.label.commonsettings'] = "Common Settings";
$lang['Cpe.label.minute'] = "Minute";
$lang['Cpe.label.hour'] = "Hour";
$lang['Cpe.label.day'] = "Day";
$lang['Cpe.label.month'] = "Month";
$lang['Cpe.label.weekday'] = "Week Day";
$lang['Cpe.label.command'] = "Command";
$lang['Cpe.label.or'] = "Or";
$lang['Cpe.label.company'] = "Company";
$lang['Cpe.label.companydiv'] = "Company Division";
$lang['Cpe.label.city'] = "City";
$lang['Cpe.label.state'] = "State";
$lang['Cpe.label.selectdom'] = "Select Domain";
$lang['Cpe.label.country'] = "Country";
$lang['Cpe.label.keyfile'] = "Key file associated with the certificate";
$lang['Cpe.label.crtfile'] = "Contents of the SSL certificate";
$lang['Cpe.label.passphrase'] = "Pass Phrase";
$lang['Cpe.label.account'] = "Account";
$lang['Cpe.label.keypaste'] = "Paste the key below";
$lang['Cpe.label.keysize'] = "Key Size";
$lang['Cpe.label.deletedbfromuser'] = "Delete Database from User";
$lang['Cpe.label.updateprivs'] = "Update Privileges";
$lang['Cpe.label.parkeddomain'] = "Parked Domain";
$lang['Cpe.label.pointsto'] = "points to";
$lang['Cpe.label.close'] = "Close";
$lang['Cpe.label.expire'] = "Expire Date";
$lang['Cpe.label.webquota'] = "Web Quota";
$lang['Cpe.label.bandwidth'] = "Bandwidth Limit (in MB)";
$lang['Cpe.label.defaultpackage'] = "Default package";
$lang['Cpe.label.default'] = "Default";
$lang['Cpe.label.package'] = "Package";
$lang['Cpe.label.shellacc'] = "Shell access";
$lang['Cpe.label.cgiacc'] = "CGI access";
$lang['Cpe.label.frontpageext'] = "Frontpage extensions";
$lang['Cpe.label.cptheme'] = "cPanel Theme";
$lang['Cpe.label.maxftp'] = "Max FTP Accounts";
$lang['Cpe.label.maxsql'] = "Max SQL Databases";
$lang['Cpe.label.maxsub'] = "Max Subdomains";
$lang['Cpe.label.maxpop'] = "Max Email Accounts";
$lang['Cpe.label.maxpark'] = "Max Parked Domains";
$lang['Cpe.label.maxaddon'] = "Max Addon Domains";
$lang['Cpe.label.type'] = "Type";
$lang['Cpe.label.acl'] = "Access Control List (if Reseller)";
$lang['Cpe.label.dedip'] = "Dedicated IP";
$lang['Cpe.label.keysnotavlb'] = "Keys and Certificate are not avilable";
$lang['Cpe.label.keysnotavlbhint'] = "Until Certificate is not installed, try to directly in cPanel";

// Tooltip
$lang['Cpe.tooltip.quota'] = "Provide a quota in megabytes, 0 means unlimited";
$lang['Cpe.tooltip.bandwidth'] = "Enter an monthly bandwidth limit in megabytes, 0 means unlimited";

// Cron specified
$lang['Cpe.cron.commonsettings'] = "-- Common Settings --";
$lang['Cpe.cron.everyminute'] = "Every minute (* * * * *)";
$lang['Cpe.cron.everyfive'] = "Every 5 minutes (*/5 * * * *)";
$lang['Cpe.cron.twicehour'] = "Twice an hour (0,30 * * * *)";
$lang['Cpe.cron.oncehour'] = "Once an hour (0 * * * *)";
$lang['Cpe.cron.twiceday'] = "Twice a day (0 0,12 * * *)";
$lang['Cpe.cron.onceday'] = "Once a day (0 0 * * *)";
$lang['Cpe.cron.onceweek'] = "Once a week (0 0 * * 0)";
$lang['Cpe.cron.firstand15th'] = "1st and 15th (0 0 1,15 * *)";
$lang['Cpe.cron.oncemonth'] = "Once a month (0 0 1 * *)";
$lang['Cpe.cron.onceyear'] = "Once a year (0 0 1 1 *)";
$lang['Cpe.cron.minute.everyminute'] = "Every minute (*)";
$lang['Cpe.cron.minute.otherminute'] = "Every other minute (*/2)";
$lang['Cpe.cron.minute.everyfive'] = "Every 5 minutes (*/5)";
$lang['Cpe.cron.minute.everyten'] = "Every 10 minutes (*/10)";
$lang['Cpe.cron.minute.every15'] = "Every 15 minutes (*/15)";
$lang['Cpe.cron.minute.every30'] = "Every 30 minutes (0,30)";
$lang['Cpe.cron.minute.minutes'] = "-- Minutes --";
$lang['Cpe.cron.minute.topofhour'] = "top of the hour (0)";
$lang['Cpe.cron.minute.quarterpast'] = "quarter past (15)";
$lang['Cpe.cron.minute.quartertil'] = "quarter til (45)";
$lang['Cpe.cron.minute.halfpast'] = "half past (30)";
$lang['Cpe.cron.hour.hours'] = "-- Hours --";
$lang['Cpe.cron.hour.midnight'] = "midnight";
$lang['Cpe.cron.hour.noon'] = "noon";

// Table Headers
$lang['Cpe.th.login'] = "Login";
$lang['Cpe.th.path'] = "Path";
$lang['Cpe.th.quota'] = "Quota";
$lang['Cpe.th.diskusage'] = "Disk Usage";
$lang['Cpe.th.actions'] = "Actions";
$lang['Cpe.th.ftpspecial'] = "Special FTP Accounts";
$lang['Cpe.th.username'] = "Username";
$lang['Cpe.th.size'] = "Size";
$lang['Cpe.th.dbname'] = "Database Name";
$lang['Cpe.th.linkeddbs'] = "Linked Databases";
$lang['Cpe.th.command'] = "Command";
$lang['Cpe.th.redirection'] = "Redirection";
$lang['Cpe.th.docroot'] = "Document Root";
$lang['Cpe.th.subdomain'] = "Subdomain";
$lang['Cpe.th.basedir'] = "Base Directory";
$lang['Cpe.th.minute'] = "Minute";
$lang['Cpe.th.hour'] = "Hour";
$lang['Cpe.th.day'] = "Day";
$lang['Cpe.th.month'] = "Month";
$lang['Cpe.th.weekday'] = "Week Day";
$lang['Cpe.th.command'] = "Command";
$lang['Cpe.th.domain'] = "Domain";
$lang['Cpe.th.expire'] = "Expire Date";

// Misc
$lang['Cpe.misc.genpasswordtext'] = "Your generated password is: ";
$lang['Cpe.misc.quotahint'] = "in MB (0 means unlimited)";
$lang['Cpe.misc.notredirected'] = "not redirected";
$lang['Cpe.misc.parkedhint'] = "<strong>Hint:</strong> Domains must be registered with a valid registrar before they can be parked. In addition, they will not be functional unless they are configured to point to your DNS servers.";
$lang['Cpe.misc.addonhint'] = "<strong>Hint:</strong> This feature must be enabled for your account before you can use it. Addon domains will not function unless the domain name is registered with a valid registrar and configured to point to the correct DNS servers.";
$lang['Cpe.misc.packageaddhint'] = "<h3>Attention!</h3><p>Everything provided above from the field \"Web Quota\", will replace the default options for the chosen package.</p>";
$lang['Cpe.misc.quotaholder'] = "Please enter a new quota";
$lang['Cpe.misc.success'] = "Changes saved successfully!";
$lang['Cpe.misc.loadertext'] = "Please wait while loading...";

// Module management
$lang['Cpe.add_module_row'] = "Add Server";
$lang['Cpe.add_module_group'] = "Add Server Group";
$lang['Cpe.manage.module_rows_title'] = "Servers";
$lang['Cpe.manage.module_groups_title'] = "Server Groups";
$lang['Cpe.manage.module_rows_heading.name'] = "Server Label";
$lang['Cpe.manage.module_rows_heading.hostname'] = "Hostname";
$lang['Cpe.manage.module_rows_heading.accounts'] = "Accounts";
$lang['Cpe.manage.module_rows_heading.options'] = "Options";
$lang['Cpe.manage.module_groups_heading.name'] = "Group Name";
$lang['Cpe.manage.module_groups_heading.servers'] = "Server Count";
$lang['Cpe.manage.module_groups_heading.options'] = "Options";
$lang['Cpe.manage.module_rows.count'] = "%1\$s / %2\$s"; // %1$s is the current number of accounts, %2$s is the total number of accounts available
$lang['Cpe.manage.module_rows.edit'] = "Edit";
$lang['Cpe.manage.module_groups.edit'] = "Edit";
$lang['Cpe.manage.module_rows.delete'] = "Delete";
$lang['Cpe.manage.module_groups.delete'] = "Delete";
$lang['Cpe.manage.module_rows.confirm_delete'] = "Are you sure you want to delete this server?";
$lang['Cpe.manage.module_groups.confirm_delete'] = "Are you sure you want to delete this server group?";
$lang['Cpe.manage.module_rows_no_results'] = "There are no servers.";
$lang['Cpe.manage.module_groups_no_results'] = "There are no server groups.";


$lang['Cpe.order_options.first'] = "First non-full server";

// Add row
$lang['Cpe.add_row.box_title'] = "Add cPanel Server";
$lang['Cpe.add_row.basic_title'] = "Basic Settings";
$lang['Cpe.add_row.name_servers_title'] = "Name Servers";
$lang['Cpe.add_row.notes_title'] = "Notes";
$lang['Cpe.add_row.name_server_btn'] = "Add Additional Name Server";
$lang['Cpe.add_row.name_server_col'] = "Name Server";
$lang['Cpe.add_row.name_server_host_col'] = "Hostname";
$lang['Cpe.add_row.name_server'] = "Name server %1\$s"; // %1$s is the name server number (e.g. 3)
$lang['Cpe.add_row.remove_name_server'] = "Remove";
$lang['Cpe.add_row.add_btn'] = "Add Server";

$lang['Cpe.edit_row.box_title'] = "Edit cPanel Server";
$lang['Cpe.edit_row.basic_title'] = "Basic Settings";
$lang['Cpe.edit_row.name_servers_title'] = "Name Servers";
$lang['Cpe.edit_row.notes_title'] = "Notes";
$lang['Cpe.edit_row.name_server_btn'] = "Add Additional Name Server";
$lang['Cpe.edit_row.name_server_col'] = "Name Server";
$lang['Cpe.edit_row.name_server_host_col'] = "Hostname";
$lang['Cpe.edit_row.name_server'] = "Name server %1\$s"; // %1$s is the name server number (e.g. 3)
$lang['Cpe.edit_row.remove_name_server'] = "Remove";
$lang['Cpe.edit_row.add_btn'] = "Edit Server";

$lang['Cpe.row_meta.server_name'] = "Server Label";
$lang['Cpe.row_meta.host_name'] = "Hostname";
$lang['Cpe.row_meta.user_name'] = "User Name";
$lang['Cpe.row_meta.key'] = "Remote Key";
$lang['Cpe.row_meta.password'] = "Password";
$lang['Cpe.row_meta.use_ssl'] = "Use SSL when connecting to the API (recommended)";
$lang['Cpe.row_meta.account_limit'] = "Account Limit";

// Package fields
$lang['Cpe.package_fields.type'] = "Account Type";
$lang['Cpe.package_fields.type_standard'] = "Standard";
$lang['Cpe.package_fields.type_reseller'] = "Reseller";
$lang['Cpe.package_fields.package'] = "cPanel Package";
$lang['Cpe.package_fields.acl'] = "Access Control List";
$lang['Cpe.package_fields.acl_default'] = "Default";

// Service fields
$lang['Cpe.service_field.domain'] = "Domain";
$lang['Cpe.service_field.username'] = "Username";
$lang['Cpe.service_field.password'] = "Password";
$lang['Cpe.service_field.confirm_password'] = "Confirm Password";

// Service management
$lang['Cpe.tab_stats.info_title'] = "Information";
$lang['Cpe.tab_stats.info_heading.field'] = "Field";
$lang['Cpe.tab_stats.info_heading.value'] = "Value";
$lang['Cpe.tab_stats.info.domain'] = "Domain";
$lang['Cpe.tab_stats.info.ip'] = "IP Address";
$lang['Cpe.tab_stats.bandwidth_title'] = "Bandwidth";
$lang['Cpe.tab_stats.bandwidth_heading.used'] = "Used";
$lang['Cpe.tab_stats.bandwidth_heading.limit'] = "Limit";
$lang['Cpe.tab_stats.bandwidth_value'] = "%1\$s MB"; // %1$s is the amount of bandwidth in MB
$lang['Cpe.tab_stats.bandwidth_unlimited'] = "unlimited";
$lang['Cpe.tab_stats.disk_title'] = "Disk";
$lang['Cpe.tab_stats.disk_heading.used'] = "Used";
$lang['Cpe.tab_stats.disk_heading.limit'] = "Limit";
$lang['Cpe.tab_stats.disk_value'] = "%1\$s MB"; // %1$s is the amount of disk in MB
$lang['Cpe.tab_stats.disk_unlimited'] = "unlimited";

// Client Service management
$lang['Cpe.tab_client_stats.info_title'] = "Information";
$lang['Cpe.tab_client_stats.info_heading.field'] = "Field";
$lang['Cpe.tab_client_stats.info_heading.value'] = "Value";
$lang['Cpe.tab_client_stats.info.domain'] = "Domain";
$lang['Cpe.tab_client_stats.info.ip'] = "IP Address";
$lang['Cpe.tab_client_stats.info.user'] = "Username";
$lang['Cpe.tab_client_stats.info.plan'] = "Current Package";
$lang['Cpe.tab_client_stats.info.theme'] = "Theme";
$lang['Cpe.tab_client_stats.info.maxftp'] = "Max FTP Accounts";
$lang['Cpe.tab_client_stats.info.maxsql'] = "Max SQL Databases";
$lang['Cpe.tab_client_stats.info.maxpop'] = "Max Email Accounts";
$lang['Cpe.tab_client_stats.info.maxsub'] = "Max Subdomains";
$lang['Cpe.tab_client_stats.info.maxparked'] = "Max Parked Domains";
$lang['Cpe.tab_client_stats.info.maxaddons'] = "Max Addons Domains";
$lang['Cpe.tab_client_stats.info.email'] = "Contact Email";
$lang['Cpe.tab_client_stats.info.startdate'] = "Creation Date";
$lang['Cpe.tab_client_stats.usage_title'] = "Account Resource Usage";
$lang['Cpe.tab_client_stats.bandwidth_title'] = "Bandwidth";
$lang['Cpe.tab_client_stats.bandwidth_heading.used'] = "Used (%1\$s MB)";
$lang['Cpe.tab_client_stats.bandwidth_heading.unused'] = "Free (%1\$s MB)";
$lang['Cpe.tab_client_stats.disk_title'] = "Disk";
$lang['Cpe.tab_client_stats.disk_heading.used'] = "Used (%1\$s MB)";
$lang['Cpe.tab_client_stats.disk_heading.unused'] = "Free (%1\$s MB)";

// Service info
$lang['Cpe.service_info.username'] = "Username";
$lang['Cpe.service_info.password'] = "Password";
$lang['Cpe.service_info.server'] = "Server";
$lang['Cpe.service_info.options'] = "Options";
$lang['Cpe.service_info.option_login'] = "Log in";

// Errors
$lang['Cpe.!error.server_name_valid'] = "You must enter a Server Label.";
$lang['Cpe.!error.host_name_valid'] = "The Hostname appears to be invalid.";
$lang['Cpe.!error.user_name_valid'] = "The User Name appears to be invalid.";
$lang['Cpe.!error.remote_key_valid'] = "The Remote Key appears to be invalid.";
$lang['Cpe.!error.remote_key_valid_connection'] = "A connection to the server could not be established. Please check to ensure that the Hostname, User Name, and Remote Key are correct.";
$lang['Cpe.!error.account_limit_valid'] = "Account Limit must be left blank (for unlimited accounts) or set to some integer value.";
$lang['Cpe.!error.name_servers_valid'] = "One or more of the name servers entered are invalid.";
$lang['Cpe.!error.name_servers_count'] = "You must define at least 2 name servers.";
$lang['Cpe.!error.meta[type].valid'] = "Account type must be either standard or reseller.";
$lang['Cpe.!error.servername.empty'] = "An server label is required";
$lang['Cpe.!error.hostname.empty'] = "A hostname is required";
$lang['Cpe.!error.username.empty'] = "An username is required";
$lang['Cpe.!error.meta[package].empty'] = "A cPanel Package is required.";
$lang['Cpe.!error.api.internal'] = "An internal error occurred, or the server did not respond to the request.";

$lang['Cpe.!error.cpanel_domain.format'] = "Please enter a valid domain name, e.g. domain.com.";
$lang['Cpe.!error.cpanel_domain.test'] = "Domain name can not start with 'test'.";
$lang['Cpe.!error.cpanel_username.format'] = "The username may contain only letters and numbers and may not start with a number.";
$lang['Cpe.!error.cpanel_username.test'] = "The username may not begin with 'test'.";
$lang['Cpe.!error.cpanel_username.length'] = "The username must be between 1 and 8 characters in length.";
$lang['Cpe.!error.cpanel_password.valid'] = "Password must be at least 8 characters in length.";
$lang['Cpe.!error.cpanel_password.matches'] = "Password and Confirm Password do not match.";
?>