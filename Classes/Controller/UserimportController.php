<?php

namespace Visol\Userimport\Controller;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use Visol\Userimport\Domain\Model\ImportJob;
use Visol\Userimport\Mvc\Property\TypeConverter\UploadedFileReferenceConverter;
use Visol\Userimport\Utility\FormattingUtility;

/**
 * UserimportController
 */
class UserimportController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    /**
     * @var \Visol\Userimport\Domain\Repository\ImportJobRepository
     * @inject
     */
    protected $importJobRepository = null;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
     * @inject
     */
    protected $persistenceManager = null;

    /**
     * @var \Visol\Userimport\Service\SpreadsheetService
     * @inject
     */
    protected $spreadsheetService = null;

    /**
     * @var \Visol\Userimport\Service\TcaService
     * @inject
     */
    protected $tcaService = null;

    /**
     * @return void
     */
    public function mainAction()
    {
        $importJob = $this->objectManager->get(ImportJob::class);
        $this->view->assign('importJob', $importJob);
    }

    protected function initializeUploadAction()
    {
        $propertyMappingConfiguration = $this->arguments['importJob']->getPropertyMappingConfiguration();
        $uploadConfiguration = [
            UploadedFileReferenceConverter::CONFIGURATION_ALLOWED_FILE_EXTENSIONS => 'xlsx,csv'
        ];
        $propertyMappingConfiguration->allowAllProperties();
        $propertyMappingConfiguration->forProperty('file')
            ->setTypeConverterOptions(
                UploadedFileReferenceConverter::class,
                $uploadConfiguration
            );
    }

    /**
     * @param ImportJob $importJob
     *
     * @return void
     */
    public function uploadAction(ImportJob $importJob)
    {
        $this->importJobRepository->add($importJob);
        $this->persistenceManager->persistAll();
        $this->redirect('options', null, null, ['importJob' => $importJob]);
    }

    /**
     * @param ImportJob $importJob
     */
    public function optionsAction(ImportJob $importJob)
    {
        $this->view->assign('importJob', $importJob);
        $this->view->assign('allowedFolders', $importJob);

        if ($importJob->getFile() instanceof FileReference) {
            $fileName = $importJob->getFile()->getOriginalResource()->getForLocalProcessing();
            $spreadsheetContent = $this->spreadsheetService->getContent($fileName, 5);
            $this->view->assign('spreadsheetContent', $spreadsheetContent);
        }

        $this->view->assign('frontendUserFolders', $this->tcaService->getFrontendUserFolders());
        $this->view->assign('frontendUserGroups', $this->tcaService->getFrontendUserGroups());
        $this->view->assign('frontendUserTableFieldNames', $this->tcaService->getFrontendUserTableUniqueFieldNames());
    }

    /**
     * @param ImportJob $importJob
     */
    public function fieldMappingAction(ImportJob $importJob)
    {
        $this->view->assign('importJob', $importJob);

        // Update ImportJob with options
        $fieldOptionArguments = [
            ImportJob::IMPORT_OPTION_TARGET_FOLDER,
            ImportJob::IMPORT_OPTION_FIRST_ROW_CONTAINS_FIELD_NAMES,
            ImportJob::IMPORT_OPTION_USE_EMAIL_AS_USERNAME,
            ImportJob::IMPORT_OPTION_GENERATE_PASSWORD,
            ImportJob::IMPORT_OPTION_USER_GROUPS,
            ImportJob::IMPORT_OPTION_UPDATE_EXISTING_USERS,
            ImportJob::IMPORT_OPTION_UPDATE_EXISTING_USERS_UNIQUE_FIELD
        ];
        $fieldOptionsArray = [];
        foreach ($fieldOptionArguments as $argumentName) {
            $fieldOptionsArray[$argumentName] = $this->request->getArgument($argumentName);
        }
        $importJob->setImportOptions($fieldOptionsArray);
        $this->importJobRepository->update($importJob);
        $this->persistenceManager->persistAll();

        // Generate data for field mapping
        $this->view->assign('frontendUserTableFieldNames', $this->tcaService->getFrontendUserTableFieldNames());
        $fileName = $importJob->getFile()->getOriginalResource()->getForLocalProcessing();
        $this->view->assign(
            'columnLabelsAndExamples',
            $this->spreadsheetService->getColumnLabelsAndExamples(
                $fileName,
                $importJob->getImportOption(ImportJob::IMPORT_OPTION_FIRST_ROW_CONTAINS_FIELD_NAMES)
            )
        );

        // If username is not generated from e-mail, the field must be mapped
        $usernameMustBeMapped = !(bool)$importJob->getImportOption(ImportJob::IMPORT_OPTION_USE_EMAIL_AS_USERNAME);
        $this->view->assign('usernameMustBeMapped', $usernameMustBeMapped);

        // If username is generated from e-mail, the field e-mail must be mapped
        $emailMustBeMapped = (bool)$importJob->getImportOption(ImportJob::IMPORT_OPTION_USE_EMAIL_AS_USERNAME);
        $this->view->assign('emailMustBeMapped', $emailMustBeMapped);
    }

    /**
     * @param ImportJob $importJob
     * @param array $fieldMapping
     */
    public function importPreviewAction(ImportJob $importJob, array $fieldMapping)
    {
        $this->view->assign('importJob', $importJob);

        // Update ImportJob with field mapping
        $importJob->setFieldMapping($fieldMapping);
        $this->importJobRepository->update($importJob);
        $this->persistenceManager->persistAll();

        $previewData = $this->spreadsheetService->generateDataFromImportJob($importJob, true);
        $this->view->assign('previewDataHeader', array_keys($previewData[0]));
        $this->view->assign('previewData', $previewData);
    }

    /**
     * @param ImportJob $importJob
     */
    public function performImportAction(ImportJob $importJob)
    {
        $rowsToImport = $this->spreadsheetService->generateDataFromImportJob($importJob);
        $this->view->assign('rowsInSource', count($rowsToImport));

        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $connectionPool->getQueryBuilderForTable('fe_users');

        $updateExisting = (bool)$importJob->getImportOption(ImportJob::IMPORT_OPTION_UPDATE_EXISTING_USERS);

        $updatedRecords = 0;
        $insertedRecords = 0;

        $log = [];

        $feUsersConnection = $connectionPool->getConnectionForTable('fe_users');

        foreach ($rowsToImport as $row) {
            $rowForLog = FormattingUtility::formatRowForImportLog($row);

            if ($updateExisting) {
                $targetFolder = (int)$importJob->getImportOption(ImportJob::IMPORT_OPTION_TARGET_FOLDER);
                $updateExistingUniqueField = $importJob->getImportOption(ImportJob::IMPORT_OPTION_UPDATE_EXISTING_USERS_UNIQUE_FIELD);
                $existing = $feUsersConnection->count(
                    'uid',
                    'fe_users',
                    [
                        $updateExistingUniqueField => $row[$updateExistingUniqueField],
                        'pid' => $targetFolder,
                        'deleted' => 0,
                        'disable' => 0
                    ]
                );
                if ($existing > 0) {
                    $affectedRecords = $feUsersConnection->update(
                        'fe_users',
                        $row,
                        [
                            $updateExistingUniqueField => $row[$updateExistingUniqueField],
                            'pid' => $targetFolder,
                            'deleted' => 0,
                            'disable' => 0
                        ]
                    );

                    if ($affectedRecords < 1) {
                        // Error case
                        $log[] = [
                            'action' => 'update.fail',
                            'row' => $rowForLog
                        ];
                    } else {
                        $log[] = [
                            'action' => 'update.success',
                            'row' => $rowForLog
                        ];
                    }

                    $updatedRecords += $affectedRecords;

                    continue;
                }
            }
            // Must be newly imported
            $affectedRecords = $queryBuilder
                ->insert('fe_users', null)
                ->values($row)
                ->execute();

            if ($affectedRecords < 1) {
                // Error case
                $log[] = [
                    'action' => 'insert.fail',
                    'row' => $rowForLog
                ];
            } else {
                $log[] = [
                    'action' => 'insert.success',
                    'row' => $rowForLog
                ];
            }
            $insertedRecords += $affectedRecords;

            continue;
        }

        $this->view->assign('updatedRecords', $updatedRecords);
        $this->view->assign('insertedRecords', $insertedRecords);
        $this->view->assign('log', $log);

        // Remove import job
        $this->importJobRepository->remove($importJob);
        $this->persistenceManager->persistAll();
    }

    /**
     * Deactivate errorFlashMessage
     *
     * @return bool|string
     */
    public function getErrorFlashMessage()
    {
        return false;
    }
}
