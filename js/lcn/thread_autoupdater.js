/**
 * @file Thread auto updater.
 * @author jonsmy
 */

$().ready(() => {

    const kIsEnabled = LCNToggleSetting.build("enabled")
    //const kIsBellEnabled = LCNToggleSetting.build("bellEnabled")
    void LCNSettingsSubcategory.for("general", "threadUpdater")
      .setLabel("Thread Updater")
      .addSetting(kIsEnabled
        .setLabel(_("Fetch new replies in the background"))
        .setDefaultValue(true))
      /*.addSetting(kIsBellEnabled
        .setLabel(_("Play an audible chime when new replies are found"))
        .setDefaultValue(false))*/;

    if (LCNSite.INSTANCE.isThreadPage()) {
        let threadUpdateStatus = null
        let secondsCounter = 0
        let threadState = null
        let threadStats = null
        let statReplies = null
        let statFiles = null
        let statPage = null
        let statUniqueIPs = null

        const parser = new DOMParser()
        const abortable = LCNSite.createAbortable()
        const threadStatsItems = []
        const updateDOMStatus = () => {
            const text = threadState ?? (secondsCounter >= 0 ? `${secondsCounter}s` : "…")
            threadUpdateStatus.innerText = text
        }

        const updateSecondsByTSLP = post_info => {
            secondsCounter = Math.floor(((Date.now() - post_info.getCreatedAt().getTime()) / 120000))
            secondsCounter = Math.min(1000, secondsCounter)
            secondsCounter = Math.max(11, secondsCounter)
        }

        const updateStatsFn = async thread => {
            // XXX: Using /%b/%d.json would be better however the page number isn't provided.
            const res = await fetch(`/${thread.getContent().getInfo().getBoardId()}/threads.json`, {
                "signal": abortable.signal
            })

            if (res.ok) {
                const stats = LCNSite.getThreadFromPages(await res.json(), thread.getContent().getInfo().getThreadId())
                if (stats != null) {
                    threadStats = stats
                } else {
                    threadState = String(res.status)
                }
            } else {
                throw new Error(`Server responded with non-OK status '${res.status}'`)
            }
        }

        const findMissingReplies = (thread_op, thread_dom, thread_latest) => {
            const lastPostTs = (thread_dom.at(-1)?.getInfo() ?? thread_op.getInfo()).getCreatedAt().getTime()
            const missing = []

            for (const pc of thread_latest.reverse()) {
                if (pc.getContent().getInfo().getCreatedAt().getTime() > lastPostTs) {
                    missing.unshift(pc)
                } else {
                    break
                }
            }
            return missing
        }

        const updateRepliesFn = (thread, missingPCList) => {
            if (missingPCList.length) {
                const documentPCList = [ thread.getContent(), ...(thread.getReplies()).map(p => p.getParent()) ]

                for (const pc of missingPCList) {
                    documentPCList.at(-1).getElement().after(pc.getElement())
                    documentPCList.push(pc)
                }

                for (const pc of missingPCList) {
                    $(document).trigger("new_post", [ pc.getContent().getElement() ])
                }

                LCNSite.INSTANCE.setUnseen(LCNSite.INSTANCE.getUnseen() + missingPCList.length)
            }
        }

        const updateThreadFn = async (thread, dom) => {
            const threadPost = thread.getContent()
            const threadReplies = thread.getReplies()
            const missingPCList = findMissingReplies(
              threadPost,
              threadReplies, 
              LCNPostContainer.all(dom.querySelector(`#thread_${threadPost.getInfo().getThreadId()}`)))

            updateRepliesFn(thread, missingPCList)
        }

        const fetchThreadFn = async () => {
            const res = await fetch(location.href, { "signal": abortable.signal })
            if (res.ok) {
                return parser.parseFromString(await res.text(), "text/html")
            } else {
                if (res.status == 404) {
                    threadState = String(res.status)
                }
                throw new Error(`Server responded with non-OK status '${res.status}'`)
            }
        }

        const onTickClean = () => {
            if (onTickId != null) {
                clearTimeout(onTickId)
                onTickId = null
            }
            abortable.abort()
        }

        let onTickId = null
        const onTickFn = async () => {
            void secondsCounter--;
            onTickClean()
            updateDOMStatus()

            if (threadState == null) {
                if (secondsCounter < 0) {
                    const thread = LCNThread.first()
                    try {
                        await updateStatsFn(thread)
                        if (threadState == null && threadStats.last_modified > (thread.getPosts().at(-1).getInfo().getCreatedAt().getTime() / 1000)) {
                            updateThreadFn(thread, await fetchThreadFn())
                        }

                        const threadEl = thread.getElement()
                        statUniqueIPs.innerText = threadStats.unique_ips
                        statReplies.innerText = thread.getReplies().length
                        statFiles.innerText = threadEl.querySelectorAll(".files .file").length - threadEl.querySelectorAll(".files .file .post-image.deleted").length
                        statPage.innerText = threadStats.page + 1
                        updateSecondsByTSLP(thread.getPosts().at(-1).getInfo())
                    } catch (error) {
                        console.error("threadAutoUpdater: Failed while processing update. Probably a network error", error)
                        secondsCounter = 60
                    }
                }

                onTickId = setTimeout(onTickFn, 1000)
            }
        }

        $(document).on("ajax_after_post", (_, xhr_body) => {
            if (kIsEnabled.getValue() && xhr_body != null) {
                if (!xhr_body.mod) {
                    const thread = LCNThread.first()
                    const dom = parser.parseFromString(xhr_body.thread, "text/html")
                    updateThreadFn(thread, dom)
                    updateSecondsByTSLP(thread.getPosts().at(-1).getInfo())
                } else {
                    $(document).trigger("thread_manual_refresh")
                }
            }
        })

        $(document).on("thread_manual_refresh", () => {
            if (kIsEnabled.getValue() && secondsCounter >= 0) {
                secondsCounter = 0
                onTickFn()
            }
        })

        let floaterLinkBox = null
        const onStateChangeFn = v => {
            onTickClean()

            if (v) {
                _domsetup_btn: {
                    const container = LCNSite.INSTANCE.getFloaterLContainer()
                    floaterLinkBox = document.createElement("span")
                    const threadlink = document.createElement("span")
                    const threadUpdateLink = document.createElement("a")
                    threadUpdateStatus = document.createElement("span")

                    threadUpdateStatus.id = "thread-update-status"
                    threadUpdateStatus.innerText = "…"
                    threadUpdateLink.addEventListener("click", e => {
                        e.preventDefault()
                        $(document).trigger("thread_manual_refresh")
                    })
                    threadUpdateLink.href = "#"
                    threadUpdateLink.appendChild(new Text("Refresh: "))
                    threadUpdateLink.appendChild(threadUpdateStatus)
                    threadlink.classList.add("threadlink")
                    threadlink.appendChild(threadUpdateLink)
                    floaterLinkBox.classList.add("threadlinks")
                    floaterLinkBox.appendChild(threadlink)
                    container.appendChild(floaterLinkBox)
                }

                _domsetup_stats: {
                    const container = LCNSite.INSTANCE.getThreadStatsRContainer()
                    const span1 = document.createElement("span")
                    const span2 = document.createElement("span")
                    const span3 = document.createElement("span")
                    statUniqueIPs = document.getElementById("lcn-uniqueips")
                    statReplies = document.createElement("span")
                    statFiles = document.createElement("span")
                    statPage = document.createElement("span")

                    statReplies.id = "lcn_replies_n"
                    statReplies.innerText = "…"

                    statFiles.id = "lcn_files_n"
                    statReplies.innerText = "…"

                    statPage.id = "lcn_page_n"
                    statPage.innerText = "…"

                    span1.appendChild(new Text("Replies: "))
                    span1.appendChild(statReplies)
                    span2.appendChild(new Text("Files: "))
                    span2.appendChild(statFiles)
                    span3.appendChild(new Text("Page: "))
                    span3.appendChild(statPage)

                    for (const span of [ span1, span2, span3 ]) {
                        threadStatsItems.push(span)
                        container.appendChild(span)
                    }
                }

                $(document).trigger("thread_manual_refresh")
            } else {
                floaterLinkBox?.remove()
                floaterLinkBox = null
                statReplies = null
                statFiles = null
                statPage = null

                while (threadStatsItems.length) {
                    threadStatsItems.shift().remove()
                }
            }
        }

        kIsEnabled.onChange(onStateChangeFn)
        onStateChangeFn(kIsEnabled.getValue())
    }
})
