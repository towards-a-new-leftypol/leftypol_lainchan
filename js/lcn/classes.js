/**
 * @file Supporting classes for leftychan javascript.
 * @author jonsmy
 */

globalThis.LCNSite = class LCNSite {

    static "createAbortable" () {
        const obj = { "abort": null, "controller": null, "signal": null }
        const setupController = () => {
            obj.controller = new AbortController()
            obj.signal = obj.controller.signal
        }

        obj.abort = () => {
            obj.controller.abort()
            setupController()
        }

        setupController()
        return obj
    }

    static "getThreadFromPages" (pages, thread_id) {
        for (const page of pages) {
            for (const thread of page.threads) {
                if (thread_id == String(thread.no)) {
                    return { "page": page.page, ...thread }
                }
            }
        }

        return null
    };

    static "constructor" () {
        this._isModerator = document.body.classList.contains("is-moderator");

        this._isThreadPage = document.body.classList.contains("active-thread");
        this._isBoardPage = document.body.classList.contains("active-board");
        this._isCatalogPage = document.body.classList.contains("active-catalog");

        this._isModPage = location.pathname == "/mod.php";
        this._isModRecentsPage = this._isModPage && (location.search == "?/recent" || location.search.startsWith("?/recent/"));
        this._isModReportsPage = this._isModPage && (location.search == "?/reports" || location.search.startsWith("?/reports/"));
        this._isModLogPage   = this._isModPage && (location.search == "?/log" || location.search.startsWith("?/log/"));
        this._unseen = 0;
        this._pageTitle = document.title;

        this._doTitleUpdate = () => {
            document.title = (this._unseen > 0 ? `(${this._unseen}) ` : "") + this._pageTitle;
        };

        this._favicon = document.querySelector("head > link[rel=\"shortcut icon\"]");
        this._generatedStyle = null;
    }

    "isModerator" () { return this._isModerator; }
    "isThreadPage" () { return this._isThreadPage; }
    "isBoardPage" () { return this._isBoardPage; }
    "isCatalogPage" () { return this._isCatalogPage; }

    "isModPage" () { return this._isModPage; }
    "isModRecentsPage" () { return this._isModRecentsPage; }
    "isModReportsPage" () { return this._isModReportsPage; }
    "isModLogPage" () { return this._isModLogPage; }

    "getUnseen" () { return this._unseen; }
    "clearUnseen" () { if (this._unseen != 0) { this.setUnseen(0); } }
    "setUnseen" (int) {
        const bool = !!int
        if (bool != !!this._unseen) {
            this.setFaviconType(bool ? "reply" : null)
        }
        this._unseen = int
        this._doTitleUpdate()
    }

    "getTitle" () { return this._pageTitle; }
    "setTitle" (title) { this._pageTitle = title; this._doTitleUpdate(); }

    "setFaviconType" (type=null) {
        if (this._favicon == null) {
            this._favicon = document.createElement("link")
            this._favicon.rel = "shortcut icon"
            document.head.appendChild(this._favicon)
        }

        this._favicon.href = `/favicon${type ? "-" + type : ""}.ico`
    }

    "getFloaterLContainer" () { return document.getElementById("bar-bottom-l"); }
    "getFloaterRContainer" () { return document.getElementById("bar-bottom-r"); }
    "getThreadStatsLContainer" () { return document.getElementById("lcn-threadstats-l"); }
    "getThreadStatsRContainer" () { return document.getElementById("lcn-threadstats-r"); }

    "writeCSSStyle" (origin, stylesheet) {
        if (this._generatedStyle == null && (this._generatedStyle = document.querySelector("head > style.generated-css")) == null) {
            this._generatedStyle = document.createElement("style")
            this._generatedStyle.classList.add("generated-css")
            document.head.appendChild(this._generatedStyle)
        }
        this._generatedStyle.textContent += `${this._generatedStyle.textContent.length ? "\n\n" : ""}/*** Generated by ${origin} ***/\n${stylesheet}`
    }

}

LCNSite.INSTANCE = null;

globalThis.LCNPostInfo = class LCNPostInfo {

    static "constructor" () {
        this._boardId = null;
        this._threadId = null;
        this._postId = null;
        this._name = null;
        this._email = null;
        this._capcode = null;
        this._flag = null;
        this._ip = null;
        this._subject = null;
        this._createdAt = null;
        this._parent = null;
        this._isThread = false;
        this._isReply = false;
        this._isLocked = false;
        this._isSticky = false;
    }


    static "assign" (post) { return post[this.nodeAttrib] ?? (post[this.nodeAttrib] = this.from(post)); }
    static "from" (post) {
        assert.ok(post.classList.contains("post"), "Arty must be expected Element.")
        const inst = new this()
        const intro = post.querySelector(".intro")
        const link = intro.querySelector(".post_no:not([id])").href.split("/").reverse()
        inst.#postId = link[0].slice(link[0].indexOf("#q") + 2)
        inst.#threadId = link[0].slice(0, link[0].indexOf("."))
        inst.#boardId = link[2]
        inst.#isThread = post.classList.contains("op")
        inst.#isReply = !inst.#isThread

        inst.#subject = intro.querySelector(".subject")?.innerText ?? null
        inst.#name = intro.querySelector(".name")?.innerText ?? null
        inst.#email = intro.querySelector(".email")?.href.slice(7) ?? null
        inst.#flag = intro.querySelector(".flag")?.src.split("/").reverse()[0].slice(0, -4) ?? null

        inst.#capcode = intro.querySelector(".capcode")?.innerText ?? null
        inst.#ip = intro.querySelector(".ip-link")?.innerText ?? null
        inst.#createdAt = new Date(intro.querySelector("time[datetime]").dateTime ?? NaN)

        inst.#isSticky = !!intro.querySelector("i.fa-thumb-tack")
        inst.#isLocked = !!intro.querySelector("i.fa-lock")

        return inst
    }


//     "getParent" () { return this.#parent; }
//     "__setParent" (inst) { return this.#parent = inst; }
// 
//     "getBoardId" () { return this.#boardId; }
//     "getThreadId" () { return this.#threadId; }
//     "getPostId" () { return this.#postId; }
//     "getHref" () { return `/${this.boardId}/res/${this.threadId}.html#q${this.postId}`; }
// 
//     "getName" () { return this.#name; }
//     "getEmail" () { return this.#email; }
//     "getIP" () { return this.#ip; }
//     "getCapcode" () { return this.#capcode; }
//     "getSubject" () { return this.#subject; }
//     "getCreatedAt" () { return this.#createdAt; }
// 
//     "isSticky" () { return this.#isSticky; }
//     "isLocked" () { return this.#isLocked; }
//     "isThread" () { return this.#isThread; }
//     "isReply" () { return this.#isReply; }
// 
//     "is" (info) {
//         assert.ok(info, "Must be LCNPost.")
//         return this.getBoardId() == info.getBoardId() && this.getPostId() == info.getPostId()
//     }
// 
}

LCNPostInfo.nodeAttrib = "$LCNPostInfo";
LCNPostInfo.selector = ".post:not(.grid-li)";

// globalThis.LCNPost = class LCNPost {
// 
//     static nodeAttrib = "$LCNPost";
//     static selector = ".post:not(.grid-li)";
//     #parent = null;
//     #post = null;
//     #info = null;
//     #ipLink = null;
//     #controls = null;
//     #customControlsSeperatorNode = null;
// 
//     static "assign" (post) { return post[this.nodeAttrib] ?? (post[this.nodeAttrib] = this.from(post)); }
//     static "from" (post) { return new this(post); }
// 
//     "constructor" (post) {
//         assert.ok(post.classList.contains("post"), "Arty must be expected Element.")
//         const intro = post.querySelector(".intro")
//         this.#post = post
//         this.#info = LCNPostInfo.assign(post)
//         this.#ipLink = intro.querySelector(".ip-link")
//         this.#controls = Array.prototype.at.apply(post.querySelectorAll(".controls"), [ -1 ])
// 
//         assert.equal(this.#info.getParent(), null, "Info should not have parent.")
//         this.#info.__setParent(this)
//     }
// 
//     "jQuery" () { return $(this.#post); }
//     "trigger" (event_id, data=null) { $(this.#post).trigger(event_id, [ data ]); }
// 
//     "getElement" () { return this.#post; }
//     "getInfo" () { return this.#info; }
// 
//     "getIPLink" () { return this.#ipLink; }
//     "setIP" (ip) { this.#ipLink.innerText = ip; }
// 
//     "getParent" () { return this.#parent; }
//     "__setParent" (inst) { return this.#parent = inst; }
// 
//     static #NBSP = String.fromCharCode(160);
//     "addCustomControl" (obj) {
//         if (LCNSite.INSTANCE.isModerator()) {
//             const link = document.createElement("a")
//             link.innerText = `[${obj.btn}]`
//             link.title = obj.tooltip
// 
//             if (typeof obj.href == "string") {
//                 link.href = obj.href
//                 link.referrerPolicy = "no-referrer"
//             } else if (obj.onClick != undefined) {
//                 link.style.cursor = "pointer"
//                 link.addEventListener("click", e => { e.preventDefault(); obj.onClick(this); })
//             }
// 
//             if (this.#customControlsSeperatorNode == null) {
//                 this.#controls.insertBefore(this.#customControlsSeperatorNode = new Text(`${this.constructor.#NBSP}-${this.constructor.#NBSP}`), this.#controls.firstElementChild)
//             } else {
//                 this.#controls.insertBefore(new Text(this.constructor.#NBSP), this.#customControlsSeperatorNode)
//             }
// 
//             this.#controls.insertBefore(link, this.#customControlsSeperatorNode)
//         }
//     }
// 
// }
// 
// globalThis.LCNThread = class LCNThread {
// 
//     static nodeAttrib = "$LCNThread";
//     static selector = ".thread:not(.grid-li)";
//     #element = null;
//     #parent = null;
//     #op = null;
// 
//     static "assign" (thread) { return thread[this.nodeAttrib] ?? (thread[this.nodeAttrib] = this.from(thread)); }
//     static "from" (thread) { return new this(thread); }
// 
//     "constructor" (thread) {
//         assert.ok(thread.classList.contains("thread"), "Arty must be expected Element.")
//         this.#element = thread
//         this.#op = LCNPost.assign(this.#element.querySelector(".post.op"))
// 
//         //assert.equal(this.#op.getParent(), null, "Op should not have parent.")
//         this.#op.__setParent(this)
//     }
// 
//     "getElement" () { return this.#element; }
//     "getContent" () { return this.#op; }
//     "getPosts" () { return Array.prototype.map.apply(this.#element.querySelectorAll(".post"), [ el => LCNPost.assign(el) ]); }
//     "getReplies" () { return Array.prototype.map.apply(this.#element.querySelectorAll(".post:not(.op)"), [ el => LCNPost.assign(el) ]); }
// 
//     "getParent" () { return this.#parent; }
//     "__setParent" (inst) { return this.#parent = inst; }
// }
// 
// 
// globalThis.LCNPostContainer = class LCNPostContainer {
// 
//     static nodeAttrib = "$LCNPostContainer";
//     static selector = ".postcontainer";
//     #parent = null;
//     #element = null;
//     #content = null;
//     #postId = null;
//     #boardId = null;
// 
//     static "assign" (container) { return container[this.nodeAttrib] ?? (container[this.nodeAttrib] = this.from(container)); }
//     static "from" (container) { return new this(container); }
// 
//     "constructor" (container) {
//         assert.ok(container.classList.contains("postcontainer"), "Arty must be expected Element.")
//         const child = container.querySelector(".thread, .post")
//         this.#element = container
//         this.#content = child.classList.contains("thread") ? LCNThread.assign(child) : LCNPost.assign(child)
//         this.#boardId = container.dataset.board
//         this.#postId = container.id.slice(2)
// 
//         assert.equal(this.#content.getParent(), null, "Content should not have parent.")
//         this.#content.__setParent(this)
//     }
// 
//     "getElement" () { return this.#element; }
//     "getContent" () { return this.#content; }
//     "getBoardId" () { return this.#boardId; }
//     "getPostId" () { return this.#postId; }
// 
//     "getParent" () { return this.#parent; }
//     "__setParent" (inst) { return this.#parent = inst; }
// 
// }
// 
// globalThis.LCNPostWrapper = class LCNPostWrapper {
// 
//     static nodeAttrib = "$LCNPostWrapper";
//     static selector = ".post-wrapper";
//     #wrapper = null;
//     #eitaLink = null;
//     #eitaId = null;
//     #eitaHref = null
//     #content = null;
// 
//     static "assign" (wrapper) { return wrapper[this.nodeAttrib] ?? (wrapper[this.nodeAttrib] = this.from(wrapper)); }
//     static "from" (wrapper) { return new this(wrapper); }
// 
//     "constructor" (wrapper) {
//         assert.ok(wrapper.classList.contains("post-wrapper"), "Arty must be expected Element.")
//         this.#wrapper = wrapper
//         this.#eitaLink = wrapper.querySelector(".eita-link")
//         this.#eitaId = this.#eitaLink.id
//         this.#eitaHref = this.#eitaLink.href
//         void Array.prototype.find.apply(wrapper.children, [
//             el => {
//                 if (el.classList.contains("thread")) {
//                     return this.#content = LCNThread.assign(el)
//                 } else if (el.classList.contains("postcontainer")) {
//                     return this.#content = LCNPostContainer.assign(el)
//                 }
//             }
//         ])
// 
//         assert.ok(this.#content, "Wrapper should contain content.")
//         assert.equal(this.#content.getParent(), null, "Content should not have parent.")
//         this.#content.__setParent(this)
//     }
// 
//     "getPost" () {
//         const post = this.getContent().getContent()
//         assert.ok(post instanceof LCNPost, "Post should be LCNPost.")
//         return post
//     }
// 
//     "getElement" () { return this.#wrapper; }
//     "getContent" () { return this.#content; }
//     "getEitaId" () { return this.#eitaId; }
//     "getEitaHref" () { return this.#eitaHref; }
//     "getEitaLink" () { return this.#eitaLink; }
// 
// }
// 
// globalThis.LCNSetting = class LCNSetting {
//     #id = null;
//     #eventId = null;
//     #label = null;
//     #value = null;
//     #valueDefault = null;
// 
//     static "build" (id) { return new this(id); }
// 
//     "constructor" (id) {
//         this.#id = id;
//         this.#eventId = `lcnsetting::${this.#id}`
//     }
// 
//     #getValue () {
//         const v = localStorage.getItem(this.#id)
//         if (v != null) {
//             return this.__builtinValueImporter(v)
//         } else {
//             return this.#valueDefault
//         }
//     }
// 
//     "getValue" () { return this.#value ?? (this.#value = this.#getValue()); }
//     "setValue" (v) {
//         if (this.#value !== v) {
//             this.#value = v
//             localStorage.setItem(this.#id, this.__builtinValueExporter(this.#value))
//             setTimeout(() => $(document).trigger(`${this.#eventId}::change`, [ v, this ]), 1)
//         }
//     }
// 
//     "getLabel" () { return this.#label; }
//     "setLabel" (label) { this.#label = label; return this; }
// 
//     "getDefaultValue" () { return this.#valueDefault; }
//     "setDefaultValue" (vd) { this.#valueDefault = vd; return this; }
// 
//     "onChange" (fn) { $(document).on(`${this.#eventId}::change`, (_,v,i) => fn(v, i)); }
//     __setIdPrefix (prefix) { this.#id = `${prefix}_${this.#id}`; }
// }
// 
// globalThis.LCNToggleSetting = class LCNToggleSetting extends LCNSetting {
//     __builtinValueImporter (v) { return v == "1"; }
//     __builtinValueExporter (v) { return v ? "1" : ""; }
//     __builtinDOMConstructor () {
//         const div = document.createElement("div")
//         const chk = document.createElement("input")
//         const txt = document.createElement("label")
//         txt.innerText = this.getLabel()
//         chk.type = "checkbox"
//         chk.checked = this.getValue()
//         chk.addEventListener("click", e => {
//             e.preventDefault();
//             this.setValue(!this.getValue())
//         })
//         this.onChange(v => chk.checked = v)
// 
//         div.appendChild(chk)
//         div.appendChild(txt)
//         return div
//     }
// }
// 
// globalThis.LCNSettingsSubcategory = class LCNSettingsSubcategory {
// 
//     #tab_id = null;
//     #id = null;
// 
//     #fieldset = null;
//     #legend = null;
//     #label = null;
// 
//     static "for" (tab_id, id) {
//         const domid = `lcnssc_${tab_id}_${id}`
//         const inst = document.getElementById(domid)?.$LCNSettingsSubcategory
//         if (inst == null) {
//             const fieldset = document.createElement("fieldset")
//             const legend = document.createElement("legend")
//             fieldset.id = domid
//             fieldset.appendChild(legend)
// 
//             // XXX: extend_tab only takes a string so this hacky workaround is used to let us use the regular dom api
//             Options.extend_tab(tab_id, `<div id="__${domid}" hidden></div>`)
//             const div = document.getElementById(`__${domid}`)?.parentElement
//             assert.ok(div)
// 
//             div.replaceChildren(fieldset)
//             return new this(tab_id, id, fieldset)
//         } else {
//             return inst
//         }
//     }
// 
//     "constructor" (tab_id, id, fieldset) {
//         this.#tab_id = tab_id
//         this.#id = id
//         this.#fieldset = fieldset
//         this.#legend = this.#fieldset.querySelector("legend")
//         this.#fieldset.$LCNSettingsSubcategory = this
//     }
// 
//     "getLabel" () { return this.#label; }
//     "setLabel" (label) { this.#legend.innerText = this.#label = label; return this; }
//     "addSetting" (setting) {
//         assert.ok(setting instanceof LCNSetting)
//         setting.__setIdPrefix(`lcnsetting_${this.#tab_id}_${this.#id}`)
//         if (setting.__builtinDOMConstructor != null) {
//             const div = setting.__builtinDOMConstructor()
//             div.classList.add("lcn-setting-entry")
//             this.#fieldset.appendChild(div)
//         }
// 
//         return this
//     }
// 
// }
// 
// $().ready(() => {
//     LCNSite.INSTANCE = new LCNSite();
// 
//     for (const clazz of [ LCNPost, LCNPostInfo, LCNThread, LCNPostContainer, LCNPostWrapper ]) {
//         clazz.allNodes = (node=document) => node.querySelectorAll(clazz.selector)
//         clazz.all = (node=document) => Array.prototype.map.apply(clazz.allNodes(node), [ elem => clazz.assign(elem) ]);
//         clazz.clear = (node=document) => Array.prototype.forEach.apply(clazz.allNodes(node), [ elem => elem[clazz.nodeAttrib] = null ])
//         clazz.forEach = (fn, node=document) => clazz.allNodes(node).forEach(elem => fn(clazz.assign(elem)))
//         clazz.filter = (fn, node=document) => clazz.all(node).filter(fn)
//         clazz.find = fn => clazz.all().find(fn)
//         clazz.first = (node=document) => clazz.assign(node.querySelector(clazz.selector))
//         clazz.last = (node=document) => clazz.assign(Array.prototype.at.apply(clazz.allNodes(node), [ -1 ]))
//     }
// 
//     // XXX: May be a cleaner way to do this but this should be fine for now.
//     for (const clazz of [ LCNPostContainer, LCNPostWrapper, LCNThread, LCNPost ]) { void clazz.all(); }
//     $(document).on("new_post", (e, post) => {
//         if (LCNSite.INSTANCE.isModRecentsPage()) {
//             void LCNPostWrapper.all()
//         } else {
//             void LCNPostContainer.all()
//         }
//     })
// 
//     $(window).on("focus", () => LCNSite.INSTANCE.clearUnseen())
//     $(document.body).on("mousemove", () => LCNSite.INSTANCE.clearUnseen())
// })
