(function() {
    'use strict';

    /**
     * Zweck: Orchestriert Ladezustand und fachliche UI-Workflows der Assistenzplanung.
     * Zusammenspiel: main.js -> PlanApp -> PlanChrome/PlanPanel/PlanRepository.
     */
    class PlanApp {
        constructor(options) {
            this.repository = options.repository;
            this.showNotice = options.showNotice;
            this.showError = options.showError;
            this.state = { currentUser: null, teams: [], selectedTeamCode: '', month: '', activeView: 'month', monthPlan: null, organization: {}, loading: false };
            this.chrome = new options.PlanChrome({
                byId: options.byId, esc: options.esc,
                onTeamChange: value => this.selectTeam(value),
                onMonthChange: value => this.selectMonth(value),
                onViewChange: value => this.selectView(value),
            });
            this.panel = new options.PlanPanel({
                byId: options.byId,
                renderMonth: options.renderMonth,
                renderSettings: options.renderSettings,
                addShiftRow: options.addShiftRow,
                removeShiftRow: options.removeShiftRow,
                collectShifts: options.collectShifts,
                openAssignmentPicker: options.openAssignmentPicker,
                onAction: button => this.handleAction(button),
                onSaveSettings: values => this.saveSettings(values),
            });
        }

        selectedTeam() { return this.state.teams.find(team => team.code === this.state.selectedTeamCode) || null; }
        render() { this.chrome.render(this.state); this.panel.render(this.state, this.selectedTeam()); }

        async init() {
            try {
                const data = await this.repository.state();
                this.applyState(data, true);
                this.render();
                if (this.state.selectedTeamCode) await this.reloadActive();
                else {
                    this.showNotice(`Keine Assistenzteam-Gruppe mit dem konfigurierten Präfix ${this.state.organization.teamGroupPrefix || ''} gefunden.`);
                    this.render();
                }
            } catch (error) { this.showError(error, 'Dienstplanung konnte nicht geladen werden.'); }
        }

        applyState(data, initial = false) {
            this.state.currentUser = data.currentUser || this.state.currentUser;
            this.state.teams = data.teams || [];
            this.state.organization = data.organization || this.state.organization;
            if (initial) this.state.month = data.defaultMonth || new Date().toISOString().slice(0, 7);
            if (!this.state.teams.some(team => team.code === this.state.selectedTeamCode)) this.state.selectedTeamCode = this.state.teams[0]?.code || '';
        }

        async reloadActive() {
            this.state.loading = true; this.render(); this.showNotice('');
            try {
                if (this.state.activeView === 'settings') this.applyState(await this.repository.state());
                else await this.loadMonth();
            } catch (error) { this.showError(error, 'Ansicht konnte nicht geladen werden.'); }
            finally { this.state.loading = false; this.render(); }
        }

        async loadMonth() {
            this.state.monthPlan = await this.repository.monthPlan(this.state.selectedTeamCode, this.state.month);
            const updatedTeam = this.state.monthPlan.team;
            this.state.teams = this.state.teams.map(team => team.code === updatedTeam.code ? updatedTeam : team);
        }

        async selectTeam(value) { this.state.selectedTeamCode = value; this.state.monthPlan = null; await this.reloadActive(); }
        async selectMonth(value) { this.state.month = value; if (this.state.activeView === 'month') await this.reloadActive(); }
        async selectView(value) { this.state.activeView = value; this.render(); await this.reloadActive(); }

        async handleAction(button) {
            try {
                const action = button.dataset.action;
                const team = this.state.selectedTeamCode; const month = this.state.month; const slot = button.dataset.slotId;
                if (action === 'add-self') await this.repository.addSelf(team, month, slot);
                else if (action === 'add-selected') {
                    const select = this.panel.panel.querySelector(`select[data-add-select="${CSS.escape(slot)}"]`);
                    if (!select || select.disabled || !select.value) return;
                    await this.repository.addSelected(team, month, slot, select.value);
                } else if (action === 'remove-candidate') await this.repository.removeCandidate(team, month, slot, button.dataset.targetUid || '');
                else if (action === 'transition-status') await this.repository.transitionStatus(team, month, button.dataset.targetStatus || '');
                else if (action === 'save-note') {
                    const textarea = this.panel.panel.querySelector(`textarea[data-note-date="${CSS.escape(button.dataset.date)}"]`);
                    await this.repository.saveDayNote(team, month, button.dataset.date, textarea ? textarea.value : '');
                } else return;
                await this.loadMonth();
            } catch (error) { this.showError(error, 'Aktion konnte nicht ausgeführt werden.'); }
            finally { this.panel.render(this.state, this.selectedTeam()); }
        }

        async saveSettings(values) {
            try {
                if (values.shifts.length === 0) throw new Error('Mindestens eine Schicht muss konfiguriert sein.');
                await this.repository.saveSettings(this.state.selectedTeamCode, values.displayName, values.meetingDay, values.shifts);
                this.applyState(await this.repository.state());
                this.state.activeView = 'month';
                await this.loadMonth();
                this.render();
            } catch (error) { this.showError(error, 'Einstellungen konnten nicht gespeichert werden.'); }
        }
    }

    window.ADPlaner = window.ADPlaner || {};
    window.ADPlaner.PlanApp = PlanApp;
})();
