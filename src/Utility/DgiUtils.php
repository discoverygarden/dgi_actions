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

    if ($reaction_type == '\Drupal\dgi_actions\Plugin\ContextReaction\EntityInsertReaction') {
      \Drupal::logger('DGI Actions')->notice('Reaction Type: @reaction_type', ['@reaction_type' => $reaction_type]);
      \Drupal::logger('DGI Actions')->notice('Entity instanceof FieldableEntityInterface: @entity', ['@entity' => ($entity instanceof FieldableEntityInterface ? 'True' : 'False')]);
      \Drupal::logger('DGI Actions')->notice('Entity is: @class', ['@class' => get_class($entity)]);
      \Drupal::logger('DGI Actions')->notice('Entity bundle type is: @type', ['@type' => $entity->bundle()]);
    }
    // Fire off index reactions.
    if ($entity->bundle() == 'islandora_object') { // This needs a better solution, like referencing the bundles identified in the configured identifiers.
      // The above line "fixes" things but the problem is that the reaction shouldn't just be executed for every entity
      // It should only be executed for configured entities.

      // How do we sort those out in such a way that this can be used elsewhere...
      //  Do we just allow the reactions to be 
      foreach ($this->contextManager->getActiveReactions($reaction_type) as $reaction) {
        $reaction->execute($entity);
      }
    }
  }

}
