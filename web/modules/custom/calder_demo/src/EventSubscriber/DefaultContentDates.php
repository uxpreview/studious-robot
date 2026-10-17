<?php

declare(strict_types=1);

namespace Drupal\calder_demo\EventSubscriber;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\DefaultContent\PreEntityImportEvent;
use Drupal\Core\DefaultContent\PreExportEvent;
use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Keeps the demo's dates meaningful and fresh.
 *
 * On export, core leaves out "created" dates; news needs them, so they are
 * kept for news stories.
 *
 * The sample content was exported on CONTENT_REFERENCE_DATE. When the site
 * template is installed later, every event date and news publish date is moved
 * forward by the same number of days, so upcoming events are still upcoming
 * and the news still reads as recent. If you re-export the content, update
 * CONTENT_REFERENCE_DATE to the day of the export.
 */
final class DefaultContentDates implements EventSubscriberInterface {

  public const CONTENT_REFERENCE_DATE = '2026-10-17';

  public function __construct(
    private readonly TimeInterface $time,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      PreExportEvent::class => 'keepNewsDates',
      PreEntityImportEvent::class => 'shiftDates',
    ];
  }

  /**
   * Exports the publish date of news stories, which core skips by default.
   */
  public function keepNewsDates(PreExportEvent $event): void {
    if ($event->entity instanceof NodeInterface && $event->entity->bundle() === 'news') {
      $event->setExportable('created');
    }
  }

  /**
   * Moves event and news dates forward to match the install date.
   */
  public function shiftDates(PreEntityImportEvent $event): void {
    if ($event->metadata['entity_type'] !== 'node') {
      return;
    }
    $days = $this->daysSinceReference();
    if ($days <= 0) {
      return;
    }
    $seconds = $days * 86400;

    foreach ($event->data as $langcode => &$values) {
      if (!is_array($values)) {
        continue;
      }
      foreach ($values['field_event_date'] ?? [] as $delta => $item) {
        foreach (['value', 'end_value'] as $column) {
          if (!empty($item[$column])) {
            $values['field_event_date'][$delta][$column] = gmdate('Y-m-d\TH:i:s', strtotime($item[$column] . ' UTC') + $seconds);
          }
        }
      }
      foreach (['created', 'changed'] as $field) {
        foreach ($values[$field] ?? [] as $delta => $item) {
          if (isset($item['value']) && is_numeric($item['value'])) {
            $values[$field][$delta]['value'] = (int) $item['value'] + $seconds;
          }
        }
      }
    }
  }

  /**
   * Whole days between the export and today.
   */
  private function daysSinceReference(): int {
    $reference = strtotime(self::CONTENT_REFERENCE_DATE . ' 00:00:00 UTC');
    return (int) floor(($this->time->getRequestTime() - $reference) / 86400);
  }

}
