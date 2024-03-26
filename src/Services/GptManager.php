<?php

namespace Drupal\workshop\Services;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\DependencyInjection\ContainerNotInitializedException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Service description.
 */
class GptManager {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * A config manager object.
   * 
   * its config factory
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configManager;
   


  /**
   * Constructs a Manager object.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   The HTTP client.
   */
  public function __construct(ClientInterface $client, ConfigFactory $configManager) {
    $this->client = $client;
    $this->configManager = $configManager;
  }

  /**
   * Helper to get the OpenAI API key from the config.
   */
  public function getOpenAiApiKey() {
    try {
      return $this->configManager->get('workshop.settings')->get('openai_api_key');
    }
    catch (ContainerNotInitializedException $e) {
      return '';
    }
  }

  /**
   * Call the OpenAI GPT-4 API and get a response based on the input provided.
   *
   * @param string $input
   *   The input text to generate a response for.
   *
   * @return string
   *   The generated response text.
   */
  public function generateResponse($prompt, $model = 'gpt-3.5-turbo', $max_tokens = 8000, $temperature = 0) {
    $max_attempts = 1;
    $attempt = 0;
    $response = '';
    while ($attempt < $max_attempts) {
      $response = $this->attemptGptApiCall($prompt, $max_tokens, $temperature, $model);
      // If the response is a type of RequestException, then we want to try again.
      if ($response instanceof RequestException) {
        sleep(5);
        $attempt++;
      }
      else {
        return $response;
      }
    }
    // If we get here, then we have tried 3 times and failed. We want to log the error and return an empty string.
    \Drupal::logger('workshop')->error('There was an error calling the OpenAI API. The error was: @error', ['@error' => $response->getMessage()]);
    return '';
  }

  /**
   * This will attempt the call and catch any errors.
   */
  public function attemptGptApiCall($prompt, $max_tokens = 8000, $temperature = 0, $model = 'gpt-4') {
    $api_key = $this->getOpenAiApiKey();
    $endpoint = 'https://api.openai.com/v1/chat/completions';

    $headers = [
      'Content-Type' => 'application/json',
      'Authorization' => 'Bearer ' . $api_key,
    ];

    $body = [
      'model' => $model,
      'messages' => $prompt,
    ];

    try {
      // We want to post with a 60 second timeout.
      $response = $this->client->post($endpoint, [
        'headers' => $headers,
        'json' => $body,
        'timeout' => 60,
      ]);
      

      $data = json_decode($response->getBody(), TRUE);
      // Log the $data object so we can see the entire response.
      \Drupal::logger('workshop')->debug(print_r($data, TRUE));
      return $data['choices'][0]['message']['content'];
    }
    catch (RequestException $e) {
      // Lets log the whole e message
      \Drupal::logger('workshop')->error(print_r($e->getMessage(), TRUE));
      return $e;
    }
  }

  /**
   * The API can return a variety of responses. This function will take the response and return the code. 
   * 
   * @param mixed $input 
   * @return array 
   *  An array suitable for passing to GPT API.
   */
  public function getCodeFromOutput($input) {
    $prompt = [
      ["role" => "system", "content" => "Provide only the code from:"],
      ["role" => "user", "content" => $input],
    ];
    return $prompt;
  }
}
