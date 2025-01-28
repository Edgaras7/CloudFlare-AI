<?php

use PHPUnit\Framework\TestCase;
use Edgaras\CloudFlareAI\TextToImage;
use Edgaras\CloudFlareAI\AI;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class TextToImageTest extends TestCase
{
    private AI $mockConfig;
    private Client $mockClient;
    private TextToImage $textToImage;
    private RequestInterface $mockRequest;

    protected function setUp(): void
    {
        $this->mockConfig = $this->createMock(AI::class);
        $this->mockClient = $this->createMock(Client::class);
        $this->mockRequest = $this->createMock(RequestInterface::class);

        $this->mockConfig->method('getAccountId')->willReturn('test_account');
        $this->mockConfig->method('getApiToken')->willReturn('test_token');

        $this->textToImage = new TextToImage($this->mockConfig, 'flux-1-schnell');

        $reflection = new \ReflectionClass(TextToImage::class);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($this->textToImage, $this->mockClient);
    }

    public function testGenerateThrowsExceptionForEmptyPrompt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Prompt cannot be empty.');

        $this->textToImage->generate('');
    }

    public function testGenerateThrowsExceptionForInvalidSteps(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Steps must be between 1 and 20');

        $this->textToImage->generate('A cyberpunk lizard', [
            'numSteps' => 0
        ]);
    }

    public function testGenerateReturnsSuccessResponse(): void
    {
        $responseBody = '{"result": {"image": "base64encodedimagestring"}}';

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockStream = $this->createMock(StreamInterface::class);

        $mockStream->method('getContents')->willReturn($responseBody);
        $mockResponse->method('getBody')->willReturn($mockStream);

        $this->mockClient->method('post')->willReturn($mockResponse);

        $result = $this->textToImage->generate('A cyberpunk lizard');
        $this->assertEquals(['result' => ['image' => 'base64encodedimagestring']], $result);
    }

    public function testGenerateHandlesConnectionException(): void
    {
        $this->mockClient->method('post')->willThrowException(
            new ConnectException('Connection error', $this->mockRequest)
        );

        $result = $this->textToImage->generate('A cyberpunk lizard', [
            'maxAttempts' => 1
        ]);

        $this->assertEquals(['error' => 'Connection timed out after multiple attempts'], $result);
    }

    public function testGenerateHandlesRequestExceptionWithResponse(): void
    {
        $responseBody = '{"error": "Invalid request"}';
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockStream = $this->createMock(StreamInterface::class);

        $mockStream->method('getContents')->willReturn($responseBody);
        $mockResponse->method('getBody')->willReturn($mockStream);

        $exception = new RequestException('Bad request', $this->mockRequest, $mockResponse);

        $this->mockClient->method('post')->willThrowException($exception);

        $result = $this->textToImage->generate('A cyberpunk lizard');
        $this->assertEquals([
            'error' => 'Bad request',
            'response' => ['error' => 'Invalid request'],
        ], $result);
    }

    public function testGenerateHandlesRequestExceptionWithoutResponse(): void
    {
        $exception = new RequestException('Bad request', $this->mockRequest);

        $this->mockClient->method('post')->willThrowException($exception);

        $result = $this->textToImage->generate('A cyberpunk lizard');
        $this->assertEquals([
            'error' => 'Bad request',
            'response' => null,
        ], $result);
    }
}
