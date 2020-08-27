<?php

namespace Drupal\dgi_actions\Plugin\Action;

use Drupal\dgi_actions\Plugin\Action\IdentifierAction;
use Drupal\Core\Form\FormStateInterface;
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
   *  The entity.
   * @param Array $configs
   *  The array of identifier configs.
   * @return String $entity
   *  Returns the value stored in the identifier field as a string.
   */
  public function getIdentifer($entity = NULL, $configs) {
    if ($entity) {
      $identifier = $entity->get($configs['credentials']->get('field'))->getString();
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
   * @param String $identifier
   *  The location of the identifier.
   * @return Request $request
   *  The Guzzle HTTP Request Object.
   */
  abstract public function buildRequest($identifier);

  /**
   * Sends the Request and Request Body.
   *
   * @param Request $request
   *  The Guzzle HTTP Request Object.
   * @param mixed $requestBody
   *  The request body structured how the API service expects.
   * @return Response $response
   *  The Guzzle HTTP Response Object.
   */
  abstract public function sendRequest($request, $configs);

  /**
   * Delete's the identifier from the service.
   *
   * @param EntityInterface $entity
   *  The entity with the identifier to delete.
   */
  public function delete($entity = NULL) {
    try {
      $configs = $this->utils->getAssociatedConfigs($this->configuration['identifier_type']);
      $this->getIdentifier($entity, $configs);
      $this->buildRequest($identifier);
      $this->sendRequest($request, $configs);
    }
    catch (Exception $e) {
      throw $e;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $this->logger->info('Deleting Identifier');
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
