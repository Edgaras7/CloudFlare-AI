<?php

namespace Edgaras\CloudFlareAI;

class AI {
 
    private string $accountId;
    private string $apiToken;
    private string $apiBaseUri = 'https://api.cloudflare.com/client/v4/';

    public function __construct(string $accountId, string $apiToken)
    {
        $this->accountId = $accountId;
        $this->apiToken = $apiToken; 
    }

    public function getAccountId(): string
    {
        return $this->accountId;
    }

    public function getApiToken(): string
    {
        return $this->apiToken;
    }

    public function apiBaseUri(): string
    {
        return $this->apiBaseUri;
    }
  
}