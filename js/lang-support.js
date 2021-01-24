(function() {

    /**
     * Util Functions
     * - this should go into a separate file but it won't right now
     */


    function parseQueryParams(querystring) {
        return querystring.split('&')
            .map(function(part) {
                return part.split('=').map(decodeURIComponent);
            })
            .reduce(function(acc, pair) { return { [pair[0]]: pair[1] } }, {});
    }

    function resolveResponse(resolve, reject) {
        return function(e) {
            var status = e.target.getResponseHeader("Status");
            if (status != null) {
                var status_code = Number(status.split(" ")[0]);
                if (status_code >= 200 && status_code < 300) {
                    resolve(e.target.responseText);
                } else {
                    reject(new Error('Server responded with ' + status));
                }
            } else {
              if (e.target.status >= 200 && e.target.status < 300) {
                  return resolve(e.target.responseText);
              } else {
                  reject(new Error('Server responded with ' + e.target.status));
              }
            }
        }
    }

    /*
     * Performs HTTP GET on the desired URL. Returns a Promise that will
     * be resolved with the response body
     */
    function get(url) {
        return new Promise(function (resolve, reject) {
            var req = new XMLHttpRequest();
            req.onload = resolveResponse(resolve, reject);
            req.onerror = reject;
            req.open('GET', url);
            req.send();
        });
    }


    /**
     * Internationalization implementation
     */


    var LANGUAGE_CODES = {
        en: 'English',
        ru: 'Russian'
    }

    var FALLBACK_LANG_CODE = 'en';

    /*
     * Loads the appopriate json file over the network and creates
     * an i18n instance using that data.
     *
     * Returns a Promise that resolves to this created i18n instance.
     */
    function initLang(langCode) {
        if (!(langCode in LANGUAGE_CODES)) {
            console.error("ERROR. Language code '", langCode, "' is not defined.");
            return;
        }

        console.log('Initializing', LANGUAGE_CODES[langCode], "language.");
        return get('/data/' + langCode + '.json')
            .then(JSON.parse.bind(this))
            .then(i18n.create.bind(this));
    }

    function translateElements(i18ns) {
        var [i18nFallback, i18n] = i18ns;

        document.querySelectorAll(".i18n_doable")
            .forEach(translateElement.bind(this, i18nFallback, i18n));

        document.querySelectorAll(".i18n_not_doable")
            .forEach(setTitleAttrToElem.bind(this, i18nFallback, i18n));
    }


    /*
     * Replaces the text content of element with the a translation, or a
     * fallback translation.
     */
    var translateElement =
        modifyElement.bind(this, (e, txt) => { e.textContent = txt });

    var setTitleAttrToElem =
        modifyElement.bind(this, (e, txt) => { e.title = txt });

    function modifyElement(fmodify, i18nFallback, i18n, element) {
        // For the canonical list of all possible translationKey values,
        // see the file data/en.json
        var translationKey = element.dataset.i18nKey;
        fmodify(element, i18n(translationKey, i18nFallback(translationKey)));
    }

    function main() {
        Promise.all([initLang("en"), initLang("ru")])
            .then(translateElements);
    }

    this.onload = main;
})(this)
