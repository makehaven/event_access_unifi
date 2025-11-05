<?php

namespace Drupal\event_access_unifi\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\event_access_unifi\Service\EventAccessManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class TokenController extends ControllerBase {

  protected EventAccessManager $mgr;

  public function __construct(EventAccessManager $mgr) {
    $this->mgr = $mgr;
  }

  public static function create(ContainerInterface $c) {
    return new static($c->get('event_access_unifi.manager'));
  }

  public function view($participant_id, $hash) {
    $rec = $this->mgr->loadPassByToken((int) $participant_id, (string) $hash);
    if (!$rec) {
      throw new AccessDeniedHttpException();
    }
    $rows = [
      [$this->t('Event ID'), (int) $rec['event_id']],
      [$this->t('Valid From'), date('Y-m-d H:i', (int) $rec['valid_from'])],
      [$this->t('Valid To'), date('Y-m-d H:i', (int) $rec['valid_to'])],
      [$this->t('PIN'), $rec['pin'] !== '' ? $rec['pin'] : '—'],
    ];

    if (!empty($rec['qr_url'])) {
      $rows[] = [
        $this->t('QR'),
        Link::fromUri($rec['qr_url'], [
          'attributes' => ['target' => '_blank', 'rel' => 'noopener'],
        ])->toRenderable(),
      ];
    }
    else {
      $rows[] = [$this->t('QR'), '—'];
    }

    $renderRows = [];
    foreach ($rows as $row) {
      $renderRows[] = [
        ['data' => $row[0]],
        ['data' => $row[1]],
      ];
    }

    $build = [
      '#type' => 'table',
      '#header' => [$this->t('Field'), $this->t('Value')],
      '#rows' => $renderRows,
      '#attributes' => ['class' => ['event-access-pass']],
    ];
    return $build;
  }
}
