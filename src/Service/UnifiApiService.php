<?php

namespace Drupal\event_access_unifi\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use GuzzleHttp\ClientInterface;

class UnifiApiService {

  private ClientInterface $http;
  private $cfg;
  private LoggerChannelInterface $log;

  public function __construct(ClientInterface $http, ConfigFactoryInterface $config_factory, LoggerChannelInterface $log) {
    $this->http = $http;
    $this->cfg = $config_factory->get('event_access_unifi.settings');
    $this->log = $log;
  }

  private function base(): string {
    return rtrim($this->cfg->get('api_host'), '/');
  }

  private function headers(): array {
    return [
      'Authorization' => 'Bearer ' . $this->cfg->get('api_token'),
      'Accept' => 'application/json',
      'Content-Type' => 'application/json',
    ];
  }

  private function verify(): bool {
    return (bool) $this->cfg->get('verify_ssl');
  }

  public function createVisitor(array $payload): ?array {
    try {
      $res = $this->http->request('POST', $this->base() . '/api/v1/developer/visitors', [
        'headers' => $this->headers(),
        'verify' => $this->verify(),
        'json' => $payload,
        'timeout' => 20,
      ]);
      return json_decode($res->getBody()->getContents(), TRUE);
    } catch (\Throwable $e) {
      $this->log->error('UniFi createVisitor error: @m', ['@m' => $e->getMessage()]);
      return NULL;
    }
  }
}
