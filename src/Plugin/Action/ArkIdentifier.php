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
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = $object->access('read', $account, $return_as_object);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function mint() {
    $host = 'https://ezid.cdlib.org/shoulder/';
    $uri = $host . $this->configuration['namespace_shoulder'];
    $response = $this->client->request('POST', $uri, array('auth' => array($this->configuration['username'], $this->configuration['password']), 'Accept' => 'text/plain'));
    dsm($response->getStatusCode());
    dsm($response->getBody()->getContents());
    // Check response for "Success", else, throw an error
      // Use try-catch here, because a bad request will throw an exception.
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    // Hit the ARK html endpoint using configured values for the applicable content/bundle type
    // Verify the returned response is whats expected
    // Write the ark identifier to the custom field
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'content_type' => '',
      'username' => '',
      'password' => '',
      'namespace_shoulder' => 'ark:/99999/fk4', // Test Shoulder
      'mapped_field' => '',
    ];
  }

  public function getRecord() {
    $uri = 'https://ezid.cdlib.org/id/ark:/99999/fk4j11cz31'; // ERC Type test object

    $res = $this->client->request('GET', $uri, array('Accept' => 'text/plain'));
    $contents = $res->getBody()->getContents();
    $responseArray = preg_split('/\r\n|\r|\n/', trim($contents));
    $assocArray = [];
    foreach ($responseArray as $res_line) {
      $splitRes = explode(':', $res_line, 2);
      $assocArray[$splitRes[0]] = $splitRes[1];
    }
    dsm($assocArray, 'AssocArray');
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Need to capture all available content types, and provide them as a dropdown or a multi-select?
      // A Single policy may be applicable to 1 to N ContentTypes, but certain ones may need special configuration.
    //$this->getRecord();
    $this->mint();
    $bundles = $this->entityTypeBundleInfo->getAllBundleInfo()['node'];
    $list = [];
    foreach ($bundles as $bundle_key => $bundle) {
      $list[$bundle_key] = $bundle['label'];
    }
    $form['content_type'] = [
      '#type' => 'select',
      '#title' => t('Content Type'),
      '#default_value' => $this->configuration['content_type'],
      '#options' => $list,
      '#description' => t('Content Types affected by this configuration.'),
    ];
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => t('username'),
      '#default_value' => $this->configuration['username'],
      '#description' => t('Username for the ARK.'),
    ];
    $form['password'] = [
      '#type' => 'textfield',
      '#title' => t('password'),
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
