(function() {

    /*
     * UTILS
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


    /*
     * Internationalization implementation
     */

    var LANGUAGE_CODES = {
        en: 'English',
        ru: 'Russian'
    }

    var FALLBACK_LANG_CODE = 'en';

    /*
     * Valid translation keys:
     *  - leftypol_tagline
     */


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

        console.log('Changing the current language to:', LANGUAGE_CODES[langCode]);
        return get('/data/' + langCode + '.json')
            .then(JSON.parse.bind(this))
            .then(i18n.create.bind(this));
    }

    function translateElements(i18ns) {
        var [i18nFallback, i18n] = i18ns;

        document.querySelectorAll(".i18n_doable")
            .forEach(translateElement.bind(this, i18nFallback, i18n));
    }


    /*
     * Replaces the text content of element with the a translation, or a
     * fallback translation.
     */
    function translateElement(i18nFallback, i18n, element) {
        console.log("translate element: element", element);

        var translationKey = element.dataset.i18nKey;
        console.log("translation key:", translationKey);
        var currentText = element.textContent;
        console.log("current text:", translationKey);
        element.textContent = i18n(translationKey, i18nFallback(translationKey));
    }

    function main() {
        console.log("Hello World");
        Promise.all([initLang("en"), initLang("ru")])
            .then(translateElements);
    }

    this.onload = main;
})(this)
