
## AutoSetPageLang

AutoSetPageLang is a mediawiki extension to set automatically the page lang attribute at page creation, using current language of user wich create the page.

It also add a "Language" property to semantic pages, with the current language 
For now only on template named "Tuto Details", this must be parametized

## Features

* Set page language at creation of new page, using user's language 

* Add 'languageSource', 'Language', and 'isTranslation' properties on saving pages 
For now, this in only on template named "Tuto Details", this must be parametized

* Automaticaly set Page revision as ready to be translate when property "complete" is set to "Yes" ( to activate it, set $wgAutoSetPageLangAutoMarkTranslate = true; )


## Installation

Extract extension ant place it in the 'extensions' directory of your installation. (the directory namme must be 'AutoSetPageLang')

Load extension and enable setting page Language in DB in file LocalSetting.php : 

```
wfLoadExtension( 'AutoSetPageLang' );
$wgPageLanguageUseDB = true;
$wgGroupPermissions['user']['pagelang'] = true;
```

## Configuration

To enable auto-mark page as ready to be translate : 
```
$wgAutoSetPageLangAutoMarkTranslate = true;
```