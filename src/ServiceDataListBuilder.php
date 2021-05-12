<?php

namespace Drupal\dgi_actions;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\dgi_actions\Plugin\ServiceDataTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of Identifier setting entities.
 */
class ServiceDataListBuilder extends ConfigEntityListBuilder {

  /**
   * The config factory that knows what is overwritten.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Service Data Type manager.
   *
   * @var \Drupal\dgi_actions\Plugin\ServiceDataTypeManager
   */
  protected $serviceDataTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('plugin.manager.service_data_type')
    );
  }

  /**
   * Constructs a new EntityListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\dgi_actions\Plugin\ServiceDataTypeManager $service_data_type_manager
   *   The Service Data Type plugin manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, ServiceDataTypeManager $service_data_type_manager) {
    parent::__construct($entity_type, $storage);
    $this->serviceDataTypeManager = $service_data_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Service Data ID');
    $header['id'] = $this->t('Machine name');
    $header['service_data_type'] = $this->t('Service Data Type');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $plugin = $this->serviceDataTypeManager->createInstance($entity->getServiceDataType());
    $definition = $plugin->getPluginDefinition();
    $row['service_data_type'] = $definition['label'];

    return $row + parent::buildRow($entity);
  }

}
