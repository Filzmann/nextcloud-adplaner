(function() {
    const { Model } = window.LocalBase.models;
    window.ADPlaner = window.ADPlaner || {};
    window.ADPlaner.models = window.ADPlaner.models || {};

    class TeamSettings extends Model {
        constructor(data = {}) {
            super();
            this.teamCode = data.teamCode || data.team_code || '';
            this.displayName = data.displayName || data.display_name || '';
            this.config = data.config || {};
        }

        toArray() {
            return {
                teamCode: this.teamCode,
                displayName: this.displayName,
                config: this.config
            };
        }
    }

    window.ADPlaner.models.TeamSettings = TeamSettings;
})();
