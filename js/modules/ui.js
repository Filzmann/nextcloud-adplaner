(function() {
    const { Notice, byId, esc } = window.LocalBase.ui;
    const notice = new Notice('adp-notice');
    const weekday = ['', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];
    const monthName = ['', 'Jan', 'Feb', 'Mrz', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'];

    function dateShort(date) {
        const parts = String(date).split('-');
        if (parts.length !== 3) {
            return date;
        }
        return parts[2] + '.' + parts[1] + '.';
    }

    function dayHeader(day) {
        return esc(weekday[day.weekday] || '') + '<span>' + esc(String(day.dayOfMonth)) + '</span>';
    }

    function monthHeader(day) {
        const label = monthName[day.month] || String(day.month);
        return esc(label) + '<span>' + esc(String(day.dayOfMonth)) + '</span>';
    }

    function statusLabel(status) {
        if (status === 'approved') {
            return 'genehmigt';
        }
        if (status === 'planned') {
            return 'geplant';
        }
        return '';
    }

    function showNotice(message) {
        notice.show(message);
    }

    function showError(error, fallback = 'Die Aktion konnte nicht ausgeführt werden.') {
        notice.error(error, fallback);
    }

    window.ADPlaner = window.ADPlaner || {};
    window.ADPlaner.ui = { byId, esc, dateShort, dayHeader, monthHeader, statusLabel, showNotice, showError };
})();
