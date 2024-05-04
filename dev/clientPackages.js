var clientPackages = clientPackages || (function () {

    var url = 'URL_TO_REPLACE';
    var packages = [];

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

    var load = function (name) {
        var r = new XMLHttpRequest();
        r.onreadystatechange = function () {
            if (r.readyState === 4) {
                if (r.status === 200) {
                    (new Function(r.responseText))();
                }
            }
        };
        r.open('POST', url + '?n=' + name, true);
        r.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        r.send('');
    };

    var resolve = function (name, callback) {
        callback((new Function(packages[name][1]))());
    };

    var get = function (name) {
        return new Promise(function (resolveCallback, rejectCallback) {
            var addPending = function () {
                packages[name][2].push([resolveCallback, rejectCallback]);
            };
            if (typeof packages[name] === 'undefined') {
                packages[name] = [0, null, []]; // status, getter, pending
                addPending();
                load(name);
            } else {
                if (packages[name][0] === 1) { // loaded
                    resolve(name, resolveCallback);
                } else if (packages[name][0] === 2) { // prepared
                    addPending();
                    (new Function(packages[name][3]))();
                    delete packages[name][3];
                } else { // loading
                    addPending();
                }
            }
        });
    };

    var add = function (name, get) {
        if (typeof packages[name] === 'undefined') {
            packages[name] = [1, get, []]; // status, getter, pending
        } else {
            packages[name][0] = 1;
            packages[name][1] = get;
        }
        var pending = null;
        while (typeof (pending = packages[name][2].shift()) !== 'undefined') {
            resolve(name, pending[0]);
        }
    };

    var prepare = function (name, js) {
        if (typeof packages[name] === 'undefined') {
            packages[name] = [2, null, [], js]; // status, getter, pending, prepared
        }
    };


    return {
        'get': get,
        '__a': add, // internal function
        '__p': prepare // internal function
    };

}());
