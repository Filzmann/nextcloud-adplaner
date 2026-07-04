(function() {
    const { Model } = window.LocalBase.models;
    const { Assistant } = window.ADPlaner.models;

    class Team extends Model {
        constructor(data = {}) {
            super();
            this.code = data.code || '';
            this.groupName = data.groupName || data.group_name || '';
            this.vacationGroupName = data.vacationGroupName || data.vacation_group_name || '';
            this.displayName = data.displayName || data.display_name || this.code;
            this.assistants = Assistant.get_all(data.assistants || []);
            this.vacationAssistants = Assistant.get_all(data.vacationAssistants || data.vacation_assistants || []);
            this.isEb = !!(data.isEb ?? data.is_eb ?? data.canCoordinate ?? false);
            this.canCoordinate = !!(data.canCoordinate ?? this.isEb);
            this.settings = data.settings || {};
        }

        toArray() {
            return {
                code: this.code,
                groupName: this.groupName,
                vacationGroupName: this.vacationGroupName,
                displayName: this.displayName,
                assistants: this.assistants.map(assistant => assistant.toArray()),
                vacationAssistants: this.vacationAssistants.map(assistant => assistant.toArray()),
                isEb: this.isEb,
                canCoordinate: this.canCoordinate,
                settings: this.settings
            };
        }
    }

    window.ADPlaner.models.Team = Team;
})();
