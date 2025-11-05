<?php

namespace Drupal\event_access_unifi\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\event_access_unifi\Service\EventAccessManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MyPassesController extends ControllerBase {

  protected EventAccessManager $mgr;

  public function __construct(EventAccessManager $mgr) {
    $this->mgr = $mgr;
  }

  public static function create(ContainerInterface $c) {
    return new static($c->get('event_access_unifi.manager'));
  }

  public function list() {
    $account = $this->currentUser();
    $user = \Drupal::entityTypeManager()->getStorage('user')->load($account->id());
    $email = $user ? (string) $user->getEmail() : '';
    if (!$email) {
      return ['#markup' => $this->t('No email on your account.')];
    }
    $rows = [];
    $passes = $this->mgr->loadPassesForEmail($email);
    foreach ($passes as $p) {
      $qrCell = ['#markup' => '—'];
      if (!empty($p['qr_url'])) {
        $qrCell = Link::fromUri($p['qr_url'], [
          'attributes' => ['target' => '_blank', 'rel' => 'noopener'],
        ])->toRenderable();
      }

      $passLink = Link::fromRoute('event_access_unifi.pass_token', [
        'participant_id' => (int) $p['participant_id'],
        'hash' => $p['token_hash'],
      ], [
        'attributes' => ['target' => '_blank', 'rel' => 'noopener'],
      ])->toRenderable();

      $rows[] = [
        $p['event_id'],
        date('Y-m-d H:i', $p['valid_from']),
        date('Y-m-d H:i', $p['valid_to']),
        ['data' => $qrCell],
        $p['pin'] ?: '—',
        ['data' => $passLink],
      ];
    }
    return [
      '#type' => 'table',
      '#header' => [$this->t('Event ID'), $this->t('Valid From'), $this->t('Valid To'), $this->t('QR'), $this->t('PIN'), $this->t('Link')],
      '#rows' => $rows ?: [[['data' => ['#markup' => $this->t('No passes yet.')], 'colspan' => 6]]],
      '#empty' => $this->t('No passes yet.'),
    ];
  }
}
