<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

$GLOBALS['TCA']['tx_userimport_domain_model_importjob'] = [
    'ctrl' => [
        'title' => 'LLL:EXT:userimport/Resources/Private/Language/locallang_db.xlf:tx_userimport_domain_model_importjob',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'dividers2tabs' => true,
        'sortby' => 'sorting',

        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden'
        ],
        'searchFields' => 'title,style,cached_votes,cached_rank,image,votes,',
        'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath(
                'userimport'
            ) . 'Resources/Public/Icons/tx_userimport_domain_model_importjob.gif'
    ],
    'interface' => [
        'showRecordFieldList' => 'hidden, file',
    ],
    'types' => [
        '1' => ['showitem' => 'hidden;;1, file'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
    'columns' => [
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
            ],
        ],
        'file' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:userimport/Resources/Private/Language/locallang_db.xlf:tx_userimport_domain_model_importjob.file',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
                'file',
                [
                    'appearance' => [
                        'createNewRelationLinkTitle' => 'LLL:EXT:cms/locallang_ttc.xlf:images.addFileReference'
                    ],
                    'foreign_match_fields' => [
                        'fieldname' => 'file',
                        'tablenames' => 'tx_userimport_domain_model_importjob',
                        'table_local' => 'sys_file',
                    ],
                    'minitems' => 1,
                    'maxitems' => 1,
                ],
                'xlsx,csv'
            ),
        ],
    ],
];
