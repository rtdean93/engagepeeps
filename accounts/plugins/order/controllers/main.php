<?php
/**
 * Order System main controller
 *
 * @package blesta
 * @subpackage blesta.plugins.order.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Main extends OrderController {
	
	/**
	 * @var stdClass The order form
	 */
	private $order_form;
	/**
	 * @var object The order type object for the selected order form
	 */
	private $order_type;
	/**
	 * @var stdClass A stdClass object representing the client, null if not logged in
	 */
	private $client;
	/**
	 * @var string The string to prefix all custom client field IDs with
	 */
	private $custom_field_prefix = "custom_field";
	/**
	 * @var string The cart name used for this order form
	 */
	private $cart_name;
	
	/**
	 * Setup
	 */
	public function preAction() {
		
		if ($this->action == "complete") {
			// Disable CSRF for this request
			Configure::set("Blesta.verify_csrf_token", false);
		}
		
		parent::preAction();
		
		$this->uses(array("Order.OrderSettings", "Order.OrderForms", "Companies", "Clients", "Currencies", "PackageGroups", "Packages", "PluginManager", "Services"));

		// Redirect if this plugin is not installed for this company
		if (!$this->PluginManager->isInstalled("order", $this->company_id))
			$this->redirect($this->client_uri);

		$default_form = $this->OrderSettings->getSetting($this->company_id, "default_form");
		
		$order_label = null;
		if ($default_form)
			$order_label = $default_form->value;
		
		// Ensure that label always appears as a URI element
		if (isset($this->get[0]))
			$order_label = $this->get[0];
		elseif ($order_label)
			$this->redirect($this->base_uri . "plugin/order/main/index/" . $order_label);
		
		
		$this->order_form = $this->OrderForms->getByLabel($this->company_id, $order_label);
		
		// If the order form doesn't exist or is inactive, redirect the client away
		if (!$this->order_form || $this->order_form->status != "active")
			$this->redirect($this->base_uri);

		// Ready the session cart for this order form
		$this->cart_name = $this->company_id . "-" . $order_label;
		$this->components(array('SessionCart' => array($this->cart_name, $this->Session)));

		// If the order form requires SSL redirect to HTTPS
		if ($this->order_form->require_ssl  && !(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != "off"))
			$this->redirect(str_replace("http://", "https://", $this->base_url) . ltrim(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null, "/"));
		
		// Auto load language for the template
		Language::loadLang(array(Loader::fromCamelCase(get_class($this)), $this->order_form->type), null, PLUGINDIR . DS . "order" . DS . "views" . DS . "templates" . DS . $this->order_form->template . DS . "language" . DS);
		
		$this->view->setView(null, "templates" . DS . $this->order_form->template);
		if (($structure_dir = $this->getViewDir(null, true)) && substr($structure_dir, 0, 6) == "client")
			$this->structure->setDefaultView(APPDIR);
		$this->structure->setView(null, $structure_dir);

		$this->structure->set("outer_class", "order");
		$this->structure->set("custom_head",
			"<link href=\"" . Router::makeURI(str_replace("index.php/", "", WEBDIR) . $this->view->view_path . "views/" . $this->view->view) . "/css/order.css\" rel=\"stylesheet\" type=\"text/css\" />"
		);
		
		$this->view->setView(null, $this->getViewDir());
		
		$this->base_uri = WEBDIR;
		$this->view->base_uri = $this->base_uri;
		$this->structure->base_uri = $this->base_uri;
		
		$this->structure->set("page_title", $this->order_form->name);
		$this->structure->set("title", $this->order_form->name);
		
		// Load the order type
		$this->order_type = $this->loadOrderType($this->order_form->type);
		
		// Set the client info
		if ($this->Session->read("blesta_client_id")) {
			$this->client = $this->Clients->get($this->Session->read("blesta_client_id"));
			$this->view->set("client", $this->client);
			$this->structure->set("client", $this->client);
		}
		
		// Set the order form in the view and structure
		$this->view->set("order_form", $this->order_form);
		$this->structure->set("order_form", $this->order_form);
		
		// Set the currnecy to use for this order form
		$this->setCurrency();
	}
	
	/**
	 * List packages/select package
	 *
	 */
	public function index() {
		$this->helpers(array("TextParser"));
		
		$parser_syntax = "markdown";
		$this->TextParser->create($parser_syntax);
		
		// If pricing ID and group ID set, redirect to configure this item
		if (array_key_exists("pricing_id", $this->post) && array_key_exists("group_id", $this->post))
			$this->redirect($this->base_uri . "plugin/order/main/configure/" . $this->order_form->label . "/?" . http_build_query($this->post));

		// If the order type require pre config then redirect directly to preconfig
		if ($this->order_type->requiresPreConfig() && (!isset($this->get['skip']) || $this->get['skip'] == "false"))
			$this->redirect($this->base_uri . "plugin/order/main/preconfig/" . $this->order_form->label);
		
		$package_groups = array();
		$packages = array();
		$currency = $this->SessionCart->getData("currency");
		
		foreach ($this->order_form->groups as $group) {
			// Fetch the package group details
			$package_groups[$group->package_group_id] = $this->PackageGroups->get($group->package_group_id);
			
			// Fetch all packages for this group
			$packages[$group->package_group_id] = $this->Packages->getAllPackagesByGroup($group->package_group_id, "active");
			
			// Update package pricing for the selected currency
			$packages[$group->package_group_id] = $this->updatePackagePricing($packages[$group->package_group_id], $currency);
		}
		
		$this->set("periods", $this->getPricingPeriods());
		
		$this->set("package_groups", $package_groups);
		$this->set("packages", $packages);
		$this->set("parser_syntax", $parser_syntax);
		$this->set("currency", $currency);
		
		$this->setSummary();
	}
	
	/**
	 * Preconfiguration of the service
	 */
	public function preConfig() {
		
		// Only allow this step if the order type requires it
		if (!$this->order_type->requiresPreConfig())
			$this->redirect($this->base_uri . "plugin/order/");

		$this->get['base_uri'] = $this->base_uri;

		$content = $this->order_type->handleRequest($this->get, $this->post, $this->files);
		
		if (($errors = $this->order_type->errors())) {
			$this->setMessage("error", $errors, false, null, false);
		}
		
		$this->set("content", $content);
		$this->set("vars", (object)$this->post);
		
		// Render the view from the order type for this template
		$this->view->setView(null, "templates" . DS . $this->order_form->template . DS . "types" . DS . $this->order_form->type);
	}
	
	/**
	 * Configure the service
	 */
	public function configure() {
		$this->uses(array("ModuleManager"));
		
		// Obtain the pricing ID and package group ID of the item to order
		$item = null;
		
		// Flag whether the item came from the queue
		$queue_index = null;
		
		// Handle multiple items
		if (isset($this->post['pricing_id']) && is_array($this->post['pricing_id']) &&
			isset($this->post['group_id']) && is_array($this->post['group_id'])) {
			
			$vars = $this->post;
			unset($vars['pricing_id'], $vars['group_id']);
			
			foreach ($this->post['pricing_id'] as $key => $pricing_id) {
				$item = array(
					'pricing_id' => $pricing_id,
					'group_id' => $this->post['group_id'][$key]
				);
				
				if (isset($this->post['meta'][$key]))
					$item = array_merge($item, $this->post['meta'][$key]);
				$index = $this->SessionCart->enqueue($item);
				
				if ($queue_index === null)
					$queue_index = $index;
			}
			
			// Redirect to configure the first queued item
			$this->redirect($this->base_uri . "plugin/order/main/configure/" . $this->order_form->label . "/?q_item=" . $queue_index);
		}
		// Handle single item
		elseif (isset($this->get['pricing_id']) && isset($this->get['group_id']))
			$item = $this->SessionCart->prequeueItem($this->get);
		// Fetch the item from the cart if it already exists (allows editing existing item in cart)
		elseif (isset($this->get['item']))
			$item = $this->SessionCart->getItem($this->get['item']);
		// Fetch an item from the queue
		else {
			$queue_index = isset($this->get['q_item']) ? $this->get['q_item'] : 0;
			$item = $this->SessionCart->checkQueue($queue_index);
		}
		
		// Ensure we have an item
		if (!$item) {
			$this->flashMessage("error", Language::_("Main.!error.invalid_pricing_id", true), null, false);
			$this->redirect($this->base_uri . "plugin/order/main/index/" . $this->order_form->label);
		}
		
		// If not a valid item, redirect away and set error
		if (!$this->isValidItem($item)) {
			if ($queue_index)
				$this->SessionCart->dequeue($queue_index);
				
			$this->flashMessage("error", Language::_("Main.!error.invalid_pricing_id", true), null, false);
			$this->redirect($this->base_uri . "plugin/order/main/index/" . $this->order_form->label);
		}
		
		$currency = $this->SessionCart->getData("currency");
		
		$package = $this->updatePackagePricing($this->Packages->getByPricingId($item['pricing_id']), $currency);
		
		$module = $this->ModuleManager->initModule($package->module_id, $this->company_id);
		
		// Ensure a valid module
		if (!$module) {
			$this->flashMessage("error", Language::_("Main.!error.invalid_module", true), null, false);
			$this->redirect($this->base_uri . "plugin/order/main/index/" . $this->order_form->label);
		}
		
		$vars = (object)$item;
		// Attempt to add the item to the cart
		if (!empty($this->post)) {
			if (isset($this->post['qty']))
				$this->post['qty'] = (int)$this->post['qty'];
			
			// Verify fields look correct in order to proceed
			$this->Services->validateService($package, $this->post);
			if (($errors = $this->Services->errors())) {
				$this->setMessage("error", $errors, false, null, false);
			}
			else {
				// Add item to cart
				$item = array_merge($item, $this->post);
				unset($item['addon'], $item['submit']);
				
				if (isset($this->get['item'])) {
					$item_index = $this->get['item'];
					$this->SessionCart->updateItem($item_index, $item);
				}
				else {
					$item_index = $this->SessionCart->addItem($item);
					
					// If item came from the queue, dequeue
					if ($queue_index !== null)
						$this->SessionCart->dequeue($queue_index);
				}
				
				if (isset($this->post['addon'])) {
					// Remove any existing addons
					$this->removeAddons($item);
					
					$item = $this->SessionCart->getItem($item_index);
					
					$addon_queue = array();
					foreach ($this->post['addon'] as $addon_group_id => $addon) {
						// Queue addon items for configuration
						if (array_key_exists('pricing_id', $addon) && !empty($addon['pricing_id'])) {
							$addon_item = array(
								'pricing_id' => $addon['pricing_id'],
								'group_id' => $addon_group_id,
								'uuid' => uniqid()
							);
							$addon_queue[] = $addon_item['uuid'];
							$this->SessionCart->enqueue($addon_item);
						}
					}
					// Link the addons to this item
					$item['addons'] = $addon_queue;
					$this->SessionCart->updateItem($item_index, $item);
				}			
				
				// Process next queue item
				if (!$this->SessionCart->isEmptyQueue())
					$this->redirect($this->base_uri . "plugin/order/main/configure/" . $this->order_form->label . "?q_item=0");
				
				$uri = $this->order_type->redirectRequest($this->action, array('item_index' => $item_index));
				$this->redirect($uri != "" ? $uri : $this->base_uri . "plugin/order/main/review/" . $this->order_form->label);
			}
			
			$vars = (object)$this->post;
		}
		
		// Get all add-on groups (child "addon" groups for this package group)
		// And all packages in the group
		$addon_groups = $this->Packages->getAllAddonGroups($item['group_id']);
		
		foreach ($addon_groups as &$addon_group)
			$addon_group->packages = $this->updatePackagePricing($this->Packages->getAllPackagesByGroup($addon_group->id, "active"), $currency);
		
		$service_fields = $module->getClientAddFields($package, $vars);
		$fields = $service_fields->getFields();

		$html = $service_fields->getHtml();
		$module_name = $module->getName();
		
		// Get service name
		$service_name = $this->ModuleManager->moduleRpc($package->module_id, "getPackageServiceName", array($package, (array)$vars));

		$this->set("periods", $this->getPricingPeriods());
		$this->set(compact("vars", "item", "package", "addon_groups", "service_fields", "fields", "html", "module_name", "currency", "service_name"));
		
		$this->setSummary();
	}
	
	/**
	 * Applies a coupon
	 */
	public function applyCoupon() {
		if ($this->order_form->allow_coupons != "1")
			return false;
		
		$this->uses(array("Coupons", "Order.OrderOrders"));
		
		$coupon = null;
		if (!empty($this->post['coupon'])) {
			$packages = $this->OrderOrders->getPackagesFromItems($this->SessionCart->getData("items"));
			$coupon = $this->Coupons->getForPackages($this->post['coupon'], null, $packages);
			
			if ($coupon)
				$this->SessionCart->setData("coupon", $coupon->code);
		}
		
		if ($this->isAjax()) {
			$data = array();
			
			if ($coupon) {
				$data['coupon'] = $coupon;
				$data['success'] = $this->setMessage("message", Language::_("Main.!success.coupon_applied", true), true, null, false);
			}
			else
				$data['error'] = $this->setMessage("error", Language::_("Main.!error.coupon_applied", true), true, null, false);

			$this->outputAsJson($data);
		}
		else {
			
			if ($coupon)
				$this->flashMessage("message", Language::_("Main.!success.coupon_applied", true), null, false);
			else
				$this->flashMessage("error", Language::_("Main.!error.coupon_applied", true), null, false);
			
			$uri = $this->order_type->redirectRequest($this->action, array('coupon' => $coupon));
			$this->redirect($uri != "" ? $uri : $this->base_uri . "plugin/order/main/review/" . $this->order_form->label);
		}
		
		return false;
	}
	
	/**
	 * Remove an item from the cart
	 */
	public function remove() {
		$item = null;
		if (isset($this->get['item'])) {
			$item = $this->SessionCart->removeItem($this->get['item']);
			$this->removeAddons($item);
		}
		
		if (!$this->isAjax()) {
			$this->flashMessage("message", Language::_("Main.!success.item_removed", true), null, false);
			$uri = $this->order_type->redirectRequest($this->action, array('item' => $item));
			$this->redirect($uri != "" ? $uri : $this->base_uri . "plugin/order/main/review/" . $this->order_form->label);
		}
		
		return false;
	}
	
	/**
	 * Display cart/pay page
	 */
	public function review() {
		$this->helpers(array("TextParser"));
		
		$parser_syntax = "markdown";
		$this->TextParser->create($parser_syntax);
		
		$summary = $this->getSummary(true);
		
		$cart = $summary['cart'];
		$totals = $summary['totals'];
		$currency = $this->SessionCart->getData("currency");
		
		$items = array();
		if (isset($cart['items'])) {
			$items = $cart['items'];
		}
		
		$this->set("periods", $this->getPricingPeriods());
		$this->set(compact("cart", "totals", "items", "parser_syntax", "currency"));
		
		$this->setSummary();
	}
	
	/**
	 * Collect payment/create order
	 */
	public function checkout() {
		
		$this->uses(array("Accounts", "Contacts", "Transactions", "Payments", "Order.OrderOrders"));
		$vars = new stdClass();
		
		if ($this->SessionCart->isEmptyCart())
			$this->redirect($this->base_uri . "plugin/order/main/index/" . $this->order_form->label);
		
		// Require login to proceed
		if (!$this->client)
			$this->redirect($this->base_uri . "plugin/order/main/signup/" . $this->order_form->label);
		
		$currency = $this->SessionCart->getData("currency");
		
		$this->uses(array("GatewayManager"));
		
		// Fetch merchant gateway for this currency
		$merchant_gateway = $this->GatewayManager->getInstalledMerchant($this->company_id, $currency, null, true);
		
		// Verify $merchant_gateway is enabled for this order form, if not, unset
		// Set all nonmerchant gateways available
		$valid_merchant_gateway = false;
		$nonmerchant_gateways = array();
		foreach ($this->order_form->gateways as $gateway) {
			
			if ($merchant_gateway && $gateway->gateway_id == $merchant_gateway->id) {
				$valid_merchant_gateway = true;
				continue;
			}
			
			$gw = $this->GatewayManager->getInstalledNonmerchant($this->company_id, null, $gateway->gateway_id, $currency);
			if ($gw)
				$nonmerchant_gateways[] = $gw;
		}
		
		if (!$valid_merchant_gateway)
			$merchant_gateway = null;
		
		// Set the payment types allowed
		$transaction_types = $this->Transactions->transactionTypeNames();
		$payment_types = array();
		if ($merchant_gateway) {
			if ((in_array("MerchantAch", $merchant_gateway->info['interfaces'])
				|| in_array("MerchantAchOffsite", $merchant_gateway->info['interfaces']))
				&& $this->client->settings['payments_allowed_ach'] == "true") {
				$payment_types['ach'] = $transaction_types['ach'];
			}
			if ((in_array("MerchantCc", $merchant_gateway->info['interfaces'])
				|| in_array("MerchantCcOffsite", $merchant_gateway->info['interfaces']))
				&& $this->client->settings['payments_allowed_cc'] == "true") {
				$payment_types['cc'] = $transaction_types['cc'];
			}
		}
		
		$summary = $this->getSummary(true);

		if (!empty($this->post)) {
			// Verify agreement of terms and conditions
			if ($this->order_form->require_tos && !isset($this->post['agree_tos'])) {
				$this->setMessage("error", Language::_("Main.!error.invalid_agree_tos", true), false, null, false);
				$vars = (object)$this->post;
			}
			else {

				// Set order details
				$details = array(
					'client_id' => $this->client->id,
					'order_form_id' => $this->order_form->id,
					'currency' => $currency,
					'fraud_report' => $this->SessionCart->getData("fraud_report") ? serialize($this->SessionCart->getData("fraud_report")) : null,
					'fraud_status' => $this->SessionCart->getData("fraud_status"),
					'status' => ($this->order_form->manual_review || $this->SessionCart->getData("fraud_status") == "review" ? "pending" : "accepted"),
					'coupon' => $this->SessionCart->getData("coupon")
				);
				
				// Attempt to add the order
				$order = $this->OrderOrders->add($details, $summary['cart']['items']);
				
				// If any errors redirect back to checkout
				if (($errors = $this->OrderOrders->errors())) {
					$this->flashMessage("error", $errors, null, false);
					$this->redirect($this->base_uri . "plugin/order/main/checkout/" . $this->order_form->label);
				}
				
				// Fetch the invoice created for the order
				$invoice = $this->Invoices->get($order->invoice_id);

				$this->set("order", $order);
				$this->set("invoice", $invoice);
				
				$options = array();
				
				// Order recorded, empty the cart
				$this->SessionCart->emptyCart();
				
				$order_complete_uri = $this->base_uri . "plugin/order/main/complete/" . $this->order_form->label . "/" . $order->order_number;
				
				// No payment due, order is complete
				$total = $this->CurrencyFormat->cast($invoice->total, $invoice->currency);
				if ($total <= 0) {
					$this->redirect($order_complete_uri);
				}
				
				$this->post['pay_with'] = isset($this->post['pay_with']) ? $this->post['pay_with'] : null;
	
				if ($this->post['pay_with'] == "account" || $this->post['pay_with'] == "details") {

					$account_info = null;
					$account_id =  null;
					$type = null;
					
					// Set payment account details
					if ($this->post['pay_with'] == "account") {
						$temp = explode("_", $this->post['payment_account']);
						$type = $temp[0];
						$account_id = $temp[1];
					}
					// Set the new payment account details
					else {
						// Fetch the contact we're about to set the payment account for
						$this->post['contact_id'] = (isset($this->post['contact_id']) ? $this->post['contact_id'] : 0);
						$contact = $this->Contacts->get($this->post['contact_id']);
						
						if ($this->post['contact_id'] == "none" || !$contact || ($contact->client_id != $this->client->id))
							$this->post['contact_id'] = $this->client->contact_id;
						
						$type = $this->post['payment_type'];
						
						// Attempt to save the account, then set it as the account to use
						if (isset($this->post['save_details']) && $this->post['save_details'] == "true") {
							if ($type == "ach")
								$account_id = $this->Accounts->addAch($this->post);
							elseif ($type == "cc") {
								$this->post['expiration'] = (isset($this->post['expiration_year']) ? $this->post['expiration_year'] : "") . (isset($this->post['expiration_month']) ? $this->post['expiration_month'] : "");
								// Remove type, it will be automatically determined
								unset($this->post['type']);
								$account_id = $this->Accounts->addCc($this->post);
							}
						}
						else {
							$account_info = $this->post;
							
							if ($type == "ach") {
								$account_info['account_number'] = $account_info['account'];
								$account_info['routing_number'] = $account_info['routing'];
								$account_info['type'] = $account_info['type'];
							}
							elseif ($type == "cc") {
								$account_info['card_number'] = $account_info['number'];
								$account_info['card_exp'] = $account_info['expiration_year'] . $account_info['expiration_month'];
								$account_info['card_security_code'] = $account_info['security_code'];
							}
						}
						
					}
					
					// Set payment be applied to the invoice that was created
					$options['invoices'] = array($invoice->id => $total);
						
					$transaction = $this->Payments->processPayment($this->client->id, $type, $total, $currency, $account_info, $account_id, $options);
					
					// If payment error occurred, send client to pay invoice page (they're already logged in)
					if (($errors = $this->Payments->errors())) {
						$this->flashMessage("error", $errors, null, false);
						$this->redirect($this->client_uri . "pay/method/" . $invoice->id . "/");
					}
					
					// Display order complete
					$this->redirect($order_complete_uri);
				}
				// If paying with a nonmerchant account, display order complete page but with nonmerchant gateway button
				else {
					
					// Non-merchant gateway
					$this->uses(array("Contacts", "Countries", "Payments", "States"));
					
					// Fetch this contact
					$contact = $this->Contacts->get($this->client->contact_id);
					
					$contact_info = array(
						'id' => $this->client->contact_id,
						'client_id' => $this->client->id,
						'user_id' => $this->client->user_id,
						'contact_type' => $contact->contact_type_name,
						'contact_type_id' => $contact->contact_type_id,
						'first_name' => $this->client->first_name,
						'last_name' => $this->client->last_name,
						'title' => $contact->title,
						'company' => $this->client->company,
						'address1' => $this->client->address1,
						'address2' => $this->client->address2,
						'city' => $this->client->city,
						'zip' => $this->client->zip,
						'country' => (array)$this->Countries->get($this->client->country),
						'state' => (array)$this->States->get($this->client->country, $this->client->state)
					);
					
					$apply_amounts = array();
					// Set payment be applied to the invoice that was created
					$apply_amounts[$invoice->id] = $total;
					
					$options['description'] = Language::_("Main.checkout.description_invoice", true, $invoice->id_code);
					$options['return_url'] = rtrim($this->base_url, "/");
					
					foreach ($nonmerchant_gateways as $gateway) {
						if ($gateway->id == $this->post['pay_with']) {
							$this->set("gateway_name", $gateway->name);
							$options['return_url'] .= $order_complete_uri;
							break;
						}
					}
					
					$this->set("client", $this->client);
					$this->set("gateway_buttons", $this->Payments->getBuildProcess($contact_info, $total, $currency, $apply_amounts, $options, $this->post['pay_with']));
					
					$this->render("main_complete");
					return;
				}
			}
		}
		
		$payment_accounts = $this->getPaymentAccounts($merchant_gateway, $currency, $payment_types);
		
		$vars->country = (!empty($this->client->settings['country']) ? $this->client->settings['country'] : "");
		
		// Set the contact info partial to the view
		$this->setContactView($vars);
		// Set the CC info partial to the view
		$this->setCcView($vars, false, true);
		// Set the ACH info partial to the view
		$this->setAchView($vars, false, true);
		
		$cart = $summary['cart'];
		$totals = $summary['totals'];
		$this->set(compact("vars", "cart", "totals", "payment_accounts", "payment_types", "nonmerchant_gateways"));
		
		$this->setSummary();
	}
	
	/**
	 * Signup/login
	 */
	public function signup() {
		$vars = new stdClass();
		
		if ($this->order_type->requriesItemsOnSignup() && $this->SessionCart->isEmptyCart())
			$this->redirect($this->base_uri . "plugin/order/main/index/" . $this->order_form->label);
		
		$this->uses(array("Users", "Contacts", "Countries", "States"));
		$this->components(array("SettingsCollection"));
		$this->ArrayHelper = $this->DataStructure->create("Array");
		
		$order_settings = $this->ArrayHelper->numericToKey($this->OrderSettings->getSettings($this->company_id), "key", "value");

		// Check if captcha is required for signups
		if ($this->order_form->require_captcha == "1") {
			if (isset($order_settings['captcha'])) {
				if ($order_settings['captcha'] == "recaptcha") {
					$this->helpers(array('Recaptcha' => array($order_settings['recaptcha_pri_key'], $order_settings['recaptcha_pub_key'])));
					$this->set("captcha", $this->Recaptcha->getHtml("clean"));
				}
				elseif ($order_settings['captcha'] == "ayah") {
					$this->helpers(array('Areyouahuman' => array($order_settings['ayah_pub_key'], $order_settings['ayah_score_key'])));
					$this->set("captcha", $this->Areyouahuman->getPublisherHTML());
				}
			}
		}
		
		// Get company settings
		$company_settings = $this->SettingsCollection->fetchSettings($this->Companies, $this->company_id);
		
		// Set default currency, country, and language settings from this company
		$vars = new stdClass();
		$vars->country = $company_settings['country'];
		
		if (!empty($this->post)) {
			
			// Continue as the current client
			if ($this->client && $this->post['action'] == "continue")
				$this->redirect($this->base_uri . "plugin/order/main/checkout/" . $this->order_form->label);
			elseif ($this->post['action'] == "signup") {
				
				$errors = false;
				if (isset($this->Recaptcha)) {
					if (!$this->Recaptcha->verify($this->post['recaptcha_challenge_field'], $this->post['recaptcha_response_field']))
						$errors = array('captcha' => array('invalid' => Language::_("Main.!error.captcha.invalid", true)));
				}
				elseif (isset($this->Areyouahuman)) {
					if (!$this->Areyouahuman->scoreResult()) {
						$errors = array('captcha' => array('invalid' => Language::_("Main.!error.captcha.invalid", true)));
					}
				}
				
				if (!$errors) {
					// Set mandatory defaults
					$this->post['client_group_id'] = $this->order_form->client_group_id;
					
					$client_info = $this->post;
					$client_info['settings'] = array(
						'username_type' => $this->post['username_type'],
						'tax_id' => $this->post['tax_id'],
						'default_currency' => $this->SessionCart->getData("currency"),
						'language' => $company_settings['language']
					);
					$client_info['numbers'] = $this->ArrayHelper->keyToNumeric($client_info['numbers']);
					
					foreach ($this->post as $key => $value) {
						if (substr($key, 0, strlen($this->custom_field_prefix)) == $this->custom_field_prefix)
							$client_info['custom'][str_replace($this->custom_field_prefix, "", $key)] = $value;
					}
					
					// Fraud detection
					if (isset($order_settings['antifraud']) && $order_settings['antifraud'] != "") {
						$this->components(array("Order.Antifraud"));
						$fraud_detect = $this->Antifraud->create($order_settings['antifraud'], array($order_settings));
						$status = $fraud_detect->verify(array(
							'ip' => $_SERVER['REMOTE_ADDR'],
							'email' => $this->Html->ifSet($client_info['email']),
							'address1' => $this->Html->ifSet($client_info['address1']),
							'address2' => $this->Html->ifSet($client_info['address2']),
							'city' => $this->Html->ifSet($client_info['city']),
							'state' => $this->Html->ifSet($client_info['state']),
							'country' => $this->Html->ifSet($client_info['country']),
							'zip' => $this->Html->ifSet($client_info['zip']),
							'phone' => $this->Contacts->intlNumber($this->Html->ifSet($client_info['numbers'][0]['number']), $client_info['country'])
						));
						
						if (isset($fraud_detect->Input))
							$errors = $fraud_detect->Input->errors();
						
						$this->SessionCart->setData("fraud_report", $fraud_detect->fraudDetails());
						$this->SessionCart->setData("fraud_status", $status);
						
						if ($status == "review")
							$errors = false; // remove errors (if any)
					}
					
					if (!$errors) {
						// Create the client
						$this->client = $this->Clients->create($client_info);
						
						$errors = $this->Clients->errors();
					}
				}
				
				if ($errors) {
					$this->setMessage("error", $errors, false, null, false);
				}
				else {

					// Log the user into the newly created client account
					$login = array(
						'username' => $this->client->username,
						'password' => $client_info['new_password']
					);
					$user_id = $this->Users->login($this->Session, $login);
					
					if ($user_id) {
						$this->Session->write("blesta_company_id", $this->company_id);
						$this->Session->write("blesta_client_id", $this->client->id);
					}
					
					// Proceed to checkout
					$this->redirect($this->base_uri . "plugin/order/main/checkout/" . $this->order_form->label);
				}
			}
			
			$vars = (object)$this->post;
		}
		
		
		// Set custom fields to display
		$custom_fields = $this->Clients->getCustomFields($this->company_id, $this->order_form->client_group_id, array('show_client' => 1));
		
		// Swap key/value pairs for "Select" option custom fields (to display)
		foreach ($custom_fields as &$field) {
			if ($field->type == "select" && is_array($field->values))
				$field->values = array_flip($field->values);
		}
		
		$this->set("custom_field_prefix", $this->custom_field_prefix);
		$this->set("custom_fields", $custom_fields);
		
		$this->set("countries", $this->Form->collapseObjectArray($this->Countries->getList(), array("name", "alt_name"), "alpha2", " - "));
		$this->set("states", $this->Form->collapseObjectArray($this->States->getList($vars->country), "name", "code"));
		$this->set("currencies", $this->Currencies->getList($this->company_id));
		
		$this->set("vars", $vars);
		$this->set("client", $this->client);
		
		$this->setSummary();
	}
	
	/**
	 * Display order completion
	 */
	public function complete() {
		
		$this->uses(array("Order.OrderOrders", "Invoices"));
		
		if (!isset($this->get[1]) || !($order = $this->OrderOrders->getByNumber($this->get[1])) ||
			!isset($this->client) || $order->client_id != $this->client->id) {
			$this->redirect($this->base_uri . "plugin/order/main/index/" . $this->order_form->label);
		}
		
		$this->set("order", $order);
		$this->set("invoice", $this->Invoices->get($order->invoice_id));
		
		$this->setSummary();
	}
	
	/**
	 * Returns the order summary partial, or, if this is an AJAX request, outputs
	 * the order summary partial.
	 */
	public function summary() {
		$summary = $this->getSummary(true);
		$client = $this->client;
		$order_form = $this->order_form;
		$periods = $this->getPricingPeriods();
		$view = $this->partial("main_summary", compact("summary", "client", "order_form", "periods"), $this->getViewDir("main_summary"));
		
		if ($this->isAjax()) {
			echo $view;
			return false;
		}
		
		return $view;
	}
	
	/**
	 * Outputs the order summary in JSON format, or returns the order summary in
	 * native format
	 *
	 * @param boolean $return True to return the order summary, false to output the order summary in JSON format
	 * @return mixed Boolean values if outputing the order summary in JSON format, an array otherwise
	 */
	public function getSummary($return = false) {
		if (!isset($this->ModuleManager))
			$this->uses(array("ModuleManager"));
			
		$data = array();
		
		$client_id = null;
		$country = null;
		$state = null;
		
		if ($this->client)
			$client_id = $this->client->id;
		else {
			$user = $this->SessionCart->getItem("user");
			$country = isset($user['country']) ? $user['country'] : null;
			$state = isset($user['state']) ? $user['state'] : null;
		}
		
		$data['cart'] = $this->SessionCart->get();
		
		foreach ($data['cart']['items'] as $index => &$item) {
			
			$package = $this->Packages->getByPricingId($item['pricing_id']);
			
			// Get service name
			$service_name = $this->ModuleManager->moduleRpc($package->module_id, "getPackageServiceName", array($package, $item));
			$item['service_name'] = $service_name;
			$item['package_group_id'] = $item['group_id'];
			$item['index'] = $index;
			$item['package_options'] = $this->getPackageOptions($package, $item);
			
			// Set pricing
			$package = $this->updatePackagePricing($package, $this->SessionCart->getData("currency"));
			
			$item += array('package' => $package);
		}
		
		// Merge addons into each cart item
		foreach ($data['cart']['items'] as &$item) {
			$addons = $this->getAddons($item);
			unset($item['addons']);
			if (!empty($addons)) {
				$item['addons'] = array();
				foreach ($addons as $index) {
					$item['addons'][] = $data['cart']['items'][$index];
					unset($data['cart']['items'][$index]);
				}
			}
		}
		$data['cart']['items'] = array_values($data['cart']['items']);
		
		$data['totals'] = $this->calculateTotals($client_id, $country, $state);
		
		if (!$return) {
			$this->outputAsJson($data);
			return false;
		}
		return $data;
	}
	
	/**
	 * Set the summary into the current view and structure
	 */
	private function setSummary() {
		$order_summary = $this->summary();
		$this->view->set("order_summary", $order_summary);
		$this->structure->set("order_summary", $order_summary);
	}
	
	/**
	 * Executes the handleRequest() method on the order type object for the given order type,
	 * allowing the order type object to accept HTTP requests.
	 */
	public function handleRequest() {
		if ($this->order_type)
			$this->order_type->handleRequest($this->get, $this->post, $this->files);
		return false;
	}
	
	/**
	 * AJAX Fetch all states belonging to a given country (json encoded ajax request)
	 */
	public function getStates() {
		$this->uses(array("States"));
		// Prepend "all" option to state listing
		$states = array();
		if (isset($this->get[1]))
			$states = array_merge($states, (array)$this->Form->collapseObjectArray($this->States->getList($this->get[1]), "name", "code"));
		
		$this->outputAsJson($states);
		return false;
	}
	
	/**
	 * Fetch all packages options for the given pricing ID and optional service ID
	 */
	public function packageOptions() {
		if (!$this->isAjax())
			$this->redirect($this->base_uri . "plugin/order/");
		
		$this->uses(array("Packages", "PackageOptions"));
		
		$package = $this->Packages->getByPricingId($this->get[1]);
		
		if (!$package)
			return false;
		
		$pricing = null;
		foreach ($package->pricing as $pricing) {
			if ($pricing->id == $this->get[1])
				break;
		}

		$vars = (object)$this->get;
		
		$package_options = $this->PackageOptions->getFields($pricing->package_id, $pricing->term, $pricing->period, $pricing->currency, $vars);
		
		$this->set("fields", $package_options->getFields());
		
		echo $this->outputAsJson($this->view->fetch("main_packageoptions"));
		return false;
	}
	
	/**
	 * Fetches all package options for the given package. Uses the given item to select and set pricing
	 *
	 * @param stdClass $package The package to fetch options for
	 * @param array $item An array of item info
	 * @retrun stdClass A stdClass object representing the package option and its price
	 */
	private function getPackageOptions($package, $item) {
		if (!isset($this->PackageOptions))
			$this->uses(array("PackageOptions"));
			
		$package_options = $this->PackageOptions->getByPackageId($package->id);
		foreach ($package_options as $option) {
			if (isset($item['configoptions']) && array_key_exists($option->id, $item['configoptions'])) {
				foreach ($package->pricing as $pricing) {
					if ($pricing->id == $item['pricing_id'])
						break;
				}
				$option->price = $this->getOptionPrice($pricing, $option->id, $item['configoptions'][$option->id]);
				$option->selected_value_name = isset($option->values[0]->name) ? $option->values[0]->name : null;
				
				if (isset($option->values)) {
					foreach ($option->values as $value) {
						if ($value->value == $item['configoptions'][$option->id]) {
							$option->selected_value_name = $value->name;
							break;
						}
					}
				}
			}
		}
		unset($option);
		
		return $package_options;
	}
	
	/**
	 * Returns the pricing term for the given option ID and value
	 *
	 * @param stdClass $package_pricing The package pricing
	 * @param int $option_id The package option ID
	 * @param string $value The package option value
	 * @return mixed A stdClass object representing the price if found, false otherwise
	 */
	private function getOptionPrice($package_pricing, $option_id, $value) {
		if (!isset($this->PackageOptions))
			$this->uses(array("PackageOptions"));
			
		$singular_periods = $this->Packages->getPricingPeriods();
		$plural_periods = $this->Packages->getPricingPeriods(true);
		
		$value = $this->PackageOptions->getValue($option_id, $value);
		if ($value)
			return $this->PackageOptions->getValuePrice($value->id, $package_pricing->term, $package_pricing->period, $package_pricing->currency);
		
		return false;
	}
	
	/**
	 * Gets all payments the client can choose from
	 *
	 * @param stdClass $merchant_gateway A stdClass object representin the merchant gateway, false if no merchant gateway set
	 * @param string $currency The ISO 4217 currency code to pay in
	 * @param array $payment_types An array of allowed key/value payment types, where each key is the payment type and each value is the payment type name
	 */
	private function getPaymentAccounts($merchant_gateway, $currency, array $payment_types) {
		
		$this->uses(array("Accounts", "GatewayManager"));
		
		// Get ACH payment types
		$ach_types = $this->Accounts->getAchTypes();
		// Get CC payment types
		$cc_types = $this->Accounts->getCcTypes();
		
		// Set available payment accounts
		$payment_accounts = array();
		
		// Only allow CC payment accounts if enabled
		if (isset($payment_types['cc'])) {
			$cc = $this->Accounts->getAllCcByClient($this->client->id);
			
			$temp_cc_accounts = array();
			foreach ($cc as $account) {
				// Skip this payment account if it is expecting a different
				// merchant gateway, one is not available, or the payment
				// method is not supported by the gateway
				if (!$merchant_gateway ||
					($merchant_gateway &&
						(
							($account->gateway_id && $account->gateway_id != $merchant_gateway->id) ||
							($account->reference_id && !in_array("MerchantCcOffsite", $merchant_gateway->info['interfaces'])) ||
							(!$account->reference_id && !in_array("MerchantCc", $merchant_gateway->info['interfaces']))
						)
					))
					continue;

				$temp_cc_accounts["cc_" . $account->id] = Language::_("Main.getpaymentaccounts.account_name", true, $account->first_name, $account->last_name, $cc_types[$account->type], $account->last4);
			}
			
			// Add the CC payment accounts that can be used for this payment
			if (!empty($temp_cc_accounts)) {
				$payment_accounts[] = array('value'=>"optgroup", 'name'=>Language::_("Main.getpaymentaccounts.paymentaccount_cc", true));
				$payment_accounts = array_merge($payment_accounts, $temp_cc_accounts);
			}
			unset($temp_cc_accounts);
		}
		
		// Only allow ACH payment accounts if enabled
		if (isset($payment_types['ach'])) {
			$ach = $this->Accounts->getAllAchByClient($this->client->id);
			
			$temp_ach_accounts = array();
			foreach ($ach as $account) {
				// Skip this payment account if it is expecting a different
				// merchant gateway, one is not available, or the payment
				// method is not supported by the gateway
				if (!$merchant_gateway ||
					($merchant_gateway &&
						(
							($account->gateway_id && $account->gateway_id != $merchant_gateway->id) ||
							($account->reference_id && !in_array("MerchantAchOffsite", $merchant_gateway->info['interfaces'])) ||
							(!$account->reference_id && !in_array("MerchantAch", $merchant_gateway->info['interfaces']))
						)
					))
					continue;

				$temp_ach_accounts["ach_" . $account->id] = Language::_("Main.getpaymentaccounts.account_name", true, $account->first_name, $account->last_name, $ach_types[$account->type], $account->last4);
			}
			
			// Add the ACH payment accounts that can be used for this payment
			if (!empty($temp_ach_accounts)) {
				$payment_accounts[] = array('value'=>"optgroup", 'name'=>Language::_("Main.getpaymentaccounts.paymentaccount_ach", true));
				$payment_accounts = array_merge($payment_accounts, $temp_ach_accounts);
			}
			unset($temp_ach_accounts);
		}
		
		return $payment_accounts;
	}
	
	/**
	 * Calculates the totals for the cart
	 *
	 * @param int $client_id The ID of the client to fetch totals for in lieu of $country and $state
	 * @param string $country The ISO 3166-1 alpha2 country code to fetch tax rules on in lieu of $client_id
	 * @param string $state 3166-2 alpha-numeric subdivision code to fetch tax rules on in lieu of $client_id
	 * @return array An array of pricing information including:
	 * 	- subtotal The total before discount, fees, and tax
	 * 	- discount The total savings
	 * 	- fees An array of fees requested including:
	 * 		- setup The setup fee
	 * 		- cancel The cancel fee
	 * 	- total The total after discount, fees, but before tax
	 * 	- total_w_tax The total after discount, fees, and tax
	 * 	- tax The total tax
	 * @see Packages::calcLineTotals()
	 */
	private function calculateTotals($client_id = null, $country = null, $state = null) {
		if (!isset($this->Invoices))
			$this->uses(array("Invoices"));
		
		$cart = $this->SessionCart->get();

		$coupon = $this->SessionCart->getData("coupon");
		
		$tax_rules = null;
		if (!$client_id)
			$tax_rules = $this->Invoices->getTaxRulesByLocation($this->company_id, $country, $state);
		
		$vars = array();
		foreach ($cart['items'] as $item) {
			$vars[] = array(
				'pricing_id' => $item['pricing_id'],
				'qty' => isset($item['qty']) ? (int)$item['qty'] : 1,
				'fees' => array("setup"),
				'configoptions' => isset($item['configoptions']) ? $item['configoptions'] : array()
			);
		}
		
		return $this->Packages->calcLineTotals($client_id, $vars, $coupon, $tax_rules, $this->order_form->client_group_id, $this->SessionCart->getData("currency"));
	}

	/**
	 * Set all pricing periods
	 */
	private function getPricingPeriods() {
		// Set language for periods
		$periods = $this->Packages->getPricingPeriods();
		foreach ($this->Packages->getPricingPeriods(true) as $period => $lang)
			$periods[$period . "_plural"] = $lang;
		return $periods;
	}

	/**
	 * Load the order type required for this order form
	 *
	 * @param string $order_type The Order type for this order form
	 * @return object An OrderType object
	 */
	private function loadOrderType($order_type) {
		Loader::load(PLUGINDIR . "order" . DS . "lib" . DS . "order_type.php");
		Loader::load(PLUGINDIR . "order" . DS . "lib" . DS . "order_types" . DS . $order_type . DS . "order_type_" . $order_type . ".php");
		$class_name = Loader::toCamelCase("order_type_" . $order_type);
		
		$order_type = new $class_name();
		$order_type->setOrderForm($this->order_form);
		$order_type->setCart($this->SessionCart);
		$order_type->base_uri = $this->base_uri;
		
		return $order_type;
	}
	
	/**
	 * Sets the ISO 4217 currency code to use for the order form
	 */
	private function setCurrency() {
		
		// If user attempts to change currency, verify it can be set
		// Currency can only be changed if cart is empty
		if (isset($this->get['currency']) && $this->SessionCart->isEmptyCart()) {
			foreach ($this->order_form->currencies as $currency) {
				if ($currency->currency == $this->get['currency']) {
					$this->SessionCart->setData("currency", $currency->currency);
					break;
				}
			}
		}
		
		// If no currency for this session, default to the company's default currency,
		// or the first available currency for the order form
		if ($this->SessionCart->getData("currency") == null) {
			$temp = $this->Companies->getSetting($this->company_id, "default_currency");
			if ($temp)
				$company_currency = $temp->value;
				
			foreach ($this->order_form->currencies as $currency) {
				if ($currency->currency == $company_currency) {
					$this->SessionCart->setData("currency", $currency->currency);
					break;
				}
			}
			
			if ($this->SessionCart->getData("currency") == null && isset($this->order_form->currencies[0]->currency))
				$this->SessionCart->setData("currency", $this->order_form->currencies[0]->currency);
		}
	}
	
	/**
	 * Updates all given packages with pricing for the given currency. Evaluates
	 * the company setting to determine if package pricing can be converted based
	 * on currency conversion, or whether the package can only be offered in the
	 * configured currency. If the package pricing can not be converted automatically
	 * it will be removed.
	 *
	 * @param mixed An array of stdClass objects each representing a package, or a stdClass object representing a package
	 * @param string $currency The ISO 4217 currency code to update to
	 * @return array An array of stdClass objects each representing a package
	 */
	private function updatePackagePricing($packages, $currency) {
		$multi_currency_pricing = $this->Companies->getSetting($this->company_id, "multi_currency_pricing");
		$allow_conversion = true;
		
		if ($multi_currency_pricing->value == "package")
			$allow_conversion = false;
			
		if (is_object($packages))
			$packages = $this->convertPackagePrice($packages, $currency, $allow_conversion);
		else {
			foreach ($packages as &$package) {
				$package = $this->convertPackagePrice($package, $currency, $allow_conversion);
			}
		}
		
		return $packages;
	}
	
	/**
	 * Convert pricing for the given package and currency
	 *
	 * @param stdClass $package A stdClass object representing a package
	 * @param string $currency The ISO 4217 currency code to update to
	 * @param boolean $allow_conversion True to allow conversion, false otherwise
	 * @return stdClass A stdClass object representing a package
	 */
	private function convertPackagePrice($package, $currency, $allow_conversion) {
		$all_pricing = array();
		foreach ($package->pricing as $pricing) {
			
			$converted = false;
			if ($pricing->currency != $currency)
				$converted = true;
			
			$pricing = $this->Packages->convertPricing($pricing, $currency, $allow_conversion);
			if ($pricing) {
				if (!$converted) {
					$all_pricing[$pricing->term . $pricing->period] = $pricing;
				}
				elseif (!array_key_exists($pricing->term . $pricing->period, $all_pricing)) {
					$all_pricing[$pricing->term . $pricing->period] = $pricing;
				}
			}
		}
		
		$package->pricing = array_values($all_pricing);
		return $package;
	}
	
	/**
	 * Removes all addon items for a given item
	 *
	 * @param array $item An item in the form of:
	 * 	- pricing_id The ID of the package pricing item to add
	 * 	- group_id The ID of the package group the item belongs to
	 * 	- addons An array of addons containing:
	 * 		- uuid The unique ID for each addon
	 */
	private function removeAddons($item) {
		$indexes = $this->getAddons($item);
		$this->SessionCart->removeItems($indexes);
	}
	
	/**
	 * Fetches the cart index for each addon item associated with this item
	 *
	 * @param array $item An item in the form of:
	 * 	- pricing_id The ID of the package pricing item to add
	 * 	- group_id The ID of the package group the item belongs to
	 * @return array An array of cart item indexes where the addon items live
	 */
	private function getAddons($item) {
		if (isset($item['addons'])) {
			$indexes = array();
			$cart = $this->SessionCart->get();
			foreach ($item['addons'] as $uuid) {
				foreach ($cart['items'] as $index => $cart_item) {
					if (isset($cart_item['uuid']) && $uuid == $cart_item['uuid']) {
						$indexes[] = $index;
						break;
					}
				}
			}
			
			return $indexes;
		}
		return array();
	}
	
	/**
	 * Verifies if the given item is valid for this order form
	 *
	 * @param array $item An item in the form of:
	 * 	- pricing_id The ID of the package pricing item to add
	 * 	- group_id The ID of the package group the item belongs to
	 * @return boolean True if the item is valid for this order form, false otherwise
	 */
	private function isValidItem($item) {
		if (!isset($item['pricing_id']) || !isset($item['group_id']))
			return false;
		
		$currency = $this->SessionCart->getData("currency");
		$multi_currency_pricing = $this->Companies->getSetting($this->company_id, "multi_currency_pricing");
		$allow_conversion = true;
		
		if ($multi_currency_pricing->value == "package")
			$allow_conversion = false;
			
		$item_group = $this->PackageGroups->get($item['group_id']);

		$valid_groups = $this->order_type->getGroupIds();

		foreach ($valid_groups as $group_id) {
			if ($item_group->type == "addon") {
				foreach ($item_group->parents as $parent_group) {
					if ($parent_group->id == $group_id)
						return true;
				}
			}
			elseif ($item_group->id == $group_id) {
				$packages = $this->Packages->getAllPackagesByGroup($group_id,  "active");
				
				foreach ($packages as $package) {
					foreach ($package->pricing as $pricing) {
						if ($pricing->id == $item['pricing_id'] && $this->Packages->convertPricing($pricing, $currency, $allow_conversion)) {
							return true;
						}
					}
				}
				break;
			}
		}

		return false;
	}
	
	/**
	 * Sets the contact partial view
	 * @see ClientPay::index()
	 *
	 * @param stdClass $vars The input vars object for use in the view
	 * @param boolean $edit True if this is an edit, false otherwise
	 */
	private function setContactView(stdClass $vars, $edit=false) {
		$this->uses(array("Contacts", "Countries", "States"));
		
		$contacts = array();
		
		if (!$edit) {
			// Set an option for no contact
			$no_contact = array(
				(object)array(
					'id'=>"none",
					'first_name'=>Language::_("Main.setcontactview.text_none", true),
					'last_name'=>""
				)
			);
			
			// Set all contacts whose info can be prepopulated (primary or billing only)
			$contacts = array_merge($this->Contacts->getAll($this->client->id, "primary"), $this->Contacts->getAll($this->client->id, "billing"));
			$contacts = array_merge($no_contact, $contacts);
		}
		
		// Set partial for contact info
		$contact_info = array(
			'js_contacts' => $this->Json->encode($contacts),
			'contacts' => $this->Form->collapseObjectArray($contacts, array("first_name", "last_name"), "id", " "),
			'countries' => $this->Form->collapseObjectArray($this->Countries->getList(), array("name", "alt_name"), "alpha2", " - "),
			'states' => $this->Form->collapseObjectArray($this->States->getList($vars->country), "name", "code"),
			'vars' => $vars,
			'edit' => $edit,
			'order_form' => $this->order_form
		);
		
		$this->set("contact_info", $this->partial("main_contact_info", $contact_info));
	}
	
	/**
	 * Sets the ACH partial view
	 * @see ClientPay::index()
	 *
	 * @param stdClass $vars The input vars object for use in the view
	 * @param boolean $edit True if this is an edit, false otherwise
	 * @param boolean $save_account True to offer an option to save these payment details, false otherwise
	 */
	private function setAchView(stdClass $vars, $edit=false, $save_account=false) {
		// Set partial for ACH info
		$ach_info = array(
			'types' => $this->Accounts->getAchTypes(),
			'vars' => $vars,
			'edit' => $edit,
			'client' => $this->client,
			'save_account' => $save_account
		);
		
		$this->set("ach_info", $this->partial("main_ach_info", $ach_info));
	}
	
	/**
	 * Sets the CC partial view
	 * @see ClientPay::index()
	 *
	 * @param stdClass $vars The input vars object for use in the view
	 * @param boolean $edit True if this is an edit, false otherwise
	 * @param boolean $save_account True to offer an option to save these payment details, false otherwise
	 */
	private function setCcView(stdClass $vars, $edit=false, $save_account=false) {
		// Set available credit card expiration dates
		$expiration = array(
			// Get months with full name (e.g. "January")
			'months' => $this->Date->getMonths(1, 12, "m", "F"),
			// Sets years from the current year to 10 years in the future
			'years' => $this->Date->getYears(date("Y"), date("Y") + 10, "Y", "Y")
		);
		
		// Set partial for CC info
		$cc_info = array(
			'expiration' => $expiration,
			'vars' => $vars,
			'edit' => $edit,
			'client' => $this->client,
			'save_account' => $save_account
		);
		
		$this->set("cc_info", $this->partial("main_cc_info", $cc_info));
	}
	
	/**
	 * Set view directories. Allows order template type views to override order template views.
	 * Also allows order templates to use own structure view.
	 */
	private function getViewDir($view = null, $structure = false) {
		
		$base_dir = PLUGINDIR . "order" . DS . "views" . DS;
		
		if ($structure) {
			if (file_exists($base_dir . "templates" . DS . $this->order_form->template . DS . "types" . DS . $this->order_form->type . DS . $this->structure_view . ".pdt"))
				return "templates" . DS . $this->order_form->template . DS . "types" . DS . $this->order_form->type;
			elseif (file_exists($base_dir . "templates" . DS . $this->order_form->template . DS . $this->structure_view . ".pdt"))
				return "templates" . DS . $this->order_form->template;
			
			return "client" . DS . $this->layout;
		}
		else {
			if ($view == null)
				$view = $this->view->file;
			// Use the view file set for this view (if set)
			if (!$view) {
				// Auto-load the view file. These have the format of:
				// [controller_name]_[method_name] for all non-index methods
				$view = Loader::fromCamelCase(get_class($this)) .
					($this->action != null && $this->action != "index" ? "_" . strtolower($this->action) : "");
			}
			
			$template_type = "templates" . DS . $this->order_form->template . DS . "types" . DS . $this->order_form->type;
			if (file_exists($base_dir . $template_type . DS . $view . ".pdt")) {
				return $template_type;
			}
			
			return "templates" . DS . $this->order_form->template;
		}
	}
}
?>