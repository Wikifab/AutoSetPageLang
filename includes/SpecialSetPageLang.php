<?php

namespace AutoSetPageLang;

/**
 * Special page for changing the content language of a page
 *
 * @ingroup SpecialPage
 */
class SpecialSetPageLang extends \SpecialPageLanguage {

	public function __construct() {
		\SpecialPage::__construct( 'SetPageLang', 'pagelang' );
	}

	/**
	 *
	 * @param array $data
	 * @return Status
	 */
	public function onSubmit( array $data ) {
		$pageName = $data['pagename'];

		// Check if user wants to use default language
		if ( $data['selectoptions'] == 1 ) {
			$newLanguage = 'default';
		} else {
			$newLanguage = $data['language'];
		}

		try {
			$title = \Title::newFromTextThrow( $pageName );
		} catch ( \MalformedTitleException $ex ) {
			return \Status::newFatal( $ex->getMessageObject() );
		}

		// Check permissions and make sure the user has permission to edit the page
		$errors = $title->getUserPermissionsErrors( 'edit', $this->getUser() );

		if ( $errors ) {
			$out = $this->getOutput();
			$wikitext = $out->formatPermissionsErrorMessage( $errors );
			// Hack to get our wikitext parsed
			return \Status::newFatal( new \RawMessage( '$1', [ $wikitext ] ) );
		}

		// Url to redirect to after the operation
		$this->goToUrl = $title->getFullUrlForRedirect(
			$title->isRedirect() ? [ 'redirect' => 'no' ] : []
		);

		return self::changePageLanguage(
			$this->getContext(),
			$title,
			$newLanguage,
			$data['reason'] === null ? '' : $data['reason']
		);
	}

	/**
	 * @param IContextSource $context
	 * @param Title $title
	 * @param string $newLanguage Language code
	 * @param string $reason Reason for the change
	 * @param array $tags Change tags to apply to the log entry
	 * @return Status
	 */
	public static function changePageLanguage( \IContextSource $context, \Title $title,
		$newLanguage, $reason, array $tags = [] ) {
		// Get the default language for the wiki
		$defLang = $context->getConfig()->get( 'LanguageCode' );

		$pageId = $title->getArticleID();

		// Check if article exists
		if ( !$pageId ) {
			return \Status::newFatal(
				'pagelang-nonexistent-page',
				wfEscapeWikiText( $title->getPrefixedText() )
			);
		}

		// Load the page language from DB
		$dbw = wfGetDB( DB_MASTER );
		$oldLanguage = $dbw->selectField(
			'page',
			'page_lang',
			[ 'page_id' => $pageId ],
			__METHOD__
		);

		// Check if user wants to use the default language
		if ( $newLanguage === 'default' ) {
			$newLanguage = null;
		}

		// No change in language
		if ( $newLanguage === $oldLanguage ) {
			// Check if old language does not exist
			if ( !$oldLanguage ) {
				return \Status::newFatal( \ApiMessage::create(
					[
						'pagelang-unchanged-language-default',
						wfEscapeWikiText( $title->getPrefixedText() )
					],
					'pagelang-unchanged-language'
				) );
			}
			return \Status::newFatal(
				'pagelang-unchanged-language',
				wfEscapeWikiText( $title->getPrefixedText() ),
				$oldLanguage
			);
		}

		// Hardcoded [def] if the language is set to null
		$logOld = $oldLanguage ? $oldLanguage : $defLang . '[def]';
		$logNew = $newLanguage ? $newLanguage : $defLang . '[def]';

		// Writing new page language to database
		$dbw->update(
			'page',
			[ 'page_lang' => $newLanguage ],
			[
				'page_id' => $pageId,
				'page_lang' => $oldLanguage
			],
			__METHOD__
		);

		if ( !$dbw->affectedRows() ) {
			return \Status::newFatal( 'pagelang-db-failed' );
		}

		// Logging change of language
		$logParams = [
			'4::oldlanguage' => $logOld,
			'5::newlanguage' => $logNew
		];
		$entry = new \ManualLogEntry( 'pagelang', 'pagelang' );
		$entry->setPerformer( $context->getUser() );
		$entry->setTarget( $title );
		$entry->setParameters( $logParams );
		$entry->setComment( $reason );
		$entry->setTags( $tags );

		$logid = $entry->insert();
		$entry->publish( $logid );

		// Force re-render so that language-based content (parser functions etc.) gets updated
		$title->invalidateCache();

		self::setLanguageProperty($title, $newLanguage);

		return \Status::newGood( (object)[
			'oldLanguage' => $logOld,
			'newLanguage' => $logNew,
			'logId' => $logid,
		] );
	}

	public static function setLanguageProperty(\Title $title, $newLanguage) {

		$wiki = new \WikiPage($title);

		if ($wiki->exists()) {

			$content = $wiki->getContent()->getNativeData();

			$content = preg_replace('/Language=(.*)/', 'Language=' . $newLanguage, $content);

			$content = \ContentHandler::makeContent( $content, $wiki->getTitle() );

			$wiki->doEditContent( $content, 'set language property');
		}
	}
}
