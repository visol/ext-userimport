<?php

use Visol\Userimport\Controller\UserimportController;

return [
    'web_UserimportUserimport' => [
        'parent' => 'web',
        'access' => 'user',
        'labels' => 'LLL:EXT:userimport/Resources/Private/Language/locallang_userimport.xlf',
        'extensionName' => 'Userimport',
        'controllerActions' => [
            UserimportController::class => [
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
