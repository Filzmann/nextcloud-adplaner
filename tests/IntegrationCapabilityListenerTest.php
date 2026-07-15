<?php

declare(strict_types=1);

namespace OCP\EventDispatcher { class Event { public function __construct() {} } interface IEventListener { public function handle(Event $event): void; } }
namespace OCA\AdPlaner\AppInfo { final class Application { public const APP_ID = 'adplaner'; } }

namespace {
    require_once __DIR__ . '/../../localbase/lib/Integration/AdIntegrationCapabilities.php';
    require_once __DIR__ . '/../../localbase/lib/Integration/IntegrationCapabilityQueryEvent.php';
    require_once __DIR__ . '/../lib/Listener/IntegrationCapabilityQueryListener.php';

    use OCA\AdPlaner\Listener\IntegrationCapabilityQueryListener;
    use OCA\LocalBase\Integration\AdIntegrationCapabilities;
    use OCA\LocalBase\Integration\IntegrationCapabilityQueryEvent;

    $event = new IntegrationCapabilityQueryEvent(AdIntegrationCapabilities::all());
    (new IntegrationCapabilityQueryListener())->handle($event);
    if ($event->providersFor(AdIntegrationCapabilities::ASSISTANT_SCHEDULE_READ) !== ['adplaner']) throw new RuntimeException('Assistenzplanfähigkeit fehlt.');
    if ($event->isAvailable(AdIntegrationCapabilities::ABSENCE_READ)) throw new RuntimeException('Assistenzplaner meldet eine fremde Fähigkeit.');

    echo "AD Planer capability listener test passed\n";
}
