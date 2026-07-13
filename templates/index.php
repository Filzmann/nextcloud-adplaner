<?php
script('localbase', 'api/api-client');
script('adplaner', 'modules/api');
script('localbase', 'ui/ui');
script('adplaner', 'modules/ui');
script('localbase', 'models/model');
script('adplaner', 'models/assistant');
script('adplaner', 'models/shift-candidate');
script('adplaner', 'models/shift-definition');
script('adplaner', 'models/shift-slot');
script('adplaner', 'models/team-settings');
script('adplaner', 'models/team');
script('adplaner', 'models/day-note');
script('localbase', 'repositories/repository');
script('adplaner', 'repositories/plan-repository');
script('adplaner', 'components/candidate-chip');
script('adplaner', 'components/day-note-control');
script('adplaner', 'components/assignment-control');
script('adplaner', 'components/shift-settings-list');
script('adplaner', 'components/month-plan');
script('adplaner', 'components/settings-panel');
script('adplaner', 'main');
style('adplaner', 'style');
?>

<div id="adplaner-app">
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
