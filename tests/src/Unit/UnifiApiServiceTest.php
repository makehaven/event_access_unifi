<?php

namespace Drupal\Tests\event_access_unifi\Unit;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\event_access_unifi\Service\UnifiApiService;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @coversDefaultClass \Drupal\event_access_unifi\Service\UnifiApiService
 * @group event_access_unifi
 */
class UnifiApiServiceTest extends UnitTestCase {

  protected $http;
  protected $configFactory;
  protected $log;
  protected $api;
  protected $config;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->http = $this->createMock(ClientInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->config = $this->createMock(Config::class);
    $this->configFactory->method('get')->with('event_access_unifi.settings')->willReturn($this->config);
    $this->log = $this->createMock(LoggerChannelInterface::class);

    $this->api = new UnifiApiService($this->http, $this->configFactory, $this->log);
  }

  /**
   * @covers ::createVisitor
   */
  public function testCreateVisitor() {
    $this->config->method('get')->willReturnMap([
      ['api_host', 'https://unifi.test'],
      ['api_token', 'token123'],
      ['verify_ssl', TRUE],
    ]);

    $response = $this->createMock(ResponseInterface::class);
    $stream = $this->createMock(StreamInterface::class);
    $stream->method('getContents')->willReturn(json_encode(['id' => 'v123']));
    $response->method('getBody')->willReturn($stream);

    $this->http->expects($this->once())
      ->method('request')
      ->with('POST', 'https://unifi.test/api/v1/developer/visitors', $this->callback(function($options) {
        return $options['headers']['Authorization'] === 'Bearer token123' &&
               $options['verify'] === TRUE &&
               $options['json'] === ['name' => 'Test'];
      }))
      ->willReturn($response);

    $result = $this->api->createVisitor(['name' => 'Test']);
    $this->assertEquals(['id' => 'v123'], $result);
  }

  /**
   * @covers ::createVisitor
   */
  public function testCreateVisitorException() {
    $this->config->method('get')->willReturnMap([
      ['api_host', 'https://unifi.test'],
      ['api_token', 'token123'],
    ]);

    $this->http->method('request')->willThrowException(new \Exception('Network error'));

    $this->log->expects($this->once())
      ->method('error')
      ->with($this->stringContains('UniFi createVisitor error'), self::arrayHasKey('@m'));

    $result = $this->api->createVisitor(['name' => 'Test']);
    $this->assertNull($result);
  }
}
