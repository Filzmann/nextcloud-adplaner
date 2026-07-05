import { dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { runJavaScriptSuite } from '../../localbase/tests/Support/js-runner.mjs';

const root = dirname(dirname(fileURLToPath(import.meta.url)));

runJavaScriptSuite({
    root,
    testFiles: [
        'tests/js/assignment-control-smoke.js',
        'tests/js/main-workflow-smoke.js',
        'tests/js/model-smoke.js',
        'tests/js/month-plan-smoke.js',
        'tests/js/plan-repository-smoke.js',
        'tests/js/settings-panel-smoke.js',
        'tests/js/ui-smoke.js',
        'tests/js/vacation-plan-smoke.js',
    ],
    successMessage: 'AdPlaner JavaScript tests passed',
});
