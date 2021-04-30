<?php

namespace Drupal\dgi_actions\ContextProvider;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Sets the provided media as a context.
 */
class EntityContextProvider implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * Entity to provide in a context.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new EntityContextProvider.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Sets the entity for use in the provider.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity being processed in the context.
   */
  public function setEntity(ContentEntityInterface $entity): void {
    $this->entity = $entity;
  }

  /**
   * Clears the entity stored on the provider.
   */
  public function clearEntity(): void {
    unset($this->entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids): array {
    if (!isset($this->entity) || !$this->entity instanceof ContentEntityInterface) {
      return [];
    }
    $contexts = [];
    $contexts[$this->entity->getEntityTypeId()] = EntityContext::fromEntity($this->entity);
    return array_intersect_key($contexts, array_flip($unqualified_context_ids));
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts(): array {
    $contexts = [];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type) {
      if (!$entity_type->entityClassImplements(ContentEntityInterface::class)) {
        continue;
      }
      $context = EntityContext::fromEntityType($entity_type, $this->t('@entity_type from hook', [
        '@entity_type' => $entity_type->getLabel(),
      ]));
      $contexts[$entity_type->id()] = $context;
    }
    return $contexts;
  }

}
