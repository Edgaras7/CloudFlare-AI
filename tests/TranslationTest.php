<?php

use PHPUnit\Framework\TestCase;
use Edgaras\CloudFlareAI\Translation;
use Edgaras\CloudFlareAI\AI;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class TranslationTest extends TestCase
{
    private AI $mockConfig;
    private Client $mockClient;
    private Translation $translation;
    private RequestInterface $mockRequest;

    protected function setUp(): void
    {
        $this->mockConfig = $this->createMock(AI::class);
        $this->mockClient = $this->createMock(Client::class);
        $this->mockRequest = $this->createMock(RequestInterface::class);

        $this->mockConfig->method('apiBaseUri')->willReturn('https://api.example.com/');
        $this->mockConfig->method('getAccountId')->willReturn('test_account');
        $this->mockConfig->method('getApiToken')->willReturn('test_token');

        $this->translation = new Translation($this->mockConfig, 'test_model');
        $reflection = new \ReflectionClass(Translation::class);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($this->translation, $this->mockClient);
    }

    public function testTranslateThrowsExceptionForEmptyText(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Text to be translated cannot be empty.');

        $this->translation->translate('', 'fr');
    }

    public function testTranslateThrowsExceptionForEmptyTargetLang(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Target language cannot be empty.');

        $this->translation->translate('Hello', '');
    }

    public function testTranslateReturnsSuccessResponse(): void
    {
        $responseBody = '{"translated_text":"Bonjour"}';

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockStream = $this->createMock(StreamInterface::class);

        $mockStream->method('getContents')->willReturn($responseBody);
        $mockResponse->method('getBody')->willReturn($mockStream);

        $this->mockClient->method('post')->willReturn($mockResponse);

        $result = $this->translation->translate('Hello', 'fr');
        $this->assertEquals(['translated_text' => 'Bonjour'], $result);
    }

    public function testTranslateHandlesConnectionException(): void
    {
        $this->mockClient->method('post')->willThrowException(
            new ConnectException('Connection error', $this->mockRequest)
        );

        $result = $this->translation->translate('Hello', 'fr', 'en', 10.0, 1);
        $this->assertEquals(['error' => 'Connection timed out after multiple attempts'], $result);
    }

    public function testTranslateHandlesRequestExceptionWithResponse(): void
    {
        $responseBody = '{"error":"Invalid request"}';
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockStream = $this->createMock(StreamInterface::class);

        $mockStream->method('getContents')->willReturn($responseBody);
        $mockResponse->method('getBody')->willReturn($mockStream);

        $exception = new RequestException('Bad request', $this->mockRequest, $mockResponse);

        $this->mockClient->method('post')->willThrowException($exception);

        $result = $this->translation->translate('Hello', 'fr');
        $this->assertEquals([
            'error' => 'Bad request',
            'response' => ['error' => 'Invalid request'],
        ], $result);
    }

    public function testTranslateHandlesRequestExceptionWithoutResponse(): void
    {
        $exception = new RequestException('Bad request', $this->mockRequest);

        $this->mockClient->method('post')->willThrowException($exception);

        $result = $this->translation->translate('Hello', 'fr');
        $this->assertEquals([
            'error' => 'Bad request',
            'response' => null,
        ], $result);
    }
}
