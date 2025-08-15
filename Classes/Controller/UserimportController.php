<?php

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

namespace Visol\Userimport\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Controller\FileUploadConfiguration;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Validation\Validator\MimeTypeValidator;
use Visol\Userimport\Domain\Model\ImportJob;
use Visol\Userimport\Domain\Repository\ImportJobRepository;
use Visol\Userimport\Service\SpreadsheetService;
use Visol\Userimport\Service\TcaService;
use Visol\Userimport\Service\UserImportService;

#[AsController]
final class UserimportController extends ActionController
{
    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected ExtensionConfiguration $extensionConfiguration,
        protected ImportJob $importJob,
        protected ImportJobRepository $importJobRepository,
        protected PersistenceManagerInterface $persistenceManager,
        protected SpreadsheetService $spreadsheetService,
        protected UserImportService $userImportService,
        protected TcaService $tcaService,
    ) {
    }

    public function mainAction(): ResponseInterface
    {
        $this->view = $this->moduleTemplateFactory->create($this->request);

        $moduleConfiguration = $this->extensionConfiguration->get('userimport');

        if ($moduleConfiguration['uploadStorageFolder'] !== '') {
            $this->view->assign('uploadStorageFolder', $moduleConfiguration['uploadStorageFolder']);
        }

        $this->view->assign('importJob', $this->importJob);
        return $this->view->renderResponse('Userimport/Main');
    }

    protected function initializeUploadAction()
    {
        // As Validators can contain state, do not inject them
        $mimeTypeValidator = GeneralUtility::makeInstance(MimeTypeValidator::class);
        $mimeTypeValidator->setOptions([
            'allowedMimeTypes' => ['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'ignoreFileExtensionCheck' => false,
            'notAllowedMessage' => 'Not allowed file type',
            'invalidExtensionMessage' => 'Invalid file extension',
        ]);

        $moduleConfiguration = $this->extensionConfiguration->get('userimport');

        $fileHandlingServiceConfiguration = $this->arguments->getArgument('importJob')->getFileHandlingServiceConfiguration();
        $fileHandlingServiceConfiguration->addFileUploadConfiguration(
            (new FileUploadConfiguration('file'))
                ->setRequired()
                ->addValidator($mimeTypeValidator)
                ->setMaxFiles(1)
                ->setUploadFolder($moduleConfiguration['uploadStorageFolder']),
        );

        // Extbase's property mapping is not handling FileUploads, so it must not operate on this property.
        // When using the FileUpload attribute/annotation, this internally does the same. This is covered
        // by the `addFileUploadConfiguration()` functionality.
        $this->arguments->getArgument('importJob')->getPropertyMappingConfiguration()->skipProperties('file');
    }

    /**
     * @return ResponseInterface
     */
    public function uploadAction(ImportJob $importJob)
    {
        $this->importJobRepository->add($importJob);
        $this->persistenceManager->persistAll();
        return $this->redirect('options', null, null, ['importJob' => $importJob]);
    }

    public function optionsAction(ImportJob $importJob): ResponseInterface
    {
        $this->view = $this->moduleTemplateFactory->create($this->request);
        $this->view->assign('importJob', $importJob);

        if ($importJob->getFile() instanceof FileReference) {
            $fileName = $importJob->getFile()->getOriginalResource()->getForLocalProcessing();
            $spreadsheetContent = $this->spreadsheetService->getContent($fileName, 5);
            $this->view->assign('spreadsheetContent', $spreadsheetContent);
        }

        $this->view->assign('frontendUserFolders', $this->tcaService->getFrontendUserFolders());
        $this->view->assign('frontendUserGroups', $this->tcaService->getFrontendUserGroups());
        $this->view->assign('frontendUserTableFieldNames', $this->tcaService->getFrontendUserTableUniqueFieldNames());
        return $this->view->renderResponse('Userimport/Options');
    }

    public function fieldMappingAction(ImportJob $importJob): ResponseInterface
    {
        $this->view = $this->moduleTemplateFactory->create($this->request);
        $this->view->assign('importJob', $importJob);

        // Update ImportJob with options
        $fieldOptionArguments = [
            ImportJob::IMPORT_OPTION_TARGET_FOLDER,
            ImportJob::IMPORT_OPTION_FIRST_ROW_CONTAINS_FIELD_NAMES,
            ImportJob::IMPORT_OPTION_USE_EMAIL_AS_USERNAME,
            ImportJob::IMPORT_OPTION_GENERATE_PASSWORD,
            ImportJob::IMPORT_OPTION_USER_GROUPS,
            ImportJob::IMPORT_OPTION_UPDATE_EXISTING_USERS,
            ImportJob::IMPORT_OPTION_UPDATE_EXISTING_USERS_UNIQUE_FIELD,
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
        $usernameMustBeMapped = !(bool) $importJob->getImportOption(ImportJob::IMPORT_OPTION_USE_EMAIL_AS_USERNAME);
        $this->view->assign('usernameMustBeMapped', $usernameMustBeMapped);

        // If username is generated from e-mail, the field e-mail must be mapped
        $emailMustBeMapped = (bool) $importJob->getImportOption(ImportJob::IMPORT_OPTION_USE_EMAIL_AS_USERNAME);
        $this->view->assign('emailMustBeMapped', $emailMustBeMapped);
        return $this->view->renderResponse('Userimport/FieldMapping');
    }

    public function importPreviewAction(ImportJob $importJob, array $fieldMapping): ResponseInterface
    {
        $this->view = $this->moduleTemplateFactory->create($this->request);
        $this->view->assign('importJob', $importJob);

        // Update ImportJob with field mapping
        $importJob->setFieldMapping($fieldMapping);
        $this->importJobRepository->update($importJob);
        $this->persistenceManager->persistAll();

        $previewData = $this->spreadsheetService->generateDataFromImportJob($importJob, true);
        $this->view->assign('previewDataHeader', array_keys($previewData[0]));
        $this->view->assign('previewData', $previewData);
        return $this->view->renderResponse('Userimport/ImportPreview');
    }

    public function performImportAction(ImportJob $importJob): ResponseInterface
    {
        $this->view = $this->moduleTemplateFactory->create($this->request);
        $rowsToImport = $this->spreadsheetService->generateDataFromImportJob($importJob);
        $this->view->assign('rowsInSource', count($rowsToImport));

        $result = $this->userImportService->performImport($importJob, $rowsToImport);

        $this->view->assign('updatedRecords', $result['updatedRecords']);
        $this->view->assign('insertedRecords', $result['insertedRecords']);
        $this->view->assign('log', $result['log']);

        $this->view->assign('targetFolder', $importJob->getImportOption(ImportJob::IMPORT_OPTION_TARGET_FOLDER));

        // Remove import job
        $this->importJobRepository->remove($importJob);
        $this->persistenceManager->persistAll();
        return $this->view->renderResponse('Userimport/PerformImport');
    }

    /**
     * Deactivate errorFlashMessage
     */
    public function getErrorFlashMessage(): bool|string
    {
        return false;
    }
}
