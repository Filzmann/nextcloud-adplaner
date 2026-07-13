(function() {
    const { byId, esc, showNotice, showError } = window.ADPlaner.ui;
    const { render: renderMonthPlan } = window.ADPlaner.monthPlan;
    const { render: renderSettingsPanel } = window.ADPlaner.settingsPanel;
    const { PlanRepository } = window.ADPlaner.repositories;
    const {
        addRow: addShiftRow,
        removeRow: removeShiftRow,
        collect: collectShifts
    } = window.ADPlaner.shiftSettingsList;
    const { open: openAssignmentPicker } = window.ADPlaner.assignmentControl;
    const repository = new PlanRepository(window.ADPlaner.api);

    const state = {
        currentUser: null,
        teams: [],
        selectedTeamCode: '',
        month: '',
        activeView: 'month',
        monthPlan: null,
        organization: {},
        loading: false
    };

    function selectedTeam() {
        return state.teams.find(team => team.code === state.selectedTeamCode) || null;
    }

    async function init() {
        try {
            const data = await repository.state();
            state.currentUser = data.currentUser || null;
            state.teams = data.teams || [];
            state.organization = data.organization || {};
            state.month = data.defaultMonth || new Date().toISOString().slice(0, 7);
            state.selectedTeamCode = state.teams.length ? state.teams[0].code : '';

            renderChrome();
            if (state.selectedTeamCode) {
                await reloadActive();
            } else {
                const prefix = state.organization.teamGroupPrefix || '';
                showNotice(`Keine Assistenzteam-Gruppe mit dem konfigurierten Präfix ${prefix} gefunden.`);
                renderPanel();
            }
        } catch (e) {
            showError(e, 'Dienstplanung konnte nicht geladen werden.');
        }
    }

    function renderChrome() {
        const teamSelect = byId('team-select');
        teamSelect.innerHTML = state.teams.map(team => {
            const selected = team.code === state.selectedTeamCode ? ' selected' : '';
            return `<option value="${esc(team.code)}"${selected}>${esc(team.displayName || team.code)}</option>`;
        }).join('');
        teamSelect.disabled = state.teams.length === 0;

        byId('month-input').value = state.month;
        document.querySelectorAll('.adp-tab').forEach(button => {
            button.classList.toggle('is-active', button.dataset.view === state.activeView);
        });
    }

    function renderPanel() {
        const panel = byId('adp-panel');
        if (state.loading) {
            panel.innerHTML = '<p class="adp-loading">Lade...</p>';
            return;
        }

        if (!state.selectedTeamCode) {
            panel.innerHTML = '<p>Keine Assistenznehmer-Gruppen.</p>';
            return;
        }

        if (state.activeView === 'settings') {
            panel.innerHTML = renderSettingsPanel(selectedTeam());
            bindSettingsForm();
            return;
        }

        panel.innerHTML = renderMonthPlan(state.monthPlan, state.currentUser);
    }

    async function reloadActive() {
        state.loading = true;
        renderPanel();
        showNotice('');

        try {
            if (state.activeView === 'settings') {
                await refreshState();
            } else {
                await loadMonth();
            }
        } catch (e) {
            showError(e, 'Ansicht konnte nicht geladen werden.');
        } finally {
            state.loading = false;
            renderChrome();
            renderPanel();
        }
    }

    async function refreshState() {
        const data = await repository.state();
        state.currentUser = data.currentUser || state.currentUser;
        state.teams = data.teams || [];
        state.organization = data.organization || state.organization;
        if (!state.teams.some(team => team.code === state.selectedTeamCode)) {
            state.selectedTeamCode = state.teams.length ? state.teams[0].code : '';
        }
    }

    async function loadMonth() {
        state.monthPlan = await repository.monthPlan(state.selectedTeamCode, state.month);
        const updatedTeam = state.monthPlan.team;
        state.teams = state.teams.map(team => team.code === updatedTeam.code ? updatedTeam : team);
    }

    async function handlePanelClick(event) {
        const button = event.target instanceof Element ? event.target.closest('button[data-action]') : null;
        if (!button) {
            return;
        }

        const action = button.dataset.action;
        if (action === 'add-shift-row') {
            addShiftRow();
            return;
        }

        if (action === 'remove-shift-row') {
            removeShiftRow(button);
            return;
        }

        if (action === 'open-assignment-picker') {
            openAssignmentPicker(button);
            return;
        }

        try {
            if (action === 'add-self') {
                await repository.addSelf(state.selectedTeamCode, state.month, button.dataset.slotId);
                await loadMonth();
            } else if (action === 'add-selected') {
                const select = byId('adp-panel').querySelector(`select[data-add-select="${CSS.escape(button.dataset.slotId)}"]`);
                if (!select || select.disabled || !select.value) {
                    return;
                }

                await repository.addSelected(state.selectedTeamCode, state.month, button.dataset.slotId, select.value);
                await loadMonth();
            } else if (action === 'remove-candidate') {
                await repository.removeCandidate(state.selectedTeamCode, state.month, button.dataset.slotId, button.dataset.targetUid || '');
                await loadMonth();
            } else if (action === 'save-note') {
                const textarea = byId('adp-panel').querySelector(`textarea[data-note-date="${CSS.escape(button.dataset.date)}"]`);
                await repository.saveDayNote(state.selectedTeamCode, state.month, button.dataset.date, textarea ? textarea.value : '');
                await loadMonth();
            }
        } catch (e) {
            showError(e, 'Aktion konnte nicht ausgeführt werden.');
        } finally {
            renderPanel();
        }
    }

    function bindSettingsForm() {
        const form = byId('settings-form');
        if (!form) {
            return;
        }

        form.addEventListener('submit', async event => {
            event.preventDefault();
            const data = new FormData(form);
            try {
                const shifts = collectShifts(form);
                if (shifts.length === 0) {
                    throw new Error('Mindestens eine Schicht muss konfiguriert sein.');
                }

                await repository.saveSettings(
                    state.selectedTeamCode,
                    data.get('displayName') || '',
                    data.get('meetingDay') || '',
                    shifts
                );
                await refreshState();
                state.activeView = 'month';
                await loadMonth();
                renderChrome();
                renderPanel();
            } catch (e) {
                showError(e, 'Einstellungen konnten nicht gespeichert werden.');
            }
        });
    }

    byId('team-select').addEventListener('change', async event => {
        state.selectedTeamCode = event.target.value;
        state.monthPlan = null;
        await reloadActive();
    });

    byId('month-input').addEventListener('change', async event => {
        state.month = event.target.value;
        if (state.activeView === 'month') {
            await reloadActive();
        }
    });

    document.querySelector('.adp-tabs').addEventListener('click', async event => {
        const button = event.target instanceof Element ? event.target.closest('button[data-view]') : null;
        if (!button) {
            return;
        }

        state.activeView = button.dataset.view || 'month';
        renderChrome();
        await reloadActive();
    });

    byId('adp-panel').addEventListener('click', handlePanelClick);

    init();
})();
