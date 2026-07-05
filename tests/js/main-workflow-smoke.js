const assert = require('assert');
const {
    FakeButton,
    FakeElement,
    createElementMap,
} = require('../../../localbase/tests/js/helpers/fake-dom.js');

const elements = createElementMap([
    'team-select',
    'month-input',
    'year-input',
    'adp-panel',
]);
const tabs = new FakeElement('tabs');
const tabMonth = new FakeButton({ view: 'month' }, 'tab-month');
const tabVacation = new FakeButton({ view: 'vacation' }, 'tab-vacation');

const notices = [];
const errors = [];
const repositoryCalls = [];
let lastRepository = null;

function esc(value) {
    return String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[char]));
}

global.Element = FakeElement;
global.CSS = { escape: String };
global.window = {
    ADPlaner: {
        api: {},
        ui: {
            byId(id) {
                return elements.get(id) || null;
            },
            esc,
            showNotice(message, type = 'info') {
                notices.push({ message, type });
            },
            showError(error, fallback) {
                errors.push({ message: error.message, fallback });
            }
        },
        monthPlan: {
            render(plan) {
                return `<section data-view="month">${esc(plan && plan.team ? plan.team.code : '')}:${esc(plan ? plan.month : '')}</section>`;
            }
        },
        vacationPlan: {
            render(plan) {
                return `<section data-view="vacation">${esc(plan && plan.team ? plan.team.code : '')}:${esc(plan ? plan.year : '')}</section>`;
            }
        },
        settingsPanel: {
            render(team) {
                return `<form id="settings-form">${esc(team ? team.code : '')}</form>`;
            }
        },
        shiftSettingsList: {
            addRow() {
                repositoryCalls.push(['addShiftRow']);
            },
            removeRow() {
                repositoryCalls.push(['removeShiftRow']);
            },
            collect() {
                return [{ key: 'day' }];
            }
        },
        assignmentControl: {
            open(button) {
                repositoryCalls.push(['openAssignmentPicker', button.dataset.slotId]);
            }
        }
    }
};
global.document = {
    querySelector(selector) {
        return selector === '.adp-tabs' ? tabs : null;
    },
    querySelectorAll(selector) {
        return selector === '.adp-tab' ? [tabMonth, tabVacation] : [];
    }
};

class FakePlanRepository {
    constructor() {
        lastRepository = this;
    }

    async state() {
        repositoryCalls.push(['state']);

        return {
            currentUser: { uid: 'anna' },
            teams: [
                { code: 'TeamA', displayName: 'Team <A>' },
                { code: 'TeamB', displayName: 'Team B' }
            ],
            defaultMonth: '2026-07',
            defaultYear: 2026
        };
    }

    async monthPlan(teamCode, month) {
        repositoryCalls.push(['monthPlan', teamCode, month]);

        return { month, team: { code: teamCode, displayName: teamCode === 'TeamA' ? 'Team <A>' : teamCode } };
    }

    async vacationPlan(teamCode, year) {
        repositoryCalls.push(['vacationPlan', teamCode, year]);

        return { year, team: { code: teamCode, displayName: teamCode === 'TeamA' ? 'Team <A>' : teamCode }, requests: [] };
    }

    async addSelf(teamCode, month, slotId) {
        repositoryCalls.push(['addSelf', teamCode, month, slotId]);
    }

    async setVacationStatus(teamCode, year, assistantUid, date, status) {
        repositoryCalls.push(['setVacationStatus', teamCode, year, assistantUid, date, status]);
    }
}

window.ADPlaner.repositories = { PlanRepository: FakePlanRepository };

require('../../js/main.js');

async function flush() {
    for (let i = 0; i < 8; i++) {
        await Promise.resolve();
    }
}

(async () => {
    await flush();

    assert(lastRepository instanceof FakePlanRepository);
    assert.deepStrictEqual(repositoryCalls.slice(0, 2), [
        ['state'],
        ['monthPlan', 'TeamA', '2026-07']
    ]);
    assert(elements.get('team-select').innerHTML.includes('Team &lt;A&gt;'));
    assert(!elements.get('team-select').innerHTML.includes('Team <A>'));
    assert.strictEqual(elements.get('team-select').disabled, false);
    assert.strictEqual(elements.get('month-input').value, '2026-07');
    assert.strictEqual(elements.get('year-input').value, '2026');
    assert.strictEqual(tabMonth.classList.has('is-active'), true);
    assert(elements.get('adp-panel').innerHTML.includes('TeamA:2026-07'));

    await elements.get('team-select').listeners.change({ target: { value: 'TeamB' } });
    assert.deepStrictEqual(repositoryCalls.at(-1), ['monthPlan', 'TeamB', '2026-07']);
    assert(elements.get('adp-panel').innerHTML.includes('TeamB:2026-07'));

    await tabs.listeners.click({ target: tabVacation });
    assert.strictEqual(tabVacation.classList.has('is-active'), true);
    assert.deepStrictEqual(repositoryCalls.at(-1), ['vacationPlan', 'TeamB', '2026']);
    assert(elements.get('adp-panel').innerHTML.includes('TeamB:2026'));

    await elements.get('adp-panel').listeners.click({
        target: new FakeButton({
            action: 'set-vacation-status',
            targetUid: 'anna',
            date: '2026-07-02',
            status: 'approved'
        })
    });
    assert.deepStrictEqual(repositoryCalls.slice(-2), [
        ['setVacationStatus', 'TeamB', '2026', 'anna', '2026-07-02', 'approved'],
        ['vacationPlan', 'TeamB', '2026']
    ]);

    await tabs.listeners.click({ target: tabMonth });
    await elements.get('adp-panel').listeners.click({
        target: new FakeButton({
            action: 'add-self',
            slotId: '7'
        })
    });
    assert.deepStrictEqual(repositoryCalls.slice(-2), [
        ['addSelf', 'TeamB', '2026-07', '7'],
        ['monthPlan', 'TeamB', '2026-07']
    ]);
    assert.deepStrictEqual(errors, []);

    console.log('AdPlaner main workflow smoke test passed.');
})().catch((error) => {
    console.error(error);
    process.exit(1);
});
