<?php
$wgExtensionCredits['other'][] = array(
	'name' => 'Rich Text Editor (Wysiwyg)',
	'description' => 'CKeditor integration for MediaWiki',
	'url' => 'http://www.wikia.com/wiki/c:help:Help:New_editor',
	'author' => array('Inez Korczyński', 'Maciej Brencz')
);

$dir = dirname(__FILE__);

// autoloaded classes
$wgAutoloadClasses['RTE'] = "$dir/RTE.class.php";
$wgAutoloadClasses['RTEAjax'] = "$dir/RTEAjax.class.php";
$wgAutoloadClasses['RTEParser'] = "$dir/RTEParser.class.php";
$wgAutoloadClasses['RTEReverseParser'] = "$dir/RTEReverseParser.class.php";
$wgAutoloadClasses['RTELinker'] = "$dir/RTELinker.class.php";
$wgAutoloadClasses['RTEMarker'] = "$dir/RTEMarker.class.php";
$wgAutoloadClasses['RTEData'] = "$dir/RTEData.class.php";
$wgAutoloadClasses['RTEMagicWord'] = "$dir/RTEMagicWord.class.php";

// hooks
$wgHooks['EditPage::showEditForm:initial'][] = 'RTE::init';
$wgHooks['ParserMakeImageParams'][] = 'RTEParser::makeImageParams';
$wgHooks['AlternateEdit'][] = 'RTE::reverse';
$wgHooks['EditPageBeforeConflictDiff'][] = 'RTE::reverse';

// hooks for user preferences handling
$wgHooks['getEditingPreferencesTab'][] = 'RTE::userPreferences';
$wgHooks['UserToggles'][] = 'RTE::userToggle';
$wgHooks['UserGetOption'][] = 'RTE::userGetOption';

// __NOWYSIWYG__ magic words handling
$wgHooks['MagicWordwgVariableIDs'][] = 'RTEMagicWord::register';
$wgHooks['LanguageGetMagic'][] = 'RTEMagicWord::get';
$wgHooks['InternalParseBeforeLinks'][] = 'RTEMagicWord::remove';
$wgHooks['ParserBeforeStrip'][] = 'RTEMagicWord::checkParserBeforeStrip';
$wgHooks['EditPage::getContent::end'][] = 'RTEMagicWord::checkEditPageContent';
//$wgHooks['Parser::FetchTemplateAndTitle'][] = 'RTEMagicWord::fetchTemplate'; # not called when doing RTE parsing

// enable MW suggest - this needs to be set here to make API calls working
$wgEnableMWSuggest = true;

// Ajax dispatcher
$wgAjaxExportList[] = 'RTEAjax';
function RTEAjax() {
        global $wgRequest;
        $method = $wgRequest->getVal('method', false);

        if ($method && method_exists('RTEAjax', $method)) {
                //wfLoadExtensionMessages('RTE');

                $data = RTEAjax::$method();
                $json = Wikia::json_encode($data);

                $response = new AjaxResponse($json);
                $response->setContentType('application/json; charset=utf-8');
                return $response;
        }
}
