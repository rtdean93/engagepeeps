<?php
Loader::load(HELPERDIR . "html" . DS . "html.php");

/**
 * Simplifies the creation of widgets for the client interface
 *
 * @package blesta
 * @subpackage blesta.helpers.widget_client
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class WidgetClient extends Html {
	/**
	 * @var string The string to use as the end of line character, "\n" by default
	 */
	private $eol = "\n";
	/**
	 * @var boolean Whether or not to return output from various widget methods
	 */
	private $return_output = false;
	/**
	 * @var array Buttons that should be displayed within the window 
	 */
	private $widget_buttons = array();
	/**
	 * @var array An array of style sheet attributes to be rendered into the DOM
	 */
	private $style_sheets = array();
	/**
	 * @var string How to render the widget. Options include:
	 * 	-full The entire widget (default)
	 * 	-inner The content only (everything excluding the nav and title)
	 */
	private $render;
	/**
	 * @var boolean Controls whether or not to render the sub heading section
	 */
	private $sub_head = true;
	
	private $nav;
	private $nav_type = "links";
	private $link_buttons;
	
	/**
	 * Clear this widget, making it ready to produce the next widget
	 */
	public function clear() {
		$this->nav = null;
		$this->nav_type = "links";
		$this->link_buttons = null;
		$this->style_sheets = array();
		$this->render = "full";
		$this->sub_head = true;
	}

	/**
	 * Sets navigation links within the widget
	 *
	 * @param array $tabs A multi-dimensional array of tab info including:
	 * 	- name The name of the link to be displayed
	 * 	- current True if this element is currently active
	 * 	- attributes An array of attributes to set for this tab (e.g. array('href'=>"#"))
	 */	
	public function setLinks(array $link) {
		$this->nav = $link;
		$this->nav_type = "links";
	}
	
	/**
	 * Sets navigation buttons along with Widget::setLinks(). This method may
	 * only be used in addition with Widget::setLinks()
	 *
	 * @param array $link_buttons A multi-dimensional array of button links including:
	 * 	- name The name of the button link to be displayed
	 * 	- attributes An array of attributes to set for this button link (e.g. array('href'=>"#"))
	 */
	public function setLinkButtons(array $link_buttons) {
		$this->link_buttons = $link_buttons;
	}
	
	/**
	 * Sets a style sheet to be linked into the document
	 *
	 * @param string $path the web path to the style sheet
	 * @param array An array of attributes to set for this element
	 */
	public function setStyleSheet($path, array $attributes=null) {
		$default_attributes = array('media'=>"screen", 'type'=>"text/css", 'rel'=>"stylesheet", 'href'=>$path);
		$attributes = array_merge((array)$attributes, $default_attributes);
		
		$this->style_sheets[] = $attributes;
	}
	
	/**
	 * Sets whether or not the sub heading section should be rendered
	 *
	 * @param boolean $render True to render the sub heading, false otherwise
	 */
	public function renderSubHead($render) {
		$this->sub_head = $render;
	}
	
	/**
	 * Creates the widget with the given title and attributes
	 *
	 * @param string $title The title to display for this widget
	 * @param array $attributes An list of attributes to set for this widget's primary container
	 * @param string $render How to render the widget. Options include:
	 * 	- full The entire widget (default)
	 * 	- inner_content (everthing but the title)
	 * 	- inner The content only (everything excluding the nav and title)
	 * @return mixed An HTML string containing the widget, void if the string is output automatically
	 */
	public function create($title=null, array $attributes=null, $render=null) {
		// Don't output until this section is completely built
		$output = $this->setOutput(true);
		
		$this->render = ($render == null ? "full" : $render);
		
		$default_attributes = array('class'=>"content_section");

		// Set the attributes, don't allow overwriting the default class, concat instead
		if (isset($attributes['class']) && isset($default_attributes['class']))
			$attributes['class'] .= " " . $default_attributes['class'];
		$attributes = array_merge((array)$attributes, $default_attributes);
		
		// Control which sections are rendered
		$html = "";
		$html .= $this->buildStyleSheets();
		if ($this->render == "full") {
			$html .= '
				<div' . $this->buildAttributes($attributes) . '>
					<h4>' . $this->_($title, true) . '</h4>
					<div class="inner_content">';
		}
		
		if ($this->render == "full" || $this->render == "inner_content") {
			$html .= '
						' . $this->buildLinkButtons() . '
						' . $this->buildNav() . '
						<div class="inner">';
		}
		
		// Restore output setting
		$this->setOutput($output);
		
		return $this->output($html);
	}
	
	/**
	 * End the widget, closing an loose ends
	 *
	 * @return mixed An HTML string ending the widget, void if the string is output automatically
	 */
	public function end() {
		// Don't output until this section is completely built
		$output = $this->setOutput(true);
		
		$html = '';
		
		if ($this->render == "full") {
			$html .= '
						</div>
					</div>
				</div>';
		}
		if ($this->render == "inner_content") {
			$html .= '
					</div>';
		}
		
		// Restore output setting
		$this->setOutput($output);
		
		return $this->output($html);
	}
	
	/**
	 * Builds the nav for this widget
	 *
	 * @return mixed A string of HTML, or void if HTML is output automatically
	 */
	private function buildNav() {
		$html = null;
		
		// Draw sub heading if set to, or if we have nav (which require sub heading section)
		if ($this->sub_head || !empty($this->nav))
			$html .= '<div class="sub_head">';
		
		if (!empty($this->nav)) {
			$html .= '
				<div class="common_nav">
					' . $this->buildNavElements() . '
				</div>' . $this->eol;
		}

		if ($this->sub_head || !empty($this->nav))
			$html .= '</div>';
		
		return $html;
	}
	
	/**
	 * Builds the nav elements for this widget
	 * 
	 * @return string A string of HTML
	 */
	private function buildNavElements() {
		if (empty($this->nav))
			return null;
		
		$html = "<ul>" . $this->eol;
		$i=0;
		if (is_array($this->nav)) {
			foreach ($this->nav as $element) {
				// Set attributes on the anchor element
				$a_attr = "";
				if (isset($element['attributes']))
					$a_attr = $this->buildAttributes($element['attributes']);
				
				// Set attributes on the list element
				$li_attr = "";
				if ($i == 0 || isset($element['current']) || isset($element['highlight'])) {
					$li_attr = $this->buildAttributes(
						array(
							'class'=>$this->concat(" ", ($i == 0 ? "first" : ""),
								($this->ifSet($element['current']) ? "current" : ""),
								($this->ifSet($element['highlight']) && !$this->ifSet($element['current']) ? "highlight" : "")
							)
						)
					);
				}
				
				$html .= "<li" . $li_attr . "><a" . $a_attr . ">" . $this->ifSet($element['name']) . "</a></li>" . $this->eol;
				
				$i++;
			}
			$html .= "</ul>" . $this->eol;
		}

		return $html;
	}
	
	/**
	 * Builds link buttons for use with link navigation
	 *
	 * @return string A string of HTML
	 */
	private function buildLinkButtons(array $attributes=null) {
		$default_attributes = array('class'=>"btn right_btn");
		$attributes = array_merge($default_attributes, (array)$attributes);
		
		$html = "";
		if (is_array($this->link_buttons)) {
			foreach ($this->link_buttons as $element) {
				$html .= "<div" . $this->buildAttributes($attributes) . "><a" . $this->buildAttributes($element['attributes']) . ">" . $this->_($element['name'], true) . "</a></div>" . $this->eol;
			}
		}
		
		return $html;
	}
	
	/**
	 * Builds the markup to link style sheets into the DOM using jQuery
	 *
	 * @return string A string of HTML
	 */
	private function buildStyleSheets() {

		$html = "";
		if (is_array($this->style_sheets) && !empty($this->style_sheets)) {
			$html .= "<script type=\"text/javascript\">" . $this->eol;
			foreach ($this->style_sheets as $style) {
				$attributes = "";
				$i=0;
				foreach ($style as $key => $value)
					$attributes .= ($i++ > 0 ? "," . $this->eol : "") . $key . ": \"" . $value . "\"";
				$html .= "$(document).blestaSetHeadTag(\"link\", { " . $attributes . " });" . $this->eol;
			}
			$html .=  $this->eol . "</script>";
		}
		
		return $html;
	}
	
	/**
	 * Set whether to return $output generated by these methods, or to echo it out instead
	 *
	 * @param boolean $return True to return output from these widget methods, false to echo results instead 
	 */
	public function setOutput($return) {
		if ($return)
			$this->return_output = true;
		else
			$this->return_output = false;
	}
	
	/**
	 * Handles whether to output or return $html
	 *
	 * @param string $html The HTML to output/return
	 * @return string The HTML given, void if output enabled
	 */	
	private function output($html) {
		if ($this->return_output)
			return $html;
		echo $html;
	}
}
?>