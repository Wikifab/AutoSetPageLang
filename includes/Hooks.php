<?php
namespace AutoSetPageLang;

use JobQueueGroup;
use Revision;
use SpecialPageLanguage;
use SpecialPage;
use Title;
use TranslatablePage;
use User;
use WikitextContent;

class Hooks {

	public static function onBeforePageDisplay($output) {

		$output->addModuleStyles( [
				'ext.autosetpagelang'
		] );
	}

	/**
	 * watch Hook:PageContentInsertComplete
	 * when a new page is created, set page Language to the user's actual language
	 *
	 */
	public static function onPageContentInsertComplete(\WikiPage $wikiPage, User $user, $content, $summary, $isMinor, $isWatch, $section, $flags, Revision $revision ) {
		global $wgLang;

		// set page language to current language of user :
		if ($wikiPage->getTitle()->getNamespace() == NS_MAIN && $wgLang) {

			$sourcePageTranslatable = \TranslatablePage::isTranslationPage( $wikiPage->getTitle() );
			//var_dump($page); echo "<br/>";
			if ($sourcePageTranslatable) {
				// if this is a translated page, do not change his language !!
				return;
			}

			$codeLang = $wgLang->getCode();
			$contentLang = self::getPageLanguageFromContent($content);
			if($contentLang) {
				$codeLang = $contentLang;
			}

			$specialLang = new SpecialPageLanguage();
			$data = [
					'pagename' => $wikiPage->getTitle()->getDBkey(),
					'language' => $codeLang,
					'selectoptions' => 0,
					'reason' => 'Autoset Page Language'
			];
			if ( ! $data['language']) {
				return ;
			}
			$specialLang->onSubmit($data);
		}
	}

	public static function getPageLanguageFromContent(\WikitextContent $content) {

		if($content && preg_match('/^\|Language=([a-z]{2})$/m', $content->getNativeData(), $match)) {
			var_dump($match);
			return $match[1];
		}
		return false;
	}

	/**
	 * Hook to add class in body content
	 *
	 * @param \OutputPage $output
	 * @param \Skin $skin
	 * @param array $bodyAttrs
	 */
	public static function onOutputPageBodyAttributes( \OutputPage $output, $skin, & $bodyAttrs ){
		global $wgLang;
		$pageTitle = $output->getTitle();
		if ( ! $pageTitle || $pageTitle->getNamespace() != NS_MAIN) {
			return ;
		}

		$titleKeyArray = explode('/',$pageTitle->getDBkey());
		if (count($titleKeyArray) >= 2) {
			$bodyAttrs['class'] .= " bodyTranslatedPage";
		} else {
			$bodyAttrs['class'] .= " bodyTranslateSourcePage";
		}

		if ($pageTitle->getPageLanguage()->getCode() == $wgLang->getCode()) {
			$bodyAttrs['class'] .= " bodyPageInUserLanguage";
		} else {
			$bodyAttrs['class'] .= " bodyPageInOtherLanguage";

		}
	}




	/**
	 * Hook to add "Language" property in semantic pages
	 *
	 * @param unknown $form
	 * @param \Title $targetTitle
	 * @param unknown $targetContent
	 */
	public static function onPageFormsWritePageData( $form, Title $targetTitle, & $targetContent ){
		global $wgLang;

		$templatesToUpdate = [
				'Tuto Details',
				'BlogPost'
		];
		if ($targetTitle->exists()) {
			$languageCode = $targetTitle->getPageLanguage()->getCode();
		} else {
			$languageCode = $wgLang->getCode();
		}

		foreach ($templatesToUpdate as $templateName) {

			// this is based on str search an regexp,
			// it would be better if it extract properly semantic properties
			if(strpos($targetContent, "SourceLanguage=none\n|Language=$languageCode\n|IsTranslation=0") !== false) {
				continue;
			}

			if(preg_match('/\{\{([\s])\{\{(tntn|Tntn)\|' . $templateName . '\}\}([\s])*\|/', $targetContent, $match)) {
				$targetContent = str_replace($match[0], $match[0] . "SourceLanguage=none\n|Language=$languageCode\n|IsTranslation=0\n|", $targetContent);
			} else if(preg_match('/\{\{' . $templateName . '([\s])*\|/', $targetContent, $match)) {
				$targetContent = str_replace($match[0], $match[0] . "SourceLanguage=none\n|Language=$languageCode\n|IsTranslation=0\n|", $targetContent);
			}
		}
		$targetContent = str_replace("\r\n", "\n", $targetContent);
	}


	/**
	 * watch PagecontentSave hook to change "|Language=<code>" to the code of the target language of translated page (set in url)
	 *
	 * It also remove 'translate' tags if page not complete
	 * @param \Wikipage $wikipage
	 * @param \User $user
	 * @param \Content $content
	 * @param unknown $summary
	 * @param unknown $flags
	 */
	public static function onPageContentSave( &$wikipage, &$user, &$content, &$summary, $flags){
		global $wgAutoSetPageLangTranslateOnCompleteOnly;

		if ($content instanceof WikitextContent) {
			$text = $content->getNativeData();

			// update Page lang for translated pages :
			$titleKeyArray = explode('/',$wikipage->getTitle()->getDBkey());

			$sourcePageTranslatable = \TranslatablePage::isTranslationPage( $wikipage->getTitle() );
			//var_dump($page); echo "<br/>";
			if ($sourcePageTranslatable) {
				$languageCode = end($titleKeyArray);
				$sourceLanguage =  $sourcePageTranslatable->getTitle()->getPageLanguage()->getCode();
				if ($sourceLanguage == $languageCode) {
					$sourceLanguage = 'none';
				}
				// if this is a translated page, we update his property
				$text = preg_replace("/\\|SourceLanguage=([a-zA-Z-]+)\n/", "|SourceLanguage=$sourceLanguage\n", $text);
				$text = preg_replace("/\\|Language=([a-zA-Z-]+)\n/", "|Language=$languageCode\n", $text);
				$text = preg_replace("/\\|IsTranslation=([a-zA-Z-0-9]+)\n/", "|IsTranslation=1\n", $text);
			}

			if ($wgAutoSetPageLangTranslateOnCompleteOnly) {
				if (strpos($text, 'Tuto Status') !== false && strpos($text, '|Complete=Yes') === false) {
					// if tuto is not complete, remove translate tags :
					$text = str_replace(['<translate>','</translate>'], ['',''], $text);
				}
			}

			$content = new \WikitextContent($text);
		}

	}

	public static function onPageContentSaveComplete( $article, $user, $content, $summary, $isMinor, $isWatch, $section, $flags, $revision, $status, $baseRevId ) {

		self::checkAndMarkForTranslate($article->getTitle());
	}

	public static function checkAndMarkForTranslate (\Title $title) {
		global $wgAutoSetPageLangAutoMarkTranslate;

		if ( ! $wgAutoSetPageLangAutoMarkTranslate) {
			return;
		}
		if ( $title->getNamespace() != NS_MAIN) {
			return;
		}

		$job = new AutoMarkTranslateJob( $title, [] );
		JobQueueGroup::singleton()->push( $job );
	}

	/**
	 * Adds an "action" (i.e., a tab) to translate the current article
	 */
	static function displayTab( \SkinTemplate $obj, &$content_actions ) {
		if ( method_exists ( $obj, 'getTitle' ) ) {
			$title = $obj->getTitle();
		} else {
			$title = $obj->mTitle;
		}

		if (! $title) {
			return true;
		}
		$page = TranslatablePage::newFromTitle( $title );
		if (!$page) {
			return true;
		}

		$user = $obj->getUser();

		if ( !$user || ! $user->isAllowed( 'translate' ) ) {
			return true;
		}


		$marked = $page->getMarkedTag();

		$actions = [];
		if ( ! $marked  ) {
			// if not marked as translatable, do not show tab
			return true;
		}


		$langCode = $obj->getLanguage()->getCode();

		$translatePage = SpecialPage::getTitleFor( 'Translate' );
		$url = $translatePage->getLinkURL( [
						'group' => $page->getMessageGroupId(),
						'language' => $langCode,
						'action' => 'page',
						'filter' => '',
				], false );


		$translate_tab = array(
				'text' => wfMessage( 'tpt-tab-translate' )->text(),
				'href' => $url
		);

		$tab_keys = array_keys( $content_actions );
		$tab_values = array_values( $content_actions );
		$edit_tab_location = array_search( 'edit', $tab_keys );

		// If there's no 'edit' tab, look for the 'view source' tab
		// instead.
		if ( $edit_tab_location == null ) {
			$edit_tab_location = array_search( 'viewsource', $tab_keys );
		}

		// This should rarely happen, but if there was no edit *or*
		// view source tab, set the location index to -1, so the
		// tab shows up near the end.
		if ( $edit_tab_location == null ) {
			$edit_tab_location = - 1;
		}
		array_splice( $tab_keys, $edit_tab_location, 0, 'translate' );
		array_splice( $tab_values, $edit_tab_location, 0, array( $translate_tab ) );
		$content_actions = array();
		for ( $i = 0; $i < count( $tab_keys ); $i++ ) {
			$content_actions[$tab_keys[$i]] = $tab_values[$i];
		}

		return true;

	}

	static function displayTab2( $obj, &$links ) {
		// the old '$content_actions' array is thankfully just a
		// sub-array of this one
		return self::displayTab( $obj, $links['views'] );
	}
}