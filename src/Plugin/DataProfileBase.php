<?php

namespace Drupal\dgi_actions\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Base class for Data profile plugins.
 */
abstract class DataProfileBase extends PluginBase implements DataProfileInterface {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration(): array {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(): array {
    return [];
  }

  /**
   * Allows a profile plugin to modify data before being sent out the API.
   *
   * @return array
   *   Returns the data to be sent off to the API;
   */
  public function modifyData(array $data): array {
    return $data;
  }

}
