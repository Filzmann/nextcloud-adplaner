<?php

declare(strict_types=1);

namespace OCA\AdPlaner\Service;

use OCA\AdPlaner\AppInfo\Application;
use OCA\LocalBase\Service\AppLogger;
use Throwable;

class AdPlanerLogger {
    public function __construct(
        private AppLogger $logger
    ) {
    }

    public function error(string $action, Throwable $exception, array $context = []): void {
        $this->logger->error(Application::APP_ID, 'AdPlaner', $action, $exception, $context);
    }
}
