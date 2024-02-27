/**
 * @file Utils for leftychan javascript.
 * @author jonsmy
 */

function cont(value_to_test, fn) {
    if (value_to_test != null) {
        return fn(value_to_test);
    } else {
        return null;
    }
}

function text(s) {
    return document.createTextNode(s);
}

function prepend(elem, children) {
    var child = elem.firstChild;

    if (child) {
        elem.insertBefore(children, child);
    } else {
        elem.appendChild(children);
    }
}


function _log() {
    for (var arg of arguments) {
        if (arg == null) {
            continue;
        }

        var pre = document.createElement('pre');
        pre.appendChild(text(arg.toString()));
        document.body.appendChild(pre);
        try {
            prepend(document.body, pre);
        } catch (e) {
            var pre = document.createElement('pre');
            pre.appendChild(text(e.toString()));
            document.body.appendChild(pre);
        }

    }
}

var console = {
    log: _log,
    error: _log
};

const assert = {
    "equal": (actual, expected, message="No message set") => {
        if (actual !== expected) {
            const err = new Error(`Assertion Failed. ${message}`);
            err.data = { actual, expected}
            //Error.captureStackTrace?.(err, assert.equal);
            debugger;
            throw err;
        }
    },
    "ok": (actual, message="No message set") => {
        if (!actual) {
            const err = new Error(`Assertion Failed. ${message}`);
            err.data = { actual }
            //Error.captureStackTrace?.(err, assert.ok);
            debugger;
            throw err;
        }
    }
};

if (AbortSignal.any == null) {
    AbortSignal.any = (signals) => {
        const controller = new AbortController();
        const abortFn = () => {
            for (const signal of signals) {
                signal.removeEventListener("abort", abortFn);
            }
            controller.abort();
        }

        for (const signal of signals) {
            signal.addEventListener("abort", abortFn);
        }

        return controller.signal;
    }
}

// polyfill for replaceChildren
if( Node.prototype.replaceChildren === undefined) {
    Node.prototype.replaceChildren = function(addNodes) {
        while(this.lastChild) {
            this.removeChild(this.lastChild); 
        }
        if (addNodes !== undefined) {
            this.append(addNodes);
        }
    }
}
