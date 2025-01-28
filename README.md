# CloudFlare AI

## Features

This library supports the following model categories:
- Text Generation.
- Text to Image.
- Text Embeddings.
- Translation.
- Summarization.


## Requirements

PHP 8.1+

## Installation

1. Use the library via Composer:

```
composer require edgaras/cloudflare-ai
```

2. Include the Composer autoloader:

```php
require __DIR__ . '/vendor/autoload.php';
```

## Usage

### Text Generation

```php
use Edgaras\CloudFlareAI\AI;
use Edgaras\CloudFlareAI\TextGeneration;

$accountId = "<YOUR-ACCOUNT-ID>";
$apiToken = "<YOUR-API-TOKEN>";
$modelName = "@cf/deepseek-ai/deepseek-math-7b-instruct"; // Define the model name

$config = new AI($accountId, $apiToken);
 
$textGeneration = new TextGeneration($config, $modelName);

$messages = [
    ["role" => "system", "content" => "You are a helpful assistant"],
    ["role" => "user", "content" => "2+2=?"], 
];
 
// Options
$options = [
    'temperature' => 0.7,
    'max_tokens' => 300,
    'top_p' => 0.8,
    'frequency_penalty' => 0.2,
    'presence_penalty' => 0.3,
];

// Run Model
$response = $textGeneration->run($messages, $options, 60);


```

### Text to Image

```php
use Edgaras\CloudFlareAI\AI;
use Edgaras\CloudFlareAI\TextToImage;

$accountId = "<YOUR-ACCOUNT-ID>";
$apiToken = "<YOUR-API-TOKEN>";
$modelName = "@cf/black-forest-labs/flux-1-schnell"; // Define the model name

$config = new AI($accountId, $apiToken);

$textToImage = new TextToImage($config, $modelName);

$prompt = "A futuristic cityscape at night with neon lights";
$options = [
    'negativePrompt' => "blurry, low resolution",
    'height' => 768,
    'width' => 1024,
    'numSteps' => 20,
    'guidance' => 7.5,
    'seed' => 12345678,
    'timeout' => 20.0,
    'maxAttempts' => 3,
];

// Generate the image
try {
    $result = $textToImage->generate($prompt, $options);

    if (isset($result['error'])) {
        echo "Error: " . $result['error'] . PHP_EOL;
        if (isset($result['response'])) {
            print_r($result['response']);
        }
    } else { 
        echo "<img src=\"data:image/jpeg;base64,{$result['result']['image']}\">";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . PHP_EOL;
}
```

### Text Embeddings

```php
use Edgaras\CloudFlareAI\AI;
use Edgaras\CloudFlareAI\TextEmbeddings;

$accountId = "<YOUR-ACCOUNT-ID>";
$apiToken = "<YOUR-API-TOKEN>";
$modelName = "@cf/baai/bge-large-en-v1.5"; // Define the model name

$config = new AI($accountId, $apiToken);

$embeddings = new TextEmbeddings($config, $modelName);

// Provide the text to vectorize and the maximum timeout
$embed = $embeddings->embed("Text to vectorize", 60);  

```

### Translation

```php
use Edgaras\CloudFlareAI\AI;
use Edgaras\CloudFlareAI\Translation;

$accountId = "<YOUR-ACCOUNT-ID>";
$apiToken = "<YOUR-API-TOKEN>";
$modelName = "@cf/meta/m2m100-1.2b"; // Define the model name

$config = new AI($accountId, $apiToken);
 
$translation = new Translation($config, $modelName);
 
$text = "Hello, how are you?";
$sourceLang = "en";
$targetLang = "lt";

$response = $translation->translate($text, $targetLang, $sourceLang); // Optional parameters: $timeout, $maxAttempts
// echo $response['result']['translated_text'];
// Sveiki, kaip jūs jaučiatės?

```

### Summarization

```php
use Edgaras\CloudFlareAI\AI;
use Edgaras\CloudFlareAI\Summarization;

$accountId = "<YOUR-ACCOUNT-ID>";
$apiToken = "<YOUR-API-TOKEN>";
$modelName = "@cf/facebook/bart-large-cnn"; // Define the model name

$config = new AI($accountId, $apiToken);

$summarization = new Summarization($config, $modelName);

$inputText = "
Contrary to popular belief, Lorem Ipsum is not simply random text. It has roots in a piece of classical Latin literature from 45 BC, making it over 2000 years old. Richard McClintock, a Latin professor at Hampden-Sydney College in Virginia, looked up one of the more obscure Latin words, consectetur, from a Lorem Ipsum passage, and going through the cites of the word in classical literature, discovered the undoubtable source.
";
$maxLength = 100;  

$response = $summarization->summarize($inputText, $maxLength);
// echo $response['result']['summary'];
// Lorem Ipsum is a piece of classical Latin literature from 45 BC. The word consectetur is one of the more obscure Latin words.

```


## Useful links

- [Models](https://developers.cloudflare.com/workers-ai/models/).
- [Find zone and account IDs](https://developers.cloudflare.com/fundamentals/setup/find-account-and-zone-ids/).
- [Create API token](https://developers.cloudflare.com/fundamentals/api/get-started/create-token/).