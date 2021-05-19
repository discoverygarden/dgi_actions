<?php

namespace Drupal\dgi_actions_ezid\Utility;

use Drupal\Core\Entity\EntityInterface;

/**
 * Utilities when interacting with CDL's EZID service.
 */
trait EzidTrait {

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
   * Parses CDL EZID API responses into a key-value array.
   *
   * @param string $response
   *   Response body from EZID.
   *
   * @return array
   *   Response body reorganized into a key-value array.
   */
  public function parseEzidResponse(string $response): array {
    $responseArray = preg_split('/\r\n|\r|\n/', trim($response));
    $assocArray = [];
    foreach ($responseArray as $res_line) {
      $splitRes = explode(':', $res_line, 2);
      $assocArray[trim($splitRes[0])] = trim($splitRes[1]);
    }

    return $assocArray;
  }

  /**
   * Builds the request content body for the EZID service.
   *
   * Build the request content body from a supplied key-value array.
   *
   * @param array $data
   *   The key-value array of data to be formatted.
   *
   * @return string
   *   The request content body.
   */
  public function buildEzidRequestBody(array $data): string {
    $output = "";
    foreach ($data as $key => $val) {
      $output .= "$key: $val\r\n";
    }
    return $output;
  }

  /**
   * Constructs the auth parameters for Guzzle to connect to EZID's API.
   *
   * @return array
   *   Authorization parameters to be passed to Guzzle.
   */
  public function getAuthorizationParams(): array {
    return [
      $this->getIdentifier()->getServiceData()->getData()['username'],
      $this->getIdentifier()->getServiceData()->getData()['password'],
    ];
  }

  /**
   * Gets the entity being used.
   */
  public function getEntity(): EntityInterface {
    return $this->entity;
  }

}
