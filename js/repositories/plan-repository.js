(function() {
    const { ShiftDefinition, ShiftSlot, Team, VacationRequest } = window.ADPlaner.models;

    class PlanRepository {
        constructor(api) {
            this.api = api;
        }

        async state() {
            const data = await this.api.request('/api/state');

            return {
                ...data,
                teams: Team.get_all(data.teams || [])
            };
        }

        async monthPlan(teamCode, month) {
            return this.hydrateMonthPlan(await this.api.request(this.teamPath(teamCode) + '/months/' + this.encode(month)));
        }

        async vacationPlan(teamCode, year) {
            return this.hydrateVacationPlan(await this.api.request(this.teamPath(teamCode) + '/vacations/' + this.encode(year)));
        }

        addSelf(teamCode, month, slotId) {
            return this.post(this.teamPath(teamCode) + '/months/' + this.encode(month) + '/slots/' + this.encode(slotId) + '/candidates');
        }

        addSelected(teamCode, month, slotId, targetUid) {
            return this.post(this.teamPath(teamCode) + '/months/' + this.encode(month) + '/slots/' + this.encode(slotId) + '/candidates', {
                targetUid
            });
        }

        removeCandidate(teamCode, month, slotId, targetUid) {
            return this.post(this.teamPath(teamCode) + '/months/' + this.encode(month) + '/slots/' + this.encode(slotId) + '/candidates/remove', {
                targetUid
            });
        }

        saveDayNote(teamCode, month, date, note) {
            return this.post(this.teamPath(teamCode) + '/months/' + this.encode(month) + '/days/' + this.encode(date) + '/note', {
                note
            });
        }

        createVacation(dateFrom, dateTo, note) {
            return this.post('/api/vacations', { dateFrom, dateTo, note });
        }

        deleteVacation(requestId) {
            return this.post('/api/vacations/' + this.encode(requestId) + '/delete');
        }

        setVacationStatus(teamCode, year, assistantUid, date, status) {
            return this.post(this.teamPath(teamCode) + '/vacations/' + this.encode(year) + '/status', {
                assistantUid,
                date,
                status
            });
        }

        saveSettings(teamCode, displayName, meetingDay, shifts) {
            return this.post(this.teamPath(teamCode) + '/settings', {
                displayName,
                meetingDay,
                shiftsJson: JSON.stringify(shifts)
            });
        }

        post(url, body = {}) {
            return this.api.request(url, {
                method: 'POST',
                body: JSON.stringify(body)
            });
        }

        teamPath(teamCode) {
            return '/api/teams/' + this.encode(teamCode);
        }

        hydrateMonthPlan(plan) {
            if (!plan) {
                return null;
            }

            return {
                ...plan,
                team: Team.get(plan.team),
                segments: ShiftDefinition.get_all(plan.segments || []),
                days: (plan.days || []).map(day => ({
                    ...day,
                    slots: ShiftSlot.get_all(day.slots || [])
                }))
            };
        }

        hydrateVacationPlan(plan) {
            if (!plan) {
                return null;
            }

            return {
                ...plan,
                team: Team.get(plan.team),
                requests: VacationRequest.get_all(plan.requests || [])
            };
        }

        encode(value) {
            return this.api.encode ? this.api.encode(value) : encodeURIComponent(String(value));
        }
    }

    window.ADPlaner = window.ADPlaner || {};
    window.ADPlaner.repositories = window.ADPlaner.repositories || {};
    window.ADPlaner.repositories.PlanRepository = PlanRepository;
})();
