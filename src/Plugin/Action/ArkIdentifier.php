<?php

namespace Drupal\dgi_actions\Plugin\ArkIdentifier;

use Drupal\dgi_actions\Plugin\AbstractIdentifier;

/**
 * Creates an ARK Record on CDL EZID.
 *
 * @Action(
 *   id = "ark_identifier_mint_record",
 *   label = @Translation("ARK Identifier"),
 *   type = "ark identifier"
 * )
 */
class ArkIdentifier extends AbstractIdentifier {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  //protected $connection;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection.
   */
  /* Not defining a custom constructor yet. Default constructor should be fine.
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Connection $connection,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->connection = $connection;
  }*/

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    // Hit the ARK html endpoint
    // Make sure the returned response is whats expected
    // Wrire the Handle identifier to the custom field
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['credentials'] = [
      '#type' => 'textfield',
      '#title' => t('Credentials'),
      '#default_value' => $this->configuration['credentials'],
      '#description' => t('Credentials for the ARK.'),
    ];
    $form['namespace_shoulder'] = [
      '#type' => 'textfield',
      '#title' => t('Namespace/Shoulder'),
      '#default_value' => $this->configuration['namespace_shoulder'],
      '#description' => t('Namespace/Shoulder for the ARK Record.'),
    ];
    $form['mapped_field'] = [
      '#type' => 'textfield',
      '#title' => t('ARK Field Mapping'),
      '#default_value' => $this->configuration['mapped_field'],
      '#description' => t('Field Mapping for the ARK identifier.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration = $form_state->getValues();
  }

}
