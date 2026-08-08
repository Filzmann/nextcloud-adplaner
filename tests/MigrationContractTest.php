<?php

declare(strict_types=1);

$migration = __DIR__ . '/../lib/Migration/Version000003Date202608080001.php';
if (!is_file($migration)) {
    throw new RuntimeException('Die additive Monatsplanstatus-Migration fehlt.');
}

$source = file_get_contents($migration);
if ($source === false) {
    throw new RuntimeException('Die Monatsplanstatus-Migration konnte nicht gelesen werden.');
}

foreach (['adp_month_plans', 'team_code', 'plan_month', 'status', 'updated_by_uid', 'updated_at', 'adp_month_plan_unique'] as $contract) {
    if (!str_contains($source, $contract)) {
        throw new RuntimeException("Der Monatsplanstatus-Migrationsvertrag fehlt: {$contract}");
    }
}
if (!str_contains($source, "'default' => 'draft'")) {
    throw new RuntimeException('Bestehende Monatspläne erhalten keinen sicheren Entwurfsstatus.');
}

echo "AdPlaner month plan migration contract test passed\n";
