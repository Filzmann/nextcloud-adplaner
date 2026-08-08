<?php

declare(strict_types=1);

require __DIR__ . '/helpers.php';
require __DIR__ . '/../../../localbase/lib/Model/ModelApiTrait.php';
require __DIR__ . '/../../lib/Model/Assistant.php';
require __DIR__ . '/../../lib/Model/ShiftDefinition.php';
require __DIR__ . '/../../lib/Model/ShiftSlot.php';
require __DIR__ . '/../../lib/Model/Team.php';
require __DIR__ . '/../../lib/Service/ShiftConfigService.php';
require __DIR__ . '/../../lib/Service/TeamAccessService.php';
require __DIR__ . '/../../lib/Service/PlanningHintService.php';
require __DIR__ . '/../../lib/Service/ScheduleService.php';
require __DIR__ . '/../../lib/Store/ShiftPlanStore.php';

use OCA\AdPlaner\Model\ShiftSlot;
use OCA\AdPlaner\Model\Team;
use OCA\AdPlaner\Service\ScheduleService;
use OCA\AdPlaner\Service\ShiftConfigService;
use OCA\AdPlaner\Service\TeamAccessService;
use OCA\AdPlaner\Service\PlanningHintService;
use OCA\AdPlaner\Store\ShiftPlanStore;
use function OCA\AdPlaner\Tests\assertDomainException;
use function OCA\AdPlaner\Tests\assertSameValue;

final class MonthPlanStatusStoreFake extends ShiftPlanStore {
    /** @var array<string, string> */
    public array $statuses = [];
    public array $added = [];
    public array $updatedSlots = [];

    public function __construct() {}

    public function monthStatus(string $teamCode, string $month): string {
        return $this->statuses[$teamCode . '|' . $month] ?? 'draft';
    }

    public function transitionMonthStatus(string $teamCode, string $month, string $expectedStatus, string $targetStatus, string $updatedByUid): bool {
        $key = $teamCode . '|' . $month;
        if ($this->monthStatus($teamCode, $month) !== $expectedStatus) {
            return false;
        }
        $this->statuses[$key] = $targetStatus;
        return true;
    }

    public function slotForMonth(int $slotId, string $teamCode, string $month): ?ShiftSlot {
        return new ShiftSlot($slotId, $teamCode, $month, $month . '-01', 'early', 'Früh', '08:00', '14:00', true);
    }

    public function slotsForMonth(string $teamCode, string $month): array {
        return [new ShiftSlot(1, $teamCode, $month, $month . '-01', 'early', 'Früh', '08:00', '14:00', true)];
    }

    public function candidatesForSlotIds(array $slotIds): array { return []; }
    public function dayNotesForMonth(string $teamCode, string $month): array { return []; }

    public function addCandidate(int $slotId, string $assistantUid, string $createdByUid): void {
        $this->added[] = compact('slotId', 'assistantUid', 'createdByUid');
    }

    public function updateSlotDefinition(int $slotId, string $label, string $startsAt, string $endsAt, bool $enabled): void {
        $this->updatedSlots[] = compact('slotId', 'label', 'startsAt', 'endsAt', 'enabled');
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
        return 100;
    }
}

final class MonthPlanStatusTeamAccessFake extends TeamAccessService {
    public function __construct() {}
}

final class MonthPlanStatusHintServiceFake extends PlanningHintService {
    public function __construct() {}
    public function forMonth(string $month, array $employeeUids): array { return []; }
}

$assistants = [[
    'uid' => 'assistant-a',
    'displayName' => 'Assistant A',
    'isEb' => false,
    'canReceiveShifts' => true,
]];
$settings = ['shifts' => [[
    'key' => 'early',
    'label' => 'Früh',
    'startsAt' => '08:00',
    'endsAt' => '14:00',
    'enabled' => true,
]]];
$assistantTeam = new Team('A1', 'ad-ASN-A1', 'Team A1', $assistants, false, $settings);
$ebTeam = new Team('A1', 'ad-ASN-A1', 'Team A1', $assistants, true, $settings);
$store = new MonthPlanStatusStoreFake();
$service = new ScheduleService($store, new ShiftConfigService(), new MonthPlanStatusTeamAccessFake(), new MonthPlanStatusHintServiceFake());

assertSameValue('draft', $service->monthPlan($ebTeam, '2026-08', 'test-eb')['status'] ?? null, 'A new month plan starts as draft.');
assertDomainException(
    static fn() => $service->transitionMonthStatus($assistantTeam, '2026-08', 'planned', 'assistant-a'),
    'Only the responsible EB may transition a month plan.'
);
assertDomainException(
    static fn() => $service->transitionMonthStatus($ebTeam, '2026-08', 'approved', 'test-eb'),
    'A draft plan may not skip the planned state.'
);

assertSameValue('planned', $service->transitionMonthStatus($ebTeam, '2026-08', 'planned', 'test-eb'), 'EB can mark a draft plan as planned.');
assertSameValue('approved', $service->transitionMonthStatus($ebTeam, '2026-08', 'approved', 'test-eb'), 'EB can approve a planned plan.');
assertDomainException(
    static fn() => $service->addCandidate($ebTeam, '2026-08', 1, 'assistant-a', 'test-eb'),
    'Approved plans reject candidate mutations.'
);
assertDomainException(
    static fn() => $service->saveDayNote($ebTeam, '2026-08-01', 'Gesperrt', 'test-eb'),
    'Approved plans reject note mutations.'
);

$store->updatedSlots = [];
assertSameValue('approved', $service->monthPlan($ebTeam, '2026-08', 'test-eb')['status'] ?? null, 'Approved status remains visible after reload.');
assertSameValue([], $store->updatedSlots, 'Loading an approved plan does not rewrite frozen slot definitions.');

assertSameValue('planned', $service->transitionMonthStatus($ebTeam, '2026-08', 'planned', 'test-eb'), 'EB has an explicit unlock path back to planned.');
$service->addCandidate($ebTeam, '2026-08', 1, 'assistant-a', 'test-eb');
assertSameValue(1, count($store->added), 'Unlocked plans accept candidate mutations again.');
assertSameValue('draft', $service->transitionMonthStatus($ebTeam, '2026-08', 'draft', 'test-eb'), 'EB can explicitly reset a planned plan to draft.');

echo "AdPlaner month plan status tests passed\n";
