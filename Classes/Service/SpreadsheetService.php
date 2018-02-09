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

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use TYPO3\CMS\Core\SingletonInterface;

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

        \TYPO3\CMS\Core\Utility\DebugUtility::debug($columns);

        return $columns;
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
