<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

// https://docs.typo3.org/typo3cms/CoreApiReference/ApiOverview/Icon/Index.html

$extIconPath = 'EXT:userimport/Resources/Public/Icons/';

return [
    'tx_userimport-importjob' => [
        'provider' => SvgIconProvider::class,
        'source' => $extIconPath . 'tx_userimport_domain_model_importjob.svg',
    ],
];
