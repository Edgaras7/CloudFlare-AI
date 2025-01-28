<?php

namespace Edgaras\CloudFlareAI;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;

class Translation
{
    private AI $config;
    private Client $httpClient;
    private string $modelName;

    public function __construct(AI $config, string $modelName)
    {
        $this->config = $config;
        $this->modelName = $modelName;

        $this->httpClient = new Client([
            'base_uri' => $this->config->apiBaseUri(),
        ]);
    }

    public function translate(
        string $text,
        string $targetLang,
        string $sourceLang = 'en',
        float $timeout = 10.0,
        int $maxAttempts = 3
    ): array|string {
        if (empty($text)) {
            throw new \InvalidArgumentException('Text to be translated cannot be empty.');
        }

        if (empty($targetLang)) {
            throw new \InvalidArgumentException('Target language cannot be empty.');
        }

        $endpoint = sprintf('accounts/%s/ai/run/%s',
            $this->config->getAccountId(),
            $this->modelName
        );

        $payload = [
            'text' => $text,
            'source_lang' => $sourceLang,
            'target_lang' => $targetLang,
        ];
 

        $attempt = 0;
        while ($attempt < $maxAttempts) {
            try {
                $response = $this->httpClient->post($endpoint, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->config->getApiToken(),
                        'Content-Type'  => 'application/json',
                    ],
                    'json' => $payload,
                    'timeout' => $timeout,
                ]);

                return json_decode($response->getBody()->getContents(), true) ?: [];
            } catch (ConnectException $e) {
                $attempt++;
                if ($attempt >= $maxAttempts) {
                    return [
                        'error' => 'Connection timed out after multiple attempts',
                    ];
                }
                sleep(2);  
            } catch (RequestException $e) {
                $responseBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null;
                return [
                    'error' => $e->getMessage(),
                    'response' => $responseBody ? json_decode($responseBody, true) : null,
                ];
            }
        }
    }
}
