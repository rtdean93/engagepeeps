<?php
/**
 * Wrapper for text parsers
 *
 * @package blesta
 * @subpackage blesta.helpers.text_parser
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class TextParser {
	
	/**
	 * Creates and returns an instance of the requested text parser
	 *
	 * @param string $parser The parser to use for encouding. Acceptable types are:
	 * 	- markdown
	 * @return mixed Returns an instance of the parser object that was loaded, false if the parser was not found
	 */
	public function create($parser) {
		
		switch ($parser) {
			case "markdown":
				$this->load("php-markdown" . DS . "markdown_extended.php");
				return new MarkdownExtraExtended_Parser();
			case "html2text":
				$this->load("html2text" . DS . "html2text.class.php");
				return new html2text();
		}
		return false;
	}
	
	/**
	 * Encodes a string using the given parser
	 *
	 * @param string $parser The parser to use for encouding. Acceptable types are:
	 * 	- markdown
	 * @param string $text The text to encode using the given parser
	 * @return string The encoded text using the given parser
	 */
	public function encode($parser, $text) {
		
		switch ($parser) {
			case "markdown":
				if (!isset($this->MarkdownExtraExtended_Parser))
					$this->MarkdownExtraExtended_Parser = $this->create($parser);
				
				return $this->MarkdownExtraExtended_Parser->transform($text);
		}
		return null;
	}
	
	/**
	 * Load the given file from the vendor directory
	 *
	 * @param string $file The file, including its relative path from the vendor directory, to load
	 */
	private function load($file) {
		Loader::load(VENDORDIR . $file);
	}
}
?>