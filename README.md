
## AutoSetPageLang

AutoSetPageLang is a mediawiki extension to set automatically the page lang attribute at page creation, using current language of user wich create the page.


## Features

* Set page language at creation of new page, using user's language 

* Add 'languageSource', 'Language', and 'isTranslation' properties on saving pages 
For now, this in only on template named "Tuto Details", this must be parametized

* Automaticaly set Page revision as ready to be translate when property "complete" is set to "Yes" ( to activate it, set $wgAutoSetPageLangAutoMarkTranslate = true; )

* remove <translate> tags for all tutorials that are not 'complete'

* add a translate tab, to each page ready to be translated, and when user has translate rights


## Installation

Extract extension ant place it in the 'extensions' directory of your installation. (the directory namme must be 'AutoSetPageLang')

Load extension and enable setting page Language in DB in file LocalSetting.php : 

```
wfLoadExtension( 'AutoSetPageLang' );
$wgPageLanguageUseDB = true;
$wgGroupPermissions['user']['pagelang'] = true;
```

## Configuration

To disable auto-mark page as ready to be translate : 
```
$wgAutoSetPageLangAutoMarkTranslate = false;
```
To disable remove translate tags on all tutorial not complete : 
```
$wgAutoSetPageLangTranslateOnCompleteOnly = false;
```

To set allowed namespaces : 
```
$wgAutoSetPageLangAllowedNamespaces
```

default : NS_MAIN (0)
