<?php

namespace Domainregistration\Util;

use DateTime;
use Domainregistration\Exception\AbstractException;
use Domainregistration\Http\Client;
use Exception;

class Sentry
{
    const SENTRY_URL = 'https://sentry.io/api/%s/store/';
    const AUTH_HEADER = 'X-Sentry-Auth: Sentry sentry_version=7, sentry_key=%s, sentry_client=raven-bash/0.1';

    /**
     * @var string
     */
    private $dsn;

    /**
     * @var Client
     */
    private $client;

    /**
     * @param string $dsn
     * @param Client $client
     * @return void
     */
    public function __construct($dsn, $client)
    {
        $this->dsn = $dsn;
        $this->client = $client;
    }

    /**
     * @param AbstractException $exception
     * @return void
     */
    public function notify($exception)
    {
        if (!$this->isDsnValid()) {
            return;
        }

        $payload = $this->getPayload($exception);
        if (empty($payload)) {
            return;
        }

        $url = $this->getSentryUrl();
        $headers = $this->getHeaders();

        try {
            $this->client->request(Client::METHOD_POST, $url, $payload, $headers);
        } catch (Exception $exception) {
        }
    }

    /**
     * @return bool
     */
    private function isDsnValid()
    {
        if (empty($this->dsn)) {
            return false;
        }

        if (1 !== preg_match('/https?:\/\/\w+@.*sentry\.io\/\d+/', $this->dsn)) {
            return false;
        }

        return true;
    }

    /**
     * @param array $trace
     * @return array
     */
    private function getStackTraceFrames($trace)
    {
        $frames = [];

        foreach ($trace as $item) {
            $frames[] = [
                'filename' => $item['file'],
                'function' => $item['function'],
                'raw_function' => $item['function'],
                'lineno' => $item['line'],
                'module' => (!empty($item['class']) ? $item['class'] : ''),
                'vars' => (!empty($item['args']) ? $item['args'] : ''),
            ];
        }

        return $frames;
    }

    /**
     * @param AbstractException $exception
     * @return string|false
     */
    private function getPayload($exception)
    {
        $frames = $this->getStackTraceFrames($exception->getTrace());

        $payload = json_encode([
            'event_id' => md5(uniqid()),
            'transaction' => get_class($exception),
            'timestamp' => (new DateTime())->format('c'),
            'exception' => [
                'values' => [
                    [
                        'type' => get_class($exception),
                        'value' => $exception->getMessage(),
                        'stacktrace' => [
                            'frames' => $frames,
                        ],
                    ],
                ],
            ],
        ]);

        return $payload;
    }

    /**
     * @return string[]
     */
    private function getHeaders()
    {
        $authHeader = sprintf(self::AUTH_HEADER, $this->getSentryKey());

        return [
            'Content-Type: application/json',
            $authHeader,
        ];
    }

    /**
     * @return string|false|null
     */
    private function getSentryKey()
    {
        return parse_url($this->dsn, PHP_URL_USER);
    }

    /**
     * @return string
     */
    private function getSentryUrl()
    {
        $path = parse_url($this->dsn, PHP_URL_PATH);
        if (empty($path)) {
            // Ignore this for the code coverage. It cannot be tested because the DSN format
            // is already validated before we arrive here.
            // @codeCoverageIgnoreStart
            return '';
            // @codeCoverageIgnoreEnd
        }

        $projectId = ltrim($path, '/');

        return sprintf(self::SENTRY_URL, $projectId);
    }
}
