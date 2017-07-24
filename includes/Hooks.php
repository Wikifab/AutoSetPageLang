<?php
namespace AutoSetPageLang;

use User;
use Revision;
use SpecialPageLanguage;
use Title;
use WikitextContent;
use JobQueueGroup;

class Hooks {

	/**
	 * watch Hook:PageContentInsertComplete
	 * when a new page is created, set page Language to the user's actual language
	 *
	 */
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
				'Tuto Details'
		];
		if ($targetTitle->exists()) {
			$languageCode = $targetTitle->getPageLanguage()->getCode();
		} else {
			$languageCode = $wgLang->getCode();
		}


		foreach ($templatesToUpdate as $templateName) {
			if(preg_match('/\{\{([\s])\{\{(tntn|Tntn)\|' . $templateName . '\}\}([\s])*\|/', $targetContent, $match)) {
				$targetContent = str_replace($match[0], $match[0] . "SourceLanguage=$languageCode\n|Language=$languageCode\n|IsTranslation=0\n|", $targetContent);
			} else if(preg_match('/\{\{' . $templateName . '([\s])*\|/', $targetContent, $match)) {
				$targetContent = str_replace($match[0], $match[0] . "SourceLanguage=$languageCode\n|Language=$languageCode\n|IsTranslation=0\n|", $targetContent);
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
			if (count($titleKeyArray) >= 2) {
				// if this is a translated page, we update his property
				$languageCode = end($titleKeyArray);
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
}