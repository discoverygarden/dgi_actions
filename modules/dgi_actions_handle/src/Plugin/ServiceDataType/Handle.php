<?php

namespace Drupal\dgi_actions_handle\Plugin\ServiceDataType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\dgi_actions\Plugin\ServiceDataTypeBase;

/**
 * Mints a Handle from Handle.net.
 *
 * @ServiceDataType(
 *   id = "handle",
 *   label = @Translation("Handle"),
 *   description = @Translation("Service information for Handle.net Handles.")
 * )
 */
class Handle extends ServiceDataTypeBase {

  /**
   * Handle service data plugin constructor.
   *
   * @param array $configuration
   *   Array containing default configuration for the plugin.
   * @param string $plugin_id
   *   The ID of the plugin being instantiated.
   * @param array $plugin_definition
   *   Array describing the plugin definition.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'host' => NULL,
      'username' => NULL,
      'password' => NULL,
      'prefix' => NULL,
      'suffix_field' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['host'] = [
      '#type' => 'url',
      '#title' => $this->t('Host'),
      '#description' => $this->t('Host address for the Handle.net endpoint.'),
      '#default_value' => $this->configuration['host'],
      '#required' => TRUE,
    ];
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#description' => $this->t('Username of the Handle administrator.'),
      '#default_value' => $this->configuration['username'],
      '#required' => TRUE,
    ];
    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#description' => $this->t('Password of the Handle administrator.'),
      '#default_value' => $this->configuration['password'],
      '#required' => is_null($this->configuration['password']),
      '#placeholder' => $this->configuration['password'] ? '********' : '',
    ];
    $form['prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prefix'),
      '#description' => $this->t('Handle prefix as specified from Handle.net'),
      '#default_value' => $this->configuration['prefix'],
      '#required' => TRUE,
    ];
    $form['suffix_field'] = [
      '#type' => 'hidden',
      '#default_value' => $this->configuration['suffix_field'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getStateKeys(): array {
    return [
      'username',
      'password',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['host'] = $form_state->getValue('host');
    $this->configuration['prefix'] = $form_state->getValue('prefix');
    $this->configuration['username'] = $form_state->getValue('username');
    $this->configuration['suffix_field'] = $form_state->getValue('suffix_field');
    $this->configuration['password'] = !empty($form_state->getValue('password')) ? $form_state->getValue('password') : $this->configuration['password'];
    // Handle the scenario where the user did not modify the password as this
    // gets stored on the entity.
    $form_state->setValue('password', $this->configuration['password']);
  }

}
