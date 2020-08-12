<?php

namespace Drupal\dgi_actions\Plugin\Action;

use Drupal\dgi_actions\Plugin\Action\AbstractIdentifier;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Creates an ARK Record on CDL EZID.
 *
 * @Action(
 *   id = "ark_identifier_mint_record",
 *   label = @Translation("ARK Identifier"),
 *   type = "entity"
 * )
 */
class ArkIdentifier extends AbstractIdentifier {

  /**
   * {@inheritdoc}
   */
  public function mint() {
    $body = $this->buildMetadataString();

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

  public function deleteRecord() {
    // Pull current identifier attached to object
    // Verify a match
    // Delete from identifier CDL EZID
    // Delete identifier from field
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
      'host' => 'https://ezid.cdlib.org', // Test Host
      'content_type' => '',
      'bundle' => '',
      'username' => '',
      'password' => '',
      'namespace_shoulder' => 'ark:/99999/fk4', // Test Shoulder
      'mapped_field' => '',
    ];
  }

  /**
   * Reconstructs the data array into the body string for minting.
   */
  public function buildMetadataString() {
    $testArray = [
      'erc.who' => 'Test One',
      'erc.what' => 'Test Two',
      'erc.when' => '1900.01.20',
    ];

    // Expects an associative array of key values. With keys and values matching the ERC data profile standards.

    $outputString = "";
    foreach($testArray as $key => $val) {
      $outputString .= $key . ": " . $val . "\r\n";
    }

    return $outputString;
  }

  public function getRecord() {
    $uri = 'https://ezid.cdlib.org/id/ark:/99999/fk4j11cz31'; // ERC Type test object

    $res = $this->client->request('GET', $uri, array('Accept' => 'text/plain'));
    $contents = $res->getBody()->getContents();
    dsm(responseArray($contents), 'AssocArray');
  }

  public function responseArray($contents) {
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
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Need to capture all available content types, and provide them as a dropdown or a multi-select?
      // A Single policy may be applicable to 1 to N ContentTypes, but certain ones may need special configuration.
    //$this->getRecord();
    //$this->mint();
    /*
    $bundles = $this->entityTypeBundleInfo->getAllBundleInfo()['node'];
    $list = [];
    foreach ($bundles as $bundle_key => $bundle) {
      $list[$bundle_key] = $bundle['label'];
    }*/


    $content_types = $this->entityTypeBundleInfo->getAllBundleInfo();
    $contentTypes = [];
    $bundleList = [];
    foreach($content_types as $content_type_key => $content_type_value) {
      $contentTypes[] = $content_type_key;
      foreach($content_type_value as $bundle_key => $bundle_value) {
        $bundleList[$content_type_key][] = $bundle_key;
      }
    }

    $identifier_config = $this->configFactory->get('dgi_actions.identifier.ark')->get();
    dsm($identifier_config);
    $credentials_config = $this->configFactory->get('dgi_actions.credentials.ark')->get();
    dsm($credentials_config);
    $dataprofile_config = $this->configFactory->get('dgi_actions.data_profile.erc')->get('data');
    dsm($dataprofile_config);


    //dsm($contentTypes);
    //dsm($bundleList);

/*
    $entityFieldMap = $this->entityFieldManager->getFieldMap();
    foreach ($entityFieldMap as $key => $value) {
      if ($key == 'node') {
        dsm($value, 'node');
      }
      else {
        dsm($key);
      }
    }
*/
    $form['content_type'] = [
      '#type' => 'select',
      '#title' => t('Content Type'),
      '#default_value' => $this->configuration['content_type'],
      '#options' => $contentTypes,
      '#description' => t('Content Type and bundle affected by this configuration.'),
    ];
    $form['host'] = [
      '#type' => 'textfield',
      '#title' => t('Host'),
      '#default_value' => $this->configuration['host'],
      '#description' => t('Host address. ex. \'https://ezid.cdlib.org/shoulder/\''),
    ];
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => t('Username'),
      '#default_value' => $this->configuration['username'],
      '#description' => t('Username for the ARK.'),
    ];
    $form['password'] = [
      '#type' => 'textfield',
      '#title' => t('Password'),
      '#default_value' => $this->configuration['password'],
      '#description' => t('Password for the ARK.'),
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
