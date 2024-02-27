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

if (Array.prototype.at === undefined) {
    Array.prototype.at = function(index) {
        if (index >= 0) {
            return this[index];
        } else {
            return this[this.length + index];
        }
    };
}
