<?php
/**
 *
 *
 */
class WhmcsServices {
	
	public function __construct(Record $remote) {
		$this->remote = $remote;
	}
	
	/**
	 * Fetch all standard services
	 *
	 * @return PDOStatement
	 */
	public function get() {
		return $this->remote->select()->from("tblhosting")->getStatement();
	}
	
	/**
	 * Fetch all domain-name services
	 *
	 * @return PDOStatement
	 */
	public function getDomains() {
		return $this->remote->select()->from("tbldomains")->getStatement();
	}
	
	/**
	 * Coverts term name into actual term/period
	 *
	 * @param mixed $term_name The term name (e.g. "Monthly", "Semi-Annually", etc.), or an integer representing the number of years
	 * @return array An array of key/value pairs including:
	 * 	- term The term
	 * 	- period The period
	 */
	public function getTerm($term_name) {
		if (is_numeric($term_name))
			return array('term' => $term_name, 'period' => "year");
			
		switch ($term_name) {
			default:
			case "Free Account":
			case "One Time":
				return array('term' => 0, 'period' => "onetime");
			case "Monthly":
				return array('term' => 1, 'period' => "month");
			case "Quarterly":
				return array('term' => 3, 'period' => "month");
			case "Semi-Annually":
				return array('term' => 6, 'period' => "month");
			case "Annually":
				return array('term' => 1, 'period' => "year");
			case "Biennially":
				return array('term' => 2, 'period' => "year");
			case "Triennially":
				return array('term' => 3, 'period' => "year");
		}
	}
}
?>