<?php

namespace Drupal\dgi_actions\Plugin\Action;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\rules\Exception\InvalidArgumentException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\BadResponseException;

/**
 * Basic implementation for deleting an identifier.
 */
abstract class DeleteIdentifier extends IdentifierAction {

  /**
   * Gets the Identifier from the entity field.
   *
   * @param EntityInterface $entity
   *   The entity.
   *
   * @throws Drupal\rules\Exception\InvalidArgumentException
   *   If the Entity doesn't have the configured identifier field.
   *
   * @return string
   *   Returns the value stored in the identifier field as a string.
   */
  public function getIdentifier(EntityInterface $entity) {
    $field = $this->configs['credentials']->get('field');
    $identifier = $entity->get($field)->getString();
    if (empty($identifier)) {
      $this->logger->error('Identifier field @field is empty.', ['@field' => $field]);
    }

    return $identifier;
  }

  /**
   * Builds the Guzzle HTTP Request.
   *
   * @param string $identifier
   *   The location of the identifier.
   *
   * @throws GuzzleHttp\Exception\RequestException
   *   Thrown by Guzzle when creating an invalid Request.
   *
   * @return Request
   *   The Guzzle HTTP Request Object.
   */
  abstract public function buildRequest($identifier);

  /**
   * Sends the Request and Request Body.
   *
   * @param Request $request
   *   The Guzzle HTTP Request Object.
   *
   * @throws GuzzleHttp\Exception\BadResponseException
   *   Thrown when receiving 4XX or 5XX error.
   *
   * @return Response
   *   The Guzzle HTTP Response Object.
   */
  abstract public function sendRequest(Request $request);

  /**
   * Delete's the identifier from the service.
   *
   * @param EntityInterface $entity
   *   The entity with the identifier to delete.
   */
  public function delete(EntityInterface $entity) {
    $identifier = $this->getIdentifier($entity);
    $request = $this->buildRequest($identifier);
    $this->sendRequest($request);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(EntityInterface $entity) {
    if ($entity instanceof FieldableEntityInterface) {
      try {
        $this->delete($entity);
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
