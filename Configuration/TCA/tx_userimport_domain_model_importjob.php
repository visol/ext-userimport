<?php

if (!defined('TYPO3')) {
    die('Access denied.');
}

$ll = 'LLL:EXT:userimport/Resources/Private/Language/locallang_db.xlf:';

$GLOBALS['TCA']['tx_userimport_domain_model_importjob'] = [
    'ctrl' => [
        'hideTable' => true,
        'title' => 'LLL:EXT:userimport/Resources/Private/Language/locallang_db.xlf:tx_userimport_domain_model_importjob',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'searchFields' => 'title,style,cached_votes,cached_rank,image,votes,',
        'iconfile' => 'EXT:userimport/Resources/Public/Icons/tx_userimport_domain_model_importjob.gif',
    ],
    'types' => [
        '1' => ['showitem' => 'hidden,--palette--;;1,file,import_options,field_mapping'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
    'columns' => [
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
            ],
        ],
        'file' => [
            'exclude' => true,
            'label' => $ll . 'tx_userimport_domain_model_importjob.file',
            'config' => [
                // !!! Watch out for fieldName different from columnName
                'type' => 'file',
                'allowed' => 'xlsx,csv',
                'appearance' => [
                    'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference',
                ],
                'foreign_match_fields' => [
                    'fieldname' => 'file',
                    'tablenames' => 'tx_userimport_domain_model_importjob',
                ],
                'minitems' => 1,
                'maxitems' => 1,
            ],
        ],
        'import_options' => [
            'exclude' => true,
            'label' => $ll . 'tx_userimport_domain_model_importjob.import_options',
            'config' => [
                'type' => 'text',
                'cols' => 60,
                'rows' => 5,
            ],
        ],
        'field_mapping' => [
            'exclude' => true,
            'label' => $ll . 'tx_userimport_domain_model_importjob.field_mapping',
            'config' => [
                'type' => 'text',
                'cols' => 60,
                'rows' => 5,
            ],
        ],
    ],
];
