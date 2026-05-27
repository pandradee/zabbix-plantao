var ZabbixToAPI = {
    params: {},

    setParams: function (params) {
        if (typeof params !== 'object') {
            return;
        }
        ZabbixToAPI.params = params;
    },

    setProxy: function (HTTPProxy) {
        ZabbixToAPI.HTTPProxy = HTTPProxy;
    },

    request: function (method, url, data, authToken) {
        var response,
            request = new HttpRequest();

        request.addHeader('Content-Type: application/x-www-form-urlencoded');
        request.addHeader('Authorization: ' + authToken);

        if (typeof ZabbixToAPI.HTTPProxy !== 'undefined' && ZabbixToAPI.HTTPProxy !== '') {
            request.setProxy(ZabbixToAPI.HTTPProxy);
        }

        if (typeof data !== 'undefined') {
            data = Object.keys(data).map(function (key) {
                return encodeURIComponent(key) + '=' + encodeURIComponent(data[key]);
            }).join('&');
        }

        Zabbix.log(4, '[ API Webhook ] Sending request: ' + url + ((typeof data === 'string') ? ('\n' + data) : ''));

        switch (method) {
            case 'post':
                response = request.post(url, data);
                break;
            default:
                throw 'Unsupported HTTP request method: ' + method;
        }

        Zabbix.log(4, '[ API Webhook ] Received response with status code ' + request.getStatus() + '\n' + response);

        if (request.getStatus() < 200 || request.getStatus() >= 300) {
            throw 'Request failed with status code ' + request.getStatus();
        }

        return response;
    }
};

try {
    var params = JSON.parse(value);
    var url = 'https://portal.ligmee.com/api/notification/call';
    var data = {};
    var authToken = params.api_key;

    if (!authToken || authToken === '') {
        throw 'Token de API não fornecido!';
    }

    if (!params.branch_number || params.branch_number.length > 4) {
        throw 'O campo branch_number deve ter no máximo 4 caracteres.';
    }
    if (!params.destination_number || params.destination_number.length < 8 || params.destination_number.length > 16) {
        throw 'O campo destination_number deve ter entre 8 e 16 caracteres.';
    }
    if (!params.alert_message || params.alert_message.length < 8 || params.alert_message.length > 512) {
        throw 'O campo text deve ter entre 8 e 512 caracteres.';
    }
    if (params.voice_type !== 'Masculina' && params.voice_type !== 'Feminina') {
        throw 'O campo voice_type deve ser "Masculina" ou "Feminina".';
    }

    data.branch_number = params.branch_number;
    data.destination_number = params.destination_number;
    data.text = params.alert_message;
    data.voice_type = params.voice_type;

    if (Object.keys(data).length === 0) {
        throw 'Dados para envio estão vazios.';
    }

    var response = ZabbixToAPI.request('post', url, data, authToken);

    Zabbix.log(4, '[ API Webhook ] Notificação enviada com sucesso. Resposta da API: ' + response);

    return JSON.stringify({
        status: 'OK',
        message: 'Notificação enviada com sucesso',
        api_response: response,
        destination_number: data.destination_number
    });
}
catch (error) {
    Zabbix.log(4, '[ API Webhook ] ERROR: ' + error);
    throw 'Falha no envio: ' + error;
}
