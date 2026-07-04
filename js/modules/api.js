(function() {
    const client = new window.LocalBase.api.ApiClient({ appId: 'adplaner' });

    window.ADPlaner = window.ADPlaner || {};
    window.ADPlaner.api = {
        request: client.request.bind(client),
        encode: client.encode.bind(client)
    };
})();
