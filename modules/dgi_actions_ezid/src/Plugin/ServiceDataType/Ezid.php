<?php

namespace Drupal\dgi_actions_ezid\Plugin\ServiceDataType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\dgi_actions\Plugin\ServiceDataTypeBase;

/**
 * Mints an identifier via the EZID service.
 *
 * @ServiceDataType(
 *   id = "ezid",
 *   label = @Translation("EZID"),
 *   description = @Translation("Service data for California Digital Library's EZID.")
 * )
 */
class Ezid extends ServiceDataTypeBase {

  /**
   * The Drupal state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * EZID service data plugin constructor.
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
      'namespace' => NULL,
      'resolver' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['host'] = [
      '#type' => 'url',
      '#title' => $this->t('Host'),
      '#description' => $this->t('Host address for the EZID service endpoint.'),
      '#default_value' => $this->configuration['host'],
      '#required' => TRUE,
    ];
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#description' => $this->t('Username for interacting with EZID.'),
      '#default_value' => $this->configuration['username'],
      '#required' => TRUE,
    ];
    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#description' => $this->t('Used to authenticate with the EZID service.'),
      '#default_value' => $this->configuration['password'],
      '#required' => is_null($this->configuration['password']),
      '#placeholder' => $this->configuration['password'] ? '********' : '',
    ];
    $form['namespace'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Shoulder'),
      '#description' => $this->t('EZID shoulder for minting the Identifier. E.g. `ark:/99999/fk4`.'),
      '#default_value' => $this->configuration['namespace'],
      '#required' => TRUE,
    ];
    $form['resolver'] = [
      '#type' => 'url',
      '#title' => $this->t('Resolver'),
      '#description' => $this->t('Host address for the EZID resolver. (Falls back to the EZID Host plus "/id".)'),
      '#default_value' => $this->configuration['resolver'],
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
    $this->configuration['namespace'] = $form_state->getValue('prefix');
    $this->configuration['username'] = $form_state->getValue('username');
    $this->configuration['password'] = !empty($form_state->getValue('password')) ? $form_state->getValue('password') : $this->configuration['password'];
    // Handle the scenario where the user did not modify the password as this
    // gets stored on the entity.
    $form_state->setValue('password', $this->configuration['password']);
  }

}
