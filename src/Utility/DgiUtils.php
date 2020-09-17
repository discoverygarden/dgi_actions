<?php

namespace Drupal\dgi_actions\Utility;

use Drupal\islandora\IslandoraContextManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\dgi_actions\ContextProvider\EntityContextProvider;

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
   * Constructor.
   *
   * @param \Drupal\islandora\IslandoraContextManager $context_manager
   *   Islandora Context manager.
   */
  public function __construct(
    IslandoraContextManager $context_manager
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

    dpm($provided, 'Provided');
    dpm(($this->contextManager->conditionsHasBeenEvaluated() ? 'TRUE' : 'FALSE'), 'Conditions Evaluated');
    dpm($this->contextManager->getActiveContexts(), 'Active Contexts');
    dpm($this->contextManager->getActiveReactions($reaction_type), 'Active Reactions by Type');

    // Fire off index reactions.
    foreach ($this->contextManager->getActiveReactions($reaction_type) as $reaction) {
      $reaction->execute($entity);
    }
  }

}
