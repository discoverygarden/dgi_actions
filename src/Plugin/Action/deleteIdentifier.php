<?php

namespace Drupal\dgi_actions\Plugin\Action;

use Drupal\dgi_actions\Plugin\Action\IdentifierAction;

/**
 * Creates an ARK Record on CDL EZID.
 *
 * @Action(
 *   id = "mint_identifier_record",
 *   label = @Translation("Mint Identifier"),
 *   type = "entity"
 * )
 */
class MintIdentifier extends IdentifierAction {
}
