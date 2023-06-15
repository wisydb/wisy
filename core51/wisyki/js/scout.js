const VIEWPORT_VS_CLIENT_HEIGHT_RATIO = 0.65;

/**
 * Account.
 *
 * @class Account
 */
class Account {
    name;
    #occupation;
    #skills = {};
    lastvisited;
    #isLoggedIn;
    #path;
    #step;

    constructor() {
        this.#isLoggedIn = this.#checkLogin();
        this.#load();
    }

    reset() {
        if (this.#isLoggedIn) {
            // TODO: Delete scout progress on server.
        } else {
            localStorage.removeItem("account");
        }

        this.#occupation;
        this.#skills = {};
        this.#path = null;
        this.#step = null;
    }

    #checkLogin() {
        // TODO: Check if a user is logged into an account.
        return false;
    }

    /**
     * Retrieves a stored version of the users account from the localStorage.
     * If no valid account exists already, a fresh account is stored instead.
     *
     * @returns {void}
     */
    #load() {
        let storedAccountJSON;

        if (this.#isLoggedIn) {
            // TODO: Fetch Account from server.
        } else {
            storedAccountJSON = localStorage.getItem("account");
        }

        // No account stored yet.
        if (!storedAccountJSON) {
            return;
        }

        // Parse stored account and update class properties.
        try {
            const storedAccount = JSON.parse(storedAccountJSON);

            this.#occupation = storedAccount.occupation;
            this.#skills = storedAccount.skills || {};
            this.name = storedAccount.name;
            this.lastvisited = storedAccount.lastvisited;
            this.#step = storedAccount.step;
            this.#path = storedAccount.path;
        } catch (err) {
            console.error(`Error parsing "account" from localStorage:`, err);
        }
    }

    #store() {
        this.lastvisited = new Date().getTime();

        if (this.#isLoggedIn) {
            // TODO: Store account online.
        } else {
            // Store account in local storage.
            localStorage.setItem(
                "account",
                JSON.stringify({
                    occupation: this.#occupation,
                    skills: this.#skills,
                    name: this.name,
                    lastvisited: this.lastvisited,
                    path: this.#path,
                    step: this.#step,
                })
            );
        }
    }

    addSkill(label, uri, levelGoal = null, isLanguageSkill = null) {
        const skill = {
            uri: uri,
            label: label,
            levelGoal: levelGoal,
            isLanguageSkill: isLanguageSkill,
        };
        this.#skills[skill.uri] = skill;
        this.#store();
    }

    removeSkill(uri) {
        delete this.#skills[uri];
        this.#store();
    }

    getSkill(uri) {
        return this.#skills[uri];
    }

    getPath() {
        return this.#path;
    }

    setPath(path) {
        this.#path = path;
        this.#store();
    }

    getStep() {
        return this.#step;
    }

    setStep(step) {
        this.#step = step;
        this.#store();
    }
}

/**
 * Scout.
 *
 * @class Scout
 */
class Scout {
    /**
     * The diffrent paths available for the user to follow.
     * @type {Object}
     */
    paths;

    /**
     * The current path the user is on.
     * @type {Path}
     */
    currentPath;

    /**
     * The account controller.
     * @type {Account}
     */
    account;

    constructor() {
        this.account = new Account();

        // Init burgermenu.
        const menuBtn = document.querySelector(".menu-btn");
        menuBtn.addEventListener("click", () => {
            if (!menuBtn.classList.contains("open")) {
                menuBtn.classList.add("open");
            } else {
                menuBtn.classList.remove("open");
            }
        });

        this.paths = {
            occupation: new OccupationPath(),
            skill: new SkillPath(),
        };
    }

    getPath(pathname) {
        if (!pathname) {
            pathname = Object.keys(this.paths)[0];
        }
        return this.paths[pathname];
    }

    abort() {
        scout.account.reset();
        this.currentPath.update();
    }

    update(stepIndex = null) {
        if (!this.currentPath) {
            this.currentPath = this.getPath(scout.account.getPath());
        }

        if (!stepIndex) {
            stepIndex = scout.account.getStep()
        } else {
            if (stepIndex == "next") {
                stepIndex = this.checkStep(
                    this.currentPath.steps.indexOf(this.currentPath.currentStep) + 1
                );
            } else if (stepIndex == "prev") {
                stepIndex = this.checkStep(
                    this.currentPath.steps.indexOf(this.currentPath.currentStep) - 1
                );
            }
        }

        this.currentPath.update(stepIndex);
    }

    selectPath(pathname) {
        this.account.setPath(pathname);
        if (this.currentPath === this.getPath(pathname)) {
            // Nothing changes.
            return;
        }
        this.currentPath = this.getPath(pathname);

        console.log("Switch to " + pathname);
        this.currentPath.update(scout.account.getStep());
    }

    checkStep(index) {
        if (index == undefined) {
            index = 0;
        }

        // Check overflow.
        if (index >= this.currentPath.steps.length) {
            return this.currentPath.steps.length - 1;
        } else if (index < 0) {
            return 0;
        }

        if (index > 0 && !this.currentPath.steps[index].checkPrerequisites()) {
            index = this.checkStep(index - 1);
        }

        return index;
    }
}

/**
 * Abstract Class Path.
 *
 * @class Path
 */
class Path {
    /**
     * The name of the path.
     * @type {string}
     */
    name;

    /**
     * Whether the path has been rendered to the DOM.
     * @type {boolean}
     */
    rendered = false;

    /**
     * The individual steps of the path.
     * @type {Step[]}
     */
    steps;

    /**
     * The current step the user is on.
     * @type {Step}
     */
    currentStep;

    /**
     * Nav elements for the current path steps.
     * @type {NodeListOf<Element>}
     */
    scoutNavSteps;
    nextButton;
    prevButton;

    constructor() {
        if (this.constructor == Path) {
            throw new Error("Abstract classes can't be instantiated.");
        }
    }

    async #render() {
        const data = this.prepareData();

        const response = await fetch("core51/wisyki/templates/path.mustache");
        const template = await response.text();
        const html = Lang.render(template, data);
        document.getElementById("path").innerHTML = html;
        document.getElementById("path").setAttribute("currentPath", this.name);

        // Init #steps.
        this.stepsNode = document.querySelector("#steps");

        // Define UI action for aborting the scout.
        const scoutAbortBtn = document.querySelector("#scout-abort");
        scoutAbortBtn.addEventListener("click", () => scout.abort());

        // Define UI-action for ging to a specific step.
        this.scoutNavSteps = document.querySelectorAll(
            ".scout-nav__progress ul li"
        );
        const scoutNavBtns = document.querySelectorAll(
            ".scout-nav__progress ul li button"
        );
        scoutNavBtns.forEach((btn) => {
            btn.addEventListener("click", () =>
                this.update(btn.getAttribute("to-step"))
            );
        });

        // Define UI-action for going to the next or previous step.
        this.nextButton = document.querySelector("#next-step");
        this.prevButton = document.querySelector("#prev-step");
        this.nextButton.addEventListener("click", () => this.update("next"));
        this.prevButton.addEventListener("click", () => this.update("prev"));

        // Scroll the current step back into view, when user resizes the window.
        addEventListener("resize", () => {
            this.scrollToStep(this.steps.indexOf(this.currentStep));
        });

        // Disable and hide the "next"- and "prev"-buttons by default
        disable(this.nextButton);
        hide(this.nextButton, true);
        disable(this.prevButton);
        hide(this.prevButton, true);
    }

    async update(index) {
        if (!this.isRendered()) {
            await this.#render();
        }

        this.updateStep(index);
    }

    updateStep(stepIndex) {
        stepIndex = scout.checkStep(stepIndex);

        const step = this.steps[stepIndex];

        this.currentStep = step;
        scout.account.setStep(stepIndex);

        // Scroll to the step and hide the current step node
        this.scrollToStep(stepIndex);

        // Update step.
        this.currentStep.update();
    }

    isRendered() {
        return (
            document.getElementById("path").getAttribute("currentPath") ===
            this.name
        );
    }

    /**
     * Scrolls to the specified step with an optional delay.
     *
     * @param {number} stepindex The step to scroll to.
     * @param {number} [delay=0] The delay, in milliseconds, to wait before scrolling.
     *
     * @returns {Promise} A promise that resolves after the scroll was started.
     */
    scrollToStep(stepindex, delay = 0) {
        return new Promise((res, _rej) => {
            setTimeout(
                () =>
                    (this.stepsNode.style.left =
                        document.body.offsetWidth * stepindex * -1 + "px"),
                delay
            );
            res();
        });
    }

    prepareData() {
        const data = {};
        data.steps = [];
        for (let i = 0; i < this.steps.length; i++) {
            data.steps.push({
                index: i,
                nav_label: i + 1,
                name: this.steps[i].name,
            });
        }

        data.name = this.name;
        data.label = Lang.getString(this.name + "path:label");
        return data;
    }
}

/**
 * OccupationPath.
 *
 * @class OccupationPath
 * @extends {Path}
 */
class OccupationPath extends Path {
    constructor() {
        super();
        this.name = "occupation";
        this.steps = [
            new PathStep(),
            new OccupationStep(),
            new OccupationSkillsStep(),
        ];
    }

    prepareData() {
        const data = super.prepareData();
        data.label = Lang.getString("occupationpath:label");
        return data;
    }
}

/**
 * SkillPath.
 *
 * @class SkillPath
 * @extends {Path}
 */
class SkillPath extends Path {
    constructor() {
        super();
        this.name = "skill";
        this.steps = [new PathStep()];
    }

    prepareData() {
        const data = super.prepareData();
        return data;
    }
}

/**
 * Abstract Class Step.
 *
 * @class Step
 */
class Step {
    /**
     * The name of the step.
     * @type {string}
     */
    name;

    /**
     * Whether the step has been rendered.
     * @type {boolean}
     */
    rendered = false;

    /**
     * The DOM node of the step.
     * @type {HTMLElement}
     */
    node;

    constructor() {
        if (this.constructor == Step) {
            throw new Error("Abstract classes can't be instantiated.");
        }
    }

    prepareData() {
        const data = {};
        return data;
    }

    async #render() {
        const data = this.prepareData();
        const response = await fetch(
            "core51/wisyki/templates/" + this.name + "-step.mustache"
        );
        const template = await response.text();
        const html = Lang.render(template, data);
        this.node = document.getElementById(this.name);
        this.node.innerHTML = html;

        this.init();
    }

    init() {
        // To be implemented by children classes.
    }

    update() {
        if (!this.isRendered()) {
            console.log("is not rendered");
            this.#render();
        } else {
            console.log("is rendered");
        }
    }

    checkPrerequisites() {
        throw new Error("Abstract method needs to be implemented by subclass.");
    }

    isRendered() {
        return document.getElementById(this.name).children.length !== 0;
    }
}

/**
 * PathStep.
 *
 * @class PathStep
 * @extends {Step}
 */
class PathStep extends Step {
    constructor() {
        super();
        this.name = "pathchoice";
    }

    checkPrerequisites() {
        return true;
    }

    init() {
        const pathBtns = document.querySelectorAll(".path-button-list button");
        pathBtns.forEach((btn) => {
            btn.addEventListener("click", () => {
                scout.selectPath(btn.getAttribute("pathname"));
                scout.update('next');
            });
        });
    }

    prepareData() {
        const data = super.prepareData();
        data.paths = [];
        for (const key in scout.paths) {
            data.paths.push(scout.paths[key].prepareData());
        }

        return data;
    }
}

/**
 * OccupationStep.
 *
 * @class OccupationStep
 * @extends {Step}
 */
class OccupationStep extends Step {
    constructor() {
        super();
        this.name = "occupation";
    }

    checkPrerequisites() {
        return scout.account.getPath() != null;
    }

    prepareData() {
        const data = super.prepareData();
        data.clear
        return data;
    }
}

/**
 * OccupationSkillsStep.
 *
 * @class OccupationSkillsStep
 * @extends {Step}
 */
class OccupationSkillsStep extends Step {
    constructor() {
        super();
        this.name = "occupation-skills";
    }

    checkPrerequisites() {
        return (
            scout.account.getPath() != null && scout.account.occupation != null
        );
    }
}

// Detect the height of the app and update value of css property.
function setCSSPropertyDocHeight() {
    document.documentElement.style.setProperty(
        "--doc-height",
        `${window.innerHeight}px`
    );
}

function setVirtualKeyboardStatus(event) {
    if (
        (event.target.height * event.target.scale) / window.screen.height <
        VIEWPORT_VS_CLIENT_HEIGHT_RATIO
    ) {
        document.body.classList.add("virtual-keyboard-shown");
    } else {
        document.body.classList.remove("virtual-keyboard-shown");
    }
}

/**
 * Hides a given node by adding a CSS class to it.
 *
 * @param {HTMLElement} node - The node to hide.
 * @param {boolean} onlyVisibility - If true, only the visibility is hidden (CSS class 'hidden' is added), otherwise, the node is hidden completely (CSS class 'display-none' is added).
 *
 * @returns {void}
 */
function hide(node, onlyVisibility = false) {
    if (onlyVisibility) {
        node.classList.add("hidden");
    } else {
        node.classList.add("display-none");
    }
}

/**
 * This function shows the given node by removing the "hidden" and "display-none" classes.
 *
 * @param {Element} node - The node to show.
 *
 * @returns {void}
 */
function show(node) {
    node.classList.remove("hidden");
    node.classList.remove("display-none");
}

/**
 * This function disables the given node by adding the "disabled" class and the "disabled" attribute with the value "True".
 *
 * @param {Element} node - The node to disable.
 *
 * @returns {void}
 */
function disable(node) {
    node.classList.add("disabled");
    node.setAttribute("disabled", "True");
}

/**
 * This function enables the given node by removing the "disabled" class and the "disabled" attribute.
 * It also shows the node by calling the "show" function.
 *
 * @param {Element} node - The node to enable.
 *
 * @returns {void}
 */
function enable(node) {
    node.classList.remove("disabled");
    node.removeAttribute("disabled");
    show(node);
}

class Lang {
    static #langstrings;
    static #langcode;

    static async init(langcode = "de") {
        Lang.#langcode = langcode;
        const filepath = "core51/wisyki/lang/" + Lang.#langcode + ".json";
        const response = await fetch(filepath);
        Lang.#langstrings = await response.json();
    }

    static getString(key) {
        if (!Lang.#langstrings.hasOwnProperty(key)) {
            throw new Error(
                'Key "' +
                    key +
                    '" not found in lang file "' +
                    Lang.#langcode +
                    '"'
            );
        }
        return Lang.#langstrings[key];
    }

    static render(template, view, partials, config) {
        view.lang = Lang.#langstrings;
        return Mustache.render(template, view, partials, config)
    }
}