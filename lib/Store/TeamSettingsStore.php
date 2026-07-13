<?php

declare(strict_types=1);

namespace OCA\AdPlaner\Store;

use OCA\AdPlaner\Model\TeamSettings;
use OCA\AdPlaner\Repository\TeamSettingsRepository;
use OCA\AdPlaner\Service\ShiftConfigService;
use OCA\LocalBase\Organization\AdOrganizationDefinition;
use OCA\LocalBase\Organization\AdOrganizationSettingsService;

class TeamSettingsStore {
    public function __construct(
        private TeamSettingsRepository $repository,
        private ShiftConfigService $shiftConfig,
        private ?AdOrganizationSettingsService $organization = null,
    ) {
    }

    public function forTeam(string $teamCode): TeamSettings {
        $row = $this->repository->findByCode($teamCode);
        if ($row === null) {
            return new TeamSettings($teamCode, $this->defaultDisplayName($teamCode), $this->shiftConfig->defaults());
        }

        $decoded = json_decode((string)($row['settings_json'] ?? ''), true);
        if (!is_array($decoded)) {
            $decoded = [];
        }

        return new TeamSettings(
            $teamCode,
            (string)($row['display_name'] ?: $this->defaultDisplayName($teamCode)),
            $this->shiftConfig->normalize($decoded)
        );
    }

    public function save(string $teamCode, string $displayName, array $config): TeamSettings {
        $displayName = trim($displayName);
        if ($displayName === '') {
            $displayName = $this->defaultDisplayName($teamCode);
        }

        $normalized = $this->shiftConfig->normalize($config);
        $this->repository->save($teamCode, $displayName, $normalized);

        return new TeamSettings($teamCode, $displayName, $normalized);
    }

    private function defaultDisplayName(string $teamCode): string {
        $definition = $this->organization?->definition() ?? AdOrganizationDefinition::defaults();
        return $definition->teamLabelPrefix() . ' ' . $teamCode;
    }
}
