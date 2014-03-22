<?php
/**
 * Admin Main
 * 
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminMain extends AppController {
	
	/**
	 * Calendar event class for an event created by this staff member that is not shared
	 */
	private $calendar_staff_event_unshared = "event_unshared";
	/**
	 * Calendar event class for an event created by this staff member that is shared
	 */
	private $calendar_staff_event_shared = "event_shared";
	/**
	 * Calendar event class for an event created by another staff member
	 */
	private $calendar_other_events = "event_other";
	
	/**
	 * Bootstrap
	 */
	public function preAction() {
		// Don't validate CSRF on updatequicklink requests
		if (strtolower($this->action) == "updatequicklink")
			Configure::set("Blesta.verify_csrf_token", false);
			
		parent::preAction();
		
		// Require login
		$this->requireLogin();
		
		// Set language
		Language::loadLang("admin_main");
		
		$this->uses(array("Staff"));
	}

	/**
	 * Admin Home page dashboard
	 */
	public function index() {
		
		$this->set("quicklinks", $this->Staff->getQuickLinks($this->Session->read("blesta_staff_id"), $this->company_id));
		
		$layout = $this->Staff->getSetting($this->Session->read("blesta_staff_id"), "dashboard_layout", $this->company_id);
		$layout = ($layout ? $layout->value : "layout1");
		
		// Set the layout
		$this->set("content", $this->partial("admin_main_" . $layout));
		
		// Set day of the week names and abbreviations for the calendar
		$days_of_the_week = $this->getDaysOfWeek();
		$months = $this->getMonths();
		$this->set("calendar_days", $this->Json->encode($days_of_the_week['days']));
		$this->set("calendar_abbr_days", $this->Json->encode($days_of_the_week['abbr_days']));
		$this->set("calendar_months", $this->Json->encode($months['months']));
		$this->set("calendar_abbr_months", $this->Json->encode($months['abbr_months']));
		$this->set("calendar_start_day", $this->Json->encode($days_of_the_week['calendar_begins']));
		$this->set("calendar_start_month", ($this->Date->format("n") - 1)); // Calendar month range [0, 11]
		$this->set("calendar_start_year", $this->Date->format("Y"));
		
		$this->Javascript->setFile("date.min.js");
		$this->Javascript->setFile("jquery.datePicker.min.js");
	}
	
	/**
	 * Admin Calendar
	 */
	public function calendar() {
		$this->uses(array("CalendarEvents"));
		
		$date_section = "month";
		$date = date("c");
		
		// Check for any date parameters that may be set
		if (isset($this->get[0])) {
			$date_section = $this->get[0];
			
			// Set the view (month/week/day)
			$this->set("show_view", $this->swapCalendarViewName($date_section));
			
			// Set date if given (i.e. Y-m-d or Y-m)
			if (isset($this->get[1])) {
				$date = $this->CalendarEvents->dateToUtc($this->get[1] . "Z");
			}
		}
		
		$split_date = explode("-", $date);
		
		if (isset($split_date[0]))
			$this->set("show_year", substr($date, 0, 4));
		// Month is expected to be in the range [0,11], so decrease it
		if (isset($split_date[1]))
			$this->set("show_month", substr($date, 5, 2)-1);
		if (isset($split_date[2]))
			$this->set("show_day", (int)substr($date, 8, 2));
		
		$events = array();
		
		// A date section is set, get events in this range
		$day_offset = " +1 day";
		switch ($date_section) {
			case "week":
				$week = $this->getWeek($date);
				$start_date = $week['start_date'];
				$end_date = $week['end_date'];
				break;
			case "day":
				// From this day to the beginning of the next day
				$start_date = $this->Date->cast($date, "c");
				$end_date = $this->Date->cast(strtotime($date . $day_offset), "c");
				break;
			case "month":
			default:
				$start_date = $this->Date->cast($date, "Y-m-01 00:00:00\TP");
				$end_date = $this->Date->cast($date, "Y-m-t 23:59:59\TP");
				break;
		}
		
		$staff_id = $this->Session->read("blesta_staff_id");
		
		$this->set("staff_id", $staff_id);
		
		// Set day of the week names and abbreviations for the calendar
		$days_of_the_week = $this->getDaysOfWeek();
		$months = $this->getMonths();
		$this->set("calendar_days", $this->Json->encode($days_of_the_week['days']));
		$this->set("calendar_abbr_days", $this->Json->encode($days_of_the_week['abbr_days']));
		$this->set("calendar_months", $this->Json->encode($months['months']));
		$this->set("calendar_abbr_months", $this->Json->encode($months['abbr_months']));
		$this->set("calendar_start_day", $this->Json->encode($days_of_the_week['calendar_begins']));
		$this->set("calendar_start_month", ($this->Date->format("n") - 1)); // Calendar month range [0, 11]
		$this->set("calendar_start_year", $this->Date->format("Y"));
		$this->set("calendar_time_interval", Configure::get("Blesta.calendar_time_interval"));
		
		// Load calendar
		$this->Javascript->setFile("fullcalendar.js", "head", VENDORWEBDIR . "fullcalendar/fullcalendar/");
		//$this->Javascript->setFile("jquery-ui-1.8.11.custom.min.js", "head", VENDORWEBDIR . "fullcalendar/jquery/");
		$this->structure->set("calendar_css", VENDORWEBDIR . "fullcalendar/fullcalendar/fullcalendar.css");
		
		// Load date picker so that calendar events can be added/edited via javascript
		$this->Javascript->setFile("date.min.js");
		$this->Javascript->setFile("jquery.datePicker.min.js");
	}
	
	/**
	 * Retrieves the reciprocating view name between the name of the fullCalendar view
	 * and the friendly name our controllers expect
	 *
	 * @param string $view The view name
	 * @return string The equivalent name 
	 */
	private function swapCalendarViewName($view) {
		$view_name = "month";
		switch ($view) {
			case "agendaWeek":
				$view_name = "week";
				break;
			case "week":
				$view_name = "agendaWeek";
				break;
			case "agendaDay":
				$view_name = "day";
				break;
			case "day":
				$view_name = "agendaDay";
				break;
			case "month":
			default:
				break;
		}
		return $view_name;
	}
	
	/**
	 * Retrieves a date range for a given week
	 *
	 * @param string $date A date within the week whose date range to retrieve
	 */
	private function getWeek($date) {
		// Get the company's first day of the week
		$calendar_begins = $this->SettingsCollection->fetchSetting(null, $this->company_id, "calendar_begins");
		$day_offset = "";
		$date_format = "c";
		
		// Get the week
		$year = $this->Date->cast($date, "Y");
		$week = $this->Date->cast($date, "W"); // week starts on monday
		
		// Determine the day offset for the week (monday vs sunday)
		if (isset($calendar_begins['value'])) {
			switch ($calendar_begins['value']) {
				case "monday":
					break;
				case "sunday":
				default:
					// Since monday is the default start of the week, create an offset for sunday
					$day_offset = " -1 day";
					$week = $this->Date->cast(strtotime($date . " +1 day"), "W"); // week starts on sunday
			}
		}	
		
		// Set the start/end dates of the week
		$beginning_of_week = $this->Date->cast(strtotime($year . "-W" . $week . $day_offset), $date_format);
		$week_dates = array(
			'start_date' => $beginning_of_week,
			'end_date' => $this->Date->cast(strtotime($beginning_of_week . "+7 days"), $date_format)
		);
		
		return $week_dates;
	}
	
	/**
	 * Creates a new calendar event
	 */
	public function addEvent() {
		$this->uses(array("CalendarEvents"));
		$this->components(array("SettingsCollection"));
		$date_format = "Y-m-d";
		$time_format = "H:i:00";
		
		// Set calendar view (month/week/day) if given
		$current_view = "month";
		if (isset($this->get[0]))
			$current_view = $this->swapCalendarViewName($this->get[0]);
		
		// Specific date to create an event for
		$selected_start = null;
		if (isset($this->get['start']))
			$selected_start = $this->CalendarEvents->dateToUtc($this->get['start']);
		$selected_end = null;
		if (isset($this->get['end']))
			$selected_end = $this->CalendarEvents->dateToUtc($this->get['end']);
			
		// Specifically for all day
		$selected_all_day = false;
		if (isset($this->get['all_day']))
			$selected_all_day = (bool)$this->get['all_day'];
		
		// Add event
		if (!empty($this->post)) {
			$data = $this->post;
			$data['company_id'] = $this->company_id;
			$data['staff_id'] = $this->Session->read("blesta_staff_id");
			$data['start_date'] = $data['start_date'] . " " . $data['start_time'];
			$data['end_date'] = $data['end_date'] . " " . $data['end_time'];
			
			$this->CalendarEvents->add($data);
			
			if (($errors = $this->CalendarEvents->errors())) {
				// Error
				$vars = (object)$this->post;
				$this->flashMessage("error", $errors);
			}
			else {
				// Success
				$this->flashMessage("message", Language::_("AdminMain.!success.event_added", true));
			}
			
			// Redirect to the calendar
			$calendar_date = (!empty($this->post['start_date']) ? max(strtotime($this->post['start_date']), strtotime($this->Date->cast(date("c"), $date_format))) : $this->Date->cast(date("c"), $date_format));
			$this->redirect($this->base_uri . "main/calendar/" . $current_view . "/" . date($date_format, $calendar_date));
		}
		else {
			// Set initial date time values to next hour
			$start_date = $this->Date->cast(date("c"), $date_format);
			$start_time = $this->Date->cast(date("c"), $time_format);
			$end_date = $this->Date->cast(date("c"), $date_format);
			$end_time = date($time_format, $this->Date->toTime($start_date . " +1 hour"));
			
			// Set current date to the date given
			if ($selected_start != null) {
				$start_date = $this->Date->cast($selected_start, $date_format);
				$start_time = $this->Date->cast($selected_start, $time_format);
			}
			if ($selected_end != null) {
				$end_date = $this->Date->cast($selected_end, $date_format);
				$end_time = $this->Date->cast($selected_end, $time_format);
			}
			
			$vars = (object)array(
				'start_date' => $start_date,
				'start_time' => $start_time,
				'end_date' => $end_date,
				'end_time' => $end_time,
				'all_day' => $selected_all_day
			);
		}
		
		// Set when the calendar start day begins
		$calendar_begins = $this->SettingsCollection->fetchSetting(null, $this->company_id, "calendar_begins");
		
		$data = array(
			'vars' => $vars,
			'date_times' => $this->getTimes(Configure::get("Blesta.calendar_time_interval")),
			'calendar_begins' => ((isset($calendar_begins['value']) && ($calendar_begins['value'] == "sunday")) ? 0 : 1)
		);
		
		// Display edit event page
		echo $this->partial("admin_main_addevent", $data);
		return false;
	}
	
	/**
	 * Updates a calendar event
	 */
	public function editEvent() {
		$this->uses(array("CalendarEvents"));
		$this->components(array("SettingsCollection"));
		$staff_id = $this->Session->read("blesta_staff_id");
		$date_format = "Y-m-d";
		
		// Ensure an event was given
		if (!isset($this->get[0]) || !($event = $this->CalendarEvents->get((int)$this->get[0])) ||
			($this->company_id != $event->company_id)) {
			header($this->server_protocol . " 401 Unauthorized");
			exit();
		}
		
		// Ensure the event belongs to this staff member
		if (($event->staff_id != $staff_id)) {
			header($this->server_protocol . " 401 Unauthorized");
			exit();
		}
		
		// Set initial event
		$vars = $event;
			
		// Set calendar view (month/week/day)
		$current_view = "month";
		if (isset($this->get[1]))
			$current_view = $this->swapCalendarViewName($this->get[1]);
		
		// Edit event
		if (!empty($this->post)) {
			$post_data = $this->post;
			$post_data['staff_id'] = $staff_id;
			$post_data['start_date'] = $post_data['start_date'] . " " . $post_data['start_time'];
			$post_data['end_date'] = $post_data['end_date'] . " " . $post_data['end_time'];
			
			// Set initial values for checkboxes if not given
			if (!isset($post_data['shared']))
				$post_data['shared'] = "0";
			if (!isset($post_data['all_day']))
				$post_data['all_day']= "0";
			
			// Update event
			$this->CalendarEvents->edit($event->id, $post_data);
			
			if (($errors = $this->CalendarEvents->errors())) {
				// Error
				$vars = (object)$this->post;
				$this->flashMessage("error", $errors);
			}
			else {
				// Success
				$this->flashMessage("message", Language::_("AdminMain.!success.event_edited", true));
			}
			
			// Redirect to the calendar
			$calendar_date = (!empty($this->post['start_date']) ? max(strtotime($this->post['start_date']), strtotime($this->Date->cast(date("c"), $date_format))) : $this->Date->cast(date("c"), $date_format));
			$this->redirect($this->base_uri . "main/calendar/" . $current_view . "/" . date($date_format, $calendar_date));
		}
		else {
			
			// Split up dates and times
			$vars->start_time = $this->Date->cast($vars->start_date, "H:i:s");
			$vars->end_time = $this->Date->cast($vars->end_date, "H:i:s");
			$vars->start_date = $this->Date->cast($vars->start_date, $date_format);
			$vars->end_date = $this->Date->cast($vars->end_date, $date_format);
		}
		
		// Set when the calendar start day begins
		$calendar_begins = $this->SettingsCollection->fetchSetting(null, $this->company_id, "calendar_begins");
		
		$data = array(
			'vars' => $vars,
			'event_id' => $event->id,
			'calendar_view' => $current_view,
			'date_times' => $this->getTimes(Configure::get("Blesta.calendar_time_interval")),
			'calendar_begins' => ((isset($calendar_begins['value']) && ($calendar_begins['value'] == "sunday")) ? 0 : 1)
		);
		
		// Display edit event page
		echo $this->partial("admin_main_editevent", $data);
		return false;
	}
	
	/**
	 * Updates the date range and (optionally) all day status
	 */
	public function editEventRange() {
		
		$this->uses(array("CalendarEvents"));
		$staff_id = $this->Session->read("blesta_staff_id");
		
		// Ensure an event was given
		if (!isset($this->get[0]) || !($event = $this->CalendarEvents->get((int)$this->get[0])) ||
			($this->company_id != $event->company_id)) {
			header($this->server_protocol . " 401 Unauthorized");
			exit();
		}
		
		// Ensure the event belongs to this staff member
		if (($event->staff_id != $staff_id)) {
			header($this->server_protocol . " 401 Unauthorized");
			exit();
		}
		
		if (!empty($this->post)) {
			// Update event
			$this->post['staff_id'] = $staff_id;
			$this->CalendarEvents->edit($event->id, $this->post);
			
			if (($errors = $this->CalendarEvents->errors())) {
				// Error
				
				#
				# TODO: Return error as JSON response
				#
			}
			else {
				// Success
				
				#
				# TODO: Return success as JSON response Language::_("AdminMain.!success.event_edited", true)
				#
			}
		}
		
		return false;
	}
	
	/**
	 * Deletes a calendar event
	 */
	public function deleteEvent() {
		$this->uses(array("CalendarEvents"));
		$date_format = "Y-m-d";
		
		// Ensure an event was given
		if (!isset($this->post['id']) || !($event = $this->CalendarEvents->get((int)$this->post['id'])))
			$this->redirect($this->base_uri . "main/calendar/");
		
		// Set calendar view (month/week/day)
		$current_view = "month";
		if (isset($this->post['view']))
			$current_view = $this->post['view'];
		
		// Ensure the event belongs to this staff member
		if ($event->staff_id != $this->Session->read("blesta_staff_id")) {
			$this->flashMessage("error", Language::_("AdminMain.!error.delete_event.staff_id", true));
		}
		else {
			// Delete the event
			$this->CalendarEvents->delete($event->id);
			$this->flashMessage("message", Language::_("AdminMain.!success.event_deleted", true));
		}
		
		// Redirect to the calendar
		$calendar_date = max(strtotime($this->Date->cast($event->start_date, $date_format)), strtotime($this->Date->cast(date("c"), $date_format)));
		$this->redirect($this->base_uri . "main/calendar/" . $current_view . "/" . date($date_format, $calendar_date) . "/");
	}
	
	/**
	 * AJAX retrieves a list of events to update the calendar with
	 */
	public function getEvents() {
		$this->uses(array("CalendarEvents"));
		$staff_id = $this->Session->read("blesta_staff_id");
		
		if (!empty($this->get['start_date']) && !empty($this->get['end_date'])) {
			// Fetch the calendar events in the given date range
			$calendar_events = $this->CalendarEvents->getRange($this->company_id, $staff_id, $this->get['start_date'], $this->get['end_date']);
			
			// Format each event for the calendar
			$events = array();
			foreach ($calendar_events as $event) {
				// Determine a few options for colorizing the events
				$current_staff = ($event->staff_id == $staff_id);
				$current_staff_shared = ((bool)($event->shared) && $current_staff);
				$others_shared = ((bool)($event->shared) && !$current_staff);
				
				$class = ($current_staff_shared ? $this->calendar_staff_event_shared :
					($others_shared ? $this->calendar_other_events : $this->calendar_staff_event_unshared));
				
				$events[] = array(
					'id' => $event->id,
					'title' => ($others_shared ? Language::_("AdminMain.getEvents.shared_event_title", true, $event->title, $event->staff_first_name, $event->staff_last_name) : $event->title),
					'start' => $this->Date->cast($event->start_date, "Y-m-d H:i:s"),
					'end' => $this->Date->cast($event->end_date, "Y-m-d H:i:s"),
					'url' => $event->url,
					'allDay' => (bool)($event->all_day),
					'className' => $class,
					'editable' => $current_staff
				);
			}
			echo $this->Json->encode($events);
		}
		return false;
	}
	
	/**
	 * AJAX request to fetch the number of events that begin each day between the given dates
	 */
	public function getEventCounts() {
		$this->uses(array("CalendarEvents"));
		$staff_id = $this->Session->read("blesta_staff_id");
		
		if (!empty($this->get['start_date']) && !empty($this->get['end_date'])) {
			// Fetch the calendar events in the given date range
			$calendar_events = $this->CalendarEvents->getAll($this->company_id, $staff_id, $this->get['start_date'], $this->get['end_date']);
			
			$counts = array();
			foreach ($calendar_events as $event) {
				$start_date = $this->Date->cast($event->start_date, "Y-m-d");
				
				if (!isset($counts[$start_date]))
					$counts[$start_date] = 0;
				$counts[$start_date]++;
			}
			echo $this->Json->encode($counts);
		}
		
		return false;
	}
	
	/**
	 * AJAX updates the quicklink for a given page
	 */
	public function updateQuickLink() {
		if (!empty($this->post)) {

			$response = new stdClass();
			
			$staff_id = $this->Session->read("blesta_staff_id");
			
			// If the title is empty, just remove it.
			if (isset($this->post['title']) && trim($this->post['title']) == "") {
				$this->post['action'] = "remove";
			}
			
			// Add or remove a quicklink
			switch($this->post['action']) {
				case "remove":
					$this->Staff->deleteQuickLink($staff_id, $this->company_id, $this->post['uri']);
					$response->added = false;
					break;
				case "add":
				default:
					$this->Staff->addQuickLink($staff_id, $this->company_id, $this->post);
					
					if (($errors = $this->Staff->errors())) {
						// Errors, ignore
					}
					else {
						// Success, quicklink added
						$response->added = true;
					}
					break;
			}
			
			// JSON encode the AJAX response
			$this->outputAsJson($response);
		}
		
		return false;
	}
	
	/**
	 * Renders a box to select the dashboard layout to use, and sets it
	 */
	public function updateDashboard() {
		$dashboard_layout = null;
		$dashboard_layouts = array("layout1", "layout2", "layout3", "layout4");
		
		// Get the new dashboard layout if given
		if (isset($this->get[0]) && in_array($this->get[0], $dashboard_layouts))
			$dashboard_layout = $this->get[0];
		
		$this->uses(array("Staff"));
		// Ensure a valid staff member is set
		if (!($staff = $this->Staff->get($this->Session->read("blesta_staff_id"), $this->company_id)))
			$this->redirect($this->base_uri);
			
		// Update dashboard layout
		if ($dashboard_layout != null) {
			// Update the dashboard layout
			$this->Staff->setSetting($staff->id, "dashboard_layout", $dashboard_layout);
			
			// Redirect to dashboard
			$this->redirect($this->base_uri);
		}
		
		// Retrieve the current layout
		$current_layout = $this->Staff->getSetting($staff->id, "dashboard_layout", $this->company_id);
		
		// Set the default dashboard layout if one doesn't exist
		if (!$current_layout)
			$current_layout = $dashboard_layouts[0];
		else
			$current_layout = $current_layout->value;
		
		// Set all of the dashboard layouts
		$layouts = array();
		foreach ($dashboard_layouts as $layout) {
			$layouts[] = (object)array(
				'name' => $layout,
				'selected' => ($layout == $current_layout) ? true : false
			);
		}
		
		$this->set("layouts", $layouts);
		echo $this->view->fetch("admin_main_updatedashboard");
		return false;
	}
	
	/**
	 * Enable/Disable widgets from appearing on the dashboard
	 */
	public function manageWidgets() {
		$this->uses(array("PluginManager"));

		// Get all displayed widgets
		$active_widgets = $this->Staff->getHomeWidgetsState($this->Session->read("blesta_staff_id"), $this->company_id);
		
		if (!empty($this->post)) {
			
			if (is_array($this->post['widgets_on'])) {
				
				// If a widget isn't displayed it must be disabled
				foreach ($active_widgets as $key => $widget) {
					if (!in_array($key, $this->post['widgets_on']))
						$active_widgets[$key]['disabled'] = true;
				}
				
				// Set all widgets to be displayed
				foreach ($this->post['widgets_on'] as $key) {
					if (!isset($active_widgets[$key]))
						$active_widgets[$key] = array('open'=>true, 'section'=> "section1");
					else
						unset($active_widgets[$key]['disabled']);
				}

				// Update this staff member's widgets for this company
				$this->Staff->saveHomeWidgetsState($this->Session->read("blesta_staff_id"), $this->company_id, $active_widgets);
			}
			
			return false;
		}
		
		
		// Get all widgets installed for this location
		$installed_widgets = $this->PluginManager->getActions($this->company_id, "widget_staff_home");
		
		$available_widgets = array();
		foreach ($installed_widgets as $widget) {
			$key = str_replace("/", "_", trim($widget->uri, "/"));
			$available_widgets[$key] = $this->PluginManager->get($widget->plugin_id, true);
		}
		
		// Move all currently displayed widgets from available to displayed
		$displayed_widgets = array();
		foreach ($active_widgets as $key => $widget) {
			if (isset($available_widgets[$key]) && !(isset($widget['disabled']) && $widget['disabled'])) {
				$displayed_widgets[$key] = $available_widgets[$key];
				unset($available_widgets[$key]);
			}
		}
		
		// All widgets available and not displayed
		$this->set("available_widgets", $available_widgets);
		// All widgets available and displayed
		$this->set("displayed_widgets", $displayed_widgets);
		
		echo $this->view->fetch("admin_main_managewidgets");
		return false;
	}
}
?>