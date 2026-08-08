(function() {
    const { esc } = window.ADPlaner.ui;

    function render(candidate, canCoordinate, slotId, mutable = true) {
        const removable = mutable && (canCoordinate || candidate.isSelf);

        return `
            <span class="adp-chip">
                ${esc(candidate.displayName || candidate.uid)}
                ${removable ? `<button type="button" title="Entfernen" data-action="remove-candidate" data-slot-id="${esc(slotId)}" data-target-uid="${esc(candidate.uid)}">x</button>` : ''}
            </span>
        `;
    }

    window.ADPlaner = window.ADPlaner || {};
    window.ADPlaner.candidateChip = { render };
})();
