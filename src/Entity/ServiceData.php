<?php

namespace Drupal\dgi_actions\Entity;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Entity\ConfigEntityBase;
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
 *     "edit-form" = "/admin/config/dgi_actions/service_data/{dgiactions_servicedata}/edit",
 *     "delete-form" = "/admin/config/dgi_actions/service_data/{dgiactions_servicedata}/delete",
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
   * The Service Data Type plugin ID.
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
   * {@inheritdoc}
   */
  public function setData(array $data): void {
    $state = [];
    $plugin = \Drupal::service('plugin.manager.service_data_type')->createInstance($this->getServiceDataType(), []);;
    foreach ($plugin->getStateKeys() as $key) {
      $state[$key] = NestedArray::getValue($data, (array) $key);
      // Remove the values from the form_state so they do not get stored
      // on the entity directly.
      NestedArray::unsetValue($data, (array) $key);
    }
    if (!empty($state)) {
      \Drupal::service('state')->set("dgi_actions.service_data.{$this->id()}", $state);
    }
    $this->data = $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getData(): array {
    // XXX: Return any values that are stored in state as part of the entity's
    // data.
    $stated_data = $this->data;
    $plugin = \Drupal::service('plugin.manager.service_data_type')->createInstance($this->getServiceDataType(), []);
    $state_keys = $plugin->getStateKeys();
    if (!empty($state_keys)) {
      $state = \Drupal::service('state')->get("dgi_actions.service_data.{$this->id()}");
      foreach ($state_keys as $key) {
        NestedArray::setValue($stated_data, (array) $key, NestedArray::getValue($state, (array) $key));
      }
    }
    return $stated_data;
  }

  /**
   * {@inheritdoc}
   */
  public function getServiceDataType(): ?string {
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
