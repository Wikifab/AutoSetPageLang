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
	 * This job will mark the page for translation, there is 2 possible cases :
	 * - if the page is in namespace $wgAutoSetPageLangAutoMarkNamespaces
	 * - if the page is already marked (in previous revision) and is in the namespace
	 *
	 * @return bool
	 */
	public function run() {

		global $wgAutoSetPageLangAutoMarkNamespaces, $wgAutoSetPageLangTranslateOnCompleteOnly,
			$wgAutoSetPageLangAutoUpdateNamespaces;

		// Load data from $this->params and $this->title

		if ( ! in_array($this->title->getNamespace(), $wgAutoSetPageLangAutoMarkNamespaces)
				&& ! in_array($this->title->getNamespace(), $wgAutoSetPageLangAutoUpdateNamespaces)) {
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