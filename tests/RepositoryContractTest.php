<?php

declare(strict_types=1);

$shiftPlan = file_get_contents(__DIR__ . '/../lib/Repository/ShiftPlanRepository.php');
$teamSettings = file_get_contents(__DIR__ . '/../lib/Repository/TeamSettingsRepository.php');
if ($shiftPlan === false || $teamSettings === false) throw new RuntimeException('Planungs-Repository konnte nicht gelesen werden.');
if (!str_contains($shiftPlan, '$qb->getLastInsertId()') || str_contains($shiftPlan, 'db->lastInsertId')) {
    throw new RuntimeException('Moderner QueryBuilder-ID-Vertrag fehlt im Schichtplan-Repository.');
}
foreach ([$shiftPlan, $teamSettings] as $source) {
    if (!str_contains($source, 'IQueryBuilder::PARAM_DATETIME_IMMUTABLE') || str_contains($source, 'IQueryBuilder::PARAM_DATE)')) {
        throw new RuntimeException('Repository verwendet nicht durchgängig den unveränderlichen DateTime-Vertrag.');
    }
}

echo "AdPlaner repository contract test passed\n";
