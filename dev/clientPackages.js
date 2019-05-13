var clientPackages = clientPackages || (function () {

    var pendingGets = [];
    var data = [];
    var urls = [];

    Promise = window.Promise || function (callback) {
        var thenCallbacks = [];
        var catchCallback = null;
        this.then = function (f) {
            thenCallbacks.push(f);
            return this;
        };
        this.catch = function (f) {
            if (catchCallback === null) {
                catchCallback = f;
            }
            return this;
        };
        var resolve = function () {
            for (var i in thenCallbacks) {
                thenCallbacks[i].apply(null, arguments);
            }
        };
        var reject = function () {
            if (catchCallback !== null) {
                catchCallback.apply(null, arguments);
            }
        };
        window.setTimeout(function () {
            callback(resolve, reject);
        }, 16);
    };

    var get = function (name) {
        return new Promise(function (resolve, reject) {
            if (typeof data[name] === 'undefined') { // first request
                if (typeof urls[name] === 'undefined') {
                    reject();
                } else {
                    data[name] = null;
                    pendingGets.push([resolve, reject]);
                    var element = document.createElement("script");
                    element.setAttribute("src", urls[name]);
                    document.head.appendChild(element);
                }
            } else if (data[name] === null) { // has pending request
                pendingGets.push([resolve, reject]);
            } else {
                resolve((new Function(data[name]))());
            }
        });
    };

    var add = function (name, getCode) {
        data[name] = getCode;
        var pendingGet = null;
        while (typeof (pendingGet = pendingGets.shift()) !== 'undefined') {
            pendingGet[0]((new Function(data[name]))());
        }
    };

    var prepare = function (name, url) {
        urls[name] = url;
    };

    return {
        'get': get,
        '__a': add, // internal function
        '__p': prepare // internal function
    };

}());
