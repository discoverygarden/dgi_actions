<?php

namespace Drupal\dgi_actions\Plugin\ContextReaction;

use Drupal\islandora\PresetReaction\PresetReaction;

/**
 * Entity Delete context reaction.
 *
 * @ContextReaction(
 *   id = "entity_delete_dgi_actions",
 *   label = @Translation("Entity Delete (dgi_actions)")
 * )
 */
class EntityDeleteReaction extends PresetReaction {}
