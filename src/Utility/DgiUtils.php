<?php

namespace Drupal\dgi_actions\Utility;

use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\islandora\IslandoraContextManager;
use Drupal\Core\Entity\EntityInterface;

/**
 * Utility functions for figuring out when to fire derivative reactions.
 */
class DgiUtils {

  /**
   * Context manager.
   *
   * @var \Drupal\islandora\IslandoraContextManager
   */
  protected $contextManager;

  /**
   * The Context provider.
   *
   * @var \Drupal\Core\Plugin\Context\ContextProviderInterface
   */
  protected $provider;

  /**
   * Constructor.
   *
   * @param \Drupal\islandora\IslandoraContextManager $context_manager
   *   Islandora Context manager.
   * @param \Drupal\Core\Plugin\Context\ContextProviderInterface $provider
   *   The context provider.
   */
  public function __construct(IslandoraContextManager $context_manager, ContextProviderInterface $provider) {
    $this->contextManager = $context_manager;
    $this->provider = $provider;
  }

  /**
   * Executes context reactions for an Entity.
   *
   * @param string $reaction_type
   *   Reaction type.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to evaluate contexts and pass to reaction.
   */
  public function executeEntityReactions(string $reaction_type, EntityInterface $entity) {
    $provider = $this->provider;
    $provider->setEntity($entity);
    $this->contextManager->evaluateContexts();
    foreach ($this->contextManager->getActiveReactions($reaction_type) as $reaction) {
      $reaction->execute($entity);
    }
    $provider->clearEntity();
  }

}
