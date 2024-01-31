/**
 * Implements live-reloading on the recent posts mod page.
 */
(() => {
    if (AbortSignal.any === undefined) {
        AbortSignal.any = function (signals) {
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
    }

    const scriptFn = () => {
        const kLocationPath = location.href.slice(location.origin.length)
        if (/^\/mod\.php\?\/recent\/\d+$/.test(kLocationPath)) {
            const loadIntFromStorage = key => {
                const value = localStorage.getItem(`jon-liveposts::${key}`)
                return value != null ? parseInt(value) : null
            }

            const kPullXPosts = loadIntFromStorage("pull-x-posts") ?? 6
            const kPullEveryX = loadIntFromStorage("pull-every-x") ?? 8000
            const kRecentXPosts = parseInt(kLocationPath.slice(17).split(/[^\d]/)[0])
            const kWasEnabled = loadIntFromStorage("enabled") == 1

            let liveUpdateEnabled = false
            let liveUpdateAbortController = null
            let liveUpdateIntervalId = null
            let liveUpdateFaviconChanged = false

            const parser = new DOMParser()
            const fetchLatest = async () => {
                const res = await fetch(`/mod.php?/recent/${kPullXPosts}`, {
                    "signal": AbortSignal.any([ liveUpdateAbortController.signal, AbortSignal.timeout(kPullEveryX - 500) ])
                })

                if (res.ok) {
                    const dom = parser.parseFromString(await res.text(), "text/html")
                    const posts = Array.from(dom.querySelectorAll("body > .post-wrapper[data-board]"))
                    return posts.map(el => ({
                        "element": el,
                        "href": Array.prototype.find.apply(el.children, [ el => el.classList.contains("eita-link") ]).href,
                        "id": Array.prototype.find.apply(el.children, [ el => el.classList.contains("thread") || el.classList.contains("postcontainer") ]).id
                    }))
                } else {
                    return []
                }
            }

            const getPostTimestamp = post => new Date(post.lastElementChild.querySelector(".intro time").dateTime)
            const updateRecentPosts = () => {
                if (liveUpdateEnabled) {
                    fetchLatest()
                      .then(latestPosts => {
                        const lastPost = document.body.querySelector(".post-wrapper")
                        const lastPostTs = getPostTimestamp(lastPost).getTime()
                        const posts = latestPosts.filter(post => document.getElementById(post.id) == null && getPostTimestamp(post.element).getTime() > lastPostTs)
                        if (posts.length > 0) {
                            const totalPosts = Array.from(document.body.querySelectorAll(".post-wrapper"))
                            for (const post of posts) {
                                lastPost.prepend(post.element)
                                totalPosts.unshift(post.element)
                            }

                            while (totalPosts.length > kRecentXPosts) {
                                document.body.removeChild(totalPosts.pop())
                            }

                            updatePageNext()
                            makeIcon("reply")
                            liveUpdateFaviconChanged = true
                        }
                      })
                      .catch(error => {
                        // XXX: Why the hell does gecko have a space at the end??
                        if (!((error instanceof DOMException) && [ "The user aborted a request", "The operation was aborted." ].some(msg => error.message.slice(0, 26) === msg))) {
                            throw error
                        }
                      })
                }
            }

            const pageNext = Array.prototype.find.apply(document.body.children, [ el => el.nodeName == "A" && el.href.startsWith(`${location.origin}/mod.php?/recent/${kRecentXPosts}&last=`) ])
            const updatePageNext = () => {
                const posts = document.body.querySelectorAll(".post-wrapper")
                const oldestPost = posts[posts.length-1]
                pageNext.href = `/mod.php?/recent/${kRecentXPosts}&last=${getPostTimestamp(oldestPost).getTime() / 1000}`
            }

            const createCheckbox = () => {
                const id = "jon-liveposts-enabled"
                const div = document.createElement("div")
                const label = document.createElement("label")
                const checkbox = document.createElement("input")
                checkbox.type = "checkbox"
                checkbox.id = id
                checkbox.checked = kWasEnabled
                label.innerText = "live update: "
                label.for = id
                div.style.display = "inline"
                div.append(" ")
                div.appendChild(label)
                div.appendChild(checkbox)
                div.append(" ")
                return { checkbox, label, div }
            }

            const pageNums = Array.prototype.find.apply(document.body.children, [ el => el.nodeName == "P" ])
            const form = createCheckbox()

            const liveUpdateToggle = enabled => {
                if (enabled) {
                    liveUpdateEnabled = true
                    liveUpdateAbortController = new AbortController()
                    liveUpdateIntervalId = setInterval(() => updateRecentPosts(), kPullEveryX)
                } else {
                    liveUpdateEnabled = false
                    clearInterval(liveUpdateIntervalId)
                    liveUpdateAbortController.abort()
                    liveUpdateIntervalId = null
                    liveUpdateAbortController = null
                }

                if (form.checkbox.checked != enabled) {
                    form.checkbox.checked = enabled
                }
            }

            form.checkbox.addEventListener("change", () => {
                localStorage.setItem("jon-liveposts::enabled", form.checkbox.checked ? "1" : "0")
                liveUpdateToggle(form.checkbox.checked)
            })

            document.body.addEventListener("mousemove", () => {
                if (liveUpdateFaviconChanged) {
                    liveUpdateFaviconChanged = false
                    makeIcon(false)
                }
            })

            pageNums.append("|")
            pageNums.append(form.div)

            if (kWasEnabled) {
                setTimeout(() => liveUpdateToggle(true), 1)
            }
        }
    }


    if (document.readyState != "complete") {
        window.addEventListener("load", scriptFn, { "once": true })
    } else {
        setTimeout(scriptFn, 1)
    }
})()
