<?php

declare(strict_types=1);

namespace OCA\AdPlaner\Controller;

use OCA\AdPlaner\AppInfo\Application;
use OCA\AdPlaner\Service\PlanerDemoPackService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/** Zweck: Startet den Assistenzplaner-Demo-Pack ausschließlich mit Admin- und CSRF-Schutz. */
final class DemoAdminController extends Controller {
    public function __construct(IRequest $request, private IUserSession $session, private IGroupManager $groups, private PlanerDemoPackService $demoPack, private LoggerInterface $logger) { parent::__construct(Application::APP_ID, $request); }
    public function install(): JSONResponse {
        if (!$this->isAdmin()) return new JSONResponse(['error' => 'Keine Berechtigung.'], Http::STATUS_FORBIDDEN);
        try {
            return new JSONResponse(['result' => $this->demoPack->install()]);
        } catch (\Throwable $error) {
            $this->logger->error('Assistenzplaner-Demo-Pack konnte nicht installiert werden.', ['exception' => $error]);
            return new JSONResponse(['error' => $error->getMessage()], Http::STATUS_BAD_REQUEST);
        }
    }
    private function isAdmin(): bool {
        $user = $this->session->getUser();
        return $user !== null && $this->groups->isAdmin($user->getUID());
    }
}
