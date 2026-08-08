<?php

declare(strict_types=1);

require __DIR__ . '/helpers.php';
require __DIR__ . '/../../../localbase/lib/Model/ModelApiTrait.php';
require __DIR__ . '/../../../localbase/lib/Organization/AdOrganizationDefinition.php';
require __DIR__ . '/../../lib/Model/Assistant.php';
require __DIR__ . '/../../lib/Model/ShiftCandidate.php';
require __DIR__ . '/../../lib/Model/ShiftDefinition.php';
require __DIR__ . '/../../lib/Model/ShiftSlot.php';
require __DIR__ . '/../../lib/Model/Team.php';
require __DIR__ . '/../../lib/Repository/ShiftPlanRepository.php';
require __DIR__ . '/../../lib/Service/ShiftConfigService.php';
require __DIR__ . '/../../lib/Service/TeamAccessService.php';
require __DIR__ . '/../../lib/Service/PlanningHintService.php';
require __DIR__ . '/../../lib/Service/ScheduleService.php';
require __DIR__ . '/../../lib/Store/ShiftPlanStore.php';

use OCA\AdPlaner\Model\ShiftCandidate;
use OCA\AdPlaner\Model\ShiftSlot;
use OCA\AdPlaner\Model\Team;
use OCA\AdPlaner\Service\ScheduleService;
use OCA\AdPlaner\Service\ShiftConfigService;
use OCA\AdPlaner\Service\TeamAccessService;
use OCA\AdPlaner\Service\PlanningHintService;
use OCA\AdPlaner\Store\ShiftPlanStore;
use function OCA\AdPlaner\Tests\assertDomainException;
use function OCA\AdPlaner\Tests\assertSameValue;

class FakeShiftPlanStoreForSchedule extends ShiftPlanStore {
    public array $added = [];
    public array $slots = [];
    public array $updatedSlots = [];
    public array $insertedSlots = [];

    public function __construct() {
    }

    public function monthStatus(string $teamCode, string $month): string {
        return 'draft';
    }

    public function slotForMonth(int $slotId, string $teamCode, string $month): ?ShiftSlot {
        return new ShiftSlot($slotId, $teamCode, $month, $month . '-01', 'early', 'Früh', '08:00', '14:00', true);
    }

    public function addCandidate(int $slotId, string $assistantUid, string $createdByUid): void {
        $this->added[] = compact('slotId', 'assistantUid', 'createdByUid');
    }

    public function slotsForMonth(string $teamCode, string $month): array {
        if ($this->slots !== []) {
            return $this->slots;
        }

        return [
            new ShiftSlot(1, $teamCode, $month, $month . '-01', 'early', 'Früh', '08:00', '14:00', true),
        ];
    }

    public function candidatesForSlotIds(array $slotIds): array {
        return [
            1 => [
                new ShiftCandidate(1, 1, 'assistant-a', 'test-eb'),
                new ShiftCandidate(2, 1, 'test-eb', 'test-eb'),
            ],
        ];
    }

    public function dayNotesForMonth(string $teamCode, string $month): array {
        return [];
    }

    public function updateSlotDefinition(int $slotId, string $label, string $startsAt, string $endsAt, bool $enabled): void {
        $this->updatedSlots[] = compact('slotId', 'label', 'startsAt', 'endsAt', 'enabled');

        foreach ($this->slots as $slot) {
            if ($slot->id !== $slotId) {
                continue;
            }

            $slot->label = $label;
            $slot->startsAt = $startsAt;
            $slot->endsAt = $endsAt;
            $slot->enabled = $enabled;
        }
    }

    public function insertSlot(
        string $teamCode,
        string $month,
        string $workDate,
        string $segmentKey,
        string $label,
        string $startsAt,
        string $endsAt,
        bool $enabled
    ): int {
        $id = 99 + count($this->insertedSlots);
        $this->insertedSlots[] = compact(
            'id',
            'teamCode',
            'month',
            'workDate',
            'segmentKey',
            'label',
            'startsAt',
            'endsAt',
            'enabled'
        );

        if ($this->slots !== []) {
            $this->slots[] = new ShiftSlot($id, $teamCode, $month, $workDate, $segmentKey, $label, $startsAt, $endsAt, $enabled);
        }

        return $id;
    }
}

class FakeTeamAccessServiceForSchedule extends TeamAccessService {
    public function __construct() {
    }
}

class FakePlanningHintServiceForSchedule extends PlanningHintService {
    public function __construct() {}
    public function forMonth(string $month, array $employeeUids): array {
        return [$month . '-01' => [[
            'employeeUid' => 'assistant-a',
            'type' => 'absence',
            'marker' => 'U?',
            'label' => 'Urlaub',
            'blocks' => false,
        ]]];
    }
}

$assistants = [
    ['uid' => 'assistant-a', 'displayName' => 'Assistant A', 'isEb' => false, 'canReceiveShifts' => true],
    ['uid' => 'test-eb', 'displayName' => 'Test EB', 'isEb' => true, 'canReceiveShifts' => false],
];

$assistantModel = \OCA\AdPlaner\Model\Assistant::get($assistants[0]);
$assistantModels = \OCA\AdPlaner\Model\Assistant::get_all($assistants);
assertSameValue(true, $assistantModel instanceof \OCA\AdPlaner\Model\Assistant, 'Assistant::get should hydrate API data.');
assertSameValue(2, count($assistantModels), 'Assistant::get_all should hydrate API lists.');

$settings = [
    'shifts' => [
        ['key' => 'early', 'label' => 'Früh', 'startsAt' => '08:00', 'endsAt' => '14:00', 'enabled' => true],
    ],
];

$assistantTeam = new Team('A1', 'ad-ASN-A1', 'Team A1', $assistants, false, $settings);
$ebTeam = new Team('A1', 'ad-ASN-A1', 'Team A1', $assistants, true, $settings);
$mappedTeam = Team::get($ebTeam->toArray());
assertSameValue('Team A1', $mappedTeam->toArray()['displayName'], 'Team::get should keep the API payload shape.');

$store = new FakeShiftPlanStoreForSchedule();
$service = new ScheduleService($store, new ShiftConfigService(), new FakeTeamAccessServiceForSchedule(), new FakePlanningHintServiceForSchedule());

$service->addCandidate($assistantTeam, '2026-07', 9, '', 'assistant-a');
assertSameValue('assistant-a', $store->added[0]['assistantUid'] ?? null, 'Assistant should be able to add themself.');

$service->addCandidate($ebTeam, '2026-07', 9, 'assistant-a', 'test-eb');
assertSameValue('assistant-a', $store->added[1]['assistantUid'] ?? null, 'EB should be able to assign an assistant.');

assertDomainException(
    static fn() => $service->addCandidate($ebTeam, '2026-07', 9, '', 'test-eb'),
    'EB should not be able to add themself without selecting an assistant.'
);
assertDomainException(
    static fn() => $service->addCandidate($ebTeam, '2026-07', 9, 'test-eb', 'test-eb'),
    'EB accounts should not be assignable to shifts.'
);
assertDomainException(
    static fn() => $service->addCandidate($assistantTeam, '2026-07', 9, 'test-eb', 'assistant-a'),
    'Assistants should not assign other users.'
);

$plan = $service->monthPlan($ebTeam, '2026-07', 'test-eb');
$slotCandidates = $plan['days'][0]['slots'][0]['candidates'] ?? [];
assertSameValue(['assistant-a'], array_column($slotCandidates, 'uid'), 'Month plan should hide non-assignable EB candidates.');
assertSameValue('Assistant A', $plan['days'][0]['hints'][0]['displayName'] ?? null, 'Planning hints use the visible team label without exposing foreign details.');

$configuredStore = new FakeShiftPlanStoreForSchedule();
$configuredStore->slots = [
    new ShiftSlot(10, 'A1', '2026-07', '2026-07-01', 'early', 'Altfrüh', '07:00', '13:00', true),
    new ShiftSlot(11, 'A1', '2026-07', '2026-07-01', 'obsolete', 'Alt', '00:00', '01:00', true),
];
$configuredTeam = new Team('A1', 'ad-ASN-A1', 'Team A1', $assistants, true, [
    'shifts' => [
        ['key' => 'early', 'label' => 'Früh neu', 'startsAt' => '08:00', 'endsAt' => '14:00', 'enabled' => true],
        ['key' => 'late', 'label' => 'Spät', 'startsAt' => '14:00', 'endsAt' => '20:00', 'enabled' => true],
        ['key' => 'night', 'label' => 'Nacht', 'startsAt' => '20:00', 'endsAt' => '08:00', 'enabled' => false],
    ],
]);
$configuredService = new ScheduleService($configuredStore, new ShiftConfigService(), new FakeTeamAccessServiceForSchedule(), new FakePlanningHintServiceForSchedule());
$configuredPlan = $configuredService->monthPlan($configuredTeam, '2026-07', 'test-eb');
$updatesById = [];
foreach ($configuredStore->updatedSlots as $updatedSlot) {
    $updatesById[$updatedSlot['slotId']] = $updatedSlot;
}

assertSameValue('Früh neu', $updatesById[10]['label'] ?? null, 'Existing slots should be updated to the current shift label.');
assertSameValue(false, $updatesById[11]['enabled'] ?? null, 'Slots for removed shift segments should be disabled.');
assertSameValue('late', $configuredStore->insertedSlots[0]['segmentKey'] ?? null, 'Missing enabled segments should be inserted for the first day.');
assertSameValue(false, in_array('night', array_column($configuredStore->insertedSlots, 'segmentKey'), true), 'Disabled shift segments should not be inserted.');
assertSameValue('Früh neu', $configuredPlan['days'][0]['slots'][0]['label'] ?? null, 'Month plan should use refreshed slot definitions.');

echo 'AdPlaner schedule smoke tests passed' . PHP_EOL;
