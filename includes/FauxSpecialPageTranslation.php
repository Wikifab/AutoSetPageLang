<?php

namespace AutoSetPageLang;

use SpecialPageTranslation;
use Title;

class FauxSpecialPageTranslation extends SpecialPageTranslation {


	public function markPage(Title $title) {

		$revision = \Revision::newFromId($title->getLatestRevID());

		$data = ['translatetitle' => true];
		$wasPosted=true;
		$session = $this->getRequest()->getSession();
		$fauxRequest = new \FauxRequest($data, $wasPosted, $session);

		$context = new \RequestContext();
		$context->setUser(\FuzzyBot::getUser());
		$context->setRequest($fauxRequest);
		$this->getOutput()->setTitle($title);
		$context->setOutput($this->getOutput());

		$this->setContext( $context );
		$this->getRequest()->setVal('translatetitle', true);

		$this->onActionMark( $title , 0);

	}

	public function showPage( \TranslatablePage $page, array $sections ) {

	}


}