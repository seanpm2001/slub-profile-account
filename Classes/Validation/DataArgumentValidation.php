<?php

declare(strict_types=1);

/*
 * This file is part of the package slub/slub-profile-account
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Slub\SlubProfileAccount\Validation;

class DataArgumentValidation
{
    const DEFAULT_FILE_FORMAT = 'csv';
    const FILE_FORMATS = [
        'csv',
        'json',
        'html',
    ];

    /**
     * There is only the user and file format as argument.
     * User is checked somewhere else.
     *
     * @param array $arguments
     * @return string|null
     */
    public function validateDownloadArguments(array $arguments): ?string
    {
        if (!array_key_exists('fileFormat', $arguments)) {
            return self::DEFAULT_FILE_FORMAT;
        }

        if (in_array($arguments['fileFormat'], self::FILE_FORMATS)) {
            return $arguments['fileFormat'];
        }

        return null;
    }
}
