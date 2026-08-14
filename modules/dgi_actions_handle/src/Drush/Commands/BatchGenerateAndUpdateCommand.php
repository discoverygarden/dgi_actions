<?php

namespace Drupal\dgi_actions_handle\Drush\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\CommandError;
use Drupal\dgi_actions\Drush\Commands\Generate;
use Drupal\dgi_actions\Entity\IdentifierInterface;
use Drupal\dgi_actions\Plugin\ContextReaction\EntityMintReaction;
use Drush\Attributes as CLI;

/**
 * Drush command to generate and update Handles.
 */
class BatchGenerateAndUpdateCommand extends Generate {

  /**
   * Generates missing handles and update existing handles for entities.
   *
   * Mints missing handles and updates the target entity in the configured
   * field if a missing handle was minted. If a handle already exists, it's
   * updated to ensure it's resolving to the correct location for the entity.
   */
  #[CLI\Command(
    name: 'dgi_actions_handle:generate_and_update',
    aliases: ['dah:gen_up']
  )]
  #[CLI\Option(
    name: 'identifier_id',
    description: 'A string pointing to the DGI Actions Identifier ID to be used for the generation.',
  )]
  #[CLI\Option(
    name: 'ids',
    description: 'A comma separated list of IDs to be targeted or all entities if not specified.',
  )]
  #[CLI\Option(
    name: 'batch_size',
    description: 'The number of nodes to include in each batch. Defaults to 10.',
  )]
  #[CLI\Option(
    name: 'start_from_id',
    description: 'The ID to start from. If using the "ids" option alongside this, ensure this ID is within that set of IDs. If the batch execution is interrupted, the command can be run setting this option with the relevant node ID to pick up where it left off.',
  )]
  #[CLI\Usage(
    name: 'dgi_actions_handle:generate_and_update --identifier_id=handle',
    description: 'Generates missing handles and updates existing handles by searching all entities for the "handle" DGI Actions Identifier entity.'
  )]
  public function generateAndUpdate(
    array $options = [
      'identifier_id' => self::REQ,
      'ids' => self::OPT,
      'batch_size' => self::OPT,
      'start_from_id' => self::OPT,
    ],
  ): void {
    $identifier = $this->entityTypeManager->getStorage('dgiactions_identifier')->load($options['identifier_id']);
    $ids = $options['ids'];
    $batch_size = $options['batch_size'];
    $batch = [
      'title' => dt('Generating and Updating handles...'),
      'operations' => [
        [
          [$this, 'generateAndUpdateBatch'],
          [
            $identifier,
            $options['ids'],
            $options['batch_size'],
            $options['start_from_id'],
          ],
        ],
      ],
    ];
    drush_op('batch_set', $batch);
    drush_op('drush_backend_batch_process');
  }

  /**
   * Validates the generate and update command.
   *
   * @hook validate dgi_actions_handle:generate_and_update
   */
  public function generateAndUpdateValidate(CommandData $data) {
    $options = $data->getArgsAndOptions();
    $errors = [];

    if (empty($options['options']['identifier_id'])) {
      $errors[] = dt('An "identifier_id" must be specified.');
    }
    $identifiers = $this->identifierUtils->getIdentifiers();
    if (!isset($identifiers[$options['options']['identifier_id']])) {
      $errors[] = dt('The DGI Actions identifier entity (!id) does not exist.', [
        '!id' => $options['options']['identifier_id'],
      ]);
    }

    if (!empty($errors)) {
      return new CommandError(implode("\n", $errors));
    }
  }

  /**
   * Batch for generating missing Handles and updating existing handles.
   *
   * @param \Drupal\dgi_actions\Entity\IdentifierInterface $identifier
   *   The DGI Actions Identifier ID to be used for the generation.
   * @param string|null $ids
   *   The IDs to go generate identifiers for or NULL if the entire repository.
   * @param int|null $batch_size
   *   The number of nodes to include in each batch. Defaults to 10.
   * @param int|null $start_from_id
   *   The ID to start from.
   * @param array|\DrushBatchContext $context
   *   Batch context.
   */
  public function generateAndUpdateBatch(IdentifierInterface $identifier, ?string $ids, ?int $batch_size, ?int $start_from_id, &$context): void {
    $sandbox =& $context['sandbox'];

    $entity_type = $identifier->get('entity');
    $entity_id_key = $this->entityTypeManager->getDefinition($entity_type)->getKeys()['id'];

    $entity_storage = $this->entityTypeManager->getStorage($entity_type);
    $query = $entity_storage->getQuery()
      ->accessCheck(FALSE);
    if ($ids) {
      $query->condition($entity_id_key, explode(',', $ids), 'IN');
    }
    if ($start_from_id) {
      $query->condition($entity_id_key, $start_from_id, '>');
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
    $query->range(0, $batch_size ?? 10);
    foreach ($query->execute() as $result) {
      try {
        $sandbox['last_id'] = $result;
        $entity = $this->entityTypeManager->getStorage($entity_type)->load($result);
        $this->ourLogger->debug(dt('Attempting to generate or update an identifier for {entity} !entity_id.', [
          'entity' => $entity_type,
          '!entity_id' => $result,
        ]));
        if (!$entity) {
          $this->ourLogger->notice(dt('Failed to load {entity} {entity_id}; skipping.', [
            'entity' => $entity_type,
            'entity_id' => $result,
          ]));
        }
        else {
          $reactions = $this->utils->getActiveReactionsForEntity(EntityMintReaction::class, $entity);
          if (empty($reactions)) {
            $this->ourLogger->debug(dt('No active reactions for {entity} !entity_id.', [
              'entity' => $entity_type,
              '!entity_id' => $result,
            ]));
            if ($entity->hasField($identifier->getField())) {
              $identifier_location = $entity->get($identifier->getField())?->getString() ?? FALSE;
              $prefix = $identifier->getServiceData()->getData()['prefix'];
              $landmark_for_handle_substring = 'hdl.handle.net/' . $prefix;
              if ($identifier_location) {
                if (($handle_pos = strpos($identifier_location, $landmark_for_handle_substring)) !== FALSE) {
                  $handle = $prefix . substr(
                      $identifier_location,
                      $handle_pos + strlen($landmark_for_handle_substring)
                    );
                  $this->ourLogger->debug(dt(
                    'Updating handle for {entity} !entity_id using !existing_handle.', [
                      'entity' => $entity_type,
                      '!entity_id' => $result,
                      '!existing_handle' => $handle,
                    ]
                  ));
                  // Get the expected target location of the handle, which is
                  // the node's unaliased path.
                  $expected_location = $entity->toUrl('canonical', ['absolute' => TRUE, 'path_processing' => FALSE])->toString();

                  // Update handle to make sure it's resolving to the right
                  // location.
                  $params = [
                    'handle' => $handle,
                    'target_location' => $expected_location,
                  ];
                  $updater = new Update($identifier, $this->client, $params);
                  $updater->updateHandle();
                  $this->ourLogger->notice(dt('Updated !identifier_location to resolve to !location.', [
                    '!identifier_location' => $identifier_location,
                    '!location' => $expected_location,
                  ]));
                  // Set the handle field value to start with https if it
                  // starts with http.
                  if (str_starts_with($identifier_location, 'http://')) {
                    $this->ourLogger->notice(dt(
                      'Handle for {entity} !entity_id starts with http, changing to https', [
                        'entity' => $entity_type,
                        '!entity_id' => $result,
                      ]
                    ));
                    $new_handle_url = 'https://hdl.handle.net/' . $handle;
                    $entity->set($identifier->getField(), $new_handle_url);
                    $entity->save();
                  }
                }
                else {
                  $this->ourLogger->error(dt('The existing handle is using the wrong prefix in {entity} !entity_id.', [
                    'entity' => $entity_type,
                    '!entity_id' => $result,
                  ]));
                }
              }
            }
          }
          else {
            $original_entity = clone $entity;
            $this->utils->executeEntityReactions(EntityMintReaction::class, $entity);
            if ($this->islandoraUtils->haveFieldsChanged($entity, $original_entity)) {
              $entity->save();
              $new_handle = $entity->get($identifier->getField())?->getString() ?? FALSE;
              if ($new_handle) {
                $this->ourLogger->notice(dt('New handle minted for {entity} !entity_id: !new_handle', [
                  'entity' => $entity_type,
                  '!entity_id' => $result,
                  '!new_handle' => $new_handle,
                ]));
              }
              else {
                $this->ourLogger->error(dt('Failed to mint and save new handle for {entity} !entity_id.', [
                  'entity' => $entity_type,
                  '!entity_id' => $result,
                ]));
              }
            }
          }
        }
      }
      catch (\Exception $e) {
        $this->ourLogger->error(dt('Encountered an exception: {exception}', [
          'exception' => $e,
        ]));
      }
      $sandbox['completed']++;
      $context['finished'] = $sandbox['completed'] / $sandbox['total'];
      $context['message'] = 'Command has processed ' . $sandbox['completed'] . '/' . $sandbox['total'] . ' entities: ' . $context['finished'] * 100 . '%';
    }
  }

}
