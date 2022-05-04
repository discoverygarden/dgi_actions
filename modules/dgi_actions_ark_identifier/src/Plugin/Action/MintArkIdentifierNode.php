<?php

namespace Drupal\dgi_actions_ark_identifier\Plugin\Action;

/**
 * Mints an ARK Identifier Record on CDL EZID.
 *
 * @Action(
 *   id = "dgi_actions_mint_ark_identifier_node",
 *   label = @Translation("Mint ARK EZID Identifier for Node"),
 *   type = "node"
 * )
 */
class MintArkIdentifierNode extends MintArkIdentifier { }
