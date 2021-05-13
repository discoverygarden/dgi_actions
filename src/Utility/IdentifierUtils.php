<?php

namespace Drupal\dgi_actions\Utility;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * DGI Actions Identifier Utils.
 */
class IdentifierUtils {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $entityTypeManager;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LoggerInterface $logger
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
  }

  /**
   * Gets configured dgi_actions Identifier configs.
   *
   * @return array
   *   Returns list of configured DGI Actions Identifiers.
   */
  public function getIdentifiers(): array {
    $entities = $this->entityTypeManager->getStorage('dgiactions_identifier')->loadMultiple();
    $entities_options = [];
    if (!empty($entities)) {
      foreach ($entities as $entity) {
        $entities_options[$entity->id()] = $entity->label();
      }
    }

    return $entities_options;
  }

}
