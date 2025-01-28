<?php

namespace Edgaras\CloudFlareAI;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;

class TextEmbeddings
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

    public function embed(array|string $text, float $timeout = 10.0): array|string
    {
        if (empty($text)) {
            throw new \InvalidArgumentException('Text input cannot be empty.');
        }

        $endpoint = sprintf('accounts/%s/ai/run/%s',
            $this->config->getAccountId(),
            $this->modelName
        );

        $payload = [
            'text' => $text,
        ];

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
            return [
                'error' => 'Connection timed out',
            ];
        } catch (RequestException $e) {
            $responseBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null;
            return [
                'error' => $e->getMessage(),
                'response' => $responseBody ? json_decode($responseBody, true) : null,
            ];
        }
    }
}
