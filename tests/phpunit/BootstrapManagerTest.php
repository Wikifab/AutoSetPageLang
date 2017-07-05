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

	protected  $instance = null;

	protected function setUp() {
		parent::setUp();
		$this->wgResourceModules = $GLOBALS['wgResourceModules'];

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
		$GLOBALS['wgResourceModules'] = $this->wgResourceModules;
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
{{ {{tntn|Tuto Status}}}}";
		$this->instance->onPageFormsWritePageData($form, $targetTitle, $targetContent);

		$expectedContent = "{{ {{tntn|Tuto Details}}
|SourceLanguage=fr
|Language=fr
|IsTranslation=0
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
{{Tuto Status}}";
		$this->instance->onPageFormsWritePageData($form, $targetTitle, $targetContent);

		$expectedContent = "{{Tuto Details
|SourceLanguage=fr
|Language=fr
|IsTranslation=0
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
{{Tuto Status}}";

		$this->assertEquals($expectedContent, $targetContent);
	}

}
