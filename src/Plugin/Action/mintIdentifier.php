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

  protected function getFieldData($entity = null, $configs) {
    // Test Entity Condition - Replace with Error.
    if (!$entity) {
      $this->logger->info('mint identifier - getFieldData - entity is empty');
      $entity = $this->entityTypeManager->getStorage('node')->load(3);
    }

    // Test Config Condition - Replace with Error.
    if (!$configs) {
      $configs = $this->utils->getAssociatedConfigs($this->configuration['identifier_type']);
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
      $configs = $this->utils->getAssociatedConfigs($this->configuration['identifier_type']);
      $entity->set($configs['credentials']->get('field'), $configs['credentials']->get('host').'/id/'.$response['success']);
      $entity->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function mint($entity = null) {
    $configs = $this->utils->getAssociatedConfigs($this->configuration['identifier_type']);
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

    return $this->logger->warning('DGI_Action - Mint Action - Credentials Config not found.');
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    //$this->logger->info('Mint Identifier - Execute');
    if ($entity) {
      //$this->logger->info('Mint Identifier - Minting');
      $response = $this->mint($entity);
      $this->setIdentifierField($entity, $response);
    }
    /*else {
      $this->logger->info('Mint Identifier - Entity is Null');
    }*/
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
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $entity = $this->entityTypeManager->getStorage('node')->load(3);

    // Need a selection for content and bundle type
    $form['identifier_type'] = [
      '#type' => 'select',
      '#title' => t('Identifier Type'),
      '#default_value' => $this->configuration['identifier_type'],
      '#options' => $this->utils->getIdentifiers(),
      '#description' => t('The persistent identifier configuration to be used.'),
    ];
    return $form;
  }
}
