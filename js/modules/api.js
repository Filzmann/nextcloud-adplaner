(function() {
    const client = new window.LocalBase.api.ApiClient({ appId: 'adplaner' });
    const vacationClient = new window.LocalBase.api.ApiClient({ appId: 'adurlaub', errorMessage: (data, status) => data && data.error ? data.error : `HTTP ${status}` });

    window.ADPlaner = window.ADPlaner || {};
    window.ADPlaner.api = {
        request: client.request.bind(client),
        encode: client.encode.bind(client)
    };
    window.ADPlaner.vacationApi = {
        request: vacationClient.request.bind(vacationClient),
        encode: vacationClient.encode.bind(vacationClient)
    };
})();
