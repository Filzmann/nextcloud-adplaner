(function() {
    'use strict';

    /** Zweck: Rendert und bindet Team-, Monats- und Tabnavigation der Assistenzplanung. */
    class PlanChrome {
        constructor(options) {
            this.byId = options.byId;
            this.esc = options.esc;
            this.onTeamChange = options.onTeamChange;
            this.onMonthChange = options.onMonthChange;
            this.onViewChange = options.onViewChange;
            this.teamSelect = this.byId('team-select');
            this.monthInput = this.byId('month-input');
            this.tabs = document.querySelector('.adp-tabs');
            this.teamSelect.addEventListener('change', event => this.onTeamChange(event.target.value));
            this.monthInput.addEventListener('change', event => this.onMonthChange(event.target.value));
            this.tabs.addEventListener('click', event => {
                const button = event.target instanceof Element ? event.target.closest('button[data-view]') : null;
                if (button) this.onViewChange(button.dataset.view || 'month');
            });
        }

        render(state) {
            this.teamSelect.innerHTML = state.teams.map(team => {
                const selected = team.code === state.selectedTeamCode ? ' selected' : '';
                return `<option value="${this.esc(team.code)}"${selected}>${this.esc(team.displayName || team.code)}</option>`;
            }).join('');
            this.teamSelect.disabled = state.teams.length === 0;
            this.monthInput.value = state.month;
            document.querySelectorAll('.adp-tab').forEach(button => button.classList.toggle('is-active', button.dataset.view === state.activeView));
        }
    }

    window.ADPlaner = window.ADPlaner || {};
    window.ADPlaner.PlanChrome = PlanChrome;
})();
