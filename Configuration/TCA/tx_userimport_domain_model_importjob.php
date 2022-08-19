<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

$ll = 'LLL:EXT:userimport/Resources/Private/Language/locallang_db.xlf:';

$GLOBALS['TCA']['tx_userimport_domain_model_importjob'] = [
    'ctrl' => [
        'hideTable' => true,
        'title' => 'LLL:EXT:userimport/Resources/Private/Language/locallang_db.xlf:tx_userimport_domain_model_importjob',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'dividers2tabs' => true,
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden'
        ],
        'searchFields' => 'title,style,cached_votes,cached_rank,image,votes,',
        'iconfile' => 'EXT:userimport/Resources/Public/Icons/tx_userimport_domain_model_importjob.gif',
    ],
    'types' => [
        '1' => ['showitem' => 'hidden;;1, file, import_options, field_mapping'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
    'columns' => [
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
            ],
        ],
        'file' => [
            'exclude' => true,
            'label' => $ll . 'tx_userimport_domain_model_importjob.file',
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
        'import_options' => [
            'exclude' => true,
            'label' => $ll . 'tx_userimport_domain_model_importjob.import_options',
            'config' => [
                'type' => 'text',
                'cols' => 60,
                'rows' => 5,
            ]
        ],
        'field_mapping' => [
            'exclude' => true,
            'label' => $ll . 'tx_userimport_domain_model_importjob.field_mapping',
            'config' => [
                'type' => 'text',
                'cols' => 60,
                'rows' => 5,
            ]
        ],
    ],
];
