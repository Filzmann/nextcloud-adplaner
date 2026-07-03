(function() {
    const { esc, dateShort, dayHeader } = window.ADPlaner.ui;
    const { render: renderAssignmentControl } = window.ADPlaner.assignmentControl;
    const { render: renderCandidateChip } = window.ADPlaner.candidateChip;
    const { render: renderDayNoteControl } = window.ADPlaner.dayNoteControl;

    function render(plan, currentUser) {
        if (!plan || !plan.team) {
            return '<p>Kein Assistenznehmer gewaehlt.</p>';
        }

        const team = plan.team;
        const segments = plan.segments || [];
        const canCoordinate = !!team.canCoordinate;

        return `
            <section class="adp-section">
                <div class="adp-section-head">
                    <h2>${esc(team.displayName || team.code)} - ${esc(plan.month)}</h2>
                    ${team.settings && team.settings.meetingDay ? `<span class="adp-badge">Treffen ${esc(dateShort(team.settings.meetingDay))}</span>` : ''}
                </div>
                <div class="adp-table-wrap">
                    <table class="adp-table adp-month-table">
                        <thead>
                            <tr>
                                <th>Tag</th>
                                ${segments.map(segment => `<th>${esc(segment.label)}<small>${esc(segment.startsAt)}-${esc(segment.endsAt)}</small></th>`).join('')}
                                <th>Bemerkungen</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${(plan.days || []).map(day => dayRow(day, segments, team, currentUser, canCoordinate)).join('')}
                        </tbody>
                    </table>
                </div>
            </section>
        `;
    }

    function dayRow(day, segments, team, currentUser, canCoordinate) {
        const slotsByKey = {};
        (day.slots || []).forEach(slot => {
            slotsByKey[slot.segmentKey] = slot;
        });

        return `
            <tr>
                <th class="adp-day">${dayHeader(day)}<small>${esc(dateShort(day.date))}</small></th>
                ${segments.map(segment => slotCell(slotsByKey[segment.key], team, currentUser, canCoordinate)).join('')}
                <td class="adp-note-cell">${renderDayNoteControl(day, canCoordinate)}</td>
            </tr>
        `;
    }

    function slotCell(slot, team, currentUser, canCoordinate) {
        if (!slot) {
            return '<td class="adp-empty"></td>';
        }

        const candidates = slot.candidates || [];
        const selfUid = currentUser && currentUser.uid ? currentUser.uid : '';
        const hasSelf = candidates.some(candidate => candidate.uid === selfUid);
        const selfAction = !canCoordinate && !hasSelf
            ? `<button type="button" class="adp-small" data-action="add-self" data-slot-id="${esc(slot.id)}">+ ich</button>`
            : '';

        return `
            <td>
                <div class="adp-candidates">
                    ${candidates.map(candidate => renderCandidateChip(candidate, canCoordinate, slot.id)).join('')}
                </div>
                <div class="adp-cell-actions">
                    ${selfAction}
                    ${canCoordinate ? renderAssignmentControl(slot, team, candidates) : ''}
                </div>
            </td>
        `;
    }

    window.ADPlaner = window.ADPlaner || {};
    window.ADPlaner.monthPlan = { render };
})();
