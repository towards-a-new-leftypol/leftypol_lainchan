/**
 * @file Implements live-reloading on the recent posts mod page.
 * @author jonsmy
 */
$().ready(() => {
    if (LCNSite.INSTANCE.isModRecentsPage()) {
        const loadIntFromStorage = key => {
            const value = localStorage.getItem(`jon-liveposts::${key}`)
            return value != null ? parseInt(value) : null
        }

        const kPullXPosts = loadIntFromStorage("pull-x-posts") ?? 6
        const kPullEveryX = loadIntFromStorage("pull-every-x") ?? 8000
        const kRecentXPosts = parseInt(location.search.slice(9).split(/[^\d]/)[0])
        const kWasEnabled = loadIntFromStorage("enabled") == 1
        const kBellWasEnabled = loadIntFromStorage("bell-enabled") == 1

        let liveUpdateEnabled = false
        let liveUpdateBellEnabled = kBellWasEnabled
        let liveUpdateAbortController = null
        let liveUpdateIntervalId = null
        let liveUpdateFaviconChanged = false

        const parser = new DOMParser()
        const fetchRecentPosts = async () => {
            const res = await fetch(`/mod.php?/recent/${kPullXPosts}`, {
                "signal": AbortSignal.any([ liveUpdateAbortController.signal, AbortSignal.timeout(kPullEveryX - 500) ])
            })

            if (res.ok) {
                const dom = parser.parseFromString(await res.text(), "text/html")
                return dom.querySelectorAll("body > .post-wrapper")
            } else {
                return []
            }
        }

        const fetchLatestPosts = async () => {
            const postWrappers = LCNPostWrapper.all()
            const lastPostWrapper = LCNPostWrapper.assign(document.body.querySelector(".post-wrapper"))
            const lastPostTs = lastPostWrapper.getPost().getInfo().getCreatedAt().getTime()
            const missingPosts = []
            for (const element of await fetchRecentPosts()) {
                const postWrapper = LCNPostWrapper.assign(element)
                const postInfo = postWrapper.getPost().getInfo()
                if (postInfo.getCreatedAt().getTime() > lastPostTs && !postWrappers.some(pw => pw.getPost().getInfo().is(postInfo))) {
                    missingPosts.unshift(postWrapper)
                } else {
                    break
                }
            }

            return missingPosts
        }

        const updateRecentPosts = () => {
            if (liveUpdateEnabled) {
                const totalPosts = LCNPostWrapper.all()
                fetchLatestPosts()
                  .then(postWrappers => {
                    if (postWrappers.length > 0) {
                        for (const postWrapper of postWrappers) {
                            document.body.insertBefore(postWrapper.getElement(), totalPosts[0].getElement())
                            totalPosts.unshift(postWrapper)
                        }

                        while (totalPosts.length > kRecentXPosts) {
                            document.body.removeChild(totalPosts.pop().getElement())
                        }

                        // XXX: Fire ::new_post for chanx listeners
                        for (const postWrapper of postWrappers.slice(0, kRecentXPosts).reverse()) {
                            $(document).trigger("new_post", [ postWrapper.getPost().getElement() ])
                        }

                        updatePageNext()
                        LCNSite.INSTANCE.setUnseen(LCNSite.INSTANCE.getUnseen() + postWrapper.length)
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
            const oldestPostInfo = LCNPostWrapper.all().at(-1).getContent().getContent().getInfo()
            pageNext.href = `/mod.php?/recent/${kRecentXPosts}&last=${oldestPostInfo.getCreatedAt().getTime() / 1000}`
        }

        const createCheckbox = (id, text, initialState) => {
            const div = document.createElement("div")
            const label = document.createElement("label")
            const checkbox = document.createElement("input")
            checkbox.type = "checkbox"
            checkbox.id = id
            checkbox.checked = initialState
            label.innerText = text
            label.for = id
            div.style.display = "inline"
            div.append(" ")
            div.appendChild(label)
            div.appendChild(checkbox)
            div.append(" ")
            return { checkbox, label, div }
        }

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

            if (kLiveForm.checkbox.checked != enabled) {
                kLiveForm.checkbox.checked = enabled
            }
        }

        const pageNums = Array.prototype.find.apply(document.body.children, [ el => el.nodeName == "P" ])
        const kLiveForm = createCheckbox("jon-liveposts-enabled", "live: ", kWasEnabled)
        const kBellForm = createCheckbox("jon-liveposts-bell-enabled", "bell: ", kBellWasEnabled)
        const kBell = new Audio("/static/jannybell.mp3")

        kLiveForm.checkbox.addEventListener("change", () => {
            localStorage.setItem("jon-liveposts::enabled", kLiveForm.checkbox.checked ? "1" : "0")
            liveUpdateToggle(kLiveForm.checkbox.checked)
        })

        kBellForm.checkbox.addEventListener("change", () => {
            localStorage.setItem("jon-liveposts::bell-enabled", kBellForm.checkbox.checked ? "1" : "0")
            liveUpdateBellEnabled = kBellForm.checkbox.checked
        })

        $(window).on("focus", () => LCNSite.INSTANCE.clearUnseen())
        $(document.body).on("mousemove", () => LCNSite.INSTANCE.clearUnseen())
        $(document).on("new_post", () => {
            if (liveUpdateBellEnabled && kBell.paused) {
                // XXX: Site requires autoplay media permission to do this
                kBell.play().catch(console.error)
            }
        })

        for (const form of [ kLiveForm, kBellForm ]) {
            pageNums.append("|")
            pageNums.append(form.div)
        }

        if (kWasEnabled) {
            setTimeout(() => liveUpdateToggle(true), 1)
        }
    }
})
