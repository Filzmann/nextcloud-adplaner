<?php

declare(strict_types=1);

namespace OCA\AdPlaner\Model;

class Team {
    use ModelApiTrait;

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

    public static function fromArray(array $data): self {
        return new self(
            (string)($data['code'] ?? ''),
            (string)($data['groupName'] ?? $data['group_name'] ?? ''),
            (string)($data['vacationGroupName'] ?? $data['vacation_group_name'] ?? ''),
            (string)($data['displayName'] ?? $data['display_name'] ?? ''),
            is_array($data['assistants'] ?? null) ? $data['assistants'] : [],
            is_array($data['vacationAssistants'] ?? $data['vacation_assistants'] ?? null)
                ? ($data['vacationAssistants'] ?? $data['vacation_assistants'])
                : [],
            (bool)($data['isEb'] ?? $data['is_eb'] ?? $data['canCoordinate'] ?? false),
            is_array($data['settings'] ?? null) ? $data['settings'] : []
        );
    }

    public function toArray(): array {
        return [
            'code' => $this->code,
            'groupName' => $this->groupName,
            'vacationGroupName' => $this->vacationGroupName,
            'displayName' => $this->displayName,
            'assistants' => $this->assistantsArray(),
            'vacationAssistants' => $this->vacationAssistantsArray(),
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

    public function assistantsArray(): array {
        return array_map(static fn(Assistant $assistant): array => $assistant->toArray(), $this->assistants);
    }

    public function vacationAssistantsArray(): array {
        return array_map(static fn(Assistant $assistant): array => $assistant->toArray(), $this->vacationAssistants);
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
