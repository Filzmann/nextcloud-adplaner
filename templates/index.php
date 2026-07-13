<?php
\OCP\Util::addScript('localbase', 'api/api-client');
\OCP\Util::addScript('adplaner', 'modules/api');
\OCP\Util::addScript('localbase', 'ui/ui');
\OCP\Util::addScript('orgsuite', 'suite-navigation');
\OCP\Util::addScript('adplaner', 'modules/ui');
\OCP\Util::addScript('localbase', 'models/model');
\OCP\Util::addScript('adplaner', 'models/assistant');
\OCP\Util::addScript('adplaner', 'models/shift-candidate');
\OCP\Util::addScript('adplaner', 'models/shift-definition');
\OCP\Util::addScript('adplaner', 'models/shift-slot');
\OCP\Util::addScript('adplaner', 'models/team-settings');
\OCP\Util::addScript('adplaner', 'models/team');
\OCP\Util::addScript('adplaner', 'models/day-note');
\OCP\Util::addScript('localbase', 'repositories/repository');
\OCP\Util::addScript('adplaner', 'repositories/plan-repository');
\OCP\Util::addScript('adplaner', 'components/candidate-chip');
\OCP\Util::addScript('adplaner', 'components/day-note-control');
\OCP\Util::addScript('adplaner', 'components/assignment-control');
\OCP\Util::addScript('adplaner', 'components/shift-settings-list');
\OCP\Util::addScript('adplaner', 'components/month-plan');
\OCP\Util::addScript('adplaner', 'components/settings-panel');
\OCP\Util::addScript('adplaner', 'components/plan-chrome');
\OCP\Util::addScript('adplaner', 'components/plan-panel');
\OCP\Util::addScript('adplaner', 'modules/plan-app');
\OCP\Util::addScript('adplaner', 'main');
\OCP\Util::addStyle('adplaner', 'style');
\OCP\Util::addStyle('orgsuite', 'suite-navigation');
?>

<div id="adplaner-app">
    <div class="orgsuite-host" data-orgsuite data-suite="ad" data-current-app="adplaner"></div>
    <header class="adp-head">
        <h1>Assistenzplanung</h1>
        <div class="adp-controls">
            <label>
                Assistenznehmer
                <select id="team-select"></select>
            </label>
            <label>
                Monat
                <input id="month-input" type="month">
            </label>
        </div>
    </header>

    <nav class="adp-tabs" aria-label="Planbereiche">
        <button type="button" class="adp-tab is-active" data-view="month">Wunschplan</button>
        <button type="button" class="adp-tab" data-view="settings">Einstellungen</button>
    </nav>

    <div id="adp-notice" class="adp-notice" hidden></div>
    <main id="adp-panel" class="adp-panel"></main>
</div>
