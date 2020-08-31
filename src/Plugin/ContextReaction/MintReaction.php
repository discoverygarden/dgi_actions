<?php

namespace Drupal\dgi_actions\Plugin\ContextReaction;

use Drupal\islandora\PresetReaction\PresetReaction;

/**
 * Minting context reaction.
 *
 * @ContextReaction(
 *   id = "mint",
 *   label = @Translation("Mint (dgi_actions)")
 * )
 */
class MintReaction extends PresetReaction {}
