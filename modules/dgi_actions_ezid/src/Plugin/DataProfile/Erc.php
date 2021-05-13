<?php

namespace Drupal\dgi_actions_ezid\Plugin\DataProfile;

use Drupal\Core\Form\FormStateInterface;
use Drupal\dgi_actions\Plugin\DataProfileBase;

/**
 * Mints a Handle from Handle.net.
 *
 * @DataProfile(
 *   id = "erc",
 *   label = @Translation("ERC"),
 *   description = @Translation("ERC Data Profile for interacting with California Digital Library's EZID service.")
 * )
 */
class Erc extends DataProfileBase {

  /**
   * Handle service data plugin constructor.
   *
   * @param array $configuration
   *   Array containing default configuration for the plugin.
   * @param string $plugin_id
   *   The ID of the plugin being instansiated.
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
  public function modifyData(array $data): array {
    $erc_data = [];
    foreach ($data as $field => $value) {
      $erc_data["erc.$field"] = $value;
    }
    return $erc_data;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'who' => NULL,
      'what' => NULL,
      'when' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    // The available fields from the entity/bundle are passed through a
    // temporary value in the form state.
    $available_fields = $form_state->getTemporaryValue('available_fields');
    $form['who'] = [
      '#title' => $this->t('ERC Who'),
      '#description' => $this->t('Captures the WHO data (ie. Author/Publisher)'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['who'],
    ];
    $form['what'] = [
      '#title' => $this->t('ERC What'),
      '#description' => $this->t('Captures the WHAT data (ie. Title)'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['what'],
    ];
    $form['when'] = [
      '#title' => $this->t('ERC When'),
      '#description' => $this->t('Captures the WHEN data (ie. Published or posted date.)'),
      '#type' => 'select',
      '#options' => $available_fields,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->configuration['when'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['who'] = $form_state->getValue('who');
    $this->configuration['what'] = $form_state->getValue('what');
    $this->configuration['when'] = $form_state->getValue('when');
  }

}
