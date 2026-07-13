<?php

declare(strict_types=1);

namespace OCA\AdPlaner\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/** Zweck: Entfernt die durch AD Urlaub ersetzte parallele Urlaubstabelle. */
final class Version000002Date202607130001 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();
        if ($schema->hasTable('adp_vacation_requests')) $schema->dropTable('adp_vacation_requests');
        return $schema;
    }
}
