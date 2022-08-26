<?php
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use Visol\Userimport\Controller\UserimportController;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
defined('TYPO3') || die('Access denied.');

call_user_func(
    function () {

        if (TYPO3_MODE === 'BE') {

            ExtensionUtility::registerModule(
                'Userimport',
                'web', // Make module a submodule of 'web'
                'userimport', // Submodule key
                '', // Position
                [
                    UserimportController::class => 'main,upload,options,fieldMapping,importPreview,performImport',

                ],
                [
                    'access' => 'user,group',
                    'navigationComponentId' => null,
                    'icon' => 'EXT:userimport/Resources/Public/Icons/user_mod_userimport.svg',
                    'labels' => 'LLL:EXT:userimport/Resources/Private/Language/locallang_userimport.xlf',
                ]
            );
        }

        ExtensionManagementUtility::addStaticFile('userimport', 'Configuration/TypoScript', 'Frontend User Import');
    }
);
