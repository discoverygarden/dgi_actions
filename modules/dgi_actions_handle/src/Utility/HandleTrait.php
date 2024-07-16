<?php

namespace Drupal\dgi_actions_handle\Utility;

use Drupal\Core\Entity\EntityInterface;
use Drupal\dgi_actions\Plugin\Action\HttpActionTrait;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;

/**
 * Utilities when interacting with Handle.net's API.
 */
trait HandleTrait {

  use HttpActionTrait;

  /**
   * Identifier entity describing the operation to be done.
   *
   * @var \Drupal\dgi_actions\Entity\IdentifierInterface
   */
  protected $identifier;

  /**
   * Current actioned Entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * Constructs the auth parameters for Guzzle to connect to Handle.net's API.
   *
   * @return array
   *   Authorization parameters to be passed to Guzzle.
   */
  protected function getAuthorizationParams(): array {
    return [
      strtr('300%3A!prefix/!admin', [
        '!prefix' => $this->getPrefix(),
        '!admin' => $this->getIdentifier()->getServiceData()->getData()['username'],
      ]),
      $this->getIdentifier()->getServiceData()->getData()['password'],
    ];
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
    return $this->getIdentifier()->getServiceData()->getData()['prefix'];
  }

  /**
   * Gets the suffix for the entity.
   */
  public function getSuffix(): ?string {
    $suffix_field = $this->getIdentifier()->getServiceData()->getData()['suffix_field'] ?? FALSE;

    // If a field is configured, use that.
    if ($suffix_field && $this->getEntity()->hasField($suffix_field)) {
      return $this->getEntity()->get($suffix_field)->value;
    }

    // Use uuid by default.
    return $this->getEntity()->uuid();
  }

  /**
   * Returns the Handle.net REST API endpoint.
   *
   * @return string
   *   The URL to be used for Handle requests.
   */
  protected function getUri(): string {
    return "{$this->getIdentifier()->getServiceData()->getData()['host']}/{$this->getPrefix()}/{$this->getSuffix()}";
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
      '301' => t('Server not responsible for handle'),
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
