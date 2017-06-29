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
		// Load data from $this->params and $this->title

		if($this->title->getNamespace() != NS_MAIN) {
			return true;
		}
		$revision = \Revision::newFromId($this->title->getLatestRevID());

		if( ! $revision) {
			return true;
		}

		$content = $revision->getContent()->getNativeData();
		if( ! $content) {
			return true;
		}

		// check that content contain "Complete=Yes"
		if (strpos($content, "|Complete=Yes") !== false && strpos($content, "<translate") !== false) {

			$specialPageTranslation = new FauxSpecialPageTranslation();

			$specialPageTranslation->markPage($this->title);
		}
		return true;
	}
}