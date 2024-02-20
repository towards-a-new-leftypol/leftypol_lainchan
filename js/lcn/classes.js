/**
 * @file Supporting classes for leftychan javascript.
 * @author jonsmy
 */

globalThis.LCNSite = class LCNSite {
    static INSTANCE = null;

    #isModerator = document.body.classList.contains("is-moderator");
    #isThreadPage = document.body.classList.contains("active-thread");
    #isBoardPage = document.body.classList.contains("active-board");
    #isCatalogPage = document.body.classList.contains("active-catalog");

    #isModPage = location.pathname == "/mod.php";
    #isModRecentsPage = this.#isModPage && (location.search == "?/recent" || location.search.startsWith("?/recent/"));
    #isModReportsPage = this.#isModPage && (location.search == "?/reports" || location.search.startsWith("?/reports/"));
    #isModLogPage   = this.#isModPage && (location.search == "?/log" || location.search.startsWith("?/log/"));

    "isModerator" () { return this.#isModerator; }
    "isThreadPage" () { return this.#isThreadPage; }
    "isBoardPage" () { return this.#isBoardPage; }
    "isCatalogPage" () { return this.#isCatalogPage; }

    "isModPage" () { return this.#isModPage; }
    "isModRecentsPage" () { return this.#isModRecentsPage; }
    "isModReportsPage" () { return this.#isModReportsPage; }
    "isModLogPage" () { return this.#isModLogPage; }

    #unseen = 0;
    "getUnseen" () { return this.#unseen; }
    "clearUnseen" () { if (this.#unseen != 0) { this.setUnseen(0); } }
    "setUnseen" (int) {
        const bool = !!int
        if (bool != !!this.#unseen) {
            this.setFaviconType(bool ? "reply" : null)
        }
        this.#unseen = int
        this.#doTitleUpdate()
    }

    #pageTitle = document.title;
    "getTitle" () { return this.#pageTitle; }
    "setTitle" (title) { this.#pageTitle = title; this.#doTitleUpdate(); }
    #doTitleUpdate () { document.title = (this.#unseen > 0 ? `(${this.#unseen}) ` : "") + this.#pageTitle; }

    #favicon = document.querySelector("head > link[rel=\"shortcut icon\"]");
    "setFaviconType" (type=null) {
        if (this.#favicon == null) {
            this.#favicon = document.createElement("link")
            this.#favicon.rel = "shortcut icon"
            document.head.appendChild(this.#favicon)
        }

        this.#favicon.href = `/favicon${type ? "-" + type : ""}.ico`
    }
}

globalThis.LCNPostInfo = class LCNPostInfo {

    #boardId = null;
    #threadId = null;
    #postId = null;
    #name = null;
    #email = null;
    #capcode = null;
    #flag = null;
    #ip = null;
    #subject = null;
    #createdAt = null;

    #parent = null;
    #isThread = false;
    #isReply = false;
    #isLocked = false;
    #isSticky = false;

    static "assign" (post) { return post.$LCNPostInfo ?? (post.$LCNPostInfo = this.from(post)); }
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

    "getParent" () { return this.#parent; }
    "__setParent" (inst) { return this.#parent = inst; }

    "getBoardId" () { return this.#boardId; }
    "getThreadId" () { return this.#threadId; }
    "getPostId" () { return this.#postId; }
    "getHref" () { return `/${this.boardId}/res/${this.threadId}.html#q${this.postId}`; }

    "getName" () { return this.#name; }
    "getEmail" () { return this.#email; }
    "getIP" () { return this.#ip; }
    "getCapcode" () { return this.#capcode; }
    "getSubject" () { return this.#subject; }
    "getCreatedAt" () { return this.#createdAt; }

    "isSticky" () { return this.#isSticky; }
    "isLocked" () { return this.#isLocked; }
    "isThread" () { return this.#isThread; }
    "isReply" () { return this.#isReply; }

    "is" (info) {
        assert.ok(info, "Must be LCNPost.")
        return this.getBoardId() == info.getBoardId() && this.getPostId() == info.getPostId()
    }

}

globalThis.LCNPost = class LCNPost {

    #parent = null;
    #post = null;
    #info = null;
    #ipLink = null;
    #controls = null;
    #customControlsSeperatorNode = null;

    static "assign" (post) { return post.$LCNPost ?? (post.$LCNPost = this.from(post)); }
    static "from" (post) { return new this(post); }

    "constructor" (post) {
        assert.ok(post.classList.contains("post"), "Arty must be expected Element.")
        const intro = post.querySelector(".intro")
        this.#post = post
        this.#info = LCNPostInfo.assign(post)
        this.#ipLink = intro.querySelector(".ip-link")
        this.#controls = arrLast(post.querySelectorAll(".controls"))

        assert.equal(this.#info.getParent(), null, "Info should not have parent.")
        this.#info.__setParent(this)
    }

    "jQuery" () { return $(this.#post); }
    "trigger" (event_id, data=null) { $(this.#post).trigger(event_id, [ data ]); }

    "getElement" () { return this.#post; }
    "getInfo" () { return this.#info; }

    "getIPLink" () { return this.#ipLink; }
    "setIP" (ip) { this.#ipLink.innerText = ip; }

    "getParent" () { return this.#parent; }
    "__setParent" (inst) { return this.#parent = inst; }

    static #NBSP = String.fromCharCode(160);
    "addCustomControl" (obj) {
        if (LCNSite.INSTANCE.isModerator()) {
            const link = document.createElement("a")
            link.innerText = `[${obj.btn}]`
            link.title = obj.tooltip

            if (typeof obj.href == "string") {
                link.href = obj.href
                link.referrerPolicy = "no-referrer"
            } else if (obj.onClick != undefined) {
                link.style.cursor = "pointer"
                link.addEventListener("click", e => { e.preventDefault(); obj.onClick(this); })
            }

            if (this.#customControlsSeperatorNode == null) {
                this.#controls.insertBefore(this.#customControlsSeperatorNode = new Text(`${this.constructor.#NBSP}-${this.constructor.#NBSP}`), this.#controls.firstElementChild)
            } else {
                this.#controls.insertBefore(new Text(this.constructor.#NBSP), this.#customControlsSeperatorNode)
            }

            this.#controls.insertBefore(link, this.#customControlsSeperatorNode)
        }
    }

}

globalThis.LCNThread = class LCNThread {

    #parent = null;
    #thread = null;
    #op = null;

    static "assign" (thread) { return thread.$LCNThread ?? (thread.$LCNThread = this.from(thread)); }
    static "from" (thread) { return new this(thread); }

    "constructor" (thread) {
        assert.ok(thread.classList.contains("thread"), "Arty must be expected Element.")
        this.#thread = thread
        this.#op = LCNPost.assign(this.#thread.querySelector(".post.op"))

        assert.equal(this.#op.getParent(), null, "Op should not have parent.")
        this.#op.__setParent(this)
    }

    "getElement" () { return this.#thread; }
    "getContent" () { return this.#op; }
    "getPosts" () { return Array.prototype.map.apply(this.#thread.querySelectorAll(".post"), [ el => LCNPost.assign(el) ]); }
    "getReplies" () { return Array.prototype.map.apply(this.#thread.querySelectorAll(".post:not(.op)"), [ el => LCNPost.assign(el) ]); }

    "getParent" () { return this.#parent; }
    "__setParent" (inst) { return this.#parent = inst; }
}


globalThis.LCNPostContainer = class LCNPostContainer {

    #parent = null;
    #container = null;
    #content = null;
    #postId = null;
    #boardId = null;

    static "assign" (container) { return container.$LCNPostContainer ?? (container.$LCNPostContainer = this.from(container)); }
    static "from" (container) { return new this(container); }

    "constructor" (container) {
        assert.ok(container.classList.contains("postcontainer"), "Arty must be expected Element.")
        const child = container.querySelector(".thread, .post")
        this.#container = container
        this.#content = child.classList.contains("thread") ? LCNThread.assign(child) : LCNPost.assign(child)
        this.#boardId = container.dataset.board
        this.#postId = container.id.slice(2)

        assert.equal(this.#content.getParent(), null, "Content should not have parent.")
        this.#content.__setParent(this)
    }

    "getContainer" () { return this.#container; }
    "getContent" () { return this.#content; }
    "getBoardId" () { return this.#boardId; }
    "getPostId" () { return this.#postId; }

    "getParent" () { return this.#parent; }
    "__setParent" (inst) { return this.#parent = inst; }

}

globalThis.LCNPostWrapper = class LCNPostWrapper {

    #wrapper = null;
    #eitaLink = null;
    #eitaId = null;
    #eitaHref = null
    #content = null;

    static "assign" (wrapper) { return wrapper.$LCNPostWrapper ?? (wrapper.$LCNPostWrapper = this.from(wrapper)); }
    static "from" (wrapper) { return new this(wrapper); }

    "constructor" (wrapper) {
        assert.ok(wrapper.classList.contains("post-wrapper"), "Arty must be expected Element.")
        this.#wrapper = wrapper
        this.#eitaLink = wrapper.querySelector(".eita-link")
        this.#eitaId = this.#eitaLink.id
        this.#eitaHref = this.#eitaLink.href
        void Array.prototype.find.apply(wrapper.children, [
            el => {
                if (el.classList.contains("thread")) {
                    return this.#content = LCNThread.assign(el)
                } else if (el.classList.contains("postcontainer")) {
                    return this.#content = LCNPostContainer.assign(el)
                }
            }
        ])

        assert.ok(this.#content, "Wrapper should contain content.")
        assert.equal(this.#content.getParent(), null, "Content should not have parent.")
        this.#content.__setParent(this)
    }

    "getPost" () {
        const post = this.getContent().getContent()
        assert.ok(post instanceof LCNPost, "Post should be LCNPost.")
        return post
    }

    "getElement" () { return this.#wrapper; }
    "getContent" () { return this.#content; }
    "getEitaId" () { return this.#eitaId; }
    "getEitaHref" () { return this.#eitaHref; }
    "getEitaLink" () { return this.#eitaLink; }

}

globalThis.LCNPost.all = () => Array.prototype.map.apply(document.querySelectorAll(".post:not(.grid-li)"), [ node => LCNPost.assign(node) ]);
globalThis.LCNThread.all = () => Array.prototype.map.apply(document.querySelectorAll(".thread:not(.grid-li)"), [ node => LCNThread.assign(node) ]);
globalThis.LCNPostContainer.all = () => Array.prototype.map.apply(document.querySelectorAll(".postcontainer"), [ node => LCNPostContainer.assign(node) ]);
globalThis.LCNPostWrapper.all = () => Array.prototype.map.apply(document.querySelectorAll(".post-wrapper"), [ node => LCNPostWrapper.assign(node) ]);

$().ready(() => {
    LCNSite.INSTANCE = new LCNSite();

    const clazzes = [ LCNPost, LCNThread, LCNPostContainer, LCNPostWrapper ]
    for (const clazz of clazzes) {
        clazz.forEach = fn => clazz.all().forEach(fn)
        clazz.filter = fn => clazz.all().filter(fn)
        clazz.find = fn => clazz.all().find(fn)
    }

    // XXX: May be a cleaner way to do this but this should be fine for now.
    for (const clazz of clazzes) { void clazz.all(); }
    $(document).on("new_post", (e, post) => {
        if (LCNSite.INSTANCE.isModRecentsPage()) {
            void LCNPostWrapper.all()
        } else {
            void LCNPostContainer.all()
        }
    })
})
