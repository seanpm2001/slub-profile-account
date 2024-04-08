<?php

declare(strict_types=1);

/*
 * This file is part of the package slub/slub-profile-account
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Slub\SlubProfileAccount\Service;

use Slub\SlubProfileAccount\Domain\Model\Dto\ApiConfiguration;
use Slub\SlubProfileAccount\Http\Request;
use Slub\SlubProfileAccount\Utility\ApiUtility;
use Slub\SlubProfileAccount\Validation\DataArgumentValidation;

class UserDataService
{
    const FILE_NAME = 'download';

    protected ApiConfiguration $apiConfiguration;
    protected AccountService $accountService;
    protected DataArgumentValidation $dataArgumentValidation;
    protected Request $request;

    /**
     * @param ApiConfiguration $apiConfiguration
     * @param AccountService $accountService
     * @param DataArgumentValidation $dataArgumentValidation
     * @param Request $request
     */
    public function __construct(
        ApiConfiguration $apiConfiguration,
        AccountService $accountService,
        DataArgumentValidation $dataArgumentValidation,
        Request $request
    ) {
        $this->apiConfiguration = $apiConfiguration;
        $this->accountService = $accountService;
        $this->dataArgumentValidation = $dataArgumentValidation;
        $this->request = $request;
    }

    /**
     * @param array $arguments
     * @return array|null
     * @throws \JsonException
     */
    public function getDownload(array $arguments): ?array
    {
        $accountId = $this->getAccountId($arguments);
        $fileFormat = $this->dataArgumentValidation->validateDownloadArguments($arguments);

        if ($accountId === null) {
            return [];
        }

        if ($fileFormat === null) {
            $status = ApiUtility::STATUS[400];
            $status['error'] = 'Invalid file type. Only ' . implode(', ', $this->dataArgumentValidation::FILE_FORMATS) . '.';

            return $status;
        }

        $download = $this->requestDownload($accountId, $fileFormat);

        if ($download === null) {
            return ApiUtility::STATUS[500];
        }

        return [
            'contentType' => 'text/' . $fileFormat,
            'data' => $download['data'],
        ];
    }

    /**
     * @param int $accountId
     * @param string $fileFormat
     * @return array|null
     * @throws \JsonException
     */
    protected function requestDownload(int $accountId, string $fileFormat): ?array
    {
        $uri = $this->apiConfiguration->getDataDownloadUri();
        $uri = ApiUtility::replaceUriPlaceholder([$accountId, '', '', '', $fileFormat], $uri);

        return $this->request->process(
            $uri,
            'GET',
            [
                'headers' => [
                    'Pragma' => 'public',
                    'Expires' => 0,
                    'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                    'Content-Description' => 'File Transfer',
                    'Content-Type' => 'charset=UTF-8, text/' . $fileFormat . ';',
                    'Content-Disposition' => 'attachment; filename="' . self::FILE_NAME . '"',
                    'Content-Transfer-Encoding' => 'binary',
                ]
            ],
            $fileFormat === 'csv' ? 'text/text' : 'text/' . $fileFormat
        );
    }

    /**
     * @param array $arguments
     * @return int|null
     */
    protected function getAccountId(array $arguments): ?int
    {
        $account = $this->accountService->getAccountByArguments($arguments);
        $accountId = $this->accountService->getAccountId();

        if ($accountId > 0 && is_array($account)) {
            return $accountId;
        }

        return null;
    }
}
