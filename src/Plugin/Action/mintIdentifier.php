<?php

namespace Drupal\dgi_actions\Plugin\Action;

use Drupal\dgi_actions\Plugin\Action\IdentifierAction;
use Drupal\Core\Form\FormStateInterface;

/**
 * Creates an ARK Record on CDL EZID.
 *
 * @Action(
 *   id = "mint_identifier_record",
 *   label = @Translation("Mint Identifier"),
 *   type = "entity"
 * )
 */
class MintIdentifier extends IdentifierAction {

  /**
   * Constructs the data array into the expected metadata format for minting.
   */
  protected function buildMetadata() {
    // Pass in the data_profile config and entity reference.
    // Separate the config key values into an array.
    // Get the entity field values based on the key values.
    // Construct and return the metadata string.
    $testArray = [
      'erc.who' => 'Test One',
      'erc.what' => 'Test Two',
      'erc.when' => '1900.01.20',
    ];

    // Expects an associative array of key values. With keys and values matching the ERC data profile schema.
    $outputString = "";
    foreach($testArray as $key => $val) {
      $outputString .= $key . ": " . $val . "\r\n";
    }

    return $outputString;
  }

  /**
   * Returns the mint request response formatted as a key value pair array.
   */
  protected function responseArray($contents) {
    $responseArray = preg_split('/\r\n|\r|\n/', trim($contents));
    $assocArray = [];
    foreach ($responseArray as $res_line) {
      $splitRes = explode(':', $res_line, 2);
      $assocArray[$splitRes[0]] = $splitRes[1];
    }

    return $assocArray;
  }

  /**
   * {@inheritdoc}
   */
  public function mint() {
    // $this->configuration['identifier_type'] // Get the configured identifier for the action.
    // Pull the configuration for the identifier.
    // Pull the credentials and data profile for the identifier.
    // Pass the data profile to the buildMetadata along with the entity.
      // Method pulls applicable entity fields and constrcuts metadata.
    // Send a client request based on pulled credentials to mint the identifier.
    // Returns the mint request response.
    $body = $this->buildMetadata();

    try {
      $response = $this->client->request('POST', $this->configuration['host'] . '/shoulder/' . $this->confinguration['namespace_shoulder'], [
        'auth' => [$this->configuration['username'], $this->configuration['password']],
        'headers' => [
          'Content-Type' => 'text/plain; charset=UTF-8',
          'Content-Length' => strlen($body)
        ],
        'body' => $body
      ]);
    }
    catch (Exception $e) {
      $this->logger->warning('Issue occurred while minting an identifier: @response', [
        '@response' => $response,
      ]);
      return $e;
    }

    return $this->responseArray($response->getBody()->getContents());
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = null) {
    // run $this->mint();
    // Verify the returned response is success
    // Write the minted ARK Identifier to the applicable field
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'identifier_type' => '',
    ];
  }

  /**
   * Returns list of Identifier Configs.
   */
  protected function getConfigOptions() {
    $configs = $this->configFactory->listAll('dgi_actions.identifier');
    if (!empty($configs)) {
      $config_options = [];
      foreach ($configs as $config_id) {
        $config_options[$config_id] = $this->configFactory->get($config_id)->get('label');
      }
      return $config_options;
    }

    return 'No Identifiers Configured';
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['identifier_type'] = [
      '#type' => 'select',
      '#title' => t('Identifier Type'),
      '#default_value' => $this->configuration['identifier_type'],
      '#options' => $this->getConfigOptions(),
      '#description' => t('The persistent identifier configuration to be used.'),
    ];
    return $form;
  }
}
