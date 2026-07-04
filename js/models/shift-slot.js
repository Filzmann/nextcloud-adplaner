(function() {
    const { Model } = window.LocalBase.models;
    const { ShiftCandidate } = window.ADPlaner.models;

    class ShiftSlot extends Model {
        constructor(data = {}) {
            super();
            this.id = data.id ?? 0;
            this.teamCode = data.teamCode || data.team_code || '';
            this.planMonth = data.planMonth || data.plan_month || '';
            this.workDate = data.workDate || data.work_date || '';
            this.segmentKey = data.segmentKey || data.segment_key || '';
            this.label = data.label || '';
            this.startsAt = data.startsAt || data.starts_at || '';
            this.endsAt = data.endsAt || data.ends_at || '';
            this.enabled = data.enabled ?? true;
            this.candidates = ShiftCandidate.get_all(data.candidates || []);
        }

        toArray() {
            return {
                id: this.id,
                teamCode: this.teamCode,
                planMonth: this.planMonth,
                workDate: this.workDate,
                segmentKey: this.segmentKey,
                label: this.label,
                startsAt: this.startsAt,
                endsAt: this.endsAt,
                enabled: this.enabled,
                candidates: this.candidates.map(candidate => candidate.toArray())
            };
        }
    }

    window.ADPlaner.models.ShiftSlot = ShiftSlot;
})();
