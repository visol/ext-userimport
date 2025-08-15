<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die('Access denied.');

(function () {
    ExtensionManagementUtility::addTypoScriptSetup(
        '@import \'EXT:userimport/Configuration/TypoScript/setup.typoscript\''
    );

    ExtensionManagementUtility::addTypoScriptConstants(
        '@import \'EXT:userimport/Configuration/TypoScript/constants.typoscript\''
    );
})();
