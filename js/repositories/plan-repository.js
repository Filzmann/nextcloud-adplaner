(function() {
    const { Repository } = window.LocalBase.repositories;
    const { ShiftDefinition, ShiftSlot, Team, VacationRequest } = window.ADPlaner.models;

    class PlanRepository extends Repository {
        constructor(api, vacationApi = window.ADPlaner.vacationApi) {
            super(api);
            this.vacationApi = vacationApi || api;
        }

        async state() {
            const data = await this.request('/api/state');

            return {
                ...data,
                teams: Team.get_all(data.teams || [])
            };
        }

        async monthPlan(teamCode, month) {
            return this.hydrateMonthPlan(await this.request(this.teamPath(teamCode) + '/months/' + this.encode(month)));
        }

        async vacationPlan(teamCode, year) {
            return this.hydrateVacationPlan(await this.vacationRequest('/api/teams/asn-' + this.encode(teamCode) + '/years/' + this.encode(year)));
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

        createVacation(assistantUid, dateFrom, dateTo, note) {
            return this.vacationPost('/api/vacations', { employeeUid: assistantUid, startDate: dateFrom, endDate: dateTo, status: 'planned', note });
        }

        deleteVacation(requestId) {
            return this.vacationRequest('/api/vacations/' + this.encode(requestId), { method: 'DELETE', body: '{}' });
        }

        setVacationStatus(teamCode, year, assistantUid, date, status) {
            return this.vacationPost('/api/teams/asn-' + this.encode(teamCode) + '/years/' + this.encode(year) + '/status', {
                employeeUid: assistantUid,
                date,
                status
            });
        }

        vacationRequest(path, options = {}) {
            return this.vacationApi.request(path, options);
        }

        vacationPost(path, body) {
            return this.vacationRequest(path, { method: 'POST', body: JSON.stringify(body) });
        }

        saveSettings(teamCode, displayName, meetingDay, shifts) {
            return this.post(this.teamPath(teamCode) + '/settings', {
                displayName,
                meetingDay,
                shiftsJson: JSON.stringify(shifts)
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

    }

    window.ADPlaner = window.ADPlaner || {};
    window.ADPlaner.repositories = window.ADPlaner.repositories || {};
    window.ADPlaner.repositories.PlanRepository = PlanRepository;
})();
