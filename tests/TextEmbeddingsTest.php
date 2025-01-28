<?php

use PHPUnit\Framework\TestCase;
use Edgaras\CloudFlareAI\TextEmbeddings;
use Edgaras\CloudFlareAI\AI;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class TextEmbeddingsTest extends TestCase
{
    private AI $mockConfig;
    private Client $mockClient;
    private TextEmbeddings $textEmbeddings;
    private RequestInterface $mockRequest;

    protected function setUp(): void
    {
        $this->mockConfig = $this->createMock(AI::class);
        $this->mockClient = $this->createMock(Client::class);
        $this->mockRequest = $this->createMock(RequestInterface::class);

        $this->mockConfig->method('apiBaseUri')->willReturn('https://api.example.com/');
        $this->mockConfig->method('getAccountId')->willReturn('test_account');
        $this->mockConfig->method('getApiToken')->willReturn('test_token');

        $this->textEmbeddings = new TextEmbeddings($this->mockConfig, 'test_model');
        $reflection = new \ReflectionClass(TextEmbeddings::class);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($this->textEmbeddings, $this->mockClient);
    }

    public function testEmbedThrowsExceptionForEmptyText(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Text input cannot be empty.');

        $this->textEmbeddings->embed('');
    }

    public function testEmbedHandlesSingleStringInput(): void
    {
        $responseBody = '{"embedding":[' . implode(',', array_fill(0, 1024, 1.0)) . ']}';

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockStream = $this->createMock(StreamInterface::class);

        $mockStream->method('getContents')->willReturn($responseBody);
        $mockResponse->method('getBody')->willReturn($mockStream);

        $this->mockClient->method('post')->willReturn($mockResponse);

        $result = $this->textEmbeddings->embed('Test input text');
        $this->assertArrayHasKey('embedding', $result);
        $this->assertCount(1024, $result['embedding']);
    }

    public function testEmbedHandlesArrayInput(): void
    {
        $responseBody = '{"embeddings":[' .
            '[' . implode(',', array_fill(0, 1024, 1.0)) . '],' .
            '[' . implode(',', array_fill(0, 1024, 0.5)) . ']' .
            ']}';

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockStream = $this->createMock(StreamInterface::class);

        $mockStream->method('getContents')->willReturn($responseBody);
        $mockResponse->method('getBody')->willReturn($mockStream);

        $this->mockClient->method('post')->willReturn($mockResponse);

        $result = $this->textEmbeddings->embed(['Text 1', 'Text 2']);
        $this->assertArrayHasKey('embeddings', $result);
        $this->assertCount(2, $result['embeddings']);
        $this->assertCount(1024, $result['embeddings'][0]);
        $this->assertCount(1024, $result['embeddings'][1]);
    }

    public function testEmbedHandlesConnectionException(): void
    {
        $this->mockClient->method('post')->willThrowException(
            new ConnectException('Connection error', $this->mockRequest)
        );

        $result = $this->textEmbeddings->embed('Test input text');
        $this->assertEquals(['error' => 'Connection timed out'], $result);
    }

    public function testEmbedHandlesRequestExceptionWithResponse(): void
    {
        $responseBody = '{"error":"Invalid request"}';
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockStream = $this->createMock(StreamInterface::class);

        $mockStream->method('getContents')->willReturn($responseBody);
        $mockResponse->method('getBody')->willReturn($mockStream);

        $exception = new RequestException('Bad request', $this->mockRequest, $mockResponse);

        $this->mockClient->method('post')->willThrowException($exception);

        $result = $this->textEmbeddings->embed('Test input text');
        $this->assertEquals([
            'error' => 'Bad request',
            'response' => ['error' => 'Invalid request'],
        ], $result);
    }

    public function testEmbedHandlesRequestExceptionWithoutResponse(): void
    {
        $exception = new RequestException('Bad request', $this->mockRequest);

        $this->mockClient->method('post')->willThrowException($exception);

        $result = $this->textEmbeddings->embed('Test input text');
        $this->assertEquals([
            'error' => 'Bad request',
            'response' => null,
        ], $result);
    }
}
