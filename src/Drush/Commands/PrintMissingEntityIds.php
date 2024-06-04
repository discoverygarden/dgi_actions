<?php

namespace Drupal\dgi_actions\Drush\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\CommandError;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\dgi_actions\Utility\IdentifierUtils;
use Drush\Commands\DrushCommands;
use Psr\Container\ContainerInterface;

/**
 * Drush commands for generating identifiers for existing objects.
 */
class PrintMissingEntityIds extends DrushCommands {

  use DependencySerializationTrait;

  /**
   * The Drupal entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Identifier utils service.
   *
   * @var \Drupal\dgi_actions\Utility\IdentifierUtils
   */
  protected IdentifierUtils $identifierUtils;

  /**
   * Handle Drush commands.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Drupal entity type manager.
   * @param \Drupal\dgi_actions\Utility\IdentifierUtils $identifier_utils
   *   The identifier utils used to retrieve a DGI Actions Identifier.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, IdentifierUtils $identifier_utils) {
    parent::__construct();

    $this->entityTypeManager = $entity_type_manager;
    $this->identifierUtils = $identifier_utils;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) : self {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('dgi_actions.utils')
    );
  }

  /**
   * Prints entity IDs with missing identifiers.
   *
   * @param array $options
   *   An array containing options passed to the print_ids command containing:
   *   -identifier_id: The DGI Actions Identifier being targeted for use.
   *
   * @command dgi_actions:print_ids
   *
   * @option identifier_id
   *   A string pointing to the DGI Actions Identifier ID to be used.
   *
   * @aliases da:print_ids
   *
   * @usage dgi_actions:print_ids --identifier_id=handle
   *   Prints entity IDs by searching all entities for the "handle"
   *   DGI Actions Identifier entity.
   */
  public function printIds(array $options = [
    'identifier_id' => self::REQ,
  ]) {
    $identifier = $this->entityTypeManager->getStorage('dgiactions_identifier')->load($options['identifier_id']);

    $entity_type = $identifier->get('entity');
    $entity_bundle = $identifier->get('bundle');
    $entity_field = $identifier->get('field');

    // Get an array of IDs for which this identifier is missing.
    $ids = $this->entityTypeManager->getStorage($entity_type)->getQuery()->accessCheck(FALSE)
      ->condition('type', $entity_bundle)
      ->notExists($entity_field)
      ->execute();

    // If no IDs are returned, we log a message.
    if (empty($ids)) {
      return $this->logger()->log('notice', dt('No IDs found.'));
    }

    return $this->logger()->log('success', dt(implode(',', $ids)));
  }

  /**
   * Validates the print_ids command.
   *
   * @hook validate dgi_actions:print_ids
   */
  public function printIdsValidate(CommandData $data) {
    $options = $data->getArgsAndOptions();

    if (empty($options['options']['identifier_id'])) {
      return new CommandError(dt('An "identifier_id" must be specified.'));
    }
    $identifiers = $this->identifierUtils->getIdentifiers();
    if (!isset($identifiers[$options['options']['identifier_id']])) {
      return new CommandError(dt('The DGI Actions identifier entity (!id) does not exist.', ['!id' => $options['options']['identifier_id']]));
    }
  }

}
