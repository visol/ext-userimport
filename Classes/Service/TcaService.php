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
use TYPO3\CMS\Core\Database\QueryGenerator;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TcaService implements SingletonInterface
{

    /**
     * Return all pages of type folder containing frontend users
     *
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
     * Return all frontend user groups
     *
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

    /**
     * Return an array with all fields we can use for a unique check
     *
     * @return array
     */
    public function getFrontendUserTableUniqueFieldNames()
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

    /**
     * Return an array with all fields we can import to
     *
     * @return array
     */
    public function getFrontendUserTableFieldNames()
    {
        /** @var QueryGenerator $queryGenerator */
        $queryGenerator = GeneralUtility::makeInstance(QueryGenerator::class);
        $queryGenerator->table = 'fe_users';

        $fieldsToExclude = ['image', 'TSconfig', 'lastlogin', 'felogin_forgotHash', 'uid', 'pid', 'deleted', 'tstamp', 'crdate', 'cruser_id'];

        $fieldList = $queryGenerator->makeFieldList();
        $fieldArray = [];
        foreach (GeneralUtility::trimExplode(',', $fieldList) as $fieldName) {
            if (in_array($fieldName, $fieldsToExclude)) {
                // Ignore senseless or dangerous fields
                continue;
            }
            $fieldArray[] = [
                'label' => $fieldName,
                'value' => $fieldName
            ];
        }
        return $fieldArray;
    }
}
