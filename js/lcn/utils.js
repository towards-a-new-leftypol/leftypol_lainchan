/**
 * @file Utils for leftychan javascript.
 * @author jonsmy
 */

const arrLast = arr => arr[arr.length-1] ?? undefined
const getConfigBool = (k,d) => { const v = localStorage.getItem(`jon-modjs::${k}`); return v ? v == "1" : d; }
const writeCSSStyle = textContent => {
    const style = document.createElement("style")
    style.textContent = textContent
    document.head.appendChild(style)
}

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
