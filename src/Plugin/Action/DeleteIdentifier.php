<?php

namespace Drupal\dgi_actions\Plugin\Action;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\rules\Exception\InvalidArgumentException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Psr7\Response;

/**
 * Basic implementation for deleting an identifier.
 */
abstract class DeleteIdentifier extends IdentifierAction {

  /**
   * Gets the Identifier from the entity's field.
   *
   * @throws \Drupal\rules\Exception\InvalidArgumentException
   *   If the Entity doesn't have the configured identifier field.
   *
   * @return string
   *   Returns the value stored in the identifier field as a string.
   */
  public function getIdentifierFromEntity() {
    $field = $this->identifierConfig->get('field');
    $identifier = $this->entity->get($field)->getString();
    if (empty($identifier)) {
      $this->logger->error('Identifier field @field is empty.', ['@field' => $field]);
    }

    return $identifier;
  }

  /**
   * Handles identifier specific actions for response.
   *
   * @param GuzzleHttp\Psr7\Response $response
   *   The Guzzle HTTP Response Object.
   */
  abstract protected function handleResponse(Response $response);

  /**
   * Delete's the identifier from the service.
   */
  protected function delete() {
    $request = $this->buildRequest();
    $response = $this->sendRequest($request);
    $this->handleResponse($response);
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if ($entity instanceof FieldableEntityInterface) {
      try {
        $this->entity = $entity;
        $this->setConfigs();
        if ($this->entity && $this->identifierConfig) {
          $this->delete();
        }
      }
      catch (InvalidArgumentException $iae) {
        $this->logger->error('Configured field not found on Entity: @iae', ['@iae' => $iae->getMessage()]);
      }
      catch (RequestException $re) {
        $this->logger->error('Invalid Request: @re', ['@re' => $re->getMessage()]);
      }
      catch (BadResponseException $bre) {
        $this->logger->error('Bad Response: @bre', ['@bre' => $bre->getMessage()]);
      }
    }
  }

}
