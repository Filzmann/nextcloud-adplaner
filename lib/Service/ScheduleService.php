<?php

declare(strict_types=1);

namespace OCA\AdPlaner\Service;

use OCA\AdPlaner\Model\ShiftCandidate;
use OCA\AdPlaner\Model\ShiftSlot;
use OCA\AdPlaner\Model\Team;
use OCA\AdPlaner\Store\ShiftPlanStore;

class ScheduleService {
    private const STATUS_DRAFT = 'draft';
    private const STATUS_PLANNED = 'planned';
    private const STATUS_APPROVED = 'approved';

    public function __construct(
        private ShiftPlanStore $store,
        private ShiftConfigService $shiftConfig,
        private TeamAccessService $teamAccess,
        private PlanningHintService $planningHints
    ) {
    }

    public function monthPlan(Team $team, string $month, string $currentUid): array {
        $month = $this->shiftConfig->normalizeMonth($month);
        $status = $this->store->monthStatus($team->code, $month);
        if ($status !== self::STATUS_APPROVED) {
            $this->ensureMonthSlots($team, $month);
        }

        $slots = $this->store->slotsForMonth($team->code, $month);
        $enabledSlots = array_values(array_filter($slots, static fn(ShiftSlot $slot): bool => $slot->enabled));
        $candidatesBySlot = $this->store->candidatesForSlotIds(array_map(static fn(ShiftSlot $slot): int => $slot->id, $enabledSlots));
        $assistantLabels = $team->assistantLabelMap();
        $assignableUids = $team->assignableAssistantUidMap();
        $notes = $this->store->dayNotesForMonth($team->code, $month);
        $hints = $this->planningHints->forMonth($month, array_keys($assignableUids));
        $slotsByDate = [];

        foreach ($enabledSlots as $slot) {
            $slotCandidates = array_values(array_filter(
                $candidatesBySlot[$slot->id] ?? [],
                static fn(ShiftCandidate $candidate): bool => isset($assignableUids[$candidate->assistantUid])
            ));
            $slot->candidates = $this->candidatePayload($slotCandidates, $assistantLabels, $currentUid);
            $slotsByDate[$slot->workDate][] = $slot->toArray();
        }

        $days = [];
        foreach ($this->shiftConfig->monthDays($month) as $day) {
            $date = $day['date'];
            $days[] = [
                'date' => $date,
                'dayOfMonth' => $day['dayOfMonth'],
                'weekday' => $day['weekday'],
                'slots' => $slotsByDate[$date] ?? [],
                'note' => isset($notes[$date]) ? $notes[$date]->note : '',
                'hints' => array_map(static function (array $hint) use ($assistantLabels): array {
                    $hint['displayName'] = $assistantLabels[$hint['employeeUid']] ?? $hint['employeeUid'];
                    return $hint;
                }, $hints[$date] ?? []),
            ];
        }

        return [
            'team' => $team->toArray(),
            'month' => $month,
            'status' => $status,
            'segments' => array_values(array_filter($this->shiftConfig->segments($team->settings), static fn(array $segment): bool => $segment['enabled'])),
            'days' => $days,
        ];
    }

    public function addCandidate(Team $team, string $month, int $slotId, string $targetUid, string $currentUid): void {
        $month = $this->shiftConfig->normalizeMonth($month);
        $this->assertMonthMutable($team->code, $month);
        $slot = $this->requireSlot($slotId, $team->code, $month);
        if ($targetUid === '') {
            if ($team->isEb) {
                throw new \DomainException('Bitte eine Assistenzkraft auswählen.');
            }

            $targetUid = $currentUid;
        }

        $this->assertCandidateMutationAllowed($team, $targetUid, $currentUid);
        $this->assertAssignableAssistantInTeam($team, $targetUid);

        $this->store->addCandidate($slot->id, $targetUid, $currentUid);
    }

    public function removeCandidate(Team $team, string $month, int $slotId, string $targetUid, string $currentUid): void {
        $month = $this->shiftConfig->normalizeMonth($month);
        $this->assertMonthMutable($team->code, $month);
        $slot = $this->requireSlot($slotId, $team->code, $month);
        $targetUid = $targetUid === '' ? $currentUid : $targetUid;

        $this->assertCandidateMutationAllowed($team, $targetUid, $currentUid);
        $this->assertAssignableAssistantInTeam($team, $targetUid);

        $this->store->removeCandidate($slot->id, $targetUid);
    }

    public function saveDayNote(Team $team, string $workDate, string $note, string $currentUid): void {
        if (!$team->isEb) {
            throw new \DomainException('Nur die Einsatzbegleitung darf Bemerkungen bearbeiten.');
        }

        $workDate = $this->shiftConfig->normalizeDate($workDate);
        $this->assertMonthMutable($team->code, substr($workDate, 0, 7));
        $this->store->saveDayNote($team->code, $workDate, trim($note), $currentUid);
    }

    public function transitionMonthStatus(Team $team, string $month, string $targetStatus, string $currentUid): string {
        if (!$team->isEb) {
            throw new \DomainException('Nur die Einsatzbegleitung darf den Planstatus ändern.');
        }

        $month = $this->shiftConfig->normalizeMonth($month);
        $targetStatus = strtolower(trim($targetStatus));
        $currentStatus = $this->store->monthStatus($team->code, $month);
        $allowedTargets = [
            self::STATUS_DRAFT => [self::STATUS_PLANNED],
            self::STATUS_PLANNED => [self::STATUS_DRAFT, self::STATUS_APPROVED],
            self::STATUS_APPROVED => [self::STATUS_PLANNED],
        ];
        if (!in_array($targetStatus, $allowedTargets[$currentStatus] ?? [], true)) {
            throw new \DomainException('Dieser Planstatuswechsel ist nicht erlaubt.');
        }
        if (!$this->store->transitionMonthStatus($team->code, $month, $currentStatus, $targetStatus, $currentUid)) {
            throw new \DomainException('Der Planstatus wurde zwischenzeitlich geändert. Bitte neu laden.');
        }

        return $targetStatus;
    }

    private function ensureMonthSlots(Team $team, string $month): void {
        $existingSlots = $this->store->slotsForMonth($team->code, $month);
        $existing = [];
        foreach ($existingSlots as $slot) {
            $existing[$slot->workDate . '|' . $slot->segmentKey] = $slot;
        }

        $segments = $this->shiftConfig->segments($team->settings);
        $segmentKeys = array_flip(array_map(static fn(array $segment): string => (string)$segment['key'], $segments));
        foreach ($existingSlots as $slot) {
            if (isset($segmentKeys[$slot->segmentKey])) {
                continue;
            }

            $this->store->updateSlotDefinition(
                $slot->id,
                $slot->label,
                $slot->startsAt,
                $slot->endsAt,
                false
            );
        }

        foreach ($this->shiftConfig->monthDays($month) as $day) {
            foreach ($segments as $segment) {
                $key = $day['date'] . '|' . $segment['key'];
                if (isset($existing[$key])) {
                    $this->store->updateSlotDefinition(
                        $existing[$key]->id,
                        (string)$segment['label'],
                        (string)$segment['startsAt'],
                        (string)$segment['endsAt'],
                        (bool)$segment['enabled']
                    );
                    continue;
                }

                if (!$segment['enabled']) {
                    continue;
                }

                $this->store->insertSlot(
                    $team->code,
                    $month,
                    (string)$day['date'],
                    (string)$segment['key'],
                    (string)$segment['label'],
                    (string)$segment['startsAt'],
                    (string)$segment['endsAt'],
                    true
                );
            }
        }
    }

    private function requireSlot(int $slotId, string $teamCode, string $month): ShiftSlot {
        $slot = $this->store->slotForMonth($slotId, $teamCode, $month);
        if ($slot === null || !$slot->enabled) {
            throw new \DomainException('Diese Schicht wurde nicht gefunden.');
        }

        return $slot;
    }

    private function candidatePayload(array $candidates, array $assistantLabels, string $currentUid): array {
        return array_map(
            static fn(ShiftCandidate $candidate): array => $candidate->toArray($assistantLabels, $currentUid),
            $candidates
        );
    }

    private function assertCandidateMutationAllowed(Team $team, string $targetUid, string $currentUid): void {
        if ($targetUid === $currentUid) {
            return;
        }

        if ($team->isEb) {
            return;
        }

        throw new \DomainException('Assistenzkräfte dürfen nur eigene Einträge bearbeiten.');
    }

    private function assertAssignableAssistantInTeam(Team $team, string $assistantUid): void {
        $assistant = $team->assistantByUid($assistantUid);
        if ($assistant === null) {
            throw new \DomainException('Diese Assistenz gehört nicht zum Team.');
        }

        if (!$assistant->canReceiveShifts) {
            throw new \DomainException('Einsatzbegleitungen können keiner Schicht zugeteilt werden.');
        }
    }

    private function assertMonthMutable(string $teamCode, string $month): void {
        if ($this->store->monthStatus($teamCode, $month) === self::STATUS_APPROVED) {
            throw new \DomainException('Der genehmigte Monatsplan ist gegen Änderungen gesperrt.');
        }
    }
}
