<?php

namespace Wyxos\ErrorTracker;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
    const TESTING_URL = 'https://error-tracker.test';

    /**
     * @const string
     */
    const DEVELOPMENT_URL = 'https://error-tracker.wyxos.com';

    /**
     * @const string
     */
    const PRODUCTION_URL = 'https://error-tracker.wyxos.com';

    /**
     * @var string
     */
    protected $base = ErrorTracker::DEVELOPMENT_URL;

    /**
     * @var array
     */
    protected $environmentToExclude = ['testing'];

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
        return new ErrorTracker();
    }

    public function excludeEnvironment(array $array = [])
    {
        $this->environmentToExclude = $array;
    }

    public function setBase(string $environment = null)
    {
        $env = $environment ?: app()->environment();

        $env = Str::upper($env);

        $base = $env . '_URL';

        $this->setBaseUrl(constant("\\Wyxos\\ErrorTracker\\ErrorTracker::$base"));

        return $this;
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
        if (app()->environment($this->environmentToExclude)) {
            $excludes = join(',', $this->environmentToExclude);

            Log::warning("Error tracker is configured to not run on the following environments {$excludes}");

            return false;
        }

        $token = config('error-tracker.api_token');

        if (!$token) {
            throw new Exception('ERROR_TRACKER_TOKEN not defined in env.');
        }

        try {
            $client = new Client();

            $uri = $this->base . '/api/issues/store';

            return $client->post($uri, [
                RequestOptions::JSON => [
                    'content' => $this->buildContent($throwable),
                    'api_token' => $token
                ]
            ]);
        } catch (ServerException $throwable) {
            $str =
                'Failed to log error: ' .
                $throwable
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

        $callbacks = explode("\n", $throwable->getTraceAsString());

        $isVendor = false;

        $format = [];

        foreach ($trace as $index => &$item) {
            $item['function'] = '';
            $item['code'] = [];
            $item['callback'] = preg_replace('/^#\d+ (.*:)?(.*)/', '$2', $callbacks[$index]);

            if (isset($item['file'])) {
                $lines = file($item['file']);

                $count = count($lines);

                $start = max($item['line'] - 10, 0);

                $lastLine = min($item['line'] + 10, $count - 1);

                for ($i = $start; $i <= $lastLine; $i++) {
                    $item['code'][$i + 1] = $lines[$i];
                }
            }

            if ($index === 0) {
                $format[] = [
                    'trace' => [$item],
                    'vendor' => false
                ];

                continue;
            }

            if (preg_match('/vendor\/.*/', $item['file'])) {
                if ($isVendor) {
                    $format[count($format) - 1]['trace'][] = $item;
                } else {
                    $isVendor = true;
                    $format[] = [
                        'vendor' => true,
                        'trace' => [$item]
                    ];
                }

                continue;
            }

            if ($isVendor) {
                $isVendor = false;

                $format[] = [
                    'trace' => [$item],
                    'vendor' => false
                ];
            } else {
                $format[count($format) - 1]['trace'][] = $item;
            }
        }

        return $format;
    }

    /**
     * @param Throwable $throwable
     * @return string
     */
    protected function getBody(Throwable $throwable)
    {
        $body = '';

        if ($throwable instanceof ServerException) {
            $body = (string) $throwable
                ->getResponse()
                ->getBody()
                ->getContents();
        }

        if ($throwable instanceof RequestException) {
            $body = (string) $throwable
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

        $route = request()->route();

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
            'environment' => app()->environment(),
            'type' => 'php'
        ];
    }
}
