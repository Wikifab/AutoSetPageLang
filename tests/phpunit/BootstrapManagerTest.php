<?php

namespace AutoSetPageLang\Tests;

use AutoSetPageLang\Hooks;

/**
 * @uses \Bootstrap\BootstrapManager
 *
 * @ingroup Test
 *
 * @group extension-bootstrap
 * @group mediawiki-databaseless
 *
 * @license GNU GPL v3+
 * @since 1.0
 *
 * @author mwjames
 */
class HooksTest extends \PHPUnit_Framework_TestCase {

	protected $wgResourceModules = null;
	protected $wgLang = null;

	protected  $instance = null;

	protected function setUp() {
		parent::setUp();
		$this->wgResourceModules = $GLOBALS['wgResourceModules'];

		$this->wgLang = $GLOBALS['wgLang'];

		// language preset for test purposes
		$GLOBALS['wgLang'] = \Language::factory( 'fr' );	

		// Preset with empty default values to verify the initialization status
		// during invocation
		$GLOBALS['wgResourceModules'][ 'ext.bootstrap.styles' ] = array(
			'localBasePath'   => '',
			'remoteBasePath'  => '',
			'class'           => '',
			'dependencies'    => array(),
			'styles'          => array(),
			'variables'       => array(),
			'external styles' => array()
		);

		$GLOBALS['wgResourceModules'][ 'ext.bootstrap.scripts' ] = array(
			'dependencies'    => array(),
			'scripts'         => array()
		);

		$this->instance = new Hooks();
	}

	protected function tearDown() {

		// set back to initial values
		$GLOBALS['wgResourceModules'] = $this->wgResourceModules;
		$GLOBALS['wgLang'] = $this->wgLang;
		parent::tearDown();
	}


	public function testOnPageFormsWritePageDataWithTntn( ) {

		$form = null;
		$targetTitle = new \Title();
		$targetContent = "{{ {{tntn|Tuto Details}}
|Type=Technique
|Area=Clothing and Accessories
|Tags=test,
|Description=test de tuto en francais
|Difficulty=Very easy
|Cost=2
|Currency=EUR (€)
|Duration=3
|Duration-type=minute(s)
|Licences=Attribution (CC BY)
}}
{{ {{tntn|Introduction}}}}
{{ {{tntn|Materials}}}}
{{ {{tntn|Separator}}}}
{{ {{tntn|Tuto Step}}
|Step_Title=debrouille toi vite
}}
{{ {{tntn|Notes}}}}
{{ {{tntn|PageLang}}}}
{{ {{tntn|Tuto Status}}}}";
		$this->instance->onPageFormsWritePageData($form, $targetTitle, $targetContent);

		$expectedContent = "{{ {{tntn|Tuto Details}}
|Type=Technique
|Area=Clothing and Accessories
|Tags=test,
|Description=test de tuto en francais
|Difficulty=Very easy
|Cost=2
|Currency=EUR (€)
|Duration=3
|Duration-type=minute(s)
|Licences=Attribution (CC BY)
}}
{{ {{tntn|Introduction}}}}
{{ {{tntn|Materials}}}}
{{ {{tntn|Separator}}}}
{{ {{tntn|Tuto Step}}
|Step_Title=debrouille toi vite
}}
{{ {{tntn|Notes}}}}
{{ {{tntn|PageLang}}
|SourceLanguage=none
|Language=fr
|IsTranslation=0
}}
{{ {{tntn|Tuto Status}}}}";

		$this->assertEquals($expectedContent, $targetContent);

	}


	public function testOnPageFormsWritePageDataWithoutTntn( ) {

		$form = null;
		$targetTitle = new \Title();
		$targetContent = "{{Tuto Details
|Type=Technique
|Area=Clothing and Accessories
|Tags=test,
|Description=test de tuto en francais
|Difficulty=Very easy
|Cost=2
|Currency=EUR (€)
|Duration=3
|Duration-type=minute(s)
|Licences=Attribution (CC BY)
}}
{{Introduction}}
{{Materials}}
{{Separator}}
{{Tuto Step
|Step_Title=debrouille toi vite
}}
{{Notes}}
{{PageLang}}
{{Tuto Status}}";
		$this->instance->onPageFormsWritePageData($form, $targetTitle, $targetContent);

		$expectedContent = "{{Tuto Details
|Type=Technique
|Area=Clothing and Accessories
|Tags=test,
|Description=test de tuto en francais
|Difficulty=Very easy
|Cost=2
|Currency=EUR (€)
|Duration=3
|Duration-type=minute(s)
|Licences=Attribution (CC BY)
}}
{{Introduction}}
{{Materials}}
{{Separator}}
{{Tuto Step
|Step_Title=debrouille toi vite
}}
{{Notes}}
{{PageLang
|SourceLanguage=none
|Language=fr
|IsTranslation=0
}}
{{Tuto Status}}";

		$this->assertEquals($expectedContent, $targetContent);
	}

}
