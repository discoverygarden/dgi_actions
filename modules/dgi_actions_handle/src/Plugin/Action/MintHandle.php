<?php

namespace Drupal\dgi_actions_handle\Plugin\Action;

use Drupal\dgi_actions\Plugin\Action\MintIdentifier;
use Drupal\dgi_actions_handle\Utility\HandleTrait;
use GuzzleHttp\Psr7\Response;

/**
 * Mints a Handle.
 *
 * @Action(
 *   id = "dgi_actions_mint_handle",
 *   label = @Translation("Mint a Handle"),
 *   type = "entity"
 * )
 */
class MintHandle extends MintIdentifier {

  use HandleTrait;

  /**
   * {@inheritdoc}
   */
  protected function getRequestType(): string {
    return 'PUT';
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequestParams(): array {
    return [
      'auth' => $this->getAuthorizationParams(),
      'headers' => [
        'Content-Type' => 'application/json;charset=UTF-8',
      ],
      'json' => [
        [
          'index' => 1,
          'type' => 'URL',
          'data' => $this->entity->toUrl()->setAbsolute()->toString(TRUE)->getGeneratedUrl(),
        ],
      ],
      'query' => [
        'overwrite' => 'false',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function mint() {
    return $this->handleRequest();
  }

  /**
   * {@inheritdoc}
   */
  protected function getIdentifierFromResponse(Response $response): string {
    $body = json_decode($response->getBody(), TRUE);
    $this->logger->info('Handle minted: @handle.', ['@handle' => $body['handle']]);
    return "https://hdl.handle.net/{$body['handle']}";
  }

}
