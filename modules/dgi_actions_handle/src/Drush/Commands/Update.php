<?php

namespace Drupal\dgi_actions_handle\Drush\Commands;

use Drupal\dgi_actions\Entity\IdentifierInterface;
use Drupal\dgi_actions_handle\Utility\HandleTrait;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Separate class that handles Handle updates for convenience.
 */
class Update {

  use HandleTrait;

  /**
   * The type of request that's going to be executed; either "GET" or "PUT".
   *
   * @var string
   */
  protected $requestType;

  /**
   * The handle to be updated.
   *
   * @var string
   */
  protected string $handle;

  /**
   * The location to be updated.
   *
   * @var string
   */
  protected string $targetLocation;

  /**
   * The index to be updated.
   *
   * @var null|string
   */
  protected ?string $indexToUpdate;

  /**
   * Handles updating a handle via Drush.
   *
   * @param \Drupal\dgi_actions\Entity\IdentifierInterface $identifier
   *   The identifier to be used.
   * @param \GuzzleHttp\ClientInterface $client
   *   The HTTP client to be used.
   * @param array $options
   *   An array containing:
   *   -handle (string): The handle to be updated.
   *   -target_location (string): The URL to be updated to.
   */
  public function __construct(IdentifierInterface $identifier, ClientInterface $client, array $options) {
    $this->identifier = $identifier;
    $this->client = $client;
    $this->handle = $options['handle'];
    $this->targetLocation = $options['target_location'];
  }

  /**
   * {@inheritdoc}
   */
  public function getIdentifier(): IdentifierInterface {
    return $this->identifier;
  }

  /**
   * {@inheritdoc}
   */
  protected function getUri(): string {
    return "{$this->getIdentifier()->getServiceData()->getData()['host']}/{$this->handle}";
  }

  /**
   * Updates a handle.
   */
  public function updateHandle(): void {
    try {
      $get_handle = $this->retrieveHandle();
    }
    catch (RequestException $e) {
      throw new \Exception(dt('Failed to retrieve the Handle. Error: !message.', [
        '!message' => $e->getMessage(),
      ]));
    }

    // Find the index that needs to be updated.
    foreach ($get_handle['values'] as $values) {
      if ($values['type'] === 'URL' && isset($values['data']['value'])) {
        $this->indexToUpdate = $values['index'];
        break;
      }
    }

    if (!$this->indexToUpdate) {
      throw new \Exception(dt('Handle (!handle) does not have an existing URL to update.', [
        '!handle' => $this->handle,
      ]));
    }

    // Do the update now.
    $this->setRequestType('PUT');
    try {
      $this->handleRequest();
    }
    catch (RequestException $e) {
      throw new \Exception(dt('Failed to update the Handle (!handle). Error: !message.', [
        '!handle' => $this->handle,
        '!message' => $e->getMessage(),
      ]));
    }

  }

  /**
   * The Handle being updated.
   *
   * @return array
   *   An array representing an existing Handle containing:
   *   - responseCode (string): The Handle.net response code for the request.
   *   - handle (string): The handle value being requested.
   *   - values (array): A numerically indexed array where the values are an
   *   array containing the index, type and data.
   */
  public function retrieveHandle() {
    $this->setRequestType('GET');

    $response = $this->handleRequest();

    return json_decode($response->getBody(), TRUE);
  }

  /**
   * Helper to set the request type.
   *
   * @param string $type
   *   The request type to be used.
   */
  protected function setRequestType($type) {
    $this->requestType = $type;
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequestType(): string {
    return $this->requestType;
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequestParams(): array {
    $base_params = [
      'auth' => $this->getAuthorizationParams(),
    ];

    if ($this->getRequestType() === 'PUT') {
      $base_params += [
        'headers' => [
          'Content-Type' => 'application/json;charset=UTF-8',
        ],
        'json' => [
          [
            'index' => $this->indexToUpdate,
            'type' => 'URL',
            'data' => $this->targetLocation,
          ],
        ],
        'query' => [
          'overwrite' => 'true',
        ],
      ];
    }
    return $base_params;
  }

}
