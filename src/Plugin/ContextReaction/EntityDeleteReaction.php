<?php

namespace Drupal\dgi_actions\Plugin\ContextReaction;

use Drupal\islandora\PresetReaction\PresetReaction;

/**
 * Entity delete context reaction.
 *
 * @ContextReaction(
 *   id = "dgi_actions_entity_delete_reaction",
 *   label = @Translation("Deletes an identifier")
 * )
 */
class EntityDeleteReaction extends PresetReaction {}
