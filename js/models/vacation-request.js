(function() {
    const { Model } = window.LocalBase.models;
    window.ADPlaner = window.ADPlaner || {};
    window.ADPlaner.models = window.ADPlaner.models || {};

    class VacationRequest extends Model {
        constructor(data = {}) {
            super();
            this.id = data.id ?? 0;
            this.assistantUid = data.assistantUid || data.assistant_uid || '';
            this.dateFrom = data.dateFrom || data.date_from || '';
            this.dateTo = data.dateTo || data.date_to || '';
            this.status = data.status || '';
            this.note = data.note || '';
        }

        toArray() {
            return {
                id: this.id,
                assistantUid: this.assistantUid,
                dateFrom: this.dateFrom,
                dateTo: this.dateTo,
                status: this.status,
                note: this.note
            };
        }
    }

    window.ADPlaner.models.VacationRequest = VacationRequest;
})();
