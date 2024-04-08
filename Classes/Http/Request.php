<?php

declare(strict_types=1);

/*
 * This file is part of the package slub/slub-profile-account
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Slub\SlubProfileAccount\Http;

use GuzzleHttp\Exception\RequestException;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\ArrayUtility;

class Request implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public array $options = [
        'headers' => [
            'Cache-Control' => 'no-cache'
        ],
        'allow_redirects' => false
    ];
    protected RequestFactory $requestFactory;

    /**
     * Request constructor.
     * @param RequestFactory $requestFactory
     */
    public function __construct(RequestFactory $requestFactory)
    {
        $this->requestFactory = $requestFactory;
    }

    /**
     * @param string $uri
     * @param string $method
     * @param array $options
     * @param string $contentType
     * @return array|null
     * @throws JsonException
     */
    public function process(
        string $uri = '',
        string $method = 'GET',
        array $options = [],
        string $contentType = 'application/json'
    ): ?array {
        try {
            $options = $this->mergeOptions($this->options, $options);
            $response = $this->requestFactory->request($uri, $method, $options);

            return $this->getContent($response, $contentType, $uri);
        } catch (RequestException $e) {
            /** @extensionScannerIgnoreLine */
            $this->logger->error($e->getMessage());

            return null;
        }
    }

    /**
     * @param array $default
     * @param array $new
     * @return array
     */
    protected function mergeOptions(array $default, array $new): array
    {
        if (count($new) > 0) {
            ArrayUtility::mergeRecursiveWithOverrule($default, $new);
        }

        return $default;
    }

    /**
     * @param ResponseInterface $response
     * @param string $contentType
     * @param string $uri
     * @return array|null
     * @throws JsonException
     */
    protected function getContent(ResponseInterface $response, string $contentType, string $uri = ''): ?array
    {
        $content = '';

        if (
            $response->getStatusCode() === 200 &&
            strpos($response->getHeaderLine('Content-Type'), $contentType) === 0
        ) {
            $content = $response->getBody()->getContents();
        }

        if (empty($content)) {
            $this->logger->warning(
                'Requesting request was not successful.',
                [
                    'request' => $uri,
                    'status' => $response->getStatusCode(),
                    'reason' => $response->getReasonPhrase(),
                ]
            );

            return null;
        }

        return $contentType === 'application/json'
            ? (array)json_decode($content, true, 512, JSON_THROW_ON_ERROR)
            : ['data' => $content];
    }
}
