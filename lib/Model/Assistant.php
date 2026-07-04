<?php

declare(strict_types=1);

namespace OCA\AdPlaner\Model;

class Assistant {
    use ModelApiTrait;

    public function __construct(
        public string $uid,
        public string $displayName,
        public bool $isEb = false,
        public bool $canReceiveShifts = true
    ) {
    }

    public static function fromArray(array $assistant): self {
        return new self(
            (string)($assistant['uid'] ?? ''),
            (string)($assistant['displayName'] ?? ($assistant['uid'] ?? '')),
            (bool)($assistant['isEb'] ?? false),
            (bool)($assistant['canReceiveShifts'] ?? true)
        );
    }

    public static function fromUser($user, bool $isEb): self {
        $uid = $user->getUID();

        return new self(
            $uid,
            $user->getDisplayName() ?: $uid,
            $isEb,
            !$isEb
        );
    }

    public function toApiArray(): array {
        return [
            'uid' => $this->uid,
            'displayName' => $this->displayName,
            'isEb' => $this->isEb,
            'canReceiveShifts' => $this->canReceiveShifts,
        ];
    }
}
