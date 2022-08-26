<?php

namespace Visol\Userimport\Domain\Model;

use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
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
    protected $file = null;

    /**
     * @var string
     */
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
     *
     * @param FileReference $file
     *
     * @return void
     */
    public function setFile(FileReference $file)
    {
        $this->file = $file;
    }

    /**
     * @return string
     */
    public function getImportOptions(): string
    {
        return $this->importOptions;
    }

    /**
     * @return array
     */
    public function getImportOptionsArray(): array
    {
        return !empty($this->importOptions) ? unserialize($this->importOptions) : [];
    }

    /**
     * @param string $option
     *
     * @return mixed
     * @Extbase\ORM\Transient
     */
    public function getImportOption($option)
    {
        return array_key_exists($option, $this->getImportOptionsArray()) ? $this->getImportOptionsArray()[$option] : null;
    }

    /**
     * @param array $importOptions
     */
    public function setImportOptions(array $importOptions)
    {
        $this->importOptions = serialize($importOptions);
    }

    /**
     * @return string
     */
    public function getFieldMapping(): string
    {
        return $this->fieldMapping;
    }

    /**
     * @return array
     */
    public function getFieldMappingArray(): array
    {
        return !empty($this->fieldMapping) ? unserialize($this->fieldMapping) : [];
    }

    /**
     * @param array $fieldMapping
     */
    public function setFieldMapping(array $fieldMapping)
    {
        $this->fieldMapping = serialize($fieldMapping);
    }
}
