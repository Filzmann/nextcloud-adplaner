<?php

declare(strict_types=1);

namespace OCP\EventDispatcher {
    if (!class_exists(Event::class)) {
        class Event { public function __construct() {} }
    }
    if (!interface_exists(IEventDispatcher::class)) {
        interface IEventDispatcher {
            public function dispatchTyped(Event $event): Event;
        }
    }
}

namespace {
    require __DIR__ . '/helpers.php';
    require __DIR__ . '/../../../localbase/lib/Calendar/AbsenceInterval.php';
    require __DIR__ . '/../../../localbase/lib/Calendar/AbsenceQueryEvent.php';
    require __DIR__ . '/../../../localbase/lib/Calendar/ScheduleConflict.php';
    require __DIR__ . '/../../../localbase/lib/Calendar/ScheduleConflictQueryEvent.php';
    require __DIR__ . '/../../lib/Service/PlanningHintService.php';

    use OCA\AdPlaner\Service\PlanningHintService;
    use OCA\LocalBase\Calendar\AbsenceInterval;
    use OCA\LocalBase\Calendar\AbsenceQueryEvent;
    use OCA\LocalBase\Calendar\ScheduleConflict;
    use OCA\LocalBase\Calendar\ScheduleConflictQueryEvent;
    use OCP\EventDispatcher\Event;
    use OCP\EventDispatcher\IEventDispatcher;
    use function OCA\AdPlaner\Tests\assertSameValue;

    final class PlanningHintEventDispatcherFake implements IEventDispatcher {
        public bool $provideData = true;

        public function dispatchTyped(Event $event): Event {
            if (!$this->provideData) {
                return $event;
            }
            $utc = new \DateTimeZone('UTC');
            if ($event instanceof AbsenceQueryEvent) {
                $event->add(new AbsenceInterval('assistant-a', new \DateTimeImmutable('2026-08-02', $utc), new \DateTimeImmutable('2026-08-04', $utc), 'planned'));
                $event->add(new AbsenceInterval('assistant-b', new \DateTimeImmutable('2026-08-05', $utc), new \DateTimeImmutable('2026-08-06', $utc), 'approved'));
            }
            if ($event instanceof ScheduleConflictQueryEvent && $event->employeeUid() === 'assistant-a') {
                $event->add(new ScheduleConflict('appointment', new \DateTimeImmutable('2026-08-07 10:00:00', $utc), new \DateTimeImmutable('2026-08-07 11:00:00', $utc), 'Vertraulicher Titel'));
            }

            return $event;
        }
    }

    $events = new PlanningHintEventDispatcherFake();
    $service = new PlanningHintService($events);
    $hints = $service->forMonth('2026-08', ['assistant-a', 'assistant-b']);

    assertSameValue('U?', $hints['2026-08-02'][0]['marker'] ?? null, 'Planned vacation is exposed as U?.');
    assertSameValue(false, $hints['2026-08-02'][0]['blocks'] ?? null, 'Planning hints never block AdPlaner mutations.');
    assertSameValue('U', $hints['2026-08-05'][0]['marker'] ?? null, 'Approved vacation remains distinguishable.');
    assertSameValue('K', $hints['2026-08-07'][0]['marker'] ?? null, 'Calendar occupation is exposed as a compact marker.');
    assertSameValue('Termin', $hints['2026-08-07'][0]['label'] ?? null, 'Calendar titles are not leaked into the team plan.');
    assertSameValue('assistant-a', $hints['2026-08-07'][0]['employeeUid'] ?? null, 'Hints remain assigned to the visible team member.');

    $events->provideData = false;
    assertSameValue([], $service->forMonth('2026-08', ['assistant-a']), 'Missing providers are a valid empty standalone state.');

    echo "AdPlaner planning hint service tests passed\n";
}
