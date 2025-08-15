<?php

namespace Visol\Userimport\Domain\Model;

use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

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
class ImportJob extends AbstractEntity
{
    const IMPORT_OPTION_TARGET_FOLDER = 'targetFolder';
    const IMPORT_OPTION_FIRST_ROW_CONTAINS_FIELD_NAMES = 'firstRowContainsFieldNames';
    const IMPORT_OPTION_USE_EMAIL_AS_USERNAME = 'useEmailAsUsername';
    const IMPORT_OPTION_GENERATE_PASSWORD = 'generatePassword';
    const IMPORT_OPTION_USER_GROUPS = 'userGroups';
    const IMPORT_OPTION_UPDATE_EXISTING_USERS = 'updateExistingUsers';
    const IMPORT_OPTION_UPDATE_EXISTING_USERS_UNIQUE_FIELD = 'updateExistingUsersUniqueField';

    /**
     * @var FileReference
     */
    protected $file;

    /**
     * @var string
     */
    #[TYPO3\CMS\Extbase\Annotation\ORM\Transient]
    protected $importOptions;

    /**
     * @var string
     */
    protected $fieldMapping;

    /**
     * @return FileReference $file
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Sets the image
     */
    public function setFile(FileReference $file): void
    {
        $this->file = $file;
    }

    public function getImportOptions(): string
    {
        return $this->importOptions;
    }

    public function getImportOptionsArray(): array
    {
        return empty($this->importOptions) ? [] : unserialize($this->importOptions);
    }

    /**
     * @param string $option
     * @return mixed
     */
    public function getImportOption($option)
    {
        return $this->getImportOptionsArray()[$option] ?? null;
    }

    public function setImportOptions(array $importOptions): void
    {
        $this->importOptions = serialize($importOptions);
    }

    public function getFieldMapping(): string
    {
        return $this->fieldMapping;
    }

    public function getFieldMappingArray(): array
    {
        return empty($this->fieldMapping) ? [] : unserialize($this->fieldMapping);
    }

    public function setFieldMapping(array $fieldMapping): void
    {
        $this->fieldMapping = serialize($fieldMapping);
    }
}
