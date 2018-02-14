<?php

namespace Visol\Userimport\Utility;

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


class LogUtility
{

    /**
     * @param $row
     *
     * @return string
     */
    public static function formatRowForImportLog($row)
    {
        // Unset values not to be displayed in the log
        unset($row['password']);
        unset($row['crdate']);
        unset($row['tstamp']);
        unset($row['usergroup']);

        return implode(" | ", $row);
    }
}
