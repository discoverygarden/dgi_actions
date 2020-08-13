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
  protected function buildMetadata($data = null) {
    if ($data) {
      // Expects an associative array of key values. With keys and values matching the ERC data profile schema.
      $outputString = "";
      foreach($data as $key => $val) {
        $outputString .= $key . ": " . $val . "\r\n";
      }

      return $outputString;
    }
    else {
      $this->logger->warning('DGI_Actions - Build Metadata - Data is missing.');

      return "";
    }
  }

  /**
   * Returns the mint request response formatted as a key value pair array.
   */
  protected function responseArray($contents) {
    $responseArray = preg_split('/\r\n|\r|\n/', trim($contents));
    $assocArray = [];
    foreach ($responseArray as $res_line) {
      $splitRes = explode(':', $res_line, 2);
      $assocArray[trim($splitRes[0])] = trim($splitRes[1]);
    }

    return $assocArray;
  }

  public function getAssociatedConfigs() {
    $configs = [];
    $identifier = $this->configFactory->get($this->configuration['identifier_type']);
    if (!empty($identifier->get())) {
      $creds = $this->configFactory->get('dgi_actions.credentials.'.$identifier->get('identifier_id'));
      $data_profile = $this->configFactory->get('dgi_actions.data_profile.'.$identifier->get('data_profile.id'));

      $configs['identifier'] = $identifier;
      $configs['credentials'] = $creds;
      $configs['data_profile'] = $data_profile;

      return $configs;
    }

    return FALSE;
  }

  protected function getFieldData($entity = null, $configs) {
    // Test Entity Condition - Replace with Error.
    if (!$entity) {
      $entity = $this->entityTypeManager->getStorage('node')->load(3);
    }

    // Test Config Condition - Replace with Error.
    if (!$configs) {
      $configs = $this->getAssociatedConfigs();
    }

    $data = [];
    foreach ($configs['data_profile']->get() as $key => $value) {
      if(is_numeric($key)) {
        $data[$value['key']] = $entity->get($value['source_field'])->getString(); //getvalue() and deal with array
      }
    }

    $data = array_merge(['_target' => $entity->toUrl('canonical', ['absolute' => TRUE])->toString()], $data);

    return $data;
  }

  protected function setIdentifierField($entity, $response) {
    if (array_key_exists('success', $response)) {
      $configs = $this->getAssociatedConfigs();
      $entity->set($configs['credentials']->get('field'), $configs['credentials']->get('host').'/id/'.$response['success']);
      $entity->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function mint($entity = null) {
    $configs = $this->getAssociatedConfigs();
    $body = $this->buildMetadata($this->getFieldData($entity, $configs));

    if (!empty($configs['credentials'])) {
      try {
        $response = $this->client->request('POST', $configs['credentials']->get('host') . '/shoulder/' . $configs['credentials']->get('shoulder'), [
          'auth' => [$configs['credentials']->get('username'), $configs['credentials']->get('password')],
          'headers' => [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Length' => strlen($body)
          ],
          'body' => $body
        ]);
      }
      catch (Exception $e) {
        $this->logger->warning('DGI_Action - Mint Action - Issue occurred while minting an identifier: @response', [
          '@response' => $response,
        ]);
        return $e;
      }

      return $this->responseArray($response->getBody()->getContents());
    }

    return $this->logger-warning('DGI_Action - Mint Action - Credentials Config not found.');
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = null) {
    //$response = $this->mint($entity);
    //$this->setIdentifierField($entity, $response);
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
    $entity = $this->entityTypeManager->getStorage('node')->load(3);

    // Need a selection for content and bundle type
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
