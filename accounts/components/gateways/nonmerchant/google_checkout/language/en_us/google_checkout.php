<?php
// Gateway name
$lang['GoogleCheckout.name'] = "Google Checkout";


// Settings
$lang['GoogleCheckout.callback_url'] = "Callback URL";
$lang['GoogleCheckout.callback_url_note'] = "You must set this URL as your API Callback URL under Settings -> Integration in your Google Checkout account.";
$lang['GoogleCheckout.merchant_id'] = "Merchant ID";
$lang['GoogleCheckout.merchant_key'] = "Merchant Key";
$lang['GoogleCheckout.callback_key'] = "Callback Key";
$lang['GoogleCheckout.dev_mode'] = "Developer Mode";
$lang['GoogleCheckout.dev_mode_note'] = "Enabling this option will post transactions to Google Checkout's Sandbox environment. Only enable this option if you are testing with a Google Checkout Sandbox account.";


// Text
$lang['GoogleCheckout.void.notes_reason'] = "Order voided.";
$lang['GoogleCheckout.refund.notes_reason'] = "Order refunded.";


// Errors
$lang['GoogleCheckout.!error.merchant_id.empty'] = "Please enter your Merchant ID.";
$lang['GoogleCheckout.!error.merchant_key.empty'] = "Please enter your Merchant Key.";
$lang['GoogleCheckout.!error.callback_key.empty'] = "Please enter a Callback Key.";

$lang['GoogleCheckout.!error.unmodifiable.response'] = "The gateway returned a response that should have no effect on the referenced transaction.";
$lang['GoogleCheckout.!error.processing.void'] = "Google Checkout is currently processing the void request, so the transaction will be marked void once confirmed by Google Checkout.";
$lang['GoogleCheckout.!error.processing.refund'] = "Google Checkout is currently processing the refund request, so the transaction will be marked refunded once confirmed by Google Checkout.";
$lang['GoogleCheckout.!error.refund.required'] = "The transaction must be refunded before it may be voided.";
?>