<?php

namespace Drupal\Tests\event_access_unifi\Unit;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\event_access_unifi\Service\EventAccessManager;
use Drupal\event_access_unifi\Service\UnifiApiService;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Database\Connection;

/**
 * @coversDefaultClass \Drupal\event_access_unifi\Service\EventAccessManager
 * @group event_access_unifi
 */
class EventAccessManagerTest extends UnitTestCase {

  protected $db;
  protected $configFactory;
  protected $api;
  protected $log;
  protected $manager;
  protected $config;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->db = $this->createMock(Connection::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->config = $this->createMock(Config::class);
    $this->configFactory->method('get')->with('event_access_unifi.settings')->willReturn($this->config);

    $this->api = $this->createMock(UnifiApiService::class);
    $this->log = $this->createMock(LoggerChannelInterface::class);

    $this->manager = new EventAccessManager($this->db, $this->configFactory, $this->api, $this->log);
  }

  /**
   * @covers ::createVisitor
   */
  public function testCreateVisitor() {
    $this->config->method('get')->with('visitor_default_door_ids')->willReturn(['door1', 'door2']);

    $this->api->expects($this->once())
      ->method('createVisitor')
      ->with($this->callback(function($payload) {
        return $payload['name'] === 'Test User' &&
               $payload['email'] === 'test@example.com' &&
               $payload['door_ids'] === ['door1', 'door2'] &&
               $payload['valid_from'] === 1000000 && // 1000 * 1000
               $payload['valid_to'] === 2000000;    // 2000 * 1000
      }))
      ->willReturn([
        'visitorId' => 'v123',
        'qrUrl' => 'https://qr.test',
        'pin' => '1234',
      ]);

    $result = $this->manager->createVisitor('Test User', 'test@example.com', 1000, 2000);

    $this->assertEquals('v123', $result['visitor_id']);
    $this->assertEquals('https://qr.test', $result['qr_url']);
    $this->assertEquals('1234', $result['pin']);
  }

  /**
   * @covers ::createVisitor
   */
  public function testCreateVisitorFailure() {
    $this->config->method('get')->with('visitor_default_door_ids')->willReturn([]);

    $this->api->method('createVisitor')->willReturn(NULL);

    $this->log->expects($this->once())
      ->method('error')
      ->with($this->stringContains('Failed to create UniFi visitor'));

    $result = $this->manager->createVisitor('Fail User', NULL, 1000, 2000);
    $this->assertNull($result);
  }
}
