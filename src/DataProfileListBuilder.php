<?php

namespace Drupal\dgi_actions;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\dgi_actions\Annotation\DataProfile;
use Drupal\dgi_actions\Plugin\DataProfileManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of Identifier setting entities.
 */
class DataProfileListBuilder extends ConfigEntityListBuilder {

  /**
   * The Data Profile manager.
   *
   * @var \Drupal\dgi_actions\Plugin\DataProfileManager
   */
  protected $dataProfileManager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('plugin.manager.data_profile')
    );
  }

  /**
   * Constructs a new EntityListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\dgi_actions\Plugin\DataProfileManager $data_profile_manager
   *   The Data Profile manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DataProfileManager $data_profile_manager) {
    parent::__construct($entity_type, $storage);
    $this->dataProfileManager = $data_profile_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Profile Label');
    $header['id'] = $this->t('Machine name');
    $header['entity'] = $this->t('Entity');
    $header['bundle'] = $this->t('Bundle');
    $header['dataprofile'] = $this->t('Data Profile');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['entity'] = $entity->get('entity');
    $row['bundle'] = $entity->get('bundle');
    $plugin = $this->dataProfileManager->createInstance($entity->getDataProfile());
    $definition = $plugin->getPluginDefinition();
    $row['dataprofile'] = $definition['label'];

    return $row + parent::buildRow($entity);
  }

}
