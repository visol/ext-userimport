<?php

namespace Visol\Userimport\Service;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Visol\Userimport\Domain\Model\ImportJob;
use Visol\Userimport\Utility\LogUtility;

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
class UserImportService implements SingletonInterface
{
    /**
     * Imports/updates all given rows as fe_user records respecting the options in the ImportJob
     *
     * @return array
     */
    public function performImport(ImportJob $importJob, array $rowsToImport = [])
    {
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $connectionPool->getQueryBuilderForTable('fe_users');

        $updateExisting = (bool) $importJob->getImportOption(ImportJob::IMPORT_OPTION_UPDATE_EXISTING_USERS);

        $updatedRecords = 0;
        $insertedRecords = 0;

        $log = [];

        $feUsersConnection = $connectionPool->getConnectionForTable('fe_users');

        foreach ($rowsToImport as $row) {
            $rowForLog = LogUtility::formatRowForImportLog($row);

            if ($updateExisting) {
                $targetFolder = (int) $importJob->getImportOption(ImportJob::IMPORT_OPTION_TARGET_FOLDER);
                $updateExistingUniqueField = $importJob->getImportOption(ImportJob::IMPORT_OPTION_UPDATE_EXISTING_USERS_UNIQUE_FIELD);
                $existing = $feUsersConnection->count(
                    'uid',
                    'fe_users',
                    [
                        $updateExistingUniqueField => $row[$updateExistingUniqueField],
                        'pid' => $targetFolder,
                        'deleted' => 0,
                        'disable' => 0,
                    ]
                );
                if ($existing === 1) {
                    $affectedRecords = $feUsersConnection->update(
                        'fe_users',
                        $row,
                        [
                            $updateExistingUniqueField => $row[$updateExistingUniqueField],
                            'pid' => $targetFolder,
                            'deleted' => 0,
                            'disable' => 0,
                        ]
                    );
                    if ($affectedRecords === 1) {
                        $log[] = [
                            'action' => 'update.success',
                            'row' => $rowForLog,
                        ];
                    } else {
                        // Error case
                        $log[] = [
                            'action' => 'update.fail',
                            'row' => $rowForLog,
                        ];
                    }
                    $updatedRecords += $affectedRecords;
                    continue;
                }
                if ($existing > 1) {
                    // More than one record, fail
                    $log[] = [
                        'action' => 'update.moreThanOneRecordFound',
                        'row' => $rowForLog,
                    ];
                }
            }
            // Must be newly imported
            $affectedRecords = $queryBuilder
                ->insert('fe_users')->values($row)->executeStatement();

            if ($affectedRecords < 1) {
                // Error case
                $log[] = [
                    'action' => 'insert.fail',
                    'row' => $rowForLog,
                ];
            } else {
                $log[] = [
                    'action' => 'insert.success',
                    'row' => $rowForLog,
                ];
            }
            $insertedRecords += $affectedRecords;
        }

        return [
            'insertedRecords' => $insertedRecords,
            'updatedRecords' => $updatedRecords,
            'log' => $log,
        ];
    }
}
