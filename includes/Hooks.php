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
		global $wgLang, $wgAutoSetPageLangAllowedNamespaces;

		// set page language to current language of user :
		if (in_array($wikiPage->getTitle()->getNamespace(), $wgAutoSetPageLangAllowedNamespaces) && $wgLang) {

			$sourcePageTranslatable = \TranslatablePage::isTranslationPage( $wikiPage->getTitle() );
			//var_dump($page); echo "<br/>";
			if ($sourcePageTranslatable) {
				// if this is a translated page, do not change his language !!
				return true;
			}

			$codeLang = $wgLang->getCode();
			$contentLang = self::getPageLanguageFromContent($content);
			if($contentLang) {
				$codeLang = $contentLang;
			}

			$specialLang = new SpecialPageLanguage();
			$data = [
					'pagename' => $wikiPage->getTitle()->getFullText(),
					'language' => $codeLang,
					'selectoptions' => 0,
					'reason' => 'Autoset Page Language'
			];
			if ( ! $data['language']) {
				return true;
			}
			$specialLang->onSubmit($data);
		}


		return true;
	}

	public static function getPageLanguageFromContent(\WikitextContent $content) {

		if($content && preg_match('/^\|Language=([a-z]{2})$/m', $content->getNativeData(), $match)) {
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
		global $wgLang, $wgAutoSetPageLangAllowedNamespaces;
		$pageTitle = $output->getTitle();
		if ( ! $pageTitle || ! in_array($pageTitle->getNamespace(), $wgAutoSetPageLangAllowedNamespaces)) {
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

	//Return all Semantic Properties with Values for given Title object
    public static function getSMWPropertyValuesForTitle(Title $oTitle){
		$store = \SMW\StoreFactory::getStore()->getSemanticData( \SMW\DIWikiPage::newFromTitle( $oTitle ) );
		//$store instanceof SMWSql3StubSemanticData;
		$arrSMWProps = $store->getProperties();
		$arrValues = [ ];
		foreach ( $arrSMWProps as $smwProp ) {
			//$smwProp instanceof SMW\DIProperty;
			$arrSMWPropValues = $store->getPropertyValues( $smwProp );
			foreach ( $arrSMWPropValues as $smwPropValue ) {
				$arrValues[ $smwProp->getLabel() ][] = $smwPropValue->getSerialization();
			}
		}
		return $arrValues;
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

		$templateName = 'PageLang';

		if ($targetTitle->exists()) {
			$languageCode = $targetTitle->getPageLanguage()->getCode();
		} else {
			$languageCode = $wgLang->getCode();
		}

		$contentToBeAdded = '';

		if( preg_match("/\|SourceLanguage=/", $targetContent) == 0 ) {

			$contentToBeAdded .= "|SourceLanguage=none\n";
		}

		if( preg_match("/\|Language=/", $targetContent) == 0 ) {

			$contentToBeAdded .= "|Language=$languageCode\n";
		}

		if( preg_match('/\|IsTranslation=/', $targetContent) == 0 ) {
			
			$contentToBeAdded .= "|IsTranslation=0\n";
		}

		if(preg_match('/\{\{([\s])\{\{(tntn|Tntn)\|' . $templateName . '\}\}([\s])*/', $targetContent, $match)) {

			$breakline = '';
			if($match[0][strlen($match[0])-1] != "\n") { //if no break line already
				$breakline = "\n";
			}

			$targetContent = str_replace($match[0], $match[0] . $breakline . $contentToBeAdded, $targetContent);

		} else if(preg_match('/\{\{' . $templateName . '([\s])*/', $targetContent, $match)) {

			$breakline = '';
			if($match[0][strlen($match[0])-1] != "\n") { //if no break line already
				$breakline = "\n";
			}

			$targetContent = str_replace($match[0], $match[0] . $breakline . $contentToBeAdded, $targetContent);
		}
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
				$completeValues = ['Yes', 'Published'];
				$isComplete = false;
				foreach ($completeValues as $completeValue) {
					if (strpos($text, "|Complete=$completeValue") !== false) {
						$isComplete = true;
						break;
					}
				}
				if (strpos($text, 'Tuto Status') !== false && ! $isComplete) {
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
		global $wgAutoSetPageLangAutoMarkTranslate, $wgAutoSetPageLangAllowedNamespaces;

		$page = TranslatablePage::newFromTitle( $title );
		$marked = $page->getMarkedTag();

		if(!$wgAutoSetPageLangAutoMarkTranslate && !$marked){
			return;
		}

		if ( ! in_array($title->getNamespace(), $wgAutoSetPageLangAllowedNamespaces) ) {
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
		$isTranslatedPage = TranslatablePage::isTranslationPage($title);

		$actions = [];
		if ( !$isTranslatedPage && !$marked  ) {
			// if not marked as translatable, do not show tab
			return true;
		}


		$langCode = $page->getSourceLanguageCode();
		$sourcePageTitle = $page->getMessageGroupId();
		if(strpos($sourcePageTitle, '/'.$langCode)){
			$sourcePageTitle = str_replace('/'.$langCode, '', $sourcePageTitle);
		}

		$translatePage = SpecialPage::getTitleFor( 'Translate' );
		$url = $translatePage->getLinkURL( [
						'group' => $sourcePageTitle,
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

	/**
	 * Add an action to activate the translation
	 * @param $obj
	 * @param $links
	 * @return bool
	 */
	static function displayTab2( $obj, &$links ) {
		global $wgAutoSetPageLangAutoMarkTranslate;

		$title = $obj->getTitle();
		$isTranslatedPage = TranslatablePage::isTranslationPage($title);
		if($isTranslatedPage){
			return self::displayTab( $obj, $links['views'] );
		}

		$page = TranslatablePage::newFromTitle( $title );
		$marked = $page->getMarkedTag();
		if(!$wgAutoSetPageLangAutoMarkTranslate && !$marked){
			$url = SpecialPage::getSafeTitleFor('PageTranslation')->getFullUrl(['target' => $title->getFullText(), 'do' => 'mark']);
			$links['views']['markfortranslation'] = array(
				'class' => 'markfortranslation-button',
				'text' => 'Activer la traduction',
				'href' =>  $url
			);
		}

		return self::displayTab( $obj, $links['views'] );
	}
}
