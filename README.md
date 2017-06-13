
## AutoSetPageLang

AutoSetPageLang is a mediawiki extension to set automatically the page lang attribute at page creation, using current language of user wich create the page.

## Installation

Extract extension ant place it in the 'extensions' directory of your installation. (the directory namme must be 'AutoSetPageLang'

Load extension and enable setting page Language in DB in file LocalSetting.php : 

```
wfLoadExtension( 'AutoSetPageLang' );
$wgPageLanguageUseDB = true;
$wgGroupPermissions['user']['pagelang'] = true;
```