<?php

namespace Edgaras\CloudFlareAI;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;

class TextToImage
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
            'debug' => false, 
        ]);
    }

    public function generate(string $prompt, array $options = []): array|string {

        if (empty($prompt)) {
            throw new \InvalidArgumentException('Prompt cannot be empty.');
        }
    
        $defaultOptions = [
            'negativePrompt' => null,
            'height' => 512,
            'width' => 512,
            'numSteps' => 20,
            'guidance' => 7.5,
            'seed' => null,
            'timeout' => 20.0,
            'maxAttempts' => 3,
        ];
     
        $options = array_merge($defaultOptions, $options);

        if ($options['numSteps'] < 1 || $options['numSteps'] > 20) {
            throw new \InvalidArgumentException('Steps must be between 1 and 20');
        }
    
        $endpoint = sprintf('accounts/%s/ai/run/%s',
            $this->config->getAccountId(),
            $this->modelName
        );
    
        $payload = [
            'prompt' => $prompt,
            'negative_prompt' => $options['negativePrompt'],
            'height' => $options['height'],
            'width' => $options['width'],
            'num_steps' => $options['numSteps'],
            'guidance' => $options['guidance'],
            'seed' => $options['seed'],
        ];
    
        $attempt = 0;
        while ($attempt < $options['maxAttempts']) {
            try {
                $response = $this->httpClient->post($endpoint, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->config->getApiToken(),
                        'Content-Type'  => 'application/json',
                    ],
                    'json' => $payload,
                    'timeout' => $options['timeout'],
                ]);
    
                return json_decode($response->getBody()->getContents(), true) ?: [];
            } catch (ConnectException $e) {
                $attempt++;
                if ($attempt >= $options['maxAttempts']) {
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
