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
    public function optionsAction(ImportJob $importJob) {
        $this->view->assign('importJob', $importJob);
        $this->view->assign('allowedFolders', $importJob);

        if ($importJob->getFile() instanceof FileReference) {
            $fileName = $importJob->getFile()->getOriginalResource()->getForLocalProcessing();
            $spreadsheetContent = $this->spreadsheetService->getContent($fileName, 5);
            $this->view->assign('spreadsheetContent', $spreadsheetContent);
        }

        $this->view->assign('frontendUserFolders', $this->tcaService->getFrontendUserFolders());
        $this->view->assign('frontendUserGroups', $this->tcaService->getFrontendUserGroups());
        $this->view->assign('frontendUserTableFieldNames', $this->tcaService->getFrontendUserTableFieldNames());
    }

}
