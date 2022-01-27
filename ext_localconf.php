<?php

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use Visol\Userimport\Mvc\Property\TypeConverter\UploadedFileReferenceConverter;

defined('TYPO3_MODE') || die('Access denied.');

(function ($extKey = 'userimport') {
    ExtensionUtility::registerTypeConverter(
        UploadedFileReferenceConverter::class
    );

    TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup(
        '@import \'EXT:userimport/Resources/Private/TypoScript/setup.ts\''
    );

    TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptConstants(
        '@import \'EXT:userimport/Resources/Private/TypoScript/constants.ts\''
    );
})();
