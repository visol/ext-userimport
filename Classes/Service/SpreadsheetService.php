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
     * @param string $fileName
     * @param int $numberOfRowsToReturn
     *
     * @return array
     */
    public function getContent($fileName, $numberOfRowsToReturn = null)
    {
        $spreadsheet = $this->getSpreadsheet($fileName);
        $worksheet = $spreadsheet->getSheet(0);

        $rows = [];
        $i = 0;
        foreach ($worksheet->getRowIterator() as $row) {
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
     * @param $fileName
     *
     * @return Spreadsheet
     */
    protected function getSpreadsheet($fileName)
    {
        return IOFactory::load($fileName);
    }
}
