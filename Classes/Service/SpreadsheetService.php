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

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Impexp\Import;
use TYPO3\CMS\Saltedpasswords\Salt\SaltFactory;
use TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility;
use Visol\Userimport\Domain\Model\ImportJob;

class SpreadsheetService implements SingletonInterface
{

    /**
     * Return the content of the spreadsheet's first worksheet
     *
     * @param string $fileName
     * @param int $numberOfRowsToReturn
     * @param bool $skipFirstRow
     *
     * @return array
     */
    public function getContent($fileName, $numberOfRowsToReturn = null, $skipFirstRow = false)
    {
        $spreadsheet = $this->getSpreadsheet($fileName);
        // We always use the first sheet only
        $worksheet = $spreadsheet->getSheet(0);

        $rows = [];
        $i = 0;
        foreach ($worksheet->getRowIterator() as $row) {
            if ($skipFirstRow && $i === 0) {
                continue;
            }

            $cellIterator = $row->getCellIterator();
            $cells = [];
            foreach ($cellIterator as $cell) {
                $cells[] = $cell->getValue();
            }
            // Remove rows with only null values (i.e. empty rows)
            if (array_filter($cells)) {
                $rows[] = $cells;
                $i++;
            }
            if ($numberOfRowsToReturn && $i === $numberOfRowsToReturn) {
                break;
            }
        }
        return $rows;
    }

    /**
     * Get the label for each column and some examples for field mapping
     *
     * @param $fileName
     * @param bool $firstRowContainsFieldNames
     * @param int $numberOfExamples
     *
     * @return array
     */
    public function getColumnLabelsAndExamples($fileName, $firstRowContainsFieldNames = false, $numberOfExamples = 5)
    {
        $spreadsheet = $this->getSpreadsheet($fileName);
        // We always use the first sheet only
        $worksheet = $spreadsheet->getSheet(0);

        $columns = [];

        $breakOnIteration = $firstRowContainsFieldNames ? $numberOfExamples : $numberOfExamples + 1;

        $i = 0;
        foreach ($worksheet->getRowIterator() as $spreadsheetRow) {
            if ($i === 0) {
                // First row
                $cellIterator = $spreadsheetRow->getCellIterator();
                foreach ($cellIterator as $index => $cell) {
                    $columns[$index]['label'] = $firstRowContainsFieldNames ? $cell->getValue() : 'Column ' . $index;
                    $columns[$index]['index'] = $index;
                    if (!$firstRowContainsFieldNames) {
                        $columns[$index]['examples'][] = $cell->getValue();
                    }
                }
            } else {
                $cellIterator = $spreadsheetRow->getCellIterator();
                foreach ($cellIterator as $index => $cell) {
                    if (!empty($cell->getValue())) {
                        $columns[$index]['examples'][] = $cell->getValue();
                    }
                }
            }

            $i++;
            if ($i === $breakOnIteration) {
                break;
            }
        }

        return $columns;
    }

    /**
     * Generate data from import job data and configuration
     *
     * @param ImportJob $importJob
     * @param bool $isPreview
     *
     * @return array
     */
    public function generateDataFromImportJob(ImportJob $importJob, $isPreview = false)
    {
        $fileName = $importJob->getFile()->getOriginalResource()->getForLocalProcessing();
        $spreadsheet = $this->getSpreadsheet($fileName);
        // We always use the first sheet only
        $worksheet = $spreadsheet->getSheet(0);

        // Remove non-assigned columns from field mapping
        $fieldMapping = array_filter($importJob->getFieldMappingArray());

        $i = 0;
        $rows = [];

        foreach ($worksheet->getRowIterator() as $rowIndex => $spreadsheetRow) {
            if ($i === 0 && $importJob->getImportOption(ImportJob::IMPORT_OPTION_FIRST_ROW_CONTAINS_FIELD_NAMES)) {
                $i++;
                continue;
            }
            $row = [];
            foreach ($fieldMapping as $columnIndex => $fieldName) {
                $row[$fieldName] = $worksheet->getCellByColumnAndRow(Coordinate::columnIndexFromString($columnIndex), $rowIndex)->getValue();
            }

            if (!array_filter($row)) {
                // Don't further-process non-empty rows
                continue;
            }
            $rows[$i] = $row;

            // Process import options
            if ((bool)$importJob->getImportOption(ImportJob::IMPORT_OPTION_USE_EMAIL_AS_USERNAME)) {
                $rows[$i]['username'] = $rows[$i]['email'];
            }

            if ($isPreview) {
                // Rows for preview mode
                $rows[$i]['password'] = '********';
            } else {
                // Rows for actual import

                // Safe password
                if (empty($rows[$i]['password'])) {
                    // TODO respect option or remove option if we always want a password
                    // Password was not mapped, so we create one
                    /** @var Random $random */
                    $random = GeneralUtility::makeInstance(Random::class);
                    $rows[$i]['password'] = $random->generateRandomBytes(32);
                }

                // MD5 as fallback
                $saltedPassword = md5($rows[$i]['password']);
                // Create salted password
                if (ExtensionManagementUtility::isLoaded('saltedpasswords')) {
                    if (SaltedPasswordsUtility::isUsageEnabled('FE')) {
                        $objSalt = SaltFactory::getSaltingInstance(null);
                        if (is_object($objSalt)) {
                            $saltedPassword = $objSalt->getHashedPassword($rows[$i]['password']);
                        }
                    }
                }
                $rows[$i]['password'] = $saltedPassword;

                // PID
                $rows[$i]['pid'] = (int)$importJob->getImportOption(ImportJob::IMPORT_OPTION_TARGET_FOLDER);

                // User groups
                if (!empty($importJob->getImportOption(ImportJob::IMPORT_OPTION_USER_GROUPS))) {
                    $rows[$i]['usergroup'] = implode(',', $importJob->getImportOption(ImportJob::IMPORT_OPTION_USER_GROUPS));
                };
            }

            $i++;
        }

        return array_values($rows);
    }

    /**
     * @param $fileName
     *
     * @return Spreadsheet
     */
    protected function getSpreadsheet($fileName)
    {
        return IOFactory::load($fileName);
    }
}
