<?php

namespace Edgaras\CloudFlareAI;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;

class Summarization
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

    public function summarize(
        string $inputText,
        int $maxLength = 1024,
        float $timeout = 10.0,
        int $maxAttempts = 3
    ): array|string {
        if (empty($inputText)) {
            throw new \InvalidArgumentException('Input text cannot be empty.');
        }

        $endpoint = sprintf('accounts/%s/ai/run/%s',
            $this->config->getAccountId(),
            $this->modelName
        );

        $payload = [
            'input_text' => $inputText,
            'max_length' => $maxLength,
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
