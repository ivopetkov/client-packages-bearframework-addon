(function (jsFilesToLoad, jsCodeToInsert, cssFilesToLoad, cssCodeToInsert) {

    var check = function () {
        var done = true;
        for (var url in jsFilesToLoad) {
            if (jsFilesToLoad[url] !== 1) {
                done = false;
                break;
            }
        }
        for (var url in cssFilesToLoad) {
            if (cssFilesToLoad[url] !== 1) {
                done = false;
                break;
            }
        }
        if (done) {
            for (var i in cssCodeToInsert) {
                var element = document.createElement("style");
                element.type = "text/css";
                element.innerText = cssCodeToInsert[i];
                document.head.appendChild(element);
            }
            for (var i in jsCodeToInsert) {
                var element = document.createElement("script");
                element.innerHTML = jsCodeToInsert[i];
                document.head.appendChild(element);
            }
        }
    };

    for (var jsFile in jsFilesToLoad) {
        (function (url) {
            var element = document.createElement("script");
            element.setAttribute("src", url);
            element.addEventListener("load", function () {
                jsFilesToLoad[url] = 1;
                check();
            });
            document.head.appendChild(element);
        })(jsFile);
    }
    for (var cssFile in cssFilesToLoad) {
        (function (url) {
            var element = document.createElement("link");
            element.setAttribute("rel", "stylesheet");
            element.setAttribute("type", "text/css");
            element.setAttribute("href", url);
            element.addEventListener("load", function () {
                cssFilesToLoad[url] = 1;
                check();
            });
            document.head.appendChild(element);
        })(cssFile);
    }

    check();
})(["PLACE_JS_FILES_HERE"], ["PLACE_JS_CODE_HERE"], ["PLACE_CSS_FILES_HERE"], ["PLACE_CSS_CODE_HERE"]);