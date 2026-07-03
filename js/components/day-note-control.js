(function() {
    const { esc } = window.ADPlaner.ui;

    function render(day, canCoordinate) {
        if (!canCoordinate) {
            return `<span class="adp-note-text">${esc(day.note || '')}</span>`;
        }

        return `
            <textarea rows="2" data-note-date="${esc(day.date)}">${esc(day.note || '')}</textarea>
            <button type="button" class="adp-small adp-icon-button" title="Bemerkung speichern" data-action="save-note" data-date="${esc(day.date)}">&#10003;</button>
        `;
    }

    window.ADPlaner = window.ADPlaner || {};
    window.ADPlaner.dayNoteControl = { render };
})();
