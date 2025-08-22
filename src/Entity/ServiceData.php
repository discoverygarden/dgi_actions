<?php

namespace Drupal\dgi_actions\Entity;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Entity\ConfigEntityBase;

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
   * The state to be set in the Drupal state.
   *
   * @var array
   */
  protected $state = [];

  /**
   * {@inheritdoc}
   */
  public function set($property_name, $value) {
    // Due to the potential of having state based things on the entity
    // avoid directly calling set.
    if ($property_name === 'data') {
      $this->setData($value);
      return $this;
    }
    return parent::set($property_name, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function setData(array $data): void {
    $state = [];
    if ($this->getServiceDataType()) {
      $plugin = \Drupal::service('plugin.manager.service_data_type')->createInstance($this->getServiceDataType(), []);
      foreach ($plugin->getStateKeys() as $key) {
        if (NestedArray::keyExists($data, (array) $key)) {
          $state[$key] = NestedArray::getValue($data, (array) $key);
          // Remove the values so they do not get stored on the entity directly.
          NestedArray::unsetValue($data, (array) $key);
        }
      }
    }
    $this->state = $state;
    $this->data = $data;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \JsonException
   */
  public function getData(): array {
    // XXX: Return any values that are stored in state as part of the entity's
    // data.
    $stated_data = $this->data;
    $plugin = \Drupal::service('plugin.manager.service_data_type')->createInstance($this->getServiceDataType(), []);
    $state_keys = $plugin->getStateKeys();
    if (!empty($state_keys)) {
      $state = \Drupal::service('state')->get("dgi_actions.service_data.{$this->id()}");

      // If there is no value in state,
      // check if it's set in an environment variable.
      if (empty($state)) {
        $state = $this->readStateFromExternal();
      }

      if (!empty($state)) {
        foreach ($state_keys as $key) {
          NestedArray::setValue($stated_data, (array) $key, NestedArray::getValue($state, (array) $key));
        }
      }
    }
    return $stated_data;
  }

  /**
   * Read state overrides from environment variables.
   */
  protected function readStateFromExternal(): array {
    $envKey = strtoupper("DGI_ACTIONS_SERVICE_DATA_{$this->id()}");
    $raw = getenv($envKey);
    if ($raw === FALSE || $raw === '') {
      return [];
    }
    try {
      $decoded = json_decode($raw, TRUE, 3, JSON_THROW_ON_ERROR);
      return is_array($decoded) ? $decoded : [];
    }
    catch (\JsonException $e) {
      \Drupal::logger('dgi_actions')->error('Invalid JSON in env %key for %ent: %msg', [
        '%key' => $envKey,
        '%ent' => $this->id(),
        '%msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    if (!empty($this->state)) {
      \Drupal::service('state')->set("dgi_actions.service_data.{$this->id()}", $this->state);
    }
    return parent::save();
  }

  /**
   * {@inheritdoc}
   */
  public function getServiceDataType(): ?string {
    return $this->service_data_type;
  }

}
