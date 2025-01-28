<?php

namespace Edgaras\CloudFlareAI;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use Psr\Http\Message\RequestInterface;

class TextGeneration
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

    public function run(array $messages, array $options = [], float $timeout = 10.0): array|string
    {
        if (empty($messages)) {
            throw new \InvalidArgumentException('Messages cannot be empty.');
        }

        $endpoint = sprintf('accounts/%s/ai/run/%s',
            $this->config->getAccountId(),
            $this->modelName
        );

        $payload = array_merge([
            'messages' => $messages,
            'stream' => false,
        ], $options);

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

    public function runStream(array $messages, array $options = [], float $timeout = 10.0): \Generator
    {
        if (empty($messages)) {
            throw new \InvalidArgumentException('Messages cannot be empty.');
        }

        $endpoint = sprintf('accounts/%s/ai/run/%s',
            $this->config->getAccountId(),
            $this->modelName
        );

        $payload = array_merge([
            'messages' => $messages,
            'stream' => true,
        ], $options);

        try {
            $response = $this->httpClient->post($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->config->getApiToken(),
                    'Content-Type'  => 'application/json',
                ],
                'json' => $payload,
                'timeout' => $timeout,
                'stream' => true,
            ]);

            $stream = $response->getBody();

            while (!$stream->eof()) {
                yield $stream->read(1024);
            }
        } catch (ConnectException $e) {
            yield json_encode([
                'error' => 'Connection timed out',
            ]);
        } catch (RequestException $e) {
            $responseBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null;
            yield json_encode([
                'error' => $e->getMessage(),
                'response' => $responseBody ? json_decode($responseBody, true) : null,
            ]);
        }
    }
}
