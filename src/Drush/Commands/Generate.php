<?php

namespace Drupal\dgi_actions\Drush\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\CommandError;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\dgi_actions\Entity\IdentifierInterface;
use Drupal\dgi_actions\Plugin\ContextReaction\EntityMintReaction;
use Drupal\dgi_actions\Utility\DgiUtils;
use Drupal\dgi_actions\Utility\IdentifierUtils;
use Drupal\islandora\IslandoraUtils;
use Drush\Commands\DrushCommands;
use GuzzleHttp\ClientInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Drush commands for generating identifiers for existing objects.
 */
class Generate extends DrushCommands {

  use DependencySerializationTrait;

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
  protected IdentifierUtils $identifierUtils;

  /**
   * Utilities used for executing reactions.
   *
   * @var \Drupal\dgi_actions\Utility\DgiUtils
   */
  protected DgiUtils $utils;

  /**
   * Islandora utilities.
   *
   * @var \Drupal\islandora\IslandoraUtils
   */
  protected IslandoraUtils $islandoraUtils;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $ourLogger;

  /**
   * Handle Drush commands.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   The HTTP client to be used to make requests.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Drupal entity type manager.
   * @param \Drupal\dgi_actions\Utility\IdentifierUtils $identifier_utils
   *   The identifier utils used to retrieve a DGI Actions Identifier.
   * @param \Drupal\dgi_actions\Utility\DgiUtils $utils
   *   The utils used to execute identifier reactions.
   * @param \Drupal\islandora\IslandoraUtils $islandora_utils
   *   Islandora utils.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger to which to log.
   */
  public function __construct(ClientInterface $client, EntityTypeManagerInterface $entity_type_manager, IdentifierUtils $identifier_utils, DgiUtils $utils, IslandoraUtils $islandora_utils, LoggerInterface $logger) {
    parent::__construct();
    $this->client = $client;
    $this->entityTypeManager = $entity_type_manager;
    $this->identifierUtils = $identifier_utils;
    $this->utils = $utils;
    $this->islandoraUtils = $islandora_utils;
    $this->ourLogger = $logger;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) : self {
    return new static(
      $container->get('http_client'),
      $container->get('entity_type.manager'),
      $container->get('dgi_actions.utils'),
      $container->get('islandora.utils'),
      $container->get('logger.channel.dgi_actions')
    );
  }

  /**
   * Generates missing identifiers for entities.
   *
   * Mints missing identifiers of a configured identifier service and updates
   * the target entity in the configured field if a missing identifier was
   * minted.
   *
   * @param array $options
   *   An array containing options passed to the generate command containing:
   *   -identifier_id: The DGI Actions Identifier being targeted for use in the
   *   generation process.
   *   -ids: Comma separated list of IDs to be targeted or all entities if not
   *   specified.
   *
   * @command dgi_actions:generate
   *
   * @option identifier_id
   *   A string pointing to the DGI Actions Identifier ID to be used for the
   *   generation.
   * @option ids
   *   A comma separated list of IDs to be targeted or all entities if not
   *   specified.
   *
   * @aliases da:generate
   *
   * @usage dgi_actions:generate --identifier_id=handle
   *   Generates missing identifiers by searching all entities for the "handle"
   *   DGI Actions Identifier entity.
   * @usage dgi_actions:generate --identifier_id=handle --ids=1,2,3
   *   Generates missing identifiers for the entities with IDs of 1, 2, or 3 for
   *   the "handle" DGI Actions Identifier entity.
   */
  public function generate(array $options = [
    'identifier_id' => self::REQ,
    'ids' => self::OPT,
  ]): void {
    $identifier = $this->entityTypeManager->getStorage('dgiactions_identifier')->load($options['identifier_id']);
    $ids = $options['ids'];
    $batch = [
      'title' => dt('Generating identifiers...'),
      'operations' => [
        [
          [$this, 'generateBatch'], [
            $identifier,
            $ids,
          ],
        ],
      ],
    ];
    drush_op('batch_set', $batch);
    drush_op('drush_backend_batch_process');
  }

  /**
   * Validates the generate command.
   *
   * @hook validate dgi_actions:generate
   */
  public function updateValidate(CommandData $data) {
    $options = $data->getArgsAndOptions();

    if (empty($options['options']['identifier_id'])) {
      return new CommandError(dt('An "identifier_id" must be specified.'));
    }
    $identifiers = $this->identifierUtils->getIdentifiers();
    if (!isset($identifiers[$options['options']['identifier_id']])) {
      return new CommandError(dt('The DGI Actions identifier entity (!id) does not exist.', ['!id' => $options['options']['identifier_id']]));
    }
  }

  /**
   * Batch for updating NULL field_weight values where siblings are integers.
   *
   * @param \Drupal\dgi_actions\Entity\IdentifierInterface $identifier
   *   The DGI Actions Identifier ID to be used for the generation.
   * @param string|null $ids
   *   The IDs to go generate identifiers for or NULL if the entire repository.
   * @param array|\DrushBatchContext $context
   *   Batch context.
   */
  public function generateBatch(IdentifierInterface $identifier, ?string $ids, &$context): void {
    $sandbox =& $context['sandbox'];

    $entity_type = $identifier->get('entity');
    $entity_id_key = $this->entityTypeManager->getDefinition($entity_type)->getKeys()['id'];

    $entity_storage = $this->entityTypeManager->getStorage($entity_type);
    $query = $entity_storage->getQuery()
      ->accessCheck(FALSE);
    if ($ids) {
      $query->condition($entity_id_key, explode(',', $ids), 'IN');
    }
    if (!isset($sandbox['total'])) {
      $count_query = clone $query;
      $sandbox['total'] = $count_query->count()->execute();
      if ($sandbox['total'] === 0) {
        $context['message'] = dt('Batch empty.');
        $context['finished'] = 1;
        return;
      }
      $sandbox['last_id'] = FALSE;
      $sandbox['completed'] = 0;
    }

    if ($sandbox['last_id']) {
      $query->condition($entity_id_key, $sandbox['last_id'], '>');
    }
    $query->sort($entity_id_key);
    $query->range(0, 10);
    foreach ($query->execute() as $result) {
      try {
        $sandbox['last_id'] = $result;
        $entity = $this->entityTypeManager->getStorage($entity_type)->load($result);
        $this->ourLogger->debug('Attempting to generate an identifier for {entity} {entity_id}.', [
          'entity' => $entity_type,
          'entity_id' => $result,
        ]);
        if (!$entity) {
          $this->ourLogger->debug(
            'Failed to load {entity} {entity_id}; skipping.', [
              'entity' => $entity_type,
              'entity_id' => $result,
            ]
          );
          continue;
        }

        $reactions = $this->utils->getActiveReactionsForEntity(EntityMintReaction::class, $entity);
        if (empty($reactions)) {
          $this->ourLogger->debug('No active reactions for {entity} {entity_id}, skipping.', [
            'entity' => $entity_type,
            'entity_id' => $result,
          ]);
        }
        else {
          $original_entity = clone $entity;
          $this->utils->executeEntityReactions(EntityMintReaction::class, $entity);
          if ($this->islandoraUtils->haveFieldsChanged($entity, $original_entity)) {
            $entity->save();
          }
        }
      }
      catch (\Exception $e) {
        $this->ourLogger->error(
          'Encountered an exception: {exception}', [
            'exception' => $e,
          ]
        );
      }
      $sandbox['completed']++;
      $context['finished'] = $sandbox['completed'] / $sandbox['total'];
    }
  }

}
