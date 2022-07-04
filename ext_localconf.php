<?php

defined('TYPO3_MODE') || die('Access denied.');

(function () {

    TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter(\Visol\Userimport\Mvc\Property\TypeConverter\UploadedFileReferenceConverter::class);

    TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup(
        '@import \'EXT:userimport/Resources/Private/TypoScript/setup.ts\''
    );

    TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptConstants(
        '@import \'EXT:userimport/Resources/Private/TypoScript/constants.ts\''
    );
})();
