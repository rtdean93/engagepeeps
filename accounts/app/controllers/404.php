<?php
/**
 * Handle 404 (File not found) Requests
 *
 * @package blesta
 * @subpackage blesta.app.controllers
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class _404 extends AppController {

	public function preAction() {
		parent::preAction();
		Language::loadLang(array("_404"));
	}
}

?>