<?php

namespace Drupal\dgi_actions_handle\Utility;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\State\StateInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;

/**
 * Utilities when interacting with Handle.net's API.
 */
trait HandleTrait {

  /**
   * Service Data config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $serviceDataConfig;

  /**
   * Current actioned Entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * State API.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs the auth parameters for Guzzle to connect to Handle.net's API.
   *
   * @return array
   *   Authorization parameters to be passed to Guzzle.
   */
  protected function getAuthorizationParams(): array {
    $creds = $this->state->get($this->getServiceDataConfig()->get('data.state_key'));
    return [
      strtr('300%3A!prefix/!admin', [
        '!prefix' => $this->getPrefix(),
        '!admin' => $creds['username'],
      ]),
      $creds['password'],
    ];
  }

  /**
   * Gets the config to be used.
   */
  public function getServiceDataConfig(): ImmutableConfig {
    return $this->serviceDataConfig;
  }

  /**
   * Gets the state to be used.
   */
  public function getState(): StateInterface {
    return $this->state;
  }

  /**
   * Gets the entity being used.
   */
  public function getEntity(): EntityInterface {
    return $this->entity;
  }

  /**
   * Gets the Handle prefix.
   */
  public function getPrefix(): string {
    return $this->getServiceDataConfig()->get('data.data.prefix.data');
  }

  /**
   * Gets the suffix for the entity.
   */
  public function getSuffix(): ?string {
    // XXX: Should this be something different?
    return $this->getEntity()->uuid();
  }

  /**
   * Returns the Handle.net REST API endpoint.
   *
   * @return string
   *   The URL to be used for Handle requests.
   */
  protected function getUri(): string {
    return "{$this->getServiceDataConfig()->get('data.data.host.data')}/{$this->getPrefix()}/{$this->getSuffix()}";
  }

  /**
   * Helper that wraps the normal requests to get more verbosity for errors.
   */
  protected function handleRequest() {
    try {
      $request = $this->buildRequest();
      return $this->sendRequest($request);
    }
    catch (RequestException $e) {
      // Wrap the exception with a bit of extra info from Handle.net's API for
      // verbosity's sake.
      $message = $e->getMessage();
      $response = $e->getResponse();
      if ($response) {
        $handle_message = $this->mapHandleResponseCodes($response);

        if ($handle_message) {
          $message .= "Handle.net API Message: $handle_message";
        }
      }
      throw new RequestException($message, $e->getRequest(), $response, $e);
    }

  }

  /**
   * Maps Handle's response codes to error messages if they exist.
   *
   * @param \GuzzleHttp\Psr7\Response $response
   *   The response of the HTTP request to the Handle.net API.
   *
   * @return bool|string
   *   FALSE if no data or the code does not exist in our mapping, otherwise a
   *   string describing what that message actually means.
   */
  protected function mapHandleResponseCodes(Response $response) {
    $mapping = [
      '1' => t('Success'),
      '2' => t('An unexpected error on the server has occurred'),
      '100' => t('Handle not found'),
      '101' => t('Handle already exists'),
      '102' => t('Invalid handle'),
      '200' => t('Values not found'),
      '201' => t('Value already exists'),
      '202' => t('Invalid value'),
      '301' => t('Server not response for handle'),
      '402' => t('Authentication needed'),
    ];

    $body = $response->getBody();
    if ($body) {
      $json = json_decode($body, TRUE);
      if (isset($json['responseCode'])) {
        return $mapping[$json['responseCode']] ?? FALSE;
      }
    }
    return FALSE;
  }

}
