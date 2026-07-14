<?php

declare(strict_types=1);

$template = file_get_contents(__DIR__ . '/../../templates/index.php');
$css = file_get_contents(__DIR__ . '/../../css/style.css');
$info = file_get_contents(__DIR__ . '/../../appinfo/info.xml');
if ($template === false || $css === false || $info === false) throw new RuntimeException('AdPlaner-Vertragsdatei konnte nicht gelesen werden.');
if (!str_contains($info, '<app>orgsuite</app>') || str_contains($info, '<navigations>')) throw new RuntimeException('OrgSuite-Appvertrag fehlt.');
foreach (["\\OCP\\Util::addScript('orgsuite', 'suite-navigation')", "\\OCP\\Util::addStyle('orgsuite', 'suite-navigation')", 'data-orgsuite data-suite="ad" data-current-app="adplaner"'] as $contract) {
    if (!str_contains($template, $contract)) throw new RuntimeException("Suite-Navigationsvertrag fehlt: {$contract}");
}
foreach (['role="tablist"', 'role="tab"', 'aria-controls="adp-panel"', 'role="tabpanel"', 'aria-labelledby="adp-tab-month"'] as $contract) {
    if (!str_contains($template, $contract)) throw new RuntimeException("Semantischer Tabvertrag fehlt: {$contract}");
}
if (preg_match('/^\\s*(?:script|style)\\s*\\(/m', $template) === 1) throw new RuntimeException('Veralteter globaler Templatehelfer gefunden.');
foreach (['height: 100%', 'min-height: 0', 'overflow-y: auto', 'overflow-x: hidden', 'background: var(--color-main-background)'] as $contract) {
    if (!str_contains($css, $contract)) throw new RuntimeException("Scroll-/Hintergrundvertrag fehlt: {$contract}");
}
if (str_contains($css, '#content')) throw new RuntimeException('AdPlaner darf den globalen Nextcloud-Content nicht ueberschreiben.');
if (!str_contains($css, '.adp-tab:focus-visible')) throw new RuntimeException('Sichtbarer Tastaturfokus der Tabs fehlt.');
echo "AdPlaner suite navigation smoke test passed\n";
