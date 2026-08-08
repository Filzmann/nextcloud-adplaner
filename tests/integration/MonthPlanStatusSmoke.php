<?php

declare(strict_types=1);

require_once dirname(__DIR__, 4) . '/lib/base.php';

use OCA\AdPlaner\Repository\ShiftPlanRepository;
use OCP\IDBConnection;

$teamCode = 'RC6STATUS';
$month = '2099-01';
$db = \OC::$server->get(IDBConnection::class);
$repository = \OC::$server->get(ShiftPlanRepository::class);

$cleanup = static function () use ($db, $teamCode, $month): void {
    $qb = $db->getQueryBuilder();
    $qb->delete('adp_month_plans')
        ->where($qb->expr()->eq('team_code', $qb->createNamedParameter($teamCode)))
        ->andWhere($qb->expr()->eq('plan_month', $qb->createNamedParameter($month)));
    $qb->executeStatement();
};

$cleanup();
try {
    if ($repository->monthStatus($teamCode, $month) !== null) {
        throw new RuntimeException('Ein neuer Monatsplan besitzt unerwartet einen persistierten Status.');
    }
    if (!$repository->transitionMonthStatus($teamCode, $month, 'draft', 'planned', 'rc6-test')) {
        throw new RuntimeException('Der erste Statusübergang wurde nicht atomar persistiert.');
    }
    if ($repository->transitionMonthStatus($teamCode, $month, 'draft', 'approved', 'rc6-test')) {
        throw new RuntimeException('Ein veralteter Ausgangsstatus konnte den Plan überschreiben.');
    }
    if ($repository->monthStatus($teamCode, $month) !== 'planned') {
        throw new RuntimeException('Der persistierte Status ist nach einem Konflikt nicht erhalten geblieben.');
    }
    if (!$repository->transitionMonthStatus($teamCode, $month, 'planned', 'approved', 'rc6-test')) {
        throw new RuntimeException('Die Genehmigung wurde nicht persistiert.');
    }
} finally {
    $cleanup();
}

echo "AdPlaner real month plan status persistence smoke passed\n";
