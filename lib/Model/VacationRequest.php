<?php

declare(strict_types=1);

namespace OCA\AdPlaner\Model;

use OCA\LocalBase\Model\ModelApiTrait;

class VacationRequest {
    use ModelApiTrait;

    public function __construct(
        public int $id,
        public string $assistantUid,
        public string $dateFrom,
        public string $dateTo,
        public string $status,
        public string $note
    ) {
    }

    protected static function fromArray(array $data): self {
        return new self(
            (int)($data['id'] ?? 0),
            (string)($data['assistantUid'] ?? $data['assistant_uid'] ?? ''),
            (string)($data['dateFrom'] ?? $data['date_from'] ?? ''),
            (string)($data['dateTo'] ?? $data['date_to'] ?? ''),
            (string)($data['status'] ?? ''),
            (string)($data['note'] ?? '')
        );
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'assistantUid' => $this->assistantUid,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'status' => $this->status,
            'note' => $this->note,
        ];
    }
}
