<?php
namespace AutoSetPageLang;

use User;
use Revision;
use SpecialPageLanguage;


class Hooks {

	public static function onPageContentInsertComplete(\WikiPage $wikiPage, User $user, $content, $summary, $isMinor, $isWatch, $section, $flags, Revision $revision ) {
		global $wgLang;

		if ($wikiPage->getTitle()->getNamespace() == NS_MAIN && $wgLang) {
			$specialLang = new SpecialPageLanguage();
			$data = [
					'pagename' => $wikiPage->getTitle()->getDBkey(),
					'language' => $wgLang->getCode(),
					'selectoptions' => 0
			];
			if ( ! $data['language']) {
				return ;
			}
			$specialLang->onSubmit($data);
		}
	}
}