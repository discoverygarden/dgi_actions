<?php

namespace Drupal\dgi_actions\ContextProvider;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Sets the provided media as a context.
 */
class EntityContextProvider implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * Entity to provide in a context.
   *
   * @var \Drupal\Core\EntityInterface
   */
  protected $entity;

  /**
   * Constructs a new EntityContextProvider.
   *
   * @var \Drupal\Core\EntityInterface $entity
   *   The entity to provide in a context.
   */
  public function __construct(EntityInterface $entity) {
    $this->entity = $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $context_definition = new ContextDefinition('entity', NULL, FALSE);
    $context = new Context($context_definition, $this->entity);
    return ['@entity_route_context.entity_route_context:canonical_entity' => $context];
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $context = new Context(new ContextDefinition('entity', $this->t('Entity from hook')));
    return ['@entity_route_context.entity_route_context:canonical_entity' => $context];
  }

}
