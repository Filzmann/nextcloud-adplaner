const assert = require('assert');

global.window = {};

require('../../../localbase/js/ui/ui.js');
require('../../js/modules/ui.js');
require('../../js/components/vacation-plan.js');

const { vacationPlan } = window.ADPlaner;

assert(vacationPlan.render(null, { uid: 'assistant-a' }).includes('Kein Assistenznehmer gewaehlt.'));

const basePlan = {
    year: 2026,
    days: [
        { date: '2026-07-04', month: 7, dayOfMonth: 4, weekday: 6 },
        { date: '2026-07-06', month: 7, dayOfMonth: 6, weekday: 1 }
    ],
    assistants: [
        {
            uid: 'assistant-a',
            displayName: 'Assistant <A>',
            days: {
                '2026-07-04': { status: 'planned' },
                '2026-07-06': { status: 'approved' }
            }
        },
        {
            uid: 'assistant-b',
            displayName: 'Assistant B',
            days: {}
        }
    ],
    requests: [
        {
            id: 'request-<a>',
            assistantUid: 'assistant-a',
            dateFrom: '2026-07-04',
            dateTo: '2026-07-06',
            status: 'planned'
        },
        {
            id: 'request-b',
            assistantUid: 'assistant-b',
            dateFrom: '2026-08-01',
            dateTo: '2026-08-02',
            status: 'planned'
        }
    ]
};

const assistantHtml = vacationPlan.render({
    ...basePlan,
    team: {
        code: 'A1',
        displayName: 'Team <Vacation>',
        canCoordinate: false
    }
}, { uid: 'assistant-a' });

assert(assistantHtml.includes('Team &lt;Vacation&gt; - Urlaub 2026'));
assert(!assistantHtml.includes('Team <Vacation>'));
assert(assistantHtml.includes('Jul<span>4</span>'));
assert(assistantHtml.includes('Assistant &lt;A&gt;'));
assert(!assistantHtml.includes('Assistant <A>'));
assert(assistantHtml.includes('04.07.-06.07. - geplant'));
assert(assistantHtml.includes('data-action="delete-vacation" data-request-id="request-&lt;a&gt;"'));
assert(!assistantHtml.includes('request-b'));
assert(assistantHtml.includes('adp-vac-cell adp-vac-planned has-vacation is-weekend'));
assert(assistantHtml.includes('title="Assistant &lt;A&gt; - 04.07. - geplant"'));
assert(!assistantHtml.includes('data-action="set-vacation-status"'));

const coordinatorHtml = vacationPlan.render({
    ...basePlan,
    team: {
        code: 'A1',
        displayName: 'Team Vacation',
        canCoordinate: true
    }
}, { uid: 'eb' });

assert(coordinatorHtml.includes('data-action="set-vacation-status" data-target-uid="assistant-a" data-date="2026-07-04" data-status="approved"'));
assert(coordinatorHtml.includes('data-action="set-vacation-status" data-target-uid="assistant-a" data-date="2026-07-06" data-status="planned"'));
assert(coordinatorHtml.includes('data-action="set-vacation-status" data-target-uid="assistant-b" data-date="2026-07-06" data-status="planned"'));
assert(coordinatorHtml.includes('aria-label="Assistant B - 06.07."'));

console.log('AdPlaner vacation plan smoke test passed.');
