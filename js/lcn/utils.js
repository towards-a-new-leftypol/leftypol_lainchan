	/**
 * @file Utils for leftychan javascript.
 * @author jonsmy
 */

const assert = {
    "equal": (actual, expected, message="No message set") => {
        if (actual !== expected) {
            const err = new Error(`Assertion Failed. ${message}`)
            err.data = { actual, expected}
            Error.captureStackTrace(err, assert.equal)
            debugger
            throw err
        }
    },
    "ok": (actual, message="No message set") => {
        if (!actual) {
            const err = new Error(`Assertion Failed. ${message}`)
            err.data = { actual }
            Error.captureStackTrace(err, assert.ok)
            debugger
            throw err
        }
    }
}

AbortSignal.any ??= function (signals) {
    const controller = new AbortController()
    const abortFn = () => {
        for (const signal of signals) {
            signal.removeEventListener("abort", abortFn)
        }
        controller.abort()
    }

    for (const signal of signals) {
        signal.addEventListener("abort", abortFn)
    }

    return controller.signal
}
