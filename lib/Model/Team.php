<?php

declare(strict_types=1);

namespace OCA\AdPlaner\Model;

class Team {
    public function __construct(
        public string $code,
        public string $groupName,
        public string $vacationGroupName,
        public string $displayName,
        public array $assistants,
        public array $vacationAssistants,
        public bool $isEb,
        public array $settings
    ) {
        $this->assistants = $this->normalizeAssistants($this->assistants);
        $this->vacationAssistants = $this->normalizeAssistants($this->vacationAssistants);
    }

    public function toApiArray(): array {
        return [
            'code' => $this->code,
            'groupName' => $this->groupName,
            'vacationGroupName' => $this->vacationGroupName,
            'displayName' => $this->displayName,
            'assistants' => $this->assistantsApiArray(),
            'vacationAssistants' => $this->vacationAssistantsApiArray(),
            'isEb' => $this->isEb,
            'canCoordinate' => $this->isEb,
            'settings' => $this->settings,
        ];
    }

    public function assistants(): array {
        return $this->assistants;
    }

    public function vacationAssistants(): array {
        return $this->vacationAssistants;
    }

    public function assistantsApiArray(): array {
        return array_map(static fn(Assistant $assistant): array => $assistant->toApiArray(), $this->assistants);
    }

    public function vacationAssistantsApiArray(): array {
        return array_map(static fn(Assistant $assistant): array => $assistant->toApiArray(), $this->vacationAssistants);
    }

    public function assistantLabelMap(): array {
        $map = [];
        foreach ($this->assistants as $assistant) {
            $map[$assistant->uid] = $assistant->displayName;
        }

        return $map;
    }

    public function assignableAssistantUidMap(): array {
        $map = [];
        foreach ($this->assistants as $assistant) {
            if (!$assistant->canReceiveShifts) {
                continue;
            }

            $map[$assistant->uid] = true;
        }

        return $map;
    }

    public function assistantByUid(string $uid): ?Assistant {
        foreach ($this->assistants as $assistant) {
            if ($assistant->uid === $uid) {
                return $assistant;
            }
        }

        return null;
    }

    public function vacationAssistantByUid(string $uid): ?Assistant {
        foreach ($this->vacationAssistants as $assistant) {
            if ($assistant->uid === $uid) {
                return $assistant;
            }
        }

        return null;
    }

    private function normalizeAssistants(array $assistants): array {
        return array_map(static function ($assistant): Assistant {
            if ($assistant instanceof Assistant) {
                return $assistant;
            }

            return Assistant::fromArray((array)$assistant);
        }, $assistants);
    }
}
