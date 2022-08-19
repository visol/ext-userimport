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

use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use Visol\Userimport\Domain\Model\ImportJob;
use Visol\Userimport\Mvc\Property\TypeConverter\UploadedFileReferenceConverter;

/**
 * UserimportController
 */
class UserimportController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    /**
     * @var \Visol\Userimport\Domain\Repository\ImportJobRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $importJobRepository = null;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $persistenceManager = null;

    /**
     * @var \Visol\Userimport\Service\SpreadsheetService
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $spreadsheetService = null;

    /**
     * @var \Visol\Userimport\Service\UserImportService
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $userImportService = null;

    /**
     * @var \Visol\Userimport\Service\TcaService
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $tcaService = null;

    /**
     * @return void
     */
    public function mainAction()
    {
        $importJob = $this->objectManager->get(ImportJob::class);

        $configurationUtility = $this->objectManager->get('TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility');
        $moduleConfiguration = $configurationUtility->getCurrentConfiguration('userimport');
        if (!empty($moduleConfiguration['uploadStorageFolder']['value'])) {
            $this->view->assign('uploadStorageFolder', $moduleConfiguration['uploadStorageFolder']['value']);
        }

        $this->view->assign('importJob', $importJob);
    }

    protected function initializeUploadAction()
    {
        /** @var PropertyMappingConfiguration $propertyMappingConfiguration */
        $propertyMappingConfiguration = $this->arguments['importJob']->getPropertyMappingConfiguration();
        $uploadConfiguration = [
            UploadedFileReferenceConverter::CONFIGURATION_ALLOWED_FILE_EXTENSIONS => 'xlsx,csv'
        ];
        $propertyMappingConfiguration->allowProperties('file');
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

        $result = $this->userImportService->performImport($rowsToImport, $importJob);


        $this->view->assign('updatedRecords', $result['updatedRecords']);
        $this->view->assign('insertedRecords', $result['insertedRecords']);
        $this->view->assign('log', $result['log']);

        $this->view->assign('targetFolder', $importJob->getImportOption(ImportJob::IMPORT_OPTION_TARGET_FOLDER));

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
