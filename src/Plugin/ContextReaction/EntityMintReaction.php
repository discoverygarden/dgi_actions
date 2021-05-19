<?php

namespace Drupal\dgi_actions\Plugin\ContextReaction;

use Drupal\islandora\PresetReaction\PresetReaction;

/**
 * Entity minting context reaction.
 *
 * @ContextReaction(
 *   id = "dgi_actions_entity_mint_reaction",
 *   label = @Translation("Mints an identifier")
 * )
 */
class EntityMintReaction extends PresetReaction {}
