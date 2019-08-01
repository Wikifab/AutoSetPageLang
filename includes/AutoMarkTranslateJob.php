<?php
namespace AutoSetPageLang;

use Job;
use SpecialPageTranslation;

class AutoMarkTranslateJob extends Job {
	public function __construct( $title, $params ) {
		parent::__construct( 'autoMarkTranslate', $title, $params );
	}

	/**
	 * Execute the job
	 *
	 * @return bool
	 */
	public function run() {

		global $wgAutoSetPageLangAllowedNamespaces, $wgAutoSetPageLangTranslateOnCompleteOnly;

		// Load data from $this->params and $this->title

		if ( ! in_array($this->title->getNamespace(), $wgAutoSetPageLangAllowedNamespaces) ) {
			return;
		}
		
		$revision = \Revision::newFromId($this->title->getLatestRevID());

		if( ! $revision) {
			return true;
		}

		$content = $revision->getContent()->getNativeData();
		if( ! $content) {
			return true;
		}

		$completeValues = ['Yes', 'Published'];
		$isComplete = false;
		foreach ($completeValues as $completeValue) {
			if (strpos($content, "|Complete=$completeValue") !== false) {
				$isComplete = true;
				break;
			}
		}

		if(!$wgAutoSetPageLangTranslateOnCompleteOnly){
			$isComplete = true;
		}

		// check that content contain "Complete=Yes"
		if ($isComplete  && strpos($content, "<translate") !== false) {

			$specialPageTranslation = new FauxSpecialPageTranslation();

			$specialPageTranslation->markPage($this->title);

			\Hooks::run( 'AutoSetPageLangMarkForTranslation', [ $this->title ] );
		}
		return true;
	}
}