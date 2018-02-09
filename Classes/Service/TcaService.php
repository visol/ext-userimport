<?php

namespace Visol\Userimport\Service;

/***
 *
 * This file is part of the "Frontend User Import" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018 Lorenz Ulrich <lorenz.ulrich@visol.ch>, visol digitale Dienstleistungen GmbH
 *
 ***/

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TcaService implements SingletonInterface
{

    /**
     * @return array
     */
    public function getFrontendUserFolders()
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $result = $queryBuilder
            ->select('uid')
            ->addSelectLiteral("CONCAT(title, ' (uid ', uid, ')') AS title")
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('doktype', 254),
                $queryBuilder->expr()->eq('module', $queryBuilder->createNamedParameter('fe_users', \PDO::PARAM_STR))
            )
            ->execute()
            ->fetchAll();
        return $result;
    }

    /**
     * @return array
     */
    public function getFrontendUserGroups()
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('fe_groups');
        $result = $queryBuilder
            ->select('uid', 'title')
            ->from('fe_groups')
            ->execute()
            ->fetchAll();
        return $result;
    }

    public function getFrontendUserTableFieldNames()
    {
        // TODO: In rs_userimp, this could be configured in the extension configuration
        return [
            [
                'value' => 'name',
                'label' => 'name'
            ],
            [
                'value' => 'username',
                'label' => 'username'
            ],
            [
                'value' => 'email',
                'label' => 'email'
            ]
        ];
    }
}
