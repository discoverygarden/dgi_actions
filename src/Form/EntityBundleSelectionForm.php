<?php

namespace Drupal\dgi_actions\Form;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Abstract class that holds some helpers that are shared between forms.
 */
abstract class EntityBundleSelectionForm extends EntityForm {

  /**
   * The drupal Entity Field Manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * The Drupal Entity Type Bundle Info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

  /**
   * The targeted entity.
   */
  protected $targetEntity;

  /**
   * The targeted bundle.
   */
  protected $targetBundle;

  /**
   *
   */
  protected $entityOptions;

  /**
   * Constructs a new class instance.
   *
   * @param \Drupal\Core\Entity\EntityFieldManager $entityFieldManager
   *   The drupal core entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfo $entityTypeBundleInfo
   *   The drupal core entity type bundle info.
   */
  public function __construct(EntityFieldManager $entityFieldManager, EntityTypeBundleInfo $entityTypeBundleInfo) {
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * Helper function to build Entity Lists.
   *
   * @return array
   *   Returns Entity bundles and options.
   */
  public function getEntityOptions(): array {
    if (!$this->entityOptions) {
      $field_map = $this->entityFieldManager->getFieldMap();
      $options = [];
      foreach ($field_map as $entity_key => $field_data) {
        $options['entity_bundles'][$entity_key] = $this->entityTypeBundleInfo->getBundleInfo($entity_key);
        $options['entity_options'][$entity_key] = $entity_key;

        foreach (array_keys($options['entity_bundles'][$entity_key]) as $bundle) {
          $fields = array_keys($this->entityFieldManager->getFieldDefinitions($entity_key, $bundle));
          $options['entity_bundles'][$entity_key][$bundle]['fields'] = array_combine($fields, $fields);
        }
      }

      $this->entityOptions = $options;
    }
    return $this->entityOptions;
  }

  public function getEntityOptionsForDropdown() {
    return $this->getEntityOptions()['entity_options'];
  }

  public function getEntityBundlesForDropdown() {
    $options = [];
    $bundles = $this->getEntityOptions()['entity_bundles'][$this->targetEntity] ?? [];
    foreach ($bundles as $bundle => $bundle_data) {
      $options[$bundle] = $bundle_data['label'];
    }
    return $options;
  }

  public function getFieldsForDropdown($entity, $bundle) {
    return $this->getEntityOptions()['entity_bundles'][$entity][$bundle]['fields'] ?? [];
  }

}
