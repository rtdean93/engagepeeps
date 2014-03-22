<?php
// Index page content
Configure::set("Cms.index.content", '<div class="description">
	<p>Thank you for installing Blesta! This is the client portal page and is a plugin. You can change this page through a WYSIWYG editor by clicking to manage the <a href="{admin_url}settings/company/plugins/manage/{plugins.cms.id}">portal plugin</a> in the staff area.</p>
	<p>To login to the staff area, visit <a href="{admin_url}login/">{admin_url}login/</a>.</p>
	<p>Installing the Order System, Support Manager, and Download Manager plugins will add additional icons to this portal.</p>
</div>
<div class="half section">
	<div class="icon account">
		<h1><a href="{client_url}login/">My Account</a></h1>
		<p>Have an account with us? You can login here to manage your account.</p>
	</div>
</div>{% if plugins.support_manager %}<div class="half section">
	<div class="icon support_manager">
		<h1><a href="{client_url}plugin/support_manager/client_tickets/add/">Support</a></h1>
		<p>Need help? You can open a trouble ticket here.</p>
	</div>
</div>
<div class="clear">&nbsp;</div>{% endif %}{% if plugins.order %}<div class="half section">
	<div class="icon order">
		<h1><a href="{blesta_url}order/">Order</a></h1>
		<p>Visit the order form to sign up and purchase new products and services.</p>
	</div>
</div>{% endif %}{% if plugins.download_manager %}<div class="half section">
	<div class="icon download_manager">
		<h1><a href="{client_url}plugin/download_manager/">Downloads</a></h1>
		<p>Visit the download area. You may need to be logged in to access certain downloads.</p>
	</div>
</div>{% endif %}');
?>