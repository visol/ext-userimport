<?php

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use Visol\Userimport\Mvc\Property\TypeConverter\UploadedFileReferenceConverter;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
defined('TYPO3') || die('Access denied.');

(function () {

    ExtensionUtility::registerTypeConverter(UploadedFileReferenceConverter::class);

    ExtensionManagementUtility::addTypoScriptSetup(
        '@import \'EXT:userimport/Configuration/TypoScript/setup.typoscript\''
    );

    ExtensionManagementUtility::addTypoScriptConstants(
        '@import \'EXT:userimport/Configuration/TypoScript/constants.typoscript\''
    );
})();
