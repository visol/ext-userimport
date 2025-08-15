<?php

return [
    'web_UserimportUserimport' => [
        'parent' => 'web',
        'access' => 'user',
        'labels' => 'LLL:EXT:userimport/Resources/Private/Language/locallang_userimport.xlf',
        'extensionName' => 'Userimport',
        'controllerActions' => [
            \Visol\Userimport\Controller\UserimportController::class => [
                'main',
                'upload',
                'options',
                'fieldMapping',
                'importPreview',
                'performImport',
            ],
        ],
    ],
];
