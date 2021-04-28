<?php

namespace Drupal\dgi_actions\Plugin\Action;

use Drupal\Core\Entity\FieldableEntityInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

/**
 * Basic implementation for deleting an identifier.
 */
abstract class DeleteIdentifier extends IdentifierAction {

  /**
   * Gets the Identifier from the entity's field.
   *
   * @throws \InvalidArgumentException
   *   If the Entity doesn't have the configured identifier field.
   *
   * @return string
   *   Returns the value stored in the identifier field as a string.
   */
  public function getIdentifierFromEntity(): string {
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
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The Guzzle HTTP Response Object.
   */
  abstract protected function handleResponse(ResponseInterface $response);

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
  public function execute($entity = NULL): void {
    if ($entity instanceof FieldableEntityInterface) {
      try {
        $this->entity = $entity;
        $this->setConfigs();
        if ($this->entity && $this->identifierConfig) {
          $this->delete();
        }
      }
      catch (\InvalidArgumentException $iae) {
        $this->logger->error('Deleting failed for @entity: Configured field not found on Entity: @iae', [
          '@entity' => $this->getEntity()->uuid(),
          '@iae' => $iae->getMessage(),
        ]);
      }
      catch (RequestException $re) {
        $this->logger->error('Deleting failed for @entity: Bad Request: @re', [
          '@entity' => $this->getEntity()->uuid(),
          '@re' => $re->getMessage(),
        ]);
      }
      catch (\Exception $e) {
        $this->logger->error('Deleting failed for @entity: Error: @exception', [
          '@entity' => $this->getEntity()->uuid(),
          '@exception' => $e->getMessage(),
        ]);
      }
    }
  }

}
