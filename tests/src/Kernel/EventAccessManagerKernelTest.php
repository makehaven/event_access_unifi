<?php

namespace Drupal\Tests\event_access_unifi\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\event_access_unifi\Service\EventAccessManager;

/**
 * Kernel tests for EventAccessManager.
 *
 * @group event_access_unifi
 */
class EventAccessManagerKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'event_access_unifi',
  ];

  /**
   * The manager service.
   *
   * @var \Drupal\event_access_unifi\Service\EventAccessManager
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('event_access_unifi', ['event_access_unifi_pass']);
    $this->installConfig(['event_access_unifi']);

    $this->manager = $this->container->get('event_access_unifi.manager');
  }

  /**
   * Tests upserting and loading a pass.
   */
  public function testUpsertAndLoadPass() {
    $visitor = [
      'visitor_id' => 'v123',
      'qr_url' => 'http://qr.test',
      'pin' => '1234',
    ];

    $this->manager->upsertPass(1, 101, 'test@example.com', 201, $visitor, 1000, 2000);

    $pass = $this->manager->loadPassForParticipant(1);
    $this->assertNotNull($pass);
    $this->assertEquals('v123', $pass['visitor_id']);
    $this->assertEquals('test@example.com', $pass['email']);
    $this->assertEquals(101, $pass['contact_id']);
    $this->assertEquals(201, $pass['event_id']);

    // Test loading by token.
    $token = $pass['token_hash'];
    $this->assertNotEmpty($token);
    $loaded_by_token = $this->manager->loadPassByToken(1, $token);
    $this->assertNotNull($loaded_by_token);
    $this->assertEquals(1, $loaded_by_token['participant_id']);

    // Test loading by wrong token.
    $this->assertNull($this->manager->loadPassByToken(1, 'wrong-token'));

    // Test upsert (update).
    $visitor_updated = [
      'visitor_id' => 'v123',
      'qr_url' => 'http://qr.test/updated',
      'pin' => '5678',
    ];
    $this->manager->upsertPass(1, 101, 'test@example.com', 201, $visitor_updated, 1000, 3000, $pass);
    
    $pass_updated = $this->manager->loadPassForParticipant(1);
    $this->assertEquals('5678', $pass_updated['pin']);
    $this->assertEquals(3000, $pass_updated['valid_to']);
    $this->assertEquals($token, $pass_updated['token_hash'], 'Token hash should persist on update if passed in existing.');
  }

  /**
   * Tests loading passes for email.
   */
  public function testLoadPassesForEmail() {
    $v = ['visitor_id' => 'v1', 'qr_url' => '', 'pin' => '1111'];
    $this->manager->upsertPass(1, 101, 'user@example.com', 201, $v, 1000, 2000);
    $this->manager->upsertPass(2, 101, 'user@example.com', 202, $v, 3000, 4000);
    $this->manager->upsertPass(3, 102, 'other@example.com', 201, $v, 1000, 2000);

    $passes = $this->manager->loadPassesForEmail('user@example.com');
    $this->assertCount(2, $passes);
    $this->assertArrayHasKey(1, $passes);
    $this->assertArrayHasKey(2, $passes);
  }

}
