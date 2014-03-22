/**
 * Blesta Javascript Library v0.1.0
 * jQuery extension
 * 
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
(function($) {
	
	$.fn.extend({	
		/**
		 * Performs an Ajax request
		 * 
		 * @param method {String} The method of the request "GET", "POST", etc.
		 * @param uri {String} The URI to request
		 * @param params {Object, String} Data to be sent to the server, automatically converted to a query string if not already a string
		 * @param on_success {Function} The callback function to execute on success
		 * @param on_error {Function} The callback function to execute on error
		 * @param o {Object} An object of option parameters
		 */
		blestaRequest: function(method, uri, params, on_success, on_error, o) {
			params = params ? params : null;
			on_success = on_success ? on_success : null;
			on_error = on_error ? on_error : null;
			
			var defaults = {
				type: method,
				url: uri,
				data: params,
				success: on_success,
				error: on_error
			};
			o = $.extend(defaults, o);
			
			$.ajax(o);
		},
		/**
		 * Performs an Ajax request to and fills the given container in the
		 * next table row (tr) of the currently selected object with the data
		 * returned by the request
		 *
		 * @param uri {String} The URI to request
		 * @param container {Object, String} The jquery object or selector to fill
		 */
		blestaUpdateRow: function(uri, container) {
			var element = $(this).next("tr");
			var id = element.attr("id").split("_")[1];
	
			// Only make the request if the element was just opened
			if (element.is(":visible"))
				return;
			
			$(this).blestaRequest("GET", uri, null, function(data) {
				// A specific element has been referenced, use it
				if (container instanceof jQuery)
					container.html(data);
				// We have a element referenced without context so use it within context of the selected object
				else
					$(container, element).html(data);
			});
		},
		/**
		 * Loads the given widget at the specified URL
		 * 
		 * @param o {Object} A set of options including:
		 * 	-container - The jquery object representing the container to load widgets within
		 * 	-url - The URL to request to fetch the list of widgets to load
		 */
		blestaLoadWidgets: function(o) {
			
			var defaults = {
				container: null,
				fetch_uri: null,
				update_uri: null,
				toggle_uri: null
			};
			o = $.extend(defaults, o);

			// Fetch a list of widgets to appear
			var widgets = null;
			if (o.fetch_uri) {
				$(this).each(function() {
					var params = {section: null};

					var container = o.container;
					if (container == null)
						container = $(this);
					
					$(this).blestaRequest("get", o.fetch_uri, params, function(widgets) {

						// For each widget, load its contents via ajax
						var num_widgets = widgets.length;
						for (var i in widgets) {
							$(this).blestaRequest("get", widgets[i].uri, null, function(data) {
								if (typeof innerShiv == "function")
									var temp = $(innerShiv(data.content, false, false));
								else
									var temp = $(data.content);
								
								// Append the widget to the page
								$(container).append(temp);
								
							}, null, {async: false});
						}

					}, null, {dataType: "json"});
				});
			}
		},
		/**
		 * Prepares modals to load a confirmation dialogs. Works for performing
		 * confirmation prior to executing a link.
		 *
		 * @param o {Object} A set of options including:
		 * 	-base_url The base URL for the modal popup. 'dialog/confirm/?message=' will be appended, with the
		 * 	message text coming from the anchor's 'rel' attribute
		 * 	-title - The title of the modal, defaults to $(this).text()
		 * 	-close - The text to display for the close link, defaults to 'Close'
		 * 	-submit - If true, will submit the form closest to the click element when confirmed, set to false to follow the click href instead
		 */
		blestaModalConfirm: function(o) {
			$(this).each(function() {
				var elem = $(this);
				$(this).blestaModal({
					title: o.title ? o.title : null,
					close: o.close ? o.close : 'Close',
					url: false,
					onRender: function(event, api) {
						$.ajax({
							url: o.base_url + 'dialog/confirm/',
							data: {message: elem.attr('rel')},
							success: function(data) {
								api.set('content.text', data)
								
								// If 'yes' is clicked, forward to where we wanted to go
								$('.btn_right.yes', api.elements.content).click(function() {
									if (o.submit) {
										api.hide();
										elem.closest("form").submit();
										return true;
									}
									window.location = elem.attr('href');
									return false;
								});
								// If 'no' is clicked, close the modal
								$('.btn_right.no', api.elements.content).click(function() {
									api.hide();
									return false;
								});
							}
						});
					}
				});
			});
		},
		/**
		 * Prepares modals to load. Content of the modal box will be loaded via AJAX
		 * from the URL specified by the "href" of the selector by default, or through
		 * the option parameter if set
		 *
		 * @param o {Object} A set of options including:
		 * 	-title - The title of the modal, defaults to $(this).text()
		 * 	-close - The text to display for the close link, defaults to 'Close'
		 * 	-onShow - The callback to execute when the modal is loaded
		 * 	-onContentUpdate - The callback to execute when the modal is updated
		 * 	-url - The URL to request via AJAX and display in the modal box, false if processing the AJAX request via a callback (to prevent this method from making the request)
		 * 	-data - A object representing the data to submit along with the request
		 * 	-ajax - A object representing the ajax request to make (overrides url and data)
		 * 	-text - The text/HTML to display in the modal, initially (will be replaced if URL set)
		 * 	-open - True to open the modal now, false to only open when selector is clicked
		 */
		blestaModal: function(o) {
			var defaults = {
				title: null,
				close: 'Close',
				onShow: function(event, api) {},
				onHide: function(event, api) {},
				onRender: function(event, api) {},
				url: null,
				data: {},
				text: '',
				ajax: {},
				min_width: 400,
				max_width: 400,
				open: false
			};
			o = $.extend(defaults, o);
			
			// Handle modal boxes
			$(this).each(function() {
				$(this).click(function(){return false;});

				if (o.url == null)
					o.url = o.url ? o.url : $(this).attr('href');

				if (o.url != false && $.isEmptyObject(o.ajax)) {
					o.ajax = {
						url: o.url,
						data: o.data
					}
				}

				$(this).qtip({
					content: {
						text: o.text ? o.text : " ",
						title: {
							text: o.title ? o.title : $(this).text(),
							button: o.close
						},
						ajax: o.ajax
					},
					events: {
						show: o.onShow,
						hide: o.onHide,
						render: function(event, api) {
							// Set min/max widths
							$(api.elements.tooltip).css({ 'min-width': o.min_width, 'max-width': o.max_width });
							
							$(this).draggable({
								containment: 'window',
								handle: api.elements.titlebar
							});
							
							o.onRender(event, api);
						}
					},
					position: {
						my: 'center', // ...at the center of the viewport
						at: 'center',
						target: $(window)
					},
					show: {
						event: 'click', // Show it on click...
						solo: true, // ...and hide all other tooltips...
						modal: true, // ...and make it modal
						ready: o.open
					},
					hide: false,
					style: 'ui-tooltip-light ui-tooltip-rounded ui-tooltip-dialogue'
				});
			});
		},
		/**
		 * Binds basic global GUI events
		 */
		blestaBindGlobalGuiEvents: function(o) {
			var defaults = {};
			o = $.extend(defaults, o);
			
			// Close error, success, alert messages
			this.blestaBindCloseMessage();
			
			// Submit forms using button link (keep persistent)
			$("a.submit").live('click', function() {
				$(this).closest("form").submit();
				return false;
			});
			
			// Show/hide expandable table data
			$("tr.expand", this).live("click", function() {
				$(this).next(".expand_details").toggle();
			});
			$("tr.expand a,tr.expand input", this).live("click", function(e) {
				e.stopPropagation();
			});
			
			// Handle tooltips
			$(this).blestaBindToolTips();
			
			// Date picker
			try {
				$.dpText.TEXT_CHOOSE_DATE = '';
				Date.format = 'yyyy-mm-dd';
				$('input.date').datePicker({startDate:'1996-01-01'});
			}
			catch (err) {
				// date picker not loaded
			}
			
			var History = window.History;
			
			// Bind to StateChange Event
			if (History.enabled) {
				History.Adapter.bind(window,'statechange',function(){ // Note: We are using statechange instead of popstate
					var State = History.getState(); // Note: We are using History.getState() instead of event.state

					$(this).blestaRequest("GET", State.url,  null,
						// Success response
						function(data) {
							// Replace the content in the replacer section of the box
							// with that provided from the response. If replacer is null,
							// replace previous state box with current data
							if (data.replacer == null) {
								$(State.data.box).html(data.content);
								$(State.data.box).blestaBindToolTips();
							}
							else {
								$(data.replacer, State.data.box).html(data.content);
								$(data.replacer, State.data.box).blestaBindToolTips();
							}
							if (data.message != null) {
								$('section.right_content').prepend(data.message);
							}
						},
						// Error response
						null,
						// Options
						{dataType:'json'}				
					);
				});
			}
			
			// Handle AJAX link requests in widgets
			$("a.ajax").live('click', function() {
				// Find parent box
				var parent_box = $(this).closest("div.content_section");
				var url = $(this).attr("href");
				
				// If not in a widget, continue as normal, must be in a widget
				if (!parent_box)
					return true;
				
				if (History.enabled) {
					try{
						History.pushState({box: "#" + parent_box.attr("id")}, $("title").text(), url);
					}
					catch (err) {
						return true; // couldn't handle pushState, so execute without it
					}
					return false;
				}
				return true;
			});
		},		
		/**
		 * Binds the close event action to success, error, and alert messages.
		 * Persists across future (i.e. ajax) created message boxes
		 */
		blestaBindCloseMessage: function() {
			// Close error, success, alert messages
			$(".message_box a.cross_btn", this).live('click', function() {
				$(this).parent().animate({
					opacity: 0,
					height: 'hide'
				}, 400, function() {
					// Hide the entire container if there are no elements visible within it
					if ($(".message_box").has("li:visible").length == 0)
						$(".message_box").hide();
				});
	
				return false;
			});
		},
		/**
		 * Binds tooltips to the given elements
		 */
		blestaBindToolTips: function() {
			// Handle tooltips
			$('span.tooltip', this).each(function() {
				$(this).qtip({
					position: {
						adjust: {method:"flip flip"},
						my: "bottom left",
						at: "top right",
						viewport: $(window)
					},
					content: $('div', this).html(),
					show: 'mouseover',
					hide: 'mouseout',
					style: {
						color: '#4b4b4b',
						background: '#fffed9',
						'font-size': 12,
						border: {
							width: 1,
							radius: 4,
							color: '#ebec80'
						},
						width: 250,
						name: 'cream',
						tip: 'bottomLeft'
					}
				});
			});
		},
		/**
		 * Creates or updates the given tag with the given attributes in the <head> of the document.
		 * If a tag in the head contains the ID as given in the attributes object, then that tag will be updated.
		 *
		 * @param tag {String} The name of the tag to create/update
		 * @param attributes {Object} A set of tag attributes
		 */
		blestaSetHeadTag: function(tag, attributes) {
			
			// Overwrite the attributes of a given stylesheet link
			if (attributes.id && $(attributes.id).length)
				$(attributes.id, $('head')).attr(attributes);
			// Append a new style sheet to the head
			else
				$('<' + tag + ' />', attributes).appendTo('head');
		}
	});
	
	/**
	 * Prepare standard GUI elements
	 */
	$(document).ready(function() {
		// Handle AJAX request failure due to unauthorization
		$(this).ajaxError(function(event, request, settings) {
			// Attempt reload due to 401 unauthorized response, let the system
			// handle the approrpriate redirect.
			if (request.status == 401) {
				window.location = window.location.href;
				/*
				#
				# TODO: display lightbox with login credentials so current page state is not lost
				#
				*/
			}
			// If an ajax request was attempted, but the resource does not support it, reload
			if (request.status == 406) {
				window.location = window.location.href;
			}
		});
			
		$(this).blestaBindGlobalGuiEvents();
	});
})(jQuery);