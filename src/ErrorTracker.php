<?php

namespace Wyxos\ErrorTracker;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\RequestOptions;
use Log;
use Throwable;

class ErrorTracker
{
    /**
     * @const string
     */
    const LOCAL_URL = 'https://error-tracker.test';

    /**
     * @const string
     */
    const DEVELOPMENT_URL = 'https://error-tracker.wyxos.com';

    /**
     * @const string
     */
    const PRODUCTION_URL = '';

    /**
     * @var string
     */
    protected $base = ErrorTracker::DEVELOPMENT_URL;

    /**
     * @throws Exception|GuzzleException
     */
    public static function handle(Throwable $throwable)
    {
        return self::instance()->capture($throwable);
    }

    /**
     * @return ErrorTracker
     */
    public static function instance(): ErrorTracker
    {
        return new static;
    }

    /**
     * @param string $base
     * @return $this
     */
    public function setBaseUrl(string $base): ErrorTracker
    {
        $this->base = $base;

        return $this;
    }

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    public function capture(Throwable $throwable)
    {
        $token = config('error-tracker.api_token');

        if (!$token) {
            throw new Exception('ERROR_TRACKER_TOKEN not defined in env.');
        }

        try {
            $client = new Client();

            return $client->post($this->base . '/api/issues/store', [
                RequestOptions::JSON => [
                    'content' => $this->buildContent($throwable),
                    'api_token' => $token
                ]
            ]);
        } catch (ServerException $throwable) {
            $str = 'Failed to log error: ' . $throwable
                    ->getResponse()
                    ->getBody()
                    ->getContents();

            Log::error($str);
        }
    }

    /**
     * @param Throwable $throwable
     * @return array
     */
    protected function formatTrace(Throwable $throwable)
    {
        $trace = $throwable->getTrace();

        array_unshift($trace, [
            'line' => $throwable->getLine(),
            'file' => $throwable->getFile()
        ]);

        foreach ($trace as &$item) {
            $item['function'] = '';
            $item['code'] = [];

            if (isset($item['file'])) {
                $lines = file($item['file']);

                $count = count($lines);

                $start = max($item['line'] - 10, 0);

                $lastLine = min($item['line'] + 10, $count - 1);

                for ($i = $start; $i <= $lastLine; $i++) {
                    $item['code'][$i + 1] = $lines[$i];
                }
            }
        }

        return $trace;
    }

    /**
     * @param Throwable $throwable
     * @return string
     */
    protected function getBody(Throwable $throwable)
    {
        $body = '';

        if ($throwable instanceof ServerException) {
            $body = (string)$throwable
                ->getResponse()
                ->getBody()
                ->getContents();
        }

        if ($throwable instanceof RequestException) {
            $body = (string)$throwable
                ->getResponse()
                ->getBody()
                ->getContents();
        }

        return $body;
    }

    /**
     * @param Throwable $throwable
     * @return array
     */
    protected function buildContent(Throwable $throwable)
    {
        $user = auth()->user();

        $route = request()
            ->route();

        return [
            'instance' => get_class($throwable),
            'url' => request()->url(),
            'route' => $route ? $route->getName() : null,
            'request' => request()->all(),
            'user' => $user ? $user->toArray() : null,
            'file' => $throwable->getFile(),
            'message' => $throwable->getMessage(),
            'body' => $this->getBody($throwable),
            'trace' => $this->formatTrace($throwable),
            'session' => session()->all(),
            'agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'method' => $_SERVER['REQUEST_METHOD'] ?? null,
            'environment' => app()->environment()
        ];
    }
}
