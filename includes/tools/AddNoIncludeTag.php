<?php

namespace AutoSetPageLang\Tools;

class AddNoIncludeTag {

	protected $templateName = 'PageLang';

	public function __construct($templateName = null, $tagToAdd= null) {
		if ( $templateName ) {
			$this->templateName = $templateName;
		}
		if ( $tagToAdd ) {
			throw new \Exception('Feature not implemented yet');
		}
	}

	/**
	 * ad the tag around the template given in param
	 *
	 * TODO : this will not work if template contains sub template, or parsing function or anything using {} chars
	 *
	 * @param string $text
	 * @return string
	 */
	public function addTag($text) {

		$openingTag = '<noinclude>';
		$closingTag = '</noinclude>';

		$patternWithoutTag = '@\{\{([\s]*)' . $this->templateName . '([\s])*([^\{\}]+)\}\}@';
		$patternWithTag = '@'.$openingTag.'(\s*)\{\{([\s]*)' . $this->templateName . '([\s])*([^\{\}]+)\}\}(\s*)' . $closingTag . '@';
		if (preg_match($patternWithTag, $text, $match)) {
			// if tag already added in page, we do not add it twice
			return $text;
		}
		$text = preg_replace($patternWithoutTag, $openingTag . '$0' . $closingTag, $text);
		return $text;
	}
}