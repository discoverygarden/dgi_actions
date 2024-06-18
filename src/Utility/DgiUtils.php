<?php

namespace Drupal\dgi_actions\Utility;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\islandora\IslandoraContextManager;

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
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity to evaluate contexts and pass to reaction.
   */
  public function executeEntityReactions(string $reaction_type, ContentEntityInterface $entity) {
    foreach ($this->getActiveReactionsForEntity($reaction_type, $entity) as $reaction) {
      $reaction->execute($entity);
    }
    $this->provider->clearEntity();
  }

  /**
   * Helper to get applicable reactions to be fired.
   *
   * @param string $reaction_type
   *   Reaction type.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity to evaluate contexts and pass to reaction.
   *
   * @return \Drupal\context\Entity\ContextReactionInterface[]
   *   An array with all active reactions or reactions of a certain type
   */
  public function getActiveReactionsForEntity(string $reaction_type, ContentEntityInterface $entity) {
    $this->provider->setEntity($entity);

    // XXX: Need to force context re-evaluation to ensure that the entity being
    // set by the provider in a single threaded scenario is evaluated once
    // it has been changed.
    $context_manager = new \ReflectionClass($this->contextManager);
    $reset_context = $context_manager->getMethod('resetContextEvaluation');
    $reset_context->setAccessible(TRUE);
    $reset_context->invoke($this->contextManager);
    $this->contextManager->evaluateContexts();
    return $this->contextManager->getActiveReactions($reaction_type);
  }

}
