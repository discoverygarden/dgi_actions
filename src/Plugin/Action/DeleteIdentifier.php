<?php

namespace Drupal\dgi_actions\Plugin\Action;

use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Basic implementation for deleting an identifier.
 */
abstract class DeleteIdentifier extends IdentifierAction {

  /**
   * Deletes the identifier from the service.
   */
  abstract protected function delete(): void;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL): void {
    if ($entity instanceof FieldableEntityInterface) {
      try {
        $this->entity = $entity;
        if ($this->entity && $this->identifier) {
          $this->delete();
        }
      }
      catch (\InvalidArgumentException $iae) {
        $this->logger->error('Deleting failed for @type/@id: Configured field not found on Entity: @iae', [
          '@type' => $this->getEntity()->getEntityTypeId(),
          '@id' => $this->getEntity()->id(),
          '@iae' => $iae->getMessage(),
        ]);
      }
      catch (\Exception $e) {
        $this->logger->error('Deleting failed for @type/@id: Error: @exception', [
          '@type' => $this->getEntity()->getEntityTypeId(),
          '@id' => $this->getEntity()->id(),
          '@exception' => $e->getMessage(),
        ]);
      }
    }
  }

}
