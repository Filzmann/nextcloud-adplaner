<?php

declare(strict_types=1);

require __DIR__ . '/helpers.php';
require __DIR__ . '/../../../localbase/lib/Model/ModelApiTrait.php';
require __DIR__ . '/../../lib/Model/ShiftDefinition.php';
require __DIR__ . '/../../lib/Service/ShiftConfigService.php';

use OCA\AdPlaner\Service\ShiftConfigService;
use function OCA\AdPlaner\Tests\assertSameValue;

$service = new ShiftConfigService();
$defaults = $service->defaults();
$defaultSegments = $service->segments($defaults);
$defaultModels = \OCA\AdPlaner\Model\ShiftDefinition::get_all($defaultSegments);

assertSameValue(['early', 'late', 'night'], array_column($defaultSegments, 'key'), 'Default shifts should use the normal three-shift setup.');
assertSameValue(3, count($defaultModels), 'ShiftDefinition::get_all should hydrate API lists.');
assertSameValue('08:00', $defaultSegments[0]['startsAt'], 'Default early shift should start at 08:00.');
assertSameValue('14:00', $defaultSegments[0]['endsAt'], 'Default early shift should end at 14:00.');
assertSameValue('20:00', $defaultSegments[2]['startsAt'], 'Default night shift should start at 20:00.');
assertSameValue('08:00', $defaultSegments[2]['endsAt'], 'Default night shift should end at the next early start.');

$customSettings = $service->normalize([
    'meetingDay' => '2026-07-15',
    'shifts' => [
        [
            'key' => 'first',
            'label' => 'Erste',
            'startsAt' => '08:00',
            'endsAt' => '12:00',
            'enabled' => true,
        ],
        [
            'key' => 'overlap',
            'label' => 'Überlappung',
            'startsAt' => '11:00',
            'endsAt' => '15:00',
            'enabled' => true,
        ],
        [
            'key' => 'night',
            'label' => 'Nacht',
            'startsAt' => '20:00',
            'endsAt' => '08:00',
            'enabled' => false,
        ],
    ],
]);
$customSegments = $service->segments($customSettings);
$days = $service->monthDays('2026-02');

assertSameValue('2026-07-15', $customSettings['meetingDay'], 'Meeting day should be preserved.');
assertSameValue(['first', 'overlap', 'night'], array_column($customSegments, 'key'), 'Custom shifts should keep their configured order.');
assertSameValue('11:00', $customSegments[1]['startsAt'], 'Overlapping custom shifts should be allowed.');
assertSameValue('15:00', $customSegments[1]['endsAt'], 'Overlapping custom shifts should keep their end time.');
assertSameValue('20:00', $customSegments[2]['startsAt'], 'Cross-midnight custom shifts should keep their start time.');
assertSameValue('08:00', $customSegments[2]['endsAt'], 'Cross-midnight custom shifts should keep their end time.');
assertSameValue(false, $customSegments[2]['enabled'], 'Disabled custom shifts should be preserved.');
assertSameValue(28, count($days), 'February 2026 should have 28 days.');
assertSameValue('2026-02-01', $days[0]['date'], 'First month day should be correct.');

$assertInvalidArgument = static function (callable $operation, string $message): void {
    try {
        $operation();
    } catch (\InvalidArgumentException) {
        return;
    }

    throw new \RuntimeException($message);
};
$assertInvalidArgument(
    static fn() => $service->normalize(['shifts' => 'invalid']),
    'Non-list shift settings should be rejected.'
);
$assertInvalidArgument(
    static fn() => $service->monthDays('2026-13'),
    'Out-of-range months should be rejected.'
);
$assertInvalidArgument(
    static fn() => $service->normalizeDate('2026-02-30'),
    'Impossible calendar dates should be rejected.'
);
$assertInvalidArgument(
    static fn() => $service->normalizeDate('30.02.2026'),
    'Non-ISO calendar dates should be rejected.'
);

echo 'AdPlaner shift config smoke tests passed' . PHP_EOL;
