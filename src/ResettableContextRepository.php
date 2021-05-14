<?php

namespace Drupal\dgi_actions;

use Drupal\Core\Plugin\Context\LazyContextRepository;

/**
 * Allow the available contexts to be reset within a request.
 */
class ResettableContextRepository extends LazyContextRepository {

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $context_ids) {
    $this->resetEntityContextIds();
    return parent::getRuntimeContexts($context_ids);
  }

  /**
   * This forcibly unsets any contexts that utilize our EntityContextProvider.
   *
   * @see \Drupal\dgi_actions\ContextProvider\EntityContextProvider
   */
  protected function resetEntityContextIds() {
    $contexts = $this->contexts;

    foreach ($this->contexts as $id => $context) {
      // XXX: For contexts that are triggered via hooks multiple entities
      // may be actioned against in a single thread. In this scenario the
      // LazyContextRepositor statically caches the contexts and the correct
      // entity is not used when evaluating. Unsetting forces the contexts
      // to be reconstructed.
      if (strpos($id, '@dgi_actions.entity_hook_context') === 0) {
        unset($contexts[$id]);
      }
    }
    $this->contexts = $contexts;
  }

}
