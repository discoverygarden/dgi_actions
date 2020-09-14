<?php

namespace Drupal\dgi_actions\Plugin\ContextReaction;

use Drupal\islandora\PresetReaction\PresetReaction;

/**
 * Entity Insert context reaction.
 *
 * @ContextReaction(
 *   id = "entity_insert_dgi_actions",
 *   label = @Translation("Entity Insert (dgi_actions)")
 * )
 */
class EntityInsertReaction extends PresetReaction {}
