<?php

namespace Drupal\event_access_unifi\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelInterface;

class EventAccessManager {

  private Connection $db;
  private $cfg;
  private UnifiApiService $api;
  private LoggerChannelInterface $log;

  public function __construct(Connection $db, ConfigFactoryInterface $config_factory, UnifiApiService $api, LoggerChannelInterface $log) {
    $this->db = $db;
    $this->cfg = $config_factory->get('event_access_unifi.settings');
    $this->api = $api;
    $this->log = $log;
  }

  public function upsertPass(int $participant_id, int $contact_id, string $email, int $event_id, array $visitor, int $valid_from_s, int $valid_to_s, ?array $existing = NULL): array {
    $now = time();
    $token_hash = $existing['token_hash'] ?? bin2hex(random_bytes(16));

    $fields = [
      'participant_id' => $participant_id,
      'contact_id' => $contact_id,
      'email' => $email,
      'event_id' => $event_id,
      'visitor_id' => (string) ($visitor['visitor_id'] ?? ''),
      'qr_url' => (string) ($visitor['qr_url'] ?? ''),
      'pin' => (string) ($visitor['pin'] ?? ''),
      'valid_from' => $valid_from_s,
      'valid_to' => $valid_to_s,
      'token_hash' => $token_hash,
      'created' => $existing['created'] ?? $now,
      'changed' => $now,
    ];

    if ($existing) {
      $this->db->update('event_access_unifi_pass')
        ->fields($fields)
        ->condition('participant_id', $participant_id)
        ->execute();
    }
    else {
      $this->db->insert('event_access_unifi_pass')
        ->fields($fields)
        ->execute();
    }

    return $fields;
  }

  public function loadPassForParticipant(int $participant_id): ?array {
    $rec = $this->db->select('event_access_unifi_pass', 'p')
      ->fields('p')
      ->condition('participant_id', $participant_id)
      ->execute()
      ->fetchAssoc();
    return $rec ?: NULL;
  }

  public function loadPassByToken(int $participant_id, string $token_hash): ?array {
    $rec = $this->db->select('event_access_unifi_pass', 'p')->fields('p')->condition('participant_id', $participant_id)->execute()->fetchAssoc();
    if ($rec && hash_equals($rec['token_hash'], $token_hash)) {
      return $rec;
    }
    return NULL;
  }

  public function loadPassesForEmail(string $email): array {
    return $this->db->select('event_access_unifi_pass', 'p')
      ->fields('p')
      ->condition('email', $email)
      ->orderBy('valid_from', 'DESC')
      ->execute()
      ->fetchAllAssoc('participant_id', \PDO::FETCH_ASSOC);
  }

  public function createVisitor(string $name, ?string $email, int $valid_from_s, int $valid_to_s): ?array {
    $payload = [
      'name' => $name,
      'email' => $email ?: NULL,
      'valid_from' => $valid_from_s * 1000,
      'valid_to'   => $valid_to_s * 1000,
    ];
    $doors = $this->cfg->get('visitor_default_door_ids') ?? [];
    if (!empty($doors)) {
      $payload['door_ids'] = array_values($doors);
    }
    $resp = $this->api->createVisitor($payload);
    if (!$resp) {
      $this->log->error('Failed to create UniFi visitor for @name.', ['@name' => $name]);
      return NULL;
    }
    return [
      'visitor_id' => (string) ($resp['visitorId'] ?? ($resp['id'] ?? '')),
      'qr_url' => (string) ($resp['qrUrl'] ?? ''),
      'pin' => (string) ($resp['pin'] ?? ''),
      'raw' => $resp,
    ];
  }
}
