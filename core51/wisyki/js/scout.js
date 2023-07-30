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
    #filters = {};

    constructor() {
        this.#isLoggedIn = this.#checkLogin();
        const scoutid = false; // TODO: Get id from url.
        this.#load(scoutid);
    }

    reset() {
        if (this.#isLoggedIn) {
            // TODO: Delete scout progress on server.
        } else {
            localStorage.removeItem("account");
        }

        this.#occupation = null;
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
    #load(id = false) {
        let storedAccountJSON;

        if (this.#isLoggedIn && id) {
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
            this.#filters = storedAccount.filters;
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

            // {
            //     occupation: {
            //         label: "Polizist/Polizistin",
            //         uri: "http://data.europa.eu/esco/occupation/5793c124-c037-47b2-85b6-dd4a705968dc",
            //     },
            //     skills: {
            //         "http://data.europa.eu/esco/skill/87454307-a3ad-40ce-8024-1aeb7c94f1e3":
            //             {
            //                 uri: "http://data.europa.eu/esco/skill/87454307-a3ad-40ce-8024-1aeb7c94f1e3",
            //                 label: "Verkehr regeln",
            //                 levelGoal: null,
            //                 isLanguageSkill: false,
            //             },
            //         "http://data.europa.eu/esco/skill/96de2e86-e287-41f2-88ab-15a2343afc6f":
            //             {
            //                 uri: "http://data.europa.eu/esco/skill/96de2e86-e287-41f2-88ab-15a2343afc6f",
            //                 label: "mit Konfliktsituationen umgehen",
            //                 levelGoal: null,
            //                 isLanguageSkill: false,
            //             },
            //         "http://data.europa.eu/esco/skill/87cadd76-4e3a-47e5-b66d-559fbc1e8993":
            //             {
            //                 uri: "http://data.europa.eu/esco/skill/87cadd76-4e3a-47e5-b66d-559fbc1e8993",
            //                 label: "auf Anfragen antworten",
            //                 levelGoal: null,
            //                 isLanguageSkill: false,
            //             },
            //         "Kakaobohnen kosten": {
            //             uri: "Kakaobohnen kosten",
            //             label: "Kakaobohnen kosten",
            //             levelGoal: null,
            //             isLanguageSkill: false,
            //         },
            //     },
            //     filters: {
            //         "maxprice": 1000,
            //         "coursemode": "online"
            //     },
            //     lastvisited: 1689674096956,
            //     path: "occupation",
            //     step: 2,
            // }

            localStorage.setItem(
                "account",
                JSON.stringify({
                    occupation: this.#occupation,
                    skills: this.#skills,
                    name: this.name,
                    lastvisited: this.lastvisited,
                    path: this.#path,
                    step: this.#step,
                    filters: this.#filters,
                })
            );
        }
    }

    async setSkill(
        label,
        uri,
        levelGoal = null,
        isLanguageSkill = null,
        isOccupationSkill = null
    ) {
        const skill = {
            uri: uri,
            label: label,
            levelGoal: levelGoal,
            isLanguageSkill: isLanguageSkill,
            isOccupationSkill: isOccupationSkill,
        };
        this.#skills[uri] = skill;
        this.#store();

        await this.updateSkillInfo(skill);
    }

    async updateSkillInfo(skill) {
        if (skill.isLanguageSkill != null && skill.isOccupationSkill != null) {
            return;
        }
        const skillInfo = await this.getSkillInfo(skill.uri);
        if (!skillInfo) {
            skill.isLanguageSkill = false;
            skill.isOccupationSkill = false;
        }
        if (skill.isLanguageSkill == null) {
            skill.isLanguageSkill = skillInfo.isLanguageSkill;
        }
        if (skill.isOccupationSkill == null) {
            skill.isOccupationSkill = skillInfo.isOccupationSkill;
        }
        this.#skills[skill.uri] = skill;
        this.#store();
    }

    removeSkill(uri) {
        delete this.#skills[uri];
        this.#store();
    }

    getSkills() {
        return this.#skills;
    }

    getSkill(uri) {
        return this.#skills[uri];
    }

    getFilters() {
        return this.#filters;
    }

    getFilter(filter) {
        return this.#filters[filter];
    }

    setFilter(filter, value) {
        this.#filters[filter] = value;
        this.#store();
    }

    getPath() {
        return this.#path;
    }

    getOccupation() {
        return this.#occupation;
    }

    setOccupation(label, uri) {
        const occupation = {
            label: label,
            uri: uri,
        };

        this.#occupation = occupation;
        // Reset skills.
        this.#skills = {};
        this.#store();
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

    /**
     * Determines if a given skill URI is a language skill by making an HTTP request to the server.
     *
     * @param {string} uri - The URI of the skill to check.
     *
     * @returns {Promise} - A Promise that resolves to true if the skill is a language skill, false otherwise.
     */
    async getSkillInfo(uri) {
        // Return false if uri does not start with http://data.europa.eu/esco/skill/ and is therefore not an actual esco uri.
        if (!uri || !uri.startsWith("http://data.europa.eu/esco/skill/")) {
            return false;
        }
        const params = { uri: uri };
        if (this.#occupation) {
            params.occupationuri = this.#occupation.uri;
        }
        const url = "./esco/getSkillInfo?" + new URLSearchParams(params);
        const response = await fetch(url);

        if (response.ok) {
            return await response.json();
        } else {
            console.error("HTTP-Error: " + response.status);
        }
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

    /**
     * UI element to go to the next step.
     */
    nextButton;
    prevButton;

    constructor() {
        this.account = new Account();

        this.paths = {
            occupation: new OccupationPath(this),
            skill: new SkillPath(this),
        };

        this.init();
    }

    init() {
        // Init burgermenu.
        const menuBtn = document.querySelector(".menu-btn");
        menuBtn.addEventListener("click", () => {
            if (!menuBtn.classList.contains("open")) {
                menuBtn.classList.add("open");
            } else {
                menuBtn.classList.remove("open");
            }
        });

        // Define UI-action for going to the next or previous step.
        this.nextButton = document.querySelector("#next-step");
        this.prevButton = document.querySelector("#prev-step");
        this.nextButton.addEventListener("click", () => this.update("next"));
        this.prevButton.addEventListener("click", () => this.update("prev"));

        // Disable and hide the "next"- and "prev"-buttons by default
        disable(this.nextButton);
        hide(this.nextButton, "visibility");
        disable(this.prevButton);
        hide(this.prevButton, "visibility");
    }

    getPath(pathname) {
        if (!pathname) {
            pathname = Object.keys(this.paths)[0];
        }
        return this.paths[pathname];
    }

    abort() {
        this.account.reset();
        this.currentPath.update();
    }

    update(stepIndex = null) {
        if (!this.currentPath) {
            this.currentPath = this.getPath(this.account.getPath());
        }

        if (!stepIndex) {
            stepIndex = this.account.getStep();
        } else {
            if (stepIndex == "next") {
                stepIndex = this.checkStep(
                    this.currentPath.steps.indexOf(
                        this.currentPath.currentStep
                    ) + 1
                );
            } else if (stepIndex == "prev") {
                stepIndex = this.checkStep(
                    this.currentPath.steps.indexOf(
                        this.currentPath.currentStep
                    ) - 1
                );
            }
        }

        this.currentPath.update(stepIndex);
    }

    async selectPath(pathname) {
        if (this.account.getPath() === pathname) {
            // Nothing changes.
            return;
        }
        this.account.reset();
        this.account.setPath(pathname);
        this.currentPath = this.getPath(pathname);

        await this.currentPath.update(this.account.getStep());
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
     * The scout.
     * @type {Scout}
     */
    scout;

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
     * Scout nav node.
     * @type {Element}
     */
    scoutNavNode;

    /**
     * Nav elements for the current path steps.
     * @type {NodeListOf<Element>}
     */
    scoutNavSteps;

    node;

    constructor(scout) {
        if (this.constructor == Path) {
            throw new Error("Abstract classes can't be instantiated.");
        }

        this.scout = scout;
    }

    async #render() {
        const data = this.prepareData();

        const response = await fetch("core51/wisyki/templates/path.mustache");
        const template = await response.text();
        const html = Lang.render(template, data);
        this.node = document.querySelector("main");
        this.node.innerHTML = html;
        this.node.setAttribute("currentPath", this.name);

        this.init();
    }

    init() {
        // Init steps.
        this.steps.forEach((step) => step.init());
        // Init main .steps.
        this.stepsNode = this.node.querySelector(".steps > div");

        // Define UI action for aborting the scout.
        const scoutAbortBtn = this.node.querySelector("#scout-abort");
        scoutAbortBtn.addEventListener("click", () => this.scout.abort());

        // Define UI-action for ging to a specific step.
        this.scoutNavNode = this.node.querySelector(".scout-nav");
        this.scoutNavSteps = this.node.querySelectorAll(
            ".scout-nav__progress ul li"
        );
        const scoutNavBtns = this.node.querySelectorAll(
            ".scout-nav__progress ul li button"
        );
        scoutNavBtns.forEach((btn) => {
            btn.addEventListener("click", () =>
                this.update(btn.getAttribute("to-step"))
            );
        });

        // Scroll the current step back into view, when user resizes the window.
        addEventListener("resize", () => {
            this.scrollToStep(this.steps.indexOf(this.currentStep));
        });

        // Set up the level explanation modal and its associated button.
        const modalBtn = this.node.querySelector(".open-modal-btn");
        this.helpModal = this.node.querySelector(".modal");
        modalBtn.addEventListener("click", () => this.toggleHelp());

        // Close the modal if the close button is clicked or somewhere outside of the modal.
        this.helpModal.addEventListener(
            "click",
            (event) => {
                if (
                    event.target.closest(".close-modal-btn") ||
                    event.target.matches(".modal__backdrop")
                ) {
                    hide(this.helpModal);
                }
            },
            false
        );

        this.help = this.node.querySelector(".modal__content article");
    }

    async update(index) {
        if (!this.isRendered()) {
            await this.#render();
        }

        // Set the amount of steps -1 as a css prpoerty.
        document.documentElement.style.setProperty(
            "--steps",
            `${this.steps.length - 1}`
        );

        this.updateStep(index);
    }

    async updateStep(stepIndex) {
        stepIndex = this.scout.checkStep(stepIndex);

        const step = this.steps[stepIndex];

        this.currentStep = step;
        this.scout.account.setStep(stepIndex);

        // Update navigation bar.
        this.updateScoutNav();
        // Scroll to the step and hide the current step node
        this.scrollToStep(stepIndex);

        this.currentStep.showLoader();

        // Update Helpmodal content.
        this.help.innerHTML = Lang.getString(
            this.currentStep.name + "step:help"
        );
        // Update step.
        await this.currentStep.update();

        this.currentStep.hideLoader();
    }

    updateScoutNav() {
        this.scoutNavNode.classList.remove("hide-future-steps");
        if (this.currentStep.hideNav()) {
            this.scoutNavNode.classList.add("hide-future-steps");
        }

        this.scoutNavSteps.forEach((element) => {
            element.classList.remove("done");
            element.classList.remove("current-step");
        });

        const currentStepIndex = this.steps.indexOf(this.currentStep);

        for (let i = 0; i <= currentStepIndex; i++) {
            this.scoutNavSteps[i].classList.add("done");
        }

        this.scoutNavSteps[currentStepIndex].classList.add("current-step");
    }

    isRendered() {
        return (
            document.querySelector("main").getAttribute("currentPath") ===
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

    /**
     * Toggles the display of the help modal.
     */
    toggleHelp() {
        if (this.helpModal.classList.contains("display-none")) {
            show(this.helpModal);
        } else {
            hide(this.helpModal);
        }
    }
}

/**
 * OccupationPath.
 *
 * @class OccupationPath
 * @extends {Path}
 */
class OccupationPath extends Path {
    constructor(scout) {
        super(scout);
        this.name = "occupation";
        this.steps = [
            new PathStep(this.scout, this),
            new OccupationStep(this.scout, this),
            new OccupationSkillsStep(this.scout, this),
            new SkillsStep(this.scout, this),
            new LevelGoalStep(this.scout, this),
            new CouseListStep(this.scout, this),
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
    constructor(scout) {
        super(scout);
        this.name = "skill";
        this.steps = [
            new PathStep(this.scout, this),
            new SkillsStep(this.scout, this),
            new LevelGoalStep(this.scout, this),
            new CouseListStep(this.scout, this),
        ];
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
     * The scout.
     * @type {Scout}
     */
    scout;

    /**
     * The path.
     * @type {Path}
     */
    path;

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

    /**
     * The DOM node of the step loader.
     * @type {HTMLElement}
     */
    loader;

    data = {};

    partials = {};

    constructor(scout, path) {
        if (this.constructor == Step) {
            throw new Error("Abstract classes can't be instantiated.");
        }

        this.scout = scout;
        this.path = path;
    }

    async prepareTemplate() {}

    init() {
        // Get the node for the current step and loader.
        this.node = document.getElementById(this.name);
        this.loader = this.node.parentNode.querySelector(
            ".loader:not(.hidden)"
        );
    }

    async update() {
        if (!this.isRendered()) {
            await this.#render();
        }

        this.updateNavButtons();
    }

    async #render() {
        await this.prepareTemplate();
        const response = await fetch(
            "core51/wisyki/templates/" + this.name + "-step.mustache"
        );
        const template = await response.text();

        const html = Lang.render(template, this.data, this.partials);
        this.node.innerHTML = html;

        this.initRender();
    }

    initRender() {}

    showLoader() {
        hide(this.node, "visibility");
        // Hide the step, show the loader.
        if (this.loader) {
            show(this.loader);
        }
    }

    hideLoader() {
        // If a loader element was found, hide it and show the current step node with a delay.
        if (this.loader) {
            hide(this.loader, "visibility");
            setTimeout(() => hide(this.loader), 300);
            setTimeout(() => show(this.node), 100);
        } else {
            show(this.node);
        }
    }

    isFirst() {
        return this.path.steps.indexOf(this.path.currentStep) == 0;
    }

    isLast() {
        return (
            this.path.steps.indexOf(this.path.currentStep) ==
            this.path.steps.length - 1
        );
    }

    nextStep() {
        if (this.isLast()) {
            return null;
        }
        return this.path.steps[
            this.path.steps.indexOf(this.path.currentStep) + 1
        ];
    }

    updateNavButtons() {
        if (this.isFirst()) {
            hide(this.scout.prevButton, "visibility");
            disable(this.scout.prevButton);
        } else {
            show(this.scout.prevButton);
            enable(this.scout.prevButton);
        }

        if (this.isLast()) {
            hide(this.scout.nextButton, "visibility");
            disable(this.scout.nextButton);
        } else {
            show(this.scout.nextButton);
            const nextStep = this.nextStep();
            if (nextStep && nextStep.checkPrerequisites()) {
                enable(this.scout.nextButton);
            } else {
                disable(this.scout.nextButton);
            }
        }
    }

    checkPrerequisites() {
        throw new Error("Abstract method needs to be implemented by subclass.");
    }

    isRendered() {
        return document.getElementById(this.name).children.length !== 0;
    }

    hideNav() {
        return false;
    }
}

/**
 * PathStep.
 *
 * @class PathStep
 * @extends {Step}
 */
class PathStep extends Step {
    constructor(scout, path) {
        super(scout, path);

        this.name = "pathchoice";
    }

    checkPrerequisites() {
        return true;
    }

    initRender() {
        super.initRender();
        const pathBtns = this.node.querySelectorAll(
            "#pathchoice .button-list button"
        );
        pathBtns.forEach((btn) => {
            btn.addEventListener("click", async () => {
                await this.scout.selectPath(btn.getAttribute("pathname"));
                this.scout.update("next");
            });
        });
    }

    updateNavButtons() {
        if (this.isFirst()) {
            hide(this.scout.prevButton, "visibility");
            disable(this.scout.prevButton);
        } else {
            show(this.scout.prevButton);
            enable(this.scout.prevButton);
        }

        if (this.isLast()) {
            hide(this.scout.nextButton, "visibility");
            disable(this.scout.nextButton);
        } else {
            show(this.scout.nextButton);
            disable(this.scout.nextButton);
        }
    }

    async prepareTemplate() {
        this.data.paths = [];
        for (const key in this.scout.paths) {
            this.data.paths.push(this.scout.paths[key].prepareData());
        }
    }

    hideNav() {
        return true;
    }
}

/**
 * OccupationStep.
 *
 * @class OccupationStep
 * @extends {Step}
 */
class OccupationStep extends Step {
    /**
     * Autocompleter
     * @type {Autocompleter}
     */
    autocompleter;

    constructor(scout, path) {
        super(scout, path);
        this.name = "occupation";
    }

    initRender() {
        super.initRender();
        // Set up UI-elements.
        this.autocompleter = new Autocompleter(this, (label, uri) =>
            this.setOccupation(label, uri)
        );
    }

    async update() {
        await super.update();

        // Set input value.
        const occupation = this.scout.account.getOccupation();
        if (occupation) {
            this.autocompleter.inputElm.value = occupation.label;
            // Clear the output element.
            this.autocompleter.clearOutput();
        }
    }

    checkPrerequisites() {
        return this.scout.account.getPath() == this.path.name;
    }

    setOccupation(label, uri) {
        this.scout.account.setOccupation(label, uri);
        this.updateNavButtons();
    }
}

/**
 * OccupationSkillsStep.
 *
 * @class OccupationSkillsStep
 * @extends {Step}
 */
class OccupationSkillsStep extends Step {
    occupationNode;
    skillsNode;
    occupation;
    maxSkills;
    skillCounterTemplate;
    selectedOccupationNode;
    skillCounterNode;

    constructor(scout, path) {
        super(scout, path);
        this.name = "occupationskills";

        // this.maxSkills = 5;
    }

    async prepareTemplate() {
        if (this.maxSkills) {
            this.data.maxskills = this.maxSkills;
        }
    }

    checkPrerequisites() {
        return (
            this.scout.account.getPath() == this.path.name &&
            this.scout.account.getOccupation() != null
        );
    }

    initRender() {
        super.initRender();

        // Set up the UI components.
        this.occupationNode = this.node.querySelector(".selected-occupation");
        this.skillsNode = this.node.querySelector(".selectable-skills ul");
        this.selectedOccupationNode = this.node.querySelector(
            ".selected-occupation"
        );
        this.skillCounterNode = this.node.querySelector(".skill-counter");

        if (this.maxSkills) {
            this.skillCounterTemplate = Lang.getString("skillcountmaxtemplate");
            this.node.querySelector(".max-skills").textContent = this.maxSkills;
        } else {
            this.skillCounterTemplate = Lang.getString("skillcounttemplate");
        }
    }

    async update() {
        await super.update();

        const occupation = this.scout.account.getOccupation();
        // Update the displayed selected occupation.
        this.selectedOccupationNode.textContent = occupation.label;

        if (!this.occupation || this.occupation != occupation) {
            this.occupation = occupation;
            const loader = this.node.parentNode.querySelector(".loader.hidden");

            if (loader) {
                show(loader);
            }

            this.occupationNode.textContent = occupation.label;
            const suggestions = await this.suggestSkills(occupation.uri);
            this.showSkillSuggestions(suggestions);

            if (loader) {
                hide(loader);
            }
        }

        this.updateSkillSelection();
    }

    /**
     * Suggests skills based on the given ESCO concept URI.
     *
     * @param {string} uri the URI to get skills for.
     *
     * @returns {Promise<Object>} An object containing a URI and an array of skills.
     */
    async suggestSkills(uri) {
        if (!uri.startsWith("http://data.europa.eu/esco/")) {
            return { uri: uri };
        }

        const limit = 10;
        const params = { uri: uri, limit: limit, onlyrelevant: true };

        const url = "./esco/getConceptSkills?" + new URLSearchParams(params);
        const response = await fetch(url);

        if (response.ok) {
            return await response.json();
        } else {
            console.error("HTTP-Error: " + response.status);
        }
    }

    /**
     * Fills the suggestion list in the UI with the given suggestions.
     *
     * @param {object} suggestions The suggestions to be displayed.
     *
     * @returns {void}
     */
    showSkillSuggestions(suggestions) {
        if (!suggestions.title) {
            return;
        }

        while (this.skillsNode.lastChild) {
            this.skillsNode.removeChild(this.skillsNode.lastChild);
        }

        const checkboxes = [];

        // For every suggested skill, display a checkbox.
        // Check if there are skills that are already selected and set the checkbox state accordingly.
        for (const uri in suggestions.skills) {
            const suggestion = suggestions.skills[uri];
            const li = document.createElement("li");
            const checkbox = document.createElement("input");
            checkbox.setAttribute("type", "checkbox");
            // checkbox.setAttribute("label", suggestion.label);
            checkbox.setAttribute("id", this.name + uri);
            checkbox.setAttribute("skilluri", uri);
            li.appendChild(checkbox);
            const label = document.createElement("label");
            label.setAttribute("for", this.name + uri);
            label.textContent = suggestion.label;
            li.appendChild(label);
            checkboxes.push(checkbox);

            // On change event, add or remove the skill associated with the checkbox and update the view.
            checkbox.addEventListener("change", async () => {
                if (checkbox.checked) {
                    this.scout.account.setSkill(suggestion.label, uri);
                } else {
                    this.scout.account.removeSkill(uri);
                }
                this.updateSkillSelection(checkboxes);
            });

            const skills = this.scout.account.getSkills();
            if (uri in skills) {
                checkbox.setAttribute("checked", "True");
                this.skillsNode.insertBefore(li, this.skillsNode.firstChild);
            } else {
                this.skillsNode.appendChild(li);
            }
        }
    }

    /**
     * Updates the checkboxes' selection state according to the currently selected skills.
     * Disables the "add more skills" button if the maximum number of skills is already selected.
     *
     * @param {Array} checkboxes An array of the checkboxes for each skill.
     *
     * @returns {void}
     */
    updateSkillSelection(checkboxes = null) {
        if (checkboxes == null) {
            checkboxes = this.skillsNode.querySelectorAll("input");
        }

        const skills = this.scout.account.getSkills();
        const skillcount = Object.keys(skills).length;

        checkboxes.forEach((checkbox) => {
            // Check the checkbox if the skill is selected.
            if (checkbox.getAttribute("skilluri") in skills) {
                checkbox.checked = true;
            } else {
                checkbox.checked = false;
            }

            // Disable input if the max amount of selectable skills is reached.
            enable(checkbox);
            if (this.maxSkills && skillcount >= this.maxSkills) {
                if (!checkbox.checked) {
                    disable(checkbox);
                }
            }
        });

        this.updateSkillCounter();
    }

    /**
     * Updates the skill counter on the webpage.
     * Retrieves the skills of the scout's account and calculates the number of skills.
     * Updates the HTML of the skill counter node with the rendered skill counter template.
     *
     * @returns {void}
     */
    updateSkillCounter() {
        const skills = this.scout.account.getSkills();
        const skillcount = Object.keys(skills).length;
        const data = {
            skillcount: skillcount,
            maxskills: this.maxSkills,
        };
        this.skillCounterNode.innerHTML = Mustache.render(
            this.skillCounterTemplate,
            data
        );
    }
}

/**
 * SkillsStep.
 *
 * @class SkillsStep
 * @extends {Step}
 */
class SkillsStep extends Step {
    /**
     * Autocompleter
     * @type {Autocompleter}
     */
    autocompleter;
    selectedOtherSkillsNode;
    selectedOccupationSkillsNode;
    maxSkills;
    skillCounterTemplate;
    skillCounterNode;

    constructor(scout, path) {
        super(scout, path);
        this.name = "skills";
        // this.maxSkills = 5;
    }

    async prepareTemplate() {
        if (this.maxSkills) {
            this.data.maxskills = this.maxSkills;
        }
        if (this.scout.account.getOccupation()) {
            this.data.occupation = this.scout.account.getOccupation().label;
        }
    }

    checkPrerequisites() {
        return this.scout.account.getPath() == this.path.name;
    }

    initRender() {
        super.initRender();

        // Set up UI-elements.
        this.selectedOtherSkillsNode = this.node.querySelector(
            ".selectable-skills .otherskills ul"
        );
        this.selectedOccupationSkillsNode = this.node.querySelector(
            ".selectable-skills .occupationskills ul"
        );
        this.skillCounterNode = this.node.querySelector(".skill-counter");

        this.autocompleter = new Autocompleter(this, async (label, uri) => {
            this.scout.account.setSkill(label, uri).then(() => {
                this.showSelectedSkills();
            });
            this.autocompleter.clearInput();
            this.updateNavButtons();
        });

        if (this.maxSkills) {
            this.skillCounterTemplate = Lang.getString("skillcountmaxtemplate");
        } else {
            this.skillCounterTemplate = Lang.getString("skillcounttemplate");
        }
    }

    async update() {
        await super.update();

        this.showSelectedSkills(true);
    }

    /**
     * Fills the suggestion list in the UI with the given suggestions.
     *
     * @param {object} suggestions The suggestions to be displayed.
     *
     * @returns {void}
     */
    showSelectedSkills(rebuild = false) {
        if (rebuild) {
            while (this.selectedOtherSkillsNode.lastChild) {
                this.selectedOtherSkillsNode.removeChild(
                    this.selectedOtherSkillsNode.lastChild
                );
            }
            if (this.selectedOccupationSkillsNode) {
                while (this.selectedOccupationSkillsNode.lastChild) {
                    this.selectedOccupationSkillsNode.removeChild(
                        this.selectedOccupationSkillsNode.lastChild
                    );
                }
            }
        }

        const checkboxes = [];

        // For every selected skill, display a checkbox.
        // Check if there are skills that are already selected and set the checkbox state accordingly.
        const skills = this.scout.account.getSkills();
        for (const uri in skills) {
            let unchecked = null;
            const skill = skills[uri];
            if (skill.isOccupationSkill == null) {
                continue;
            }
            const selectedSkillsNode =
                skill.isOccupationSkill && this.selectedOccupationSkillsNode
                    ? this.selectedOccupationSkillsNode
                    : this.selectedOtherSkillsNode;
            // Check if the checkbox already exists.
            let checkbox = selectedSkillsNode.querySelector(
                'input[name="' + skill.label + '"]'
            );
            // If checkbox exist, skip making a new one.
            if (checkbox) {
                if (checkbox.checked) {
                    checkboxes.push(checkbox);
                    continue;
                } else {
                    unchecked = checkbox;
                }
            }

            checkbox = document.createElement("input");
            const li = document.createElement("li");
            checkbox.setAttribute("type", "checkbox");
            checkbox.setAttribute("name", skill.label);
            checkbox.setAttribute("id", this.name + uri);
            checkbox.setAttribute("skilluri", uri);
            li.appendChild(checkbox);
            const label = document.createElement("label");
            label.setAttribute("for", this.name + uri);
            label.textContent = skill.label;
            li.appendChild(label);
            if (unchecked) {
                selectedSkillsNode.insertBefore(li, unchecked.parentNode);
                selectedSkillsNode.removeChild(unchecked.parentNode);
            } else {
                selectedSkillsNode.insertBefore(
                    li,
                    selectedSkillsNode.firstChild
                );
            }
            checkboxes.push(checkbox);

            // On change event, add or remove the skill associated with the checkbox and update the view.
            checkbox.addEventListener("change", async (event) => {
                if (event.target.checked) {
                    this.scout.account.setSkill(skill.label, uri).then(() => {
                        this.showSelectedSkills();
                    });
                } else {
                    this.scout.account.removeSkill(uri);
                    this.showSelectedSkills();
                }
                this.updateSkillSelection(checkboxes);
                this.updateNavButtons();
            });
        }

        // Show message if there are no skills selected yet.
        this.updateIsEmptyMessage();

        this.updateSkillSelection(checkboxes);
    }

    updateIsEmptyMessage() {
        let isEmptyMessage =
            this.selectedOtherSkillsNode.querySelector(".is-empty");

        if (
            isEmptyMessage &&
            this.selectedOtherSkillsNode.querySelector("input")
        ) {
            isEmptyMessage.remove();
        } else if (
            !isEmptyMessage &&
            !this.selectedOtherSkillsNode.querySelector("input")
        ) {
            isEmptyMessage = document.createElement("li");
            isEmptyMessage.className = "is-empty";
            isEmptyMessage.textContent = Lang.getString(
                this.name + "step:noskillsselected"
            );
            this.selectedOtherSkillsNode.appendChild(isEmptyMessage);
        }

        if (this.selectedOccupationSkillsNode) {
            isEmptyMessage =
                this.selectedOccupationSkillsNode.querySelector(".is-empty");
            if (
                isEmptyMessage &&
                this.selectedOccupationSkillsNode.querySelector("input")
            ) {
                isEmptyMessage.remove();
            } else if (
                !isEmptyMessage &&
                !this.selectedOccupationSkillsNode.querySelector("input")
            ) {
                isEmptyMessage = document.createElement("li");
                isEmptyMessage.className = "is-empty";
                isEmptyMessage.textContent = Lang.getString(
                    this.name + "step:noskillsselected"
                );
                this.selectedOccupationSkillsNode.appendChild(isEmptyMessage);
            }
        }
    }

    /**
     * Updates the checkboxes' selection state according to the currently selected skills.
     * Disables the "add more skills" button if the maximum number of skills is already selected.
     *
     * @param {Array} checkboxes An array of the checkboxes for each skill.
     *
     * @returns {void}
     */
    updateSkillSelection(checkboxes) {
        const skills = this.scout.account.getSkills();
        const skillcount = Object.keys(skills).length;

        // Disable autocomplet inpput if max amount of selectable skills is reached.
        if (skillcount >= this.maxSkills) {
            disable(this.autocompleter.inputElm);
        } else {
            enable(this.autocompleter.inputElm);
        }

        checkboxes.forEach((checkbox) => {
            if (checkbox.getAttribute("skilluri") in skills) {
                checkbox.checked = true;
            } else {
                checkbox.checked = false;
            }

            enable(checkbox);
            if (skillcount >= this.maxSkills) {
                if (!checkbox.checked) {
                    disable(checkbox);
                }
            }
        });

        this.updateSkillCounter();
    }

    updateSkillCounter() {
        const skills = this.scout.account.getSkills();
        const skillcount = Object.keys(skills).length;
        const data = {
            skillcount: skillcount,
            maxskills: this.maxSkills,
        };
        this.skillCounterNode.innerHTML = Mustache.render(
            this.skillCounterTemplate,
            data
        );
    }
}

/**
 * SkillsStep.
 *
 * @class SkillsStep
 * @extends {Step}
 */
class LevelGoalStep extends Step {
    levelSelectionList;

    constructor(scout, path) {
        super(scout, path);
        this.name = "levelgoal";
    }

    checkPrerequisites() {
        return (
            this.scout.account.getPath() == this.path.name &&
            Object.keys(this.scout.account.getSkills()).length
        );
    }

    initRender() {
        super.initRender();

        // Create and update level goal selection list
        this.levelSelectionList = this.node.querySelector(
            "ul.level-goal-selection"
        );
    }

    async update() {
        await super.update();

        this.updateLevelSelection();
    }

    async updateLevelSelection() {
        while (this.levelSelectionList.lastChild) {
            this.levelSelectionList.removeChild(
                this.levelSelectionList.lastChild
            );
        }

        const skills = this.scout.account.getSkills();
        const data = { skills: [] };
        for (const uri in skills) {
            const skill = skills[uri];
            data.skills.push(skill);
        }

        const response = await fetch(
            "core51/wisyki/templates/levelgoal-selection.mustache"
        );
        const template = await response.text();
        const html = Lang.render(template, data);
        // Append html.
        this.levelSelectionList.innerHTML = html;

        for (const uri in skills) {
            const skill = skills[uri];
            if (skill.levelGoal) {
                const input = document.getElementById(
                    skill.uri + "-" + skill.levelGoal
                );
                if (input) {
                    input.checked = true;
                }
            }
        }

        // Get a reference to all the radio buttons in the group
        const fieldsets = this.levelSelectionList.querySelectorAll("fieldset");

        // Add an event listener to each radio button in the group
        fieldsets.forEach((fieldset) => {
            const skill = skills[fieldset.getAttribute("skilluri")];

            fieldset.addEventListener("change", () => {
                // Get the value of the selected radio button
                const selectedInput = fieldset.querySelector("input:checked");
                this.scout.account.setSkill(
                    skill.label,
                    skill.uri,
                    selectedInput.value,
                    skill.isLanguageSkill,
                    skill.isOccupationSkill
                );
            });

            // If not set, set default levelGoal.
            if (skill.levelGoal) {
                const currentSelection = fieldset.querySelector(
                    'input[value="' + skill.levelGoal + '"'
                );
                currentSelection.checked = true;
            } else {
                const firstInput = fieldset.querySelector("input");
                firstInput.checked = true;
                this.scout.account.setSkill(
                    skill.label,
                    skill.uri,
                    firstInput.value,
                    skill.isLanguageSkill,
                    skill.isOccupationSkill
                );
            }
        });
    }
}

/**
 * SkillsStep.
 *
 * @class SkillsStep
 * @extends {Step}
 */
class CouseListStep extends Step {
    filterMenu;
    resultList;

    constructor(scout, path) {
        super(scout, path);
        this.name = "courselist";

        this.filterMenu = new FilterMenu(this, () =>
            this.updateCourseResults()
        );
    }

    checkPrerequisites() {
        return (
            this.scout.account.getPath() == this.path.name &&
            Object.keys(this.scout.account.getSkills()).length
        );
    }

    async prepareTemplate() {
        this.partials.filtermenu = await this.filterMenu.getTemplate();

        this.data = await this.filterMenu.getData();
    }

    initRender() {
        super.initRender();
        this.filterMenu.initRender();

        this.resultList = this.node.querySelector(".result-list");
    }

    async update() {
        await super.update();

        await this.updateCourseResults();
    }

    async updateCourseResult(skill, detailsNode) {
        const courselistNode = detailsNode.querySelector(".course-list");
        const courselistCountNode = detailsNode.querySelector(".result-list__count");
        hide(courselistNode, "opacity");

        let courselist;
        this.results.sets.forEach((set) => {
            if (set.label == skill.label) {
                courselist = set.results;
                return;
            }
        });
        const data = {
            results: this.getFilteredCourselist(courselist, skill.levelGoal),
        }
        console.log(data);
        const response = await fetch(
            "core51/wisyki/templates/courselist-step-courselist.mustache"
        );
        const template = await response.text();
        const html = Lang.render(template, data);
        // Append html.
        while (courselistNode.lastChild) {
            courselistNode.removeChild(courselistNode.lastChild);
        }
        courselistNode.innerHTML = html;
        show(courselistNode);
        courselistCountNode.textContent = this.getKurseCountString(data.results.length);
    }

    getTemplateData(skills) {
        const data = {
            count: this.results.count,
            sets: []
        };
        this.results.sets.forEach((set) => {
            let filteredResults = []
            let label = set.label;
            let currentSkill;
            if (label == 'airecommends') {
                label = Lang.getString('courseliststep:airecommends');
                filteredResults = set.results;
            } else {
                for (let skill of Object.values(skills)) {
                    if (skill.label == label) {
                        currentSkill = skill;
                    }
                }

                filteredResults = this.getFilteredCourselist(set.results, currentSkill.levelGoal);
            }

            const filteredSet = {
                label: label,
                countstring: this.getKurseCountString(filteredResults.length),
                results: filteredResults
            };
            if (currentSkill) {
                filteredSet.skill = currentSkill;
            }
            data.sets.push(filteredSet);
        });

        return data;
    }

    getKurseCountString(count) {
        let countstring = count + " " + Lang.getString("courses");
        if (count == 1) {
            countstring = count + " " + Lang.getString("course");
        }
        return countstring;
    }

    getFilteredCourselist(courses, level) {
        const filteredResults = [];
        courses.forEach((course) => {
            if (course.levels.includes(level)) {
                filteredResults.push(course);
            }
        });
        return filteredResults;
    }

    async updateCourseResults() {
        // Get course suggestions and set template data.
        const skills = this.scout.account.getSkills();
        this.filterMenu.updateCourseCount(-1);
        this.results = await this.search();

        const data = this.getTemplateData(skills);

        this.filterMenu.updateCourseCount(data.count);

        // Get mustache templates.
        const [resultsTemplate, courselistTemplate] = await Promise.all([
            fetch(
                "core51/wisyki/templates/courselist-step-results.mustache"
            ).then((response) => response.text()),
            fetch(
                "core51/wisyki/templates/courselist-step-courselist.mustache"
            ).then((response) => response.text()),
        ]);
        const html = Lang.render(resultsTemplate, data, {
            courselist: courselistTemplate,
        });

        while (this.resultList.lastChild) {
            this.resultList.removeChild(this.resultList.lastChild);
        }
        // Append html.
        this.resultList.innerHTML = html;

        // Get all the <details> elements
        const accordions = this.resultList.querySelectorAll("details");
        if (accordions.length) {
            accordions[0].open = true;

            // Add event listeners to handle accordion interactions
            accordions.forEach((accordion) => {
                // When a <details> element is toggled (opened or closed)
                accordion.addEventListener("toggle", (event) => {
                    if (event.target.open) {
                        // If this section is opened, close all other sections
                        accordions.forEach((item) => {
                            if (item !== event.target) {
                                item.open = false;
                            }
                        });
                    }
                });
            });
        }

        // Get a reference to all the radio buttons in the group
        const complevelFilters = this.resultList.querySelectorAll(
            "fieldset.complevel-filter"
        );

        complevelFilters.forEach((complevelFilter) => {
            // Add an event listener to each radio button in the group
            const skill = skills[complevelFilter.getAttribute("skilluri")];

            complevelFilter.addEventListener("change", () => {
                // Get the value of the selected radio button
                const selectedInput =
                    complevelFilter.querySelector("input:checked");
                selectedInput.checked = true;
                skill.levelGoal = selectedInput.value;
                this.scout.account.setSkill(
                    skill.label,
                    skill.uri,
                    skill.levelGoal,
                    skill.isLanguageSkill,
                    skill.isOccupationSkill
                );

                this.updateCourseResult(skill, complevelFilter.parentNode);
            });

            // If not set, set default levelGoal.
            if (skill.levelGoal) {
                const currentSelection = complevelFilter.querySelector(
                    'input[value="' + skill.levelGoal + '"'
                );
                currentSelection.checked = true;
            } else {
                const firstInput = complevelFilter.querySelector("input");
                this.scout.account.setSkill(
                    skill.label,
                    skill.uri,
                    firstInput.value,
                    skill.isLanguageSkill,
                    skill.isOccupationSkill
                );
            }
        });
    }

    /**
     * Gets search results for courses based on the users skill goals.
     *
     * @returns {Object} The response object containing the search ID and the number of courses found.
     */
    async search() {
        const params = {
            prepare: false,
            skills: JSON.stringify(this.scout.account.getSkills()),
            filters: JSON.stringify(this.scout.account.getFilters()),
        };

        const url = "./scout-search?" + new URLSearchParams(params);
        const response = await fetch(url);

        if (response.ok) {
            return await response.json();
        } else {
            console.error("HTTP-Error: " + response.status);
        }
    }
}

class Filter {
    menu;
    selectedChoice = [];

    constructor(menu) {
        this.menu = menu;
    }

    async render() {
        const response = await fetch(
            "core51/wisyki/templates/" + this.name + "-filter.mustache"
        );
        const template = await response.text();

        return Lang.render(template);
    }

    initRender() {
        this.node = this.menu.node.querySelector("#filter-" + this.name);
        this.selectedChoice = this.menu.step.scout.account.getFilter(this.name);

        this.node.addEventListener("change", (event) => this.onChange(event));
    }

    onChange(event) {
        this.storeChoice(event.target);
        this.loadChoice();

        this.menu.onChange();
    }

    loadChoice() {}

    storeChoice(changed) {}

    reset() {}
}

class CoursemodeFilter extends Filter {
    name = "coursemode";
    choices;
    selectedChoice;
    defaultChoice;

    initRender() {
        super.initRender();

        this.choices = this.node.querySelectorAll("input");
        this.defaultChoice = this.choices[0];

        this.loadChoice();

        // if (!this.selectedChoice || this.selectedChoice.length == 0) {
        //     this.defaultChoice.checked = true;
        // }
    }

    onChange(event) {
        super.onChange(event);
    }

    loadChoice() {
        this.choices.forEach((choice) => {
            choice.checked = false;
            // Check if input value in string array this.selectedChoice.
            if (this.selectedChoice.includes(choice.value)) {
                choice.checked = true;
            }
        });

        if (!this.selectedChoice || this.selectedChoice.length == 0) {
            this.defaultChoice.checked = true;
        }
    }

    storeChoice(changed) {
        if (changed == this.defaultChoice && this.defaultChoice.checked) {
            // Delete filter choices.
            this.selectedChoice = [];
        } else {
            if (changed.checked) {
                // Add choice if not already set.
                if (!this.selectedChoice.includes(changed.value)) {
                    this.selectedChoice.push(changed.value);
                }
            } else {
                // Remove changed.value from this.selectedChoice.
                this.selectedChoice.splice(
                    this.selectedChoice.indexOf(changed.value),
                    1
                );
            }
        }

        this.menu.step.scout.account.setFilter(this.name, this.selectedChoice);
    }

    reset() {
        this.selectedChoice = [];
        this.menu.step.scout.account.setFilter(this.name, this.selectedChoice);
        this.loadChoice();
    }
}

class LocationFilter extends Filter {
    name = "location";
}

class FilterMenu {
    node;
    step;
    filters;
    onChange;
    courseCountNode;

    constructor(step, onChange) {
        this.step = step;
        this.onChange = onChange;

        this.filters = [new CoursemodeFilter(this), new LocationFilter(this)];
    }

    async getData() {
        const data = {
            filters: [],
        };

        for (const filter of this.filters) {
            const html = await filter.render();
            data.filters.push({
                name: filter.name,
                label: Lang.getString("courseliststep:filter" + filter.name),
                html: html,
            });
        }

        return data;
    }

    async getTemplate() {
        const response = await fetch(
            "core51/wisyki/templates/courselist-step-filter.mustache"
        );
        return await response.text();
    }

    initRender() {
        const filterBtn = this.step.node.querySelector(".filter-btn");
        this.node = this.step.node.querySelector(".filter-modal");
        filterBtn.addEventListener("click", () => {
            if (this.node.classList.contains("display-none")) {
                show(this.node);
            } else {
                hide(this.node);
            }
        });

        // Setup reset all filters action.
        this.node
            .querySelector(".filter-reset-btn")
            .addEventListener("click", () => this.reset());
        // Setup goToNextFilter action.
        this.node
            .querySelector(".filter-next-btn")
            .addEventListener("click", () => this.goToNextFilter());

        // Close the modal if the close button is clicked or somewhere outside of the modal.
        this.node.addEventListener(
            "click",
            (event) => {
                if (
                    event.target.closest(".filter-close-btn") ||
                    event.target.matches(".modal__backdrop")
                ) {
                    hide(this.node);
                }
            },
            false
        );

        this.courseCountNode = this.node.querySelector(
            ".filter-close-btn .coursecount"
        );

        this.courseCountLoaderNode = this.node.querySelector(
            ".filter-close-btn .loader"
        );

        this.filterNav = this.node.querySelector(".filter-nav");
        this.filterNav.querySelector("input").checked = true;
        this.filterNavChoices = this.filterNav.querySelectorAll("input");

        this.filterChoices = this.node.querySelectorAll(".filter-choice");

        this.filterNav.addEventListener("change", () => this.updateFilterNav());
        this.updateFilterNav();

        for (const filter of this.filters) {
            filter.initRender();
        }
    }

    updateCourseCount(courseCount) {
        if (courseCount < 0) {
            hide(this.courseCountNode);
            show(this.courseCountLoaderNode);
            this.courseCountNode.textContent = '';
        } else {
            hide(this.courseCountLoaderNode);
            show(this.courseCountNode);
            let text = courseCount + " " + Lang.getString("courses");
            if (courseCount == 1) {
                text = courseCount + " " + Lang.getString("course");
            }
            this.courseCountNode.textContent = text;
        }
    }

    updateFilterNav() {
        const selectedInput = this.filterNav.querySelector("input:checked");
        this.filterChoices.forEach((node) => {
            if (node.id == selectedInput.value) {
                show(node);
            } else {
                hide(node);
            }
        });
    }

    reset() {
        this.filters.forEach((filter) => filter.reset());
        this.onChange();
    }

    goToNextFilter() {
        const currentSelection = this.filterNav.querySelector("input:checked");

        for (let i = 0; i < this.filterNavChoices.length; i++) {
            if (this.filterNavChoices[i] == currentSelection) {
                // Calculate index of next
                const next = (i + 1) % this.filterNavChoices.length;
                this.filterNavChoices[next].checked = true;
                this.updateFilterNav();
                break;
            }
        }
    }
}

class Autocompleter {
    inputElm;
    outputElm;
    clearElm;
    step;
    requestID = 0;
    callback;

    constructor(step, callback) {
        this.step = step;
        this.callback = callback;
        this.inputElm = step.node.querySelector(".esco-autocomplete");
        this.outputElm = step.node.querySelector(".autocomplete-box output");
        this.clearElm = step.node.querySelector(".clear-input");

        // Set up actions of autocomplete input.
        this.inputElm.addEventListener("input", () => this.autocomplete());

        // Set up action to clear autocomplete-input.
        this.clearElm.addEventListener("click", () => {
            // Reset autocomplete input and reults.
            this.clearInput();

            // Update step completeion state.
            this.step.updateNavButtons();
            this.inputElm.focus();
        });

        // Show autocomplete reults only while the input is focused.
        hide(this.outputElm);
        this.inputElm.addEventListener("focus", () => show(this.outputElm));
        this.inputElm.addEventListener("blur", () => hide(this.outputElm));
    }

    clearOutput() {
        while (this.outputElm.lastChild) {
            this.outputElm.removeChild(this.outputElm.lastChild);
        }
    }

    /**
     * This function clears the given input field and hides the output container by removing its child nodes.
     *
     * @param {HTMLElement} input The input field to clear.
     * @param {HTMLElement} output The output container to clear and hide.
     *
     * @returns {void}
     */
    clearInput() {
        this.inputElm.value = "";
        hide(this.outputElm);
        this.clearOutput();
    }

    updateCompletions(completions, requestid) {
        if (requestid < this.requestID) {
            return;
        }
        this.clearOutput();

        const ul = document.createElement("ul");
        // Iterate through each suggestion.
        const skills = this.step.scout.account.getSkills();
        if (completions.length) {
            for (const completion of completions) {
                // Skip suggestion if it has already been selected.
                if (
                    (completion.uri && completion.uri in skills) ||
                    completion.label in skills
                ) {
                    continue;
                }
                // Create a button for the completion and add it to the list.
                const li = document.createElement("li");
                const btn = document.createElement("button");
                btn.textContent = completion.label;
                li.appendChild(btn);
                ul.appendChild(li);

                btn.addEventListener("mousedown", () => {
                    this.inputElm.value = completion.label;
                    this.clearOutput();
                    this.callback(completion.label, completion.uri);
                });
            }
        } else {
            // If no suggestions were found for this category, display a message.
            const li = document.createElement("li");
            const btn = document.createElement("button");
            btn.textContent = "Keine Ergebnisse";
            btn.setAttribute("disabled", "");
            li.appendChild(btn);
            ul.appendChild(li);
        }

        // Add the list of suggestions to the output element.
        this.outputElm.appendChild(ul);
    }

    async requestCompletion() {
        // Extract search term from input field.
        const searchterm = this.inputElm.value;

        // If search term is too short, return an empty array.
        if (searchterm.length < 2) {
            return [];
        }

        let limit = 10;
        // Set limit to set maximum number of suggestions if specified by the input field, defaults to 10.
        if (this.inputElm.getAttribute("max")) {
            limit = this.inputElm.getAttribute("max");
        }

        const params = {
            term: searchterm,
            limit: limit,
        };

        // Set ESCO concept type to limit the search to, if specified by input field.
        if (this.inputElm.getAttribute("esco-type")) {
            params.type = this.inputElm.getAttribute("esco-type").split(" ");
        }
        // Set ESCO concept scheme to limit the search to, if specified by input field.
        if (this.inputElm.getAttribute("esco-scheme")) {
            params.scheme = this.inputElm
                .getAttribute("esco-scheme")
                .split(" ");
        }
        // Set wether to only retrieve suggestions, that are deamed relevant, if specified by the input field.
        if (this.inputElm.getAttribute("onlyrelevant") === "False") {
            params.onlyrelevant = false;
        }

        // Build and make request.
        const url = "./esco/autocomplete?" + new URLSearchParams(params);
        const response = await fetch(url);

        // If response is successful, parse and return the suggestions from the response.
        if (response.ok) {
            return await response.json();
        } else {
            console.error("HTTP-Error: " + response.status);
        }
    }

    async autocomplete() {
        this.requestID = ++this.requestID;
        // Get and display autocomplete results.
        const completions = await this.requestCompletion();
        this.updateCompletions(completions, this.requestID);
    }
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

    static render(template, view = {}, partials = null, config = null) {
        view.lang = Lang.#langstrings;
        return Mustache.render(template, view, partials, config);
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
 * @param {string} mode - Allowed values "display", "visibility", "opacity" to set the strategy for hiding the element.
 *
 * @returns {void}
 */
function hide(node, mode = "display") {
    switch (mode) {
        case "visibility":
            node.classList.add("hidden");
            break;
        case "opacity":
            node.classList.add("hidden-opacity");
            break;
        default:
            node.classList.add("display-none");
    }
}

/**
 * This function shows the given node by removing the "hidden", "hidden-opacity" and "display-none" classes.
 *
 * @param {Element} node - The node to show.
 *
 * @returns {void}
 */
function show(node) {
    node.classList.remove("hidden");
    node.classList.remove("hidden-opacity");
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
