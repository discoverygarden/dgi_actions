<?php

/**
 * @file
 * Post-update hooks.
 */

use Drupal\dgi_actions\Plugin\Action\IdentifierAction;

/**
 * Resave `dgi_actions` entities to recalculate dependencies.
 */
function dgi_actions_post_update_ddst_322_dependencies() : void {
  $entity_type_manager = \Drupal::entityTypeManager();

  $to_resave = function () use ($entity_type_manager) {
    yield from $entity_type_manager->getStorage('dgiactions_dataprofile')->loadMultiple();
    yield from $entity_type_manager->getStorage('dgiactions_servicedata')->loadMultiple();
    yield from $entity_type_manager->getStorage('dgiactions_identifier')->loadMultiple();

    /**
     * @var \Drupal\system\ActionConfigEntityInterface $action
     */
    foreach ($entity_type_manager->getStorage('action')->loadByProperties(['type' => 'entity']) as $id => $action) {
      if ($action->getPlugin() instanceof IdentifierAction) {
        yield $id => $action;
      }
    }

    /**
     * @var string $id
     * @var \Drupal\context\ContextInterface $context
     */
    foreach ($entity_type_manager->getStorage('context')->loadMultiple() as $id => $context) {
      if (
        $context->hasCondition('dgi_actions_entity_persistent_identifier_populated') ||
        $context->hasReaction('dgi_actions_entity_mint_reaction') ||
        $context->hasReaction('dgi_actions_entity_delete_reaction')
      ) {
        yield $id => $context;
      }
    }
  };

  /** @var \Drupal\Core\Entity\EntityInterface $config_entity */
  foreach ($to_resave() as $config_entity) {
    $config_entity->save();
  }

}
