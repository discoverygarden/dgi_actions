<?php

namespace Drupal\dgi_actions\Drush\Commands;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\dgi_actions\Entity\IdentifierInterface;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Psr\Http\Client\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Commandfile for verifying locations.
 */
class VerifyIdentifierLocationCommands extends DrushCommands {

  use DependencySerializationTrait;

  /**
   * Constructs a new VerifyIdentifierLocationCommands object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Psr\Http\Client\ClientInterface $httpClient
   *   The HTTP client.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly ClientInterface $httpClient,
    private readonly MessengerInterface $messenger,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('http_client'),
      $container->get('messenger'),
    );
  }

  /**
   * Verifies identifier target locations.
   */
  #[CLI\Command(name: 'dgi_actions:verify-identifier-locations', aliases: ['da:vil'])]
  #[CLI\Usage(name: 'dgi_actions:verify-identifier-locations', description: 'Verifies the target location of an identifier to ensure it matches what it is expected.')]
  public function verifyIdentifierLocations(): void {
    // Create a batch to find and iterate through all configured entities
    // within an identifier that have the field populated.
    $batch = [
      'title' => dt('Generating identifiers...'),
      'operations' => [],
    ];
    foreach ($this->entityTypeManager->getStorage('dgiactions_identifier')->loadMultiple() as $identifier) {
      $batch['operations'][] = [
        [$this, 'verifyBatch'],
        [
          $identifier,
        ],
      ];
    }
    if (!empty($batch['operations'])) {
      drush_op('batch_set', $batch);
      drush_op('drush_backend_batch_process');
    }
    else {
      $this->logger()->error('No identifiers found.');
    }

  }

  /**
   * Batch for updating NULL field_weight values where siblings are integers.
   *
   * @param \Drupal\dgi_actions\Entity\IdentifierInterface $identifier
   *   The DGI Actions Identifier ID to be used for the generation.
   * @param array $context
   *   Batch context.
   */
  public function verifyBatch(IdentifierInterface $identifier, &$context): void {
    $sandbox =& $context['sandbox'];
    $entity_type = $identifier->getEntity();
    $entity_id_key = $this->entityTypeManager->getDefinition($entity_type)->getKeys()['id'];
    $entity_storage = $this->entityTypeManager->getStorage($entity_type);
    $query = $entity_storage->getQuery()
      ->condition($identifier->getField(), NULL, 'IS NOT NULL')
      ->accessCheck(FALSE);
    if (!isset($sandbox['total'])) {
      $context['results'] = [
        'failed' => [],
        'success' => [],
      ];
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
        if (!$entity) {
          $this->messenger->addError(dt('Failed to load {entity} {entity_id}; skipping.', [
            'entity' => $entity_type,
            'entity_id' => $result,
          ]));
          continue;
        }
        $identifier_location = $entity->get($identifier->getField())->getString();
        $response = $this->httpClient->request('HEAD', $identifier_location, [
          'allow_redirects' => FALSE,
          'http_errors' => FALSE,
        ]);
        $location = $response->getHeaderLine('Location');
        $expected_location = FALSE;

        /** @var \Drupal\context\Entity\Context $context_entity */
        foreach ($this->entityTypeManager->getStorage('context')->loadMultiple() as $context_entity) {
          if ($context_entity->hasCondition('dgi_actions_entity_persistent_identifier_populated') || $context_entity->hasReaction('dgi_actions_entity_mint_reaction')) {
            $reaction = $context_entity->getReaction('dgi_actions_entity_mint_reaction');
            $config = $reaction->getConfiguration();
            $action_ids = $config['actions'];
            $action = reset($action_ids);

            /** @var \Drupal\dgi_actions\Plugin\Action\MintIdentifier $action_entity */
            $action_entity = $this->entityTypeManager->getStorage('action')->load($action)->getPlugin();
            // Ensure this action corresponds to this identifier before
            // anything else.
            if ($action_entity->getIdentifier()->id() !== $identifier->id()) {
              continue;
            }
            $action_entity->setEntity($entity);
            $expected_location = $action_entity->getExternalUrl();
          }
        }
        if ($location !== $expected_location) {
          $this->messenger->addError(dt('Entity {entity} {entity_id} has a location mismatch. Expected: {expected}, Actual: {actual}', [
            'entity' => $entity_type,
            'entity_id' => $result,
            'expected' => $expected_location,
            'actual' => $location,
          ]));
        }
      }
      catch (\Exception $e) {
        $this->messenger->addError(dt('Encountered an exception: {exception}', [
          'exception' => $e,
        ]));
      }
      $sandbox['completed']++;
      $context['finished'] = $sandbox['completed'] / $sandbox['total'];
    }
  }

}
