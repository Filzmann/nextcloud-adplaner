<?php

declare(strict_types=1);

$source = file_get_contents(__DIR__ . '/../lib/Repository/ShiftPlanRepository.php');
if ($source === false) throw new RuntimeException('Schichtplan-Repository konnte nicht gelesen werden.');
if (!str_contains($source, '$qb->getLastInsertId()') || str_contains($source, 'db->lastInsertId')) {
    throw new RuntimeException('Moderner QueryBuilder-ID-Vertrag fehlt im Schichtplan-Repository.');
}

echo "AdPlaner repository contract test passed\n";
