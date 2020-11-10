<?php

namespace Drupal\dgi_actions\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Service Data setting entity.
 *
 * @ConfigEntityType(
 *   id = "dgiactions_servicedata",
 *   label = @Translation("Service Data"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigEntityStorage",
 *     "list_builder" = "Drupal\dgi_actions\ServiceDataListBuilder",
 *     "form" = {
 *       "add" = "Drupal\dgi_actions\Form\ServiceDataForm",
 *       "edit" = "Drupal\dgi_actions\Form\ServiceDataForm",
 *       "delete" = "Drupal\dgi_actions\Form\ServiceDataDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\dgi_actions\IdentifierHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "service_data",
 *   admin_permission = "administer configuration split",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/config/dgi_actions/service_data/add",
 *     "edit-form" = "/admin/config/dgi_actions/service_data/{servicedatas_servicedata}/edit",
 *     "delete-form" = "/admin/config/dgi_actions/service_data/{servicedatas_servicedata}/delete",
 *     "collection" = "/admin/config/dgi_actions/service_data"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "service_data_type",
 *     "data",
 *   }
 * )
 */
class ServiceData extends ConfigEntityBase implements ServiceDataInterface {

  /**
   * The Service Data setting ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Service Data setting label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Service Data setting service data type.
   *
   * @var string
   */
  protected $service_data_type;

  /**
   * The Service Data setting label.
   *
   * @var array
   */
  protected $data;

  /**
   * Gets the Description value.
   *
   * @return string|null
   *  Returns the description variable.
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setData(array $data) {
    $this->data = $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {
    return $this->data;
  }

  /**
   * {@inheritdoc}
   */
  public function getServiceDataType() {
    return $this->service_data_type;
  }

  /**
   * {@inheritdoc}
   */
  protected function invalidateTagsOnSave($update) {
    parent::invalidateTagsOnSave($update);
    // Clear the config_filter plugin cache.
    \Drupal::service('plugin.manager.config_filter')->clearCachedDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  protected static function invalidateTagsOnDelete(EntityTypeInterface $entity_type, array $entities) {
    parent::invalidateTagsOnDelete($entity_type, $entities);
    // Clear the config_filter plugin cache.
    \Drupal::service('plugin.manager.config_filter')->clearCachedDefinitions();
  }

}
