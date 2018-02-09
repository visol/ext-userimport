<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function()
    {

        if (TYPO3_MODE === 'BE') {

            \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
                'Visol.Userimport',
                'web', // Make module a submodule of 'web'
                'userimport', // Submodule key
                '', // Position
                [
                    'Userimport' => 'main,upload,options,fieldMapping,importPreview,performImport',
                    
                ],
                [
                    'access' => 'user,group',
                    'navigationComponentId' => null,
                    'icon'   => 'EXT:userimport/Resources/Public/Icons/user_mod_userimport.svg',
                    'labels' => 'LLL:EXT:userimport/Resources/Private/Language/locallang_userimport.xlf',
                ]
            );

        }

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('userimport', 'Configuration/TypoScript', 'Frontend User Import');

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_userimport_domain_model_userimport', 'EXT:userimport/Resources/Private/Language/locallang_csh_tx_userimport_domain_model_userimport.xlf');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_userimport_domain_model_userimport');

    }
);
