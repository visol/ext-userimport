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

        foreach ($rowsToImport as $row) {
            if ($updateExisting) {
                $updateExistingUniqueField = $importJob->getImportOption(ImportJob::IMPORT_OPTION_UPDATE_EXISTING_USERS_UNIQUE_FIELD);
                $existing = $queryBuilder
                    ->count('uid')
                    ->from('fe_users', null)
                    ->where(
                        $queryBuilder->expr()->eq($updateExistingUniqueField, $queryBuilder->createNamedParameter($row[$updateExistingUniqueField]))
                    )
                    ->execute()
                    ->fetchColumn(0);
                if ($existing) {
                    $feUsersConnection = $connectionPool->getConnectionForTable('fe_users');
                    $updatedRecords += $feUsersConnection->update(
                        'fe_users',
                        $row,
                        [$updateExistingUniqueField => $row[$updateExistingUniqueField]]
                    );

                    continue;
                }
            }
            // Must be newly imported
            $insertedRecords += $queryBuilder
                ->insert('fe_users', null)
                ->values($row)
                ->execute();
            continue;
        }

        $this->view->assign('updatedRecords', $updatedRecords);
        $this->view->assign('insertedRecords', $insertedRecords);
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
