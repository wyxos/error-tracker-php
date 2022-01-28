<?php

namespace Wyxos\ErrorTracker;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\RequestOptions;

class ErrorTracker
{
    public static function handle(\Throwable $throwable)
    {
        try {
            $client = new Client();

            $user = auth()->user();

            $trace = $throwable->getTrace();

            //                $trace = [];

            array_unshift($trace, [
                'line' => $throwable->getLine(),
                'file' => $throwable->getFile()
            ]);

            foreach ($trace as $index => &$item) {
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

            $message = $throwable->getMessage();

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

            $data = [
                'content' => [
                    'instance' => get_class($throwable),
                    'url' => request()->url(),
                    'route' => request()
                        ->route()
                        ->getName(),
                    'request' => request()->all(),
                    'user' => $user ? $user->toArray() : null,
                    'file' => $throwable->getFile(),
                    'message' => $message,
                    'body' => $body,
                    'trace' => $trace,
                    'session' => session()->all(),
                    'agent' => $_SERVER['HTTP_USER_AGENT'],
                    'method' => $_SERVER['REQUEST_METHOD'],
                    'environment' => app()->environment()
                ],
                'api_token' => config('error-tracker.api_token')
            ];

            $response = $client->post('https://error-tracker.test/api/issues/store', [
                RequestOptions::JSON => $data
            ]);

            $body = (string) $response->getBody();

            //                dd($body);

            $json = json_decode($body);

            //                dd($json->test->content->trace[0]->code);

            if (!$json->status) {
                //                    throw new \Exception('An unexpected error occurred');
            }
        } catch (ServerException $throwable) {
            dd('WHOOOPS???', (string) $throwable->getResponse()->getBody());
        }
    }
}
