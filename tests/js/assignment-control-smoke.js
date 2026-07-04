const assert = require('assert');

global.window = {};

require('../../../localbase/js/ui/ui.js');
require('../../js/modules/ui.js');
require('../../js/components/assignment-control.js');

const { assignmentControl } = window.ADPlaner;

const html = assignmentControl.render(
    { id: 7 },
    {
        assistants: [
            { uid: 'alice', displayName: 'Alice Test', canReceiveShifts: true },
            { uid: 'bob', displayName: 'Bob Test', canReceiveShifts: true },
            { uid: 'eb', displayName: 'Einsatzbegleitung', canReceiveShifts: false },
            { uid: 'chris', displayName: '<Chris & Co>', canReceiveShifts: true }
        ]
    },
    [{ uid: 'bob' }]
);

assert(html.includes('data-assignment-control="7"'));
assert(html.includes('data-action="open-assignment-picker"'));
assert(html.includes('value="alice"'));
assert(!html.includes('value="bob"'));
assert(!html.includes('value="eb"'));
assert(html.includes('value="chris"'));
assert(html.includes('&lt;Chris &amp; Co&gt;'));
assert(!html.includes('<Chris & Co>'));
assert(!html.includes('<select data-add-select="7" disabled'));

const emptyHtml = assignmentControl.render(
    { id: 8 },
    {
        assistants: [
            { uid: 'bob', displayName: 'Bob Test', canReceiveShifts: true },
            { uid: 'eb', displayName: 'Einsatzbegleitung', canReceiveShifts: false }
        ]
    },
    [{ uid: 'bob' }]
);

assert(emptyHtml.includes('<select data-add-select="8" disabled'));
assert(emptyHtml.includes('data-action="add-selected" data-slot-id="8" disabled'));

const focused = [];
const pickerOne = {
    dataset: { assignmentPicker: '1' },
    hidden: false,
    querySelector() {
        return { focus: () => focused.push('1') };
    }
};
const pickerTwo = {
    dataset: { assignmentPicker: '2' },
    hidden: true,
    querySelector() {
        return { focus: () => focused.push('2') };
    }
};

global.CSS = { escape: String };
global.document = {
    querySelectorAll(selector) {
        assert.strictEqual(selector, '[data-assignment-picker]');

        return [pickerOne, pickerTwo];
    },
    querySelector(selector) {
        assert.strictEqual(selector, '[data-assignment-picker="2"]');

        return pickerTwo;
    }
};

assignmentControl.open({ dataset: { slotId: '2' } });
assert.strictEqual(pickerOne.hidden, true);
assert.strictEqual(pickerTwo.hidden, false);
assert.deepStrictEqual(focused, ['2']);

console.log('AdPlaner assignment control smoke test passed.');
