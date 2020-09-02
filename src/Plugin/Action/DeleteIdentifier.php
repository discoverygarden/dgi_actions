<?php

namespace Drupal\dgi_actions\Plugin\Action;

use Exception;

/**
 * Basic implementation for deleting an identifier.
 *
 * @Action(
 *   id = "delete_identifier_record",
 *   label = @Translation("Delete Identifier"),
 *   type = "entity"
 * )
 */
abstract class DeleteIdentifier extends IdentifierAction {

  /**
   * Gets the Identifier from the entity field.
   *
   * @param EntityInterface $entity
   *   The entity.
   *
   * @return string
   *   Returns the value stored in the identifier field as a string.
   */
  public function getIdentifier(EntityInterface $entity) {
    if ($entity) {
      $identifier = $entity->get($this->configs['credentials']->get('field'))->getString();
      if (!empty($identifier)) {
        return $identifier;
      }
      else {
        throw new Exception('Identifier field is empty.');
      }
    }
    else {
      throw new Exception('Entity is NULL.');
    }
  }

  /**
   * Builds the Guzzle HTTP Request.
   *
   * @param string $identifier
   *   The location of the identifier.
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
    try {
      $identifier = $this->getIdentifier($entity);
      $request = $this->buildRequest($identifier);
      $this->sendRequest($request);
    }
    catch (Exception $e) {
      throw $e;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function execute(EntityInterface $entity) {
    if ($entity) {
      try {
        $this->delete($entity);
      }
      catch (Exception $e) {
        $this->logger->error('Exception: Delete Identifier Action: @e', ['@e' => $e->getMessage()]);
      }
    }
  }

}
