<?php

namespace Drupal\dgi_actions\Utility;

use Drupal\context\ContextManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\dgi_actions\ContextProvider\EntityContextProvider;

/**
 * Utility functions for figuring out when to fire derivative reactions.
 */
class DgiUtils {

  /**
   * Context manager.
   *
   * @var \Drupal\context\ContextManager
   */
  protected $contextManager;

  /**
   * Constructor.
   *
   * @param \Drupal\context\ContextManager $context_manager
   *   Context manager.
   */
  public function __construct(
    ContextManager $context_manager
  ) {
    $this->contextManager = $context_manager;
  }

  /**
   * Executes context reactions for an Entity.
   *
   * @param string $reaction_type
   *   Reaction type.
   * @param \Drupal\Core\EntityInterface $entity
   *   Entity to evaluate contexts and pass to reaction.
   */
  public function executeEntityReactions($reaction_type, EntityInterface $entity) {
    $provider = new EntityContextProvider($entity);
    $provided = $provider->getRuntimeContexts([]);
    $this->contextManager->evaluateContexts($provided);

    // Fire off index reactions.
    foreach ($this->contextManager->getActiveReactions($reaction_type) as $reaction) {
      $reaction->execute($entity);
    }
  }

}
