<?php
/**
 * Handle all default AppController events callbacks
 *
 * @package blesta
 * @subpackage blesta.components.events.default
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class EventsAppControllerCallback extends EventCallback {
	
	/**
	 * Handle AppController.preAction events.
	 *
	 * @param EventObject $event An event object for AppController.preAction events
	 * @return EventObject The processed event object
	 */
	public static function preAction(EventObject $event) {
		return parent::triggerPluginEvent($event);
	}
}
?>