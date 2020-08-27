<?php

namespace Drupal\dgi_actions\Plugin\Action;

use Drupal\dgi_actions\Plugin\Action\IdentifierAction;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Psr7\Request;
use Exception;

/**
 * Deletes an ARK Identifier Record on CDL EZID..
 *
 * @Action(
 *   id = "delete_ark_identifier_record",
 *   label = @Translation("Delete ARK Identifier"),
 *   type = "entity"
 * )
 */
class DeleteArkIdentifier extends DeleteIdentifier {

  /**
   * Formats the CDL EZID response as a key-value pair array.
   *
   * CDL EZID sends back a response body as a single string,
   * with response values separated by colons, this method
   * separates that into a key-value pair array.
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

  /**
   * {@inheritdoc}
   */
  public function buildRequest($identifier) {
    $request = new Request('DELETE', $identifier);

    return $request;
  }

  /**
   * {@inheritdoc}
   */
  public function sendRequest($request, $configs) {
    $response = $this->client->send($request, [
      'auth' => [$configs['credentials']->get('username'), $configs['credentials']->get('password')],
    ]);

    $bodyContents = $response->getBody()->getContents();
    $filteredResponse = $this->responseArray($response->getBody()->getContents());

    if(array_key_exists('success', $responseArray)) {
      return $this->logger->info('ARK Identifier Deleted: @contents', ['@contents' => $bodyContents]);
    }
    else {
      throw new Exception('@contents', ['@contents' => $bodyContents]);
    }
  }

}
