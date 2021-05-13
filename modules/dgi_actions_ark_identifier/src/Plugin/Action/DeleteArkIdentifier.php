<?php

namespace Drupal\dgi_actions_ark_identifier\Plugin\Action;

use Drupal\dgi_actions\Plugin\Action\DeleteIdentifier;
use Drupal\dgi_actions_ezid\Utility\EzidTrait;
use Psr\Http\Message\ResponseInterface;

/**
 * Deletes an ARK Identifier Record on CDL EZID.
 *
 * @Action(
 *   id = "dgi_actions_delete_ark_identifier",
 *   label = @Translation("Delete ARK EZID Identifier"),
 *   type = "entity"
 * )
 */
class DeleteArkIdentifier extends DeleteIdentifier {

  use EzidTrait;

  /**
   * {@inheritdoc}
   */
  protected function getRequestType(): string {
    return 'DELETE';
  }

  /**
   * {@inheritdoc}
   */
  protected function getUri(): string {
    // XXX: Grab the existing ARK value as it contains the end-point URL to
    // delete.
    return $this->getIdentifierFromEntity();
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequestParams(): array {
    return [
      'auth' => $this->getAuthorizationParams(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function handleResponse(ResponseInterface $response): void {
    $contents = $response->getBody()->getContents();
    $filteredResponse = $this->parseEzidResponse($contents);

    if (array_key_exists('success', $filteredResponse)) {
      $this->logger->info('ARK Identifier Deleted: @contents', ['@contents' => $contents]);
    }
    else {
      $this->logger->error('There was an issue deleting the ARK Identifier: @contents', ['@contents' => $contents]);
    }
  }

}
