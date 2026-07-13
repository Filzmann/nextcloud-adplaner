<?php

return [
    'routes' => [
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],

        ['name' => 'api#state', 'url' => '/api/state', 'verb' => 'GET'],
        ['name' => 'api#monthPlan', 'url' => '/api/teams/{teamCode}/months/{month}', 'verb' => 'GET'],
        ['name' => 'api#saveTeamSettings', 'url' => '/api/teams/{teamCode}/settings', 'verb' => 'POST'],
        ['name' => 'api#saveDayNote', 'url' => '/api/teams/{teamCode}/months/{month}/days/{workDate}/note', 'verb' => 'POST'],
        ['name' => 'api#addShiftCandidate', 'url' => '/api/teams/{teamCode}/months/{month}/slots/{slotId}/candidates', 'verb' => 'POST'],
        ['name' => 'api#removeShiftCandidate', 'url' => '/api/teams/{teamCode}/months/{month}/slots/{slotId}/candidates/remove', 'verb' => 'POST'],

        // Urlaubsrouten liegen kanonisch in AD Urlaub; die alten Services bleiben vorerst nur als Rueckbaureserve bestehen.
    ],
];
