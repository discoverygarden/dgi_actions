<?php

namespace Drupal\dgi_actions_handle\Drush\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\CommandError;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\dgi_actions\Utility\IdentifierUtils;
use Drush\Commands\DrushCommands;
use GuzzleHttp\ClientInterface;
use Psr\Container\ContainerInterface;

/**
 * Drush commands for Handle.net things.
 */
class HandleCommands extends DrushCommands {

  /**
   * The HTTP client to be used to make requests.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected ClientInterface $client;

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
  protected IdentifierUtils $utils;

  /**
   * Handle Drush commands.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   The HTTP client to be used to make requests.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Drupal entity type manager.
   * @param \Drupal\dgi_actions\Utility\IdentifierUtils $utils
   *   The identifier utils used to retrieve a DGI Actions Identifier.
   */
  public function __construct(ClientInterface $client, EntityTypeManagerInterface $entity_type_manager, IdentifierUtils $utils) {
    parent::__construct();
    $this->client = $client;
    $this->entityTypeManager = $entity_type_manager;
    $this->utils = $utils;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) : self {
    return new static(
      $container->get('http_client'),
      $container->get('entity_type.manager'),
      $container->get('dgi_actions.utils')
    );
  }

  /**
   * Validates the update command.
   *
   * @hook validate dgi_actions_handle:update
   */
  public function updateValidate(CommandData $data) {
    $options = $data->getArgsAndOptions();
    $errors = [];
    if (!$options['options']['handle']) {
      $errors[] = dt('A handle must be specified to update.');
    }

    if (!$options['options']['target_location']) {
      $errors[] = dt('A new target location must be specified to update.');
    }
    else {
      if (!UrlHelper::isValid($options['options']['target_location'], TRUE)) {
        $errors[] = dt('A valid URL must be specified to update to.');
      }
    }

    $identifiers = $this->utils->getIdentifiers();
    if (!$options['options']['identifier_id']) {
      if (empty($identifiers)) {
        $errors[] = dt('A DGI Actions identifier representing a Handle must be created.');
      }
      // Default to seeing if there is one existing that points to field_handle.
      $existing_identifiers = $this->entityTypeManager->getStorage('dgiactions_identifier')->loadByProperties([
        'field' => 'field_handle',
      ]);
      if (empty($existing_identifiers)) {
        $errors[] = dt('No default identifier referencing "field_handle" was found. Please specify a --identifier_id option.');
      }
      if (count($existing_identifiers) > 1) {
        $errors[] = dt('More than one identifier references "field_handle" for configuration. A single option passed by --identifier_id needs to be provided.');
      }
    }
    else {
      if (!isset($identifiers[$options['options']['identifier_id']])) {
        $errors[] = dt('The Handle identifier entity (!id) does not exist.', ['!id' => $options['options']['identifier_id']]);
      }
    }

    if (!empty($errors)) {
      return new CommandError(implode("\n", $errors));
    }
  }

  /**
   * Updates a Handle.
   *
   * @param array $options
   *   An array containing options to be passed to the update command.
   *
   * @command dgi_actions_handle:update
   *
   * @option handle
   *   A string representing the Handle to be updated.
   * @option target_location
   *   A string pointing to the location to be updated.
   * @option identifier_id
   *   A string pointing to the DGI Actions Identifier ID to be used for the
   *   update.
   *
   * @aliases dah-update
   *
   * @usage dgi_actions_handle:update --handle=123/abc --target_location=http://google.ca --identifier_id=handle
   *   Updates a Handle.
   */
  public function update(array $options = [
    'handle' => self::REQ,
    'target_location' => self::REQ,
    'identifier_id' => self::OPT,
  ]): void {
    // If an identifier ID wasn't passed and validation didn't fail go grab
    // the default.
    if (!$options['identifier_id']) {
      $identifiers = $this->entityTypeManager->getStorage('dgiactions_identifier')->loadByProperties(['field' => 'field_handle']);
      $identifier = reset($identifiers);
    }
    else {
      $identifier = $this->entityTypeManager->getStorage('dgiactions_identifier')->load($options['identifier_id']);
    }
    $params = [
      'handle' => $options['handle'],
      'target_location' => $options['target_location'],
    ];
    $updater = new Update($identifier, $this->client, $params);
    $updater->updateHandle();
    $this->logger()->info(dt('Updated the location of !handle to !location.', [
      '!handle' => $options['handle'],
      '!location' => $options['target_location'],
    ]));
  }

}
