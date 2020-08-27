<?php

namespace Drupal\dgi_actions\Plugin\ContextReaction;

use Drupal\islandora\PresetReaction\PresetReaction;

/**
 * Delete context reaction.
 *
 * @ContextReaction(
 *   id = "delete_dgi_actions",
 *   label = @Translation("Delete (dgi_actions)")
 * )
 */
class DeleteReaction extends PresetReaction {}
