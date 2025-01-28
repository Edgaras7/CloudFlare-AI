<?php

use PHPUnit\Framework\TestCase;
use Edgaras\CloudFlareAI\Summarization;
use Edgaras\CloudFlareAI\AI;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class SummarizationTest extends TestCase
{
    private AI $mockConfig;
    private Client $mockClient;
    private Summarization $summarization;
    private RequestInterface $mockRequest;

    protected function setUp(): void
    {
        $this->mockConfig = $this->createMock(AI::class);
        $this->mockClient = $this->createMock(Client::class);
        $this->mockRequest = $this->createMock(RequestInterface::class);

        $this->mockConfig->method('apiBaseUri')->willReturn('https://api.example.com/');
        $this->mockConfig->method('getAccountId')->willReturn('test_account');
        $this->mockConfig->method('getApiToken')->willReturn('test_token');

        $this->summarization = new Summarization($this->mockConfig, 'test_model');
        $reflection = new \ReflectionClass(Summarization::class);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($this->summarization, $this->mockClient);
    }

    public function testSummarizeThrowsExceptionForEmptyInputText(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Input text cannot be empty.');

        $this->summarization->summarize('');
    }

    public function testSummarizeHandlesNonLatinInput(): void
    {
        $responseBody = '{"summary":"これはテストの要約です"}';

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockStream = $this->createMock(StreamInterface::class);

        $mockStream->method('getContents')->willReturn($responseBody);
        $mockResponse->method('getBody')->willReturn($mockStream);

        $this->mockClient->method('post')->willReturn($mockResponse);

        $result = $this->summarization->summarize('これはテスト入力テキストです');
        $this->assertEquals(['summary' => 'これはテストの要約です'], $result);
    }

    public function testSummarizeReturnsSuccessResponse(): void
    {
        $responseBody = '{"summary":"Test summary"}';

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockStream = $this->createMock(StreamInterface::class);

        $mockStream->method('getContents')->willReturn($responseBody);
        $mockResponse->method('getBody')->willReturn($mockStream);

        $this->mockClient->method('post')->willReturn($mockResponse);

        $result = $this->summarization->summarize('Test input text');
        $this->assertEquals(['summary' => 'Test summary'], $result);
    }

    public function testSummarizeHandlesConnectionException(): void
    {
        $this->mockClient->method('post')->willThrowException(
            new ConnectException('Connection error', $this->mockRequest)
        );

        $result = $this->summarization->summarize('Test input text', 1024, 10.0, 1);
        $this->assertEquals(['error' => 'Connection timed out after multiple attempts'], $result);
    }

    public function testSummarizeHandlesRequestExceptionWithResponse(): void
    {
        $responseBody = '{"error":"Invalid request"}';
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockStream = $this->createMock(StreamInterface::class);

        $mockStream->method('getContents')->willReturn($responseBody);
        $mockResponse->method('getBody')->willReturn($mockStream);

        $exception = new RequestException('Bad request', $this->mockRequest, $mockResponse);

        $this->mockClient->method('post')->willThrowException($exception);

        $result = $this->summarization->summarize('Test input text');
        $this->assertEquals([
            'error' => 'Bad request',
            'response' => ['error' => 'Invalid request'],
        ], $result);
    }

    public function testSummarizeHandlesRequestExceptionWithoutResponse(): void
    {
        $exception = new RequestException('Bad request', $this->mockRequest);

        $this->mockClient->method('post')->willThrowException($exception);

        $result = $this->summarization->summarize('Test input text');
        $this->assertEquals([
            'error' => 'Bad request',
            'response' => null,
        ], $result);
    }

    public function testSummarizeHandlesLargeInput(): void
    {
        $largeText = str_repeat('A', 1024);
        $responseBody = '{"summary":"Test summary for large input"}';

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockStream = $this->createMock(StreamInterface::class);

        $mockStream->method('getContents')->willReturn($responseBody);
        $mockResponse->method('getBody')->willReturn($mockStream);

        $this->mockClient->method('post')->willReturn($mockResponse);

        $result = $this->summarization->summarize($largeText);
        $this->assertEquals(['summary' => 'Test summary for large input'], $result);
    }

     
}