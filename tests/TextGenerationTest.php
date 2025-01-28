<?php

use PHPUnit\Framework\TestCase;
use Edgaras\CloudFlareAI\TextGeneration;
use Edgaras\CloudFlareAI\AI;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class TextGenerationTest extends TestCase
{
    private AI $mockConfig;
    private Client $mockClient;
    private TextGeneration $textGeneration;
    private RequestInterface $mockRequest;

    protected function setUp(): void
    {
        $this->mockConfig = $this->createMock(AI::class);
        $this->mockClient = $this->createMock(Client::class);
        $this->mockRequest = $this->createMock(RequestInterface::class);

        $this->mockConfig->method('apiBaseUri')->willReturn('https://api.example.com/');
        $this->mockConfig->method('getAccountId')->willReturn('test_account');
        $this->mockConfig->method('getApiToken')->willReturn('test_token');

        $this->textGeneration = new TextGeneration($this->mockConfig, 'test_model');
        $reflection = new \ReflectionClass(TextGeneration::class);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($this->textGeneration, $this->mockClient);
    }

    public function testRunThrowsExceptionForEmptyMessages(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Messages cannot be empty.');

        $this->textGeneration->run([]);
    }

    public function testRunReturnsSuccessResponse(): void
    {
        $responseBody = '{"result": {"text": "Generated text"}}';

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockStream = $this->createMock(StreamInterface::class);

        $mockStream->method('getContents')->willReturn($responseBody);
        $mockResponse->method('getBody')->willReturn($mockStream);

        $this->mockClient->method('post')->willReturn($mockResponse);

        $result = $this->textGeneration->run([
            ['role' => 'user', 'content' => 'Hello AI!']
        ]);

        $this->assertEquals(['result' => ['text' => 'Generated text']], $result);
    }

    public function testRunHandlesConnectionException(): void
    {
        $this->mockClient->method('post')->willThrowException(
            new ConnectException('Connection error', $this->mockRequest)
        );

        $result = $this->textGeneration->run([
            ['role' => 'user', 'content' => 'Hello AI!']
        ]);

        $this->assertEquals(['error' => 'Connection timed out'], $result);
    }

    public function testRunHandlesRequestExceptionWithResponse(): void
    {
        $responseBody = '{"error": "Invalid request"}';
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockStream = $this->createMock(StreamInterface::class);

        $mockStream->method('getContents')->willReturn($responseBody);
        $mockResponse->method('getBody')->willReturn($mockStream);

        $exception = new RequestException('Bad request', $this->mockRequest, $mockResponse);

        $this->mockClient->method('post')->willThrowException($exception);

        $result = $this->textGeneration->run([
            ['role' => 'user', 'content' => 'Hello AI!']
        ]);

        $this->assertEquals([
            'error' => 'Bad request',
            'response' => ['error' => 'Invalid request'],
        ], $result);
    }

    public function testRunHandlesRequestExceptionWithoutResponse(): void
    {
        $exception = new RequestException('Bad request', $this->mockRequest);

        $this->mockClient->method('post')->willThrowException($exception);

        $result = $this->textGeneration->run([
            ['role' => 'user', 'content' => 'Hello AI!']
        ]);

        $this->assertEquals([
            'error' => 'Bad request',
            'response' => null,
        ], $result);
    }

    public function testRunStreamReturnsSuccessResponse(): void
    {
        $mockStream = $this->createMock(StreamInterface::class);

        $mockStream->method('eof')->willReturnOnConsecutiveCalls(false, false, true);
        $mockStream->method('read')->willReturnOnConsecutiveCalls(
            '{"partial": "This is the first part."}',
            '{"partial": "This is the second part."}'
        );

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getBody')->willReturn($mockStream);

        $this->mockClient->method('post')->willReturn($mockResponse);

        $generator = $this->textGeneration->runStream([
            ['role' => 'user', 'content' => 'Hello AI!']
        ]);

        $this->assertEquals('{"partial": "This is the first part."}', $generator->current());
        $generator->next();
        $this->assertEquals('{"partial": "This is the second part."}', $generator->current());
    }

    public function testRunStreamHandlesConnectionException(): void
    {
        $this->mockClient->method('post')->willThrowException(
            new ConnectException('Connection error', $this->mockRequest)
        );

        $generator = $this->textGeneration->runStream([
            ['role' => 'user', 'content' => 'Hello AI!']
        ]);

        $this->assertEquals(
            '{"error":"Connection timed out"}',
            $generator->current()
        );
    }

    public function testRunStreamHandlesRequestException(): void
    {
        $responseBody = '{"error": "Invalid request"}';
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockStream = $this->createMock(StreamInterface::class);

        $mockStream->method('getContents')->willReturn($responseBody);
        $mockResponse->method('getBody')->willReturn($mockStream);

        $exception = new RequestException('Bad request', $this->mockRequest, $mockResponse);

        $this->mockClient->method('post')->willThrowException($exception);

        $generator = $this->textGeneration->runStream([
            ['role' => 'user', 'content' => 'Hello AI!']
        ]);

        $this->assertEquals(
            '{"error":"Bad request","response":{"error":"Invalid request"}}',
            $generator->current()
        );
    }
}
