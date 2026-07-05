<?php

declare(strict_types=1);

namespace {
    if (!interface_exists(\OCP\IGroupManager::class)) {
        eval('namespace OCP; interface IGroupManager { public function get($gid); public function getUserGroupIds($user); }');
    }
    if (!interface_exists(\OCP\IUserSession::class)) {
        eval('namespace OCP; interface IUserSession { public function getUser(); }');
    }

    require __DIR__ . '/helpers.php';
    require __DIR__ . '/../../../localbase/lib/Model/ModelApiTrait.php';
    require __DIR__ . '/../../lib/Model/Assistant.php';
    require __DIR__ . '/../../lib/Model/Team.php';
    require __DIR__ . '/../../lib/Model/TeamSettings.php';
    require __DIR__ . '/../../lib/Service/TeamSettingsService.php';
    require __DIR__ . '/../../lib/Service/TeamAccessService.php';

    use OCA\AdPlaner\Model\TeamSettings;
    use OCA\AdPlaner\Service\TeamAccessService;
    use OCA\AdPlaner\Service\TeamSettingsService;
    use OCP\IGroupManager;
    use OCP\IUserSession;
    use function OCA\AdPlaner\Tests\assertDomainException;
    use function OCA\AdPlaner\Tests\assertSameValue;

    $alice = new class('alice', 'Alice Assistenz', 'alice@example.invalid') {
        public function __construct(
            private string $uid,
            private string $displayName,
            private string $email
        ) {
        }

        public function getUID(): string {
            return $this->uid;
        }

        public function getDisplayName(): string {
            return $this->displayName;
        }

        public function getEMailAddress(): string {
            return $this->email;
        }
    };
    $bob = new class('bob', 'Bob EB', 'bob@example.invalid') {
        public function __construct(
            private string $uid,
            private string $displayName,
            private string $email
        ) {
        }

        public function getUID(): string {
            return $this->uid;
        }

        public function getDisplayName(): string {
            return $this->displayName;
        }

        public function getEMailAddress(): string {
            return $this->email;
        }
    };
    $zoe = new class('zoe', 'Zoe Urlaub', '') {
        public function __construct(
            private string $uid,
            private string $displayName,
            private string $email
        ) {
        }

        public function getUID(): string {
            return $this->uid;
        }

        public function getDisplayName(): string {
            return $this->displayName;
        }

        public function getEMailAddress(): string {
            return $this->email;
        }
    };

    $teamGroup = new class([$alice, $bob]) {
        public function __construct(private array $users) {
        }

        public function getUsers(): array {
            return $this->users;
        }
    };
    $vacationGroup = new class([$zoe, $alice]) {
        public function __construct(private array $users) {
        }

        public function getUsers(): array {
            return $this->users;
        }
    };

    $groupManager = new class($teamGroup, $vacationGroup, $bob) implements IGroupManager {
        public function __construct(
            private object $teamGroup,
            private object $vacationGroup,
            private object $currentUser
        ) {
        }

        public function get($gid): ?object {
            return match ((string)$gid) {
                'ad-ASN-TeamB' => $this->teamGroup,
                'ad-ASN-TeamB-Urlaub' => $this->vacationGroup,
                default => null,
            };
        }

        public function getUserGroupIds($user): array {
            $uid = $user->getUID();
            if ($uid === 'bob') {
                return ['ad-ASN-TeamB', 'ad-EB-Team'];
            }
            if ($uid === 'alice') {
                return ['ad-ASN-TeamB', 'ad-ASN-Zulu', 'ignored', 'ad-ASN-TeamB'];
            }

            return [];
        }
    };

    $session = new class($bob) implements IUserSession {
        public function __construct(private ?object $user) {
        }

        public function getUser(): ?object {
            return $this->user;
        }

        public function setUser(?object $user): void {
            $this->user = $user;
        }
    };

    $settings = new class extends TeamSettingsService {
        public function __construct() {
        }

        public function settingsForTeam(string $teamCode): TeamSettings {
            return new TeamSettings($teamCode, 'Team ' . $teamCode, ['meetingDay' => '2026-07-15']);
        }
    };

    $service = new TeamAccessService($groupManager, $session, $settings);

    assertSameValue('bob', $service->currentUserId(), 'Current user id should come from the user session.');
    assertSameValue('TeamB', $service->normalizeTeamCode(' TeamB '), 'Team codes should be trimmed.');
    assertSameValue(true, $service->currentUserIsEbForTeam('TeamB'), 'EB users should be detected through ad-EB groups.');

    $team = $service->teamForCode('TeamB');
    assertSameValue('Team B', $team->displayName, 'Team display name should come from team settings.');
    assertSameValue('ad-ASN-TeamB', $team->groupName, 'Team group name should follow the AD schema.');
    assertSameValue('ad-ASN-TeamB-Urlaub', $team->vacationGroupName, 'Vacation group name should follow the AD schema.');
    assertSameValue(['Alice Assistenz', 'Bob EB'], array_map(static fn($assistant): string => $assistant->displayName, $team->assistants()), 'Assistants should be sorted by display name.');
    assertSameValue(['Alice Assistenz', 'Zoe Urlaub'], array_map(static fn($assistant): string => $assistant->displayName, $team->vacationAssistants()), 'Vacation assistants should use the optional vacation group when present.');
    assertSameValue(false, $team->assistantByUid('bob')->canReceiveShifts, 'EB users should not receive shifts.');
    assertSameValue(['alice' => 'Alice Assistenz', 'bob' => 'Bob EB'], $service->assistantLabelMap($team->assistants()), 'Assistant label maps should expose display names by uid.');

    $service->assertCanCoordinate('TeamB');
    $service->assertAssistantInTeam('TeamB', 'alice');
    assertDomainException(
        static fn() => $service->assertAssistantInTeam('TeamB', 'zoe'),
        'Vacation-only users should not count as team assistants.'
    );
    assertDomainException(
        static fn() => $service->assertTeamAccess('Missing'),
        'Missing teams should not be accessible.'
    );

    $session->setUser($alice);
    assertSameValue(['TeamB'], array_map(static fn($team): string => $team->code, $service->teamsForCurrentUser()), 'Teams for current user should derive unique ASN group codes and skip missing groups.');
    assertSameValue(false, $service->currentUserIsEbForTeam('TeamB'), 'Non-EB team users should not coordinate.');
    assertDomainException(
        static fn() => $service->assertCanCoordinate('TeamB'),
        'Non-EB users should not coordinate team settings.'
    );

    $session->setUser(null);
    assertSameValue([], $service->teamsForCurrentUser(), 'Anonymous sessions should not expose teams.');

    echo 'TeamAccessService smoke tests passed' . PHP_EOL;
}
