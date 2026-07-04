<?php

declare(strict_types=1);

namespace OCA\AdPlaner\Model;

class DayNote {
    use ModelApiTrait;

    public function __construct(
        public string $teamCode,
        public string $workDate,
        public string $note,
        public string $updatedByUid = '',
        public string $updatedAt = ''
    ) {
    }

    public static function fromArray(array $data): self {
        return new self(
            (string)($data['teamCode'] ?? $data['team_code'] ?? ''),
            (string)($data['workDate'] ?? $data['work_date'] ?? ''),
            (string)($data['note'] ?? ''),
            (string)($data['updatedByUid'] ?? $data['updated_by_uid'] ?? ''),
            (string)($data['updatedAt'] ?? $data['updated_at'] ?? '')
        );
    }

    public static function fromRow(array $row): self {
        return self::fromArray($row);
    }

    public function toApiArray(): array {
        return [
            'teamCode' => $this->teamCode,
            'workDate' => $this->workDate,
            'note' => $this->note,
        ];
    }
}
