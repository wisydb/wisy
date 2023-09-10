const VIEWPORT_VS_CLIENT_HEIGHT_RATIO = 0.65;

/**
 * Class representing a user account.
 */
class Account {
    // The name of the user.
    name;
    // The occupation of the user.
    #occupation;
    // The skills associated with the user.
    #skills = {};
    // The last visited date of the user.
    lastvisited;
    // The login status of the user.
    #isLoggedIn;
    // The currently selected path of the user.
    #path;
    // Teh currently selected step of the user.
    #step;
    // The filters used by the user.
    #filters = {};

    /**
     * Creates a new user account and checks if the user is logged in.
     */
    constructor() {
        this.#isLoggedIn = this.#checkLogin();
        const scoutid = false; // TODO: Get id from url to load a older saved version of the scout progress.
        this.#load(scoutid);
    }

    /**
     * Reset the account details.
     */
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

    /**
     * Check if the user is logged in.
     * @private
     * @returns {boolean} - Whether the user is logged in.
     */
    #checkLogin() {
        // TODO: Check if a user is logged into an account.
        return false;
    }

    /**
     * Load the user account from local storage or from server if logged in.
     * @private
     * @param {boolean} [id=false] - The id of the user account.
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

    /**
     * Store the user account in local storage or online if logged in.
     * @private
     */
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
                    filters: this.#filters,
                })
            );
        }
    }

    /**
     * Set a skill for the user account.
     * @async
     * @param {string} label - The label of the skill.
     * @param {string} uri - The URI of the skill.
     * @param {number} [levelGoal=null] - The goal level of the skill.
     * @param {boolean} [isLanguageSkill=null] - Whether the skill is a language skill.
     * @param {boolean} [isOccupationSkill=null] - Whether the skill is an occupation skill.
     * @param {boolean} [similarSkills=null] - Other skills that are similar.
     */
    async setSkill(
        label,
        uri,
        levelGoal = null,
        isLanguageSkill = null,
        isOccupationSkill = null,
        similarSkills = null
    ) {
        const skill = {
            uri: uri,
            label: label,
            levelGoal: levelGoal,
            isLanguageSkill: isLanguageSkill,
            isOccupationSkill: isOccupationSkill,
            similarSkills: similarSkills,
        };
        this.#skills[uri] = skill;
        this.#store();

        await this.updateSkillInfo(skill);
    }

    /**
     * Update the information of a skill.
     * @async
     * @param {Object} skill - The skill to update.
     */
    async updateSkillInfo(skill) {
        if (
            skill.isLanguageSkill != null &&
            skill.isOccupationSkill != null &&
            skill.similarSkills != null
        ) {
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
        if (skill.similarSkills == null) {
            skill.similarSkills = skillInfo.similarSkills;
        }
        this.#skills[skill.uri] = skill;
        this.#store();
    }

    /**
     * Remove a skill from the user account.
     * @param {string} uri - The URI of the skill to remove.
     */
    removeSkill(uri) {
        delete this.#skills[uri];
        this.#store();
    }

    /**
     * Get all skills of the user account.
     * @returns {Object} - The skills of the user account.
     */
    getSkills() {
        return this.#skills;
    }

    /**
     * Get a specific skill of the user account.
     * @param {string} uri - The URI of the skill to get.
     * @returns {Object} - The requested skill.
     */
    getSkill(uri) {
        return this.#skills[uri];
    }

    /**
     * Get all filters of the user account.
     * @returns {Object} - The filters of the user account.
     */
    getFilters() {
        return this.#filters;
    }

    /**
     * Get a specific filter of the user account.
     * @param {string} filter - The filter to get.
     * @returns {Object} - The requested filter.
     */
    getFilter(filter) {
        return this.#filters[filter];
    }

    /**
     * Set a filter for the user account.
     * @param {string} filter - The filter to set.
     * @param {string} value - The value of the filter.
     */
    setFilter(filter, value) {
        this.#filters[filter] = value;
        this.#store();
    }

    /**
     * Get the currently selected path of the user.
     * @returns {string} - The currently selected path of the user.
     */
    getPath() {
        return this.#path;
    }

    /**
     * Get the occupation of the user account.
     * @returns {Object} - The occupation of the user account.
     */
    getOccupation() {
        return this.#occupation;
    }

    /**
     * Set the occupation for the user account.
     * @param {string} label - The label of the occupation.
     * @param {string} uri - The URI of the occupation.
     */
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

    /**
     * Set the path for the user account.
     * @param {string} path - The path to set.
     */
    setPath(path) {
        this.#path = path;
        this.#store();
    }

    /**
     * Get the step of the user account.
     * @returns {number} - The step of the user account.
     */
    getStep() {
        return this.#step;
    }

    /**
     * Set the step for the user account.
     * @param {number} step - The step to set.
     */
    setStep(step) {
        this.#step = step;
        this.#store();
    }

    /**
     * Get information about a specific skill.
     * @async
     * @param {string} uri - The URI of the skill to get information about.
     * @returns {Promise} - A promise that resolves to the information about the skill.
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
 * Represents the Scout.
 */
class Scout {
    /**
     * The different paths available for the user to follow.
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
     * @type {HTMLElement}
     */
    nextButton;

    /**
     * UI element to go to the previous step.
     * @type {HTMLElement}
     */
    prevButton;

    /**
     * Creates an instance of Scout.
     * @constructor
     */
    constructor() {
        this.account = new Account();

        this.paths = {
            occupation: new OccupationPath(this),
            skill: new SkillPath(this),
        };
    }

    /**
     * Initializes the Scout.
     */
    init() {
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

        this.updateFav();
        this.update();
    }

    /**
     * Gets the path with the specified pathname.
     * If no pathname is provided, returns the first available path.
     * @param {string} [pathname] - The pathname of the path.
     * @returns {Path} The path with the specified pathname, or the first available path if no pathname is provided.
     */
    getPath(pathname) {
        if (!pathname) {
            pathname = Object.keys(this.paths)[0];
        }
        if (!this.paths[pathname]) {
            return Object.values(this.paths)[0];
        }
        return this.paths[pathname];
    }

    /**
     * Aborts the current path and resets the account.
     */
    abort() {
        this.account.reset();
        this.currentPath.update();
    }

    /**
     * Updates the current path to the specified step index.
     * If no step index is provided, updates to the current step of the account.
     * @param {number|string} [stepIndex=null] - The step index to update to, or "next" to go to the next step, or "prev" to go to the previous step.
     */
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
            } else {
                for (const step of this.currentPath.steps) {
                    if (step.name == stepIndex) {
                        stepIndex = this.currentPath.steps.indexOf(step);
                        break;
                    }
                }
            }
        }

        this.currentPath.update(stepIndex);
    }

    /**
     * Selects the path with the specified pathname.
     * If the selected path is the same as the current path, nothing changes.
     * @param {string} pathname - The pathname of the path to select.
     * @returns {Promise} A promise that resolves when the path update is complete.
     */
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

    /**
     * Checks if the specified step index is valid for the current path.
     * If the index is undefined, defaults to 0.
     * @param {number} index - The step index to check.
     * @returns {number} The valid step index.
     */
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

    /**
     * Updates the favorite count UI element.
     */
    updateFav() {
        const bubble = document.querySelector("nav a .bubble");
        const count = fav_count();
        bubble.textContent = count;
        if (count > 0) {
            show(bubble);
        } else {
            hide(bubble);
        }
    }
}

/**
 * Path class is used to manage the path in the application.
 * This class is abstract and cannot be instantiated directly.
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

    /**
     * The main node of the path.
     * @type {Element}
     */
    node;

    /**
     * Currently ongoing processes that should block further actions like going to the next step until resolved.
     * @type {Array}
     */
    blockingProcesses = [];

    /**
     * The constructor for the Path class.
     * @param {Scout} scout - The scout object.
     * @throws {Error} Throws an error if trying to instantiate the abstract Path class.
     */
    constructor(scout) {
        if (this.constructor == Path) {
            throw new Error("Abstract classes can't be instantiated.");
        }

        this.scout = scout;
    }

    /**
     * The private method to render the path.
     * @returns {Promise<void>} A promise that resolves when the path is rendered.
     */
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

    /**
     * Initializes the path. Sets up iu elements and user interactions.
     */
    init() {
        // Init steps.
        this.steps.forEach((step) => step.init());
        // Init main .steps.
        this.stepsNode = this.node.querySelector(".steps > div");

        // Define UI action for aborting the scout.
        const scoutShowAbortModalBtn = this.node.querySelector("#scout-abort");
        const scoutAbortModal = this.node.querySelector(".abort-modal");
        const scoutAbortBtn = this.node.querySelector(
            ".abort-modal .btn-secondary"
        );
        const scoutAbortCloseModalBtns = this.node.querySelectorAll(
            ".abort-modal .close-modal-btn"
        );
        scoutShowAbortModalBtn.addEventListener("click", () =>
            show(scoutAbortModal)
        );
        scoutAbortBtn.addEventListener("click", () => {
            hide(scoutAbortModal);
            this.scout.abort();
        });
        scoutAbortCloseModalBtns.forEach((btn) =>
            btn.addEventListener("click", () => hide(scoutAbortModal))
        );

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

    /**
     * @returns {Promise<void>} A promise that resolves when all blocking processes are finished.
     */
    async finishBlockingProcesses() {
        if (this.blockingProcesses.length == 0) {
            return;
        }

        await Promise.all(this.blockingProcesses);
        this.blockingProcesses = [];
    }

    /**
     * Updates the path.
     * @param {number} index - The index to update.
     * @returns {Promise<void>} A promise that resolves when the path is updated.
     */
    async update(index) {
        // Wait until all blocking processes are done.
        await this.finishBlockingProcesses();

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

    /**
     * Updates the step of the path.
     * @param {number} stepIndex - The index of the step to update.
     * @returns {Promise<void>} A promise that resolves when the step is updated.
     */
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
        // Update Loader text content.
        const loadertext =
            this.currentStep.loader.querySelector(".loader__text");
        loadertext.textContent = Lang.getString(
            this.currentStep.name + "step:loading"
        );
        // Update step.
        await this.currentStep.update();

        this.currentStep.hideLoader();
    }

    /**
     * Updates the scout navigation.
     */
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

    /**
     * Checks if the path is rendered.
     * @returns {boolean} True if the path is rendered, false otherwise.
     */
    isRendered() {
        return (
            document.querySelector("main").getAttribute("currentPath") ===
            this.name
        );
    }

    /**
     * Scrolls to the specified step with an optional delay.
     *
     * @param {number} stepindex - The step to scroll to.
     * @param {number} [delay=0] - The delay, in milliseconds, to wait before scrolling.
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

    /**
     * Prepares the data for the path.
     * @returns {Object} The prepared data.
     */
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
 * @extends {Path}
 */
class OccupationPath extends Path {
    /**
     * Creates an instance of OccupationPath.
     *
     * @override
     * @param {Scout} scout - The scout object.
     */
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

    /**
     * Prepares the template data for OccupationPath.
     *
     * @override
     * @returns {object} - The prepared template data object.
     */
    prepareData() {
        const data = super.prepareData();
        data.label = Lang.getString("occupationpath:label");
        return data;
    }
}

/**
 * SkillPath.
 *
 * @extends {Path}
 */
class SkillPath extends Path {
    /**
     * Creates an instance of SkillPath.
     *
     * @override
     * @param {Scout} scout - The scout object.
     */
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

    /**
     * Prepares the template data for SkillPath.
     *
     * @override
     * @returns {object} - The prepared template data object.
     */
    prepareData() {
        const data = super.prepareData();
        return data;
    }
}

/**
 * Abstract Class Step.
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

    /**
     * The data object for the html templates.
     * @type {Object}
     */
    data = {};

    /**
     * The partial templates of the main template.
     * @type {Object}
     */
    partials = {};

    /**
     * Creates an instance of Step.
     * @param {Scout} scout - The scout object.
     * @param {Path} path - The parent path.
     */
    constructor(scout, path) {
        if (this.constructor == Step) {
            throw new Error("Abstract classes can't be instantiated.");
        }

        this.scout = scout;
        this.path = path;
    }

    /**
     * Prepares the template for the step.
     * @returns {Promise<void>} - A promise that resolves when the template is prepared.
     */
    async prepareTemplate() {}

    /**
     * Initializes the step.
     */
    init() {
        // Get the node for the current step and loader.
        this.node = document.getElementById(this.name);
        this.loader = this.node.parentNode.querySelector(
            ".loader:not(.hidden)"
        );
    }

    /**
     * Renders or updates the step, if the step is already redered.
     * @returns {Promise<void>} - A promise that resolves when the step is updated.
     */
    async update() {
        if (!this.isRendered()) {
            await this.#render();
        }

        this.updateNavButtons();
    }

    /**
     * Renders the step.
     * @returns {Promise<void>} - A promise that resolves when the step is rendered.
     */
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

    /**
     * Initializes the rendered step.
     */
    initRender() {}

    /**
     * Shows the loader for the step.
     */
    showLoader() {
        hide(this.node, "visibility");
        // Hide the step, show the loader.
        if (this.loader) {
            show(this.loader);
        }
    }

    /**
     * Hides the loader for the step.
     */
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

    /**
     * Checks if the step is the first step in the path.
     * @returns {boolean} - True if the step is the first step, false otherwise.
     */
    isFirst() {
        return this.path.steps.indexOf(this.path.currentStep) == 0;
    }

    /**
     * Checks if the step is the last step in the path.
     * @returns {boolean} - True if the step is the last step, false otherwise.
     */
    isLast() {
        return (
            this.path.steps.indexOf(this.path.currentStep) ==
            this.path.steps.length - 1
        );
    }

    /**
     * Gets the next step in the path.
     * @returns {Step|null} - The next step object or null if it is the last step.
     */
    nextStep() {
        if (this.isLast()) {
            return null;
        }
        return this.path.steps[
            this.path.steps.indexOf(this.path.currentStep) + 1
        ];
    }

    /**
     * Updates the navigation buttons based on the current step.
     */
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

    /**
     * Checks if the prerequisites for the step are met.
     * @throws {Error} - Throws an error if the method is not implemented by the subclass.
     */
    checkPrerequisites() {
        throw new Error("Abstract method needs to be implemented by subclass.");
    }

    /**
     * Checks if the step has been rendered.
     * @returns {boolean} - True if the step has been rendered, false otherwise.
     */
    isRendered() {
        return document.getElementById(this.name).children.length !== 0;
    }

    /**
     * Hides the navigation for the step.
     * @returns {boolean} - False.
     */
    hideNav() {
        return false;
    }
}

/**
 * Represents a PathStep object.
 * @extends Step
 */
class PathStep extends Step {
    /**
     * Constructs a new PathStep object.
     * @param {Scout} scout - The scout object.
     * @param {Path} path - The path object.
     */
    constructor(scout, path) {
        super(scout, path);

        this.name = "pathchoice";
    }

    /**
     * Checks the prerequisites for the PathStep. The pathstep has no special prerequisites.
     * @returns {boolean} - True if prerequisites are met, false otherwise.
     */
    checkPrerequisites() {
        return true;
    }

    /**
     * Initializes the rendering of the PathStep.
     */
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

    /**
     * Updates the navigation buttons of the PathStep.
     */
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

    /**
     * Prepares the template data for the PathStep.
     */
    async prepareTemplate() {
        this.data.paths = [];
        for (const key in this.scout.paths) {
            this.data.paths.push(this.scout.paths[key].prepareData());
        }
    }

    /**
     * Hides the navigation of the PathStep.
     * @returns {boolean} - True if navigation should be hidden, false otherwise.
     */
    hideNav() {
        return true;
    }
}

/**
 * OccupationStep.
 *
 * @extends Step
 */
class OccupationStep extends Step {
    /**
     * Autocompleter object for occupation selection.
     * @type {Autocompleter}
     */
    autocompleter;

    /**
     * Constructs a new OccupationStep object.
     * @param {Scout} scout - The scout object.
     * @param {Path} path - The path object.
     */
    constructor(scout, path) {
        super(scout, path);
        this.name = "occupation";
    }

    /**
     * Initializes the rendering of the OccupationStep.
     */
    initRender() {
        super.initRender();
        // Set up UI-elements.
        this.autocompleter = new Autocompleter(this, (label, uri) =>
            this.setOccupation(label, uri)
        );
    }

    /**
     * Updates the OccupationStep.
     */
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

    /**
     * Checks the prerequisites for the OccupationStep.
     * @returns {boolean} - True if prerequisites are met, false otherwise.
     */
    checkPrerequisites() {
        return this.scout.account.getPath() == this.path.name;
    }

    /**
     * Sets the selected occupation.
     * @param {string} label - The label of the selected occupation.
     * @param {string} uri - The URI of the selected occupation.
     */
    setOccupation(label, uri) {
        this.scout.account.setOccupation(label, uri);
        this.updateNavButtons();
    }
}

/**
 * OccupationSkillsStep.
 *
 * @extends Step
 */
class OccupationSkillsStep extends Step {
    /**
     * The DOM node for the selected occupation.
     * @type {HTMLElement}
     */
    occupationNode;

    /**
     * The DOM node for the selectable skills.
     * @type {HTMLElement}
     */
    skillsNode;

    /**
     * The selected occupation.
     * @type {object}
     */
    occupation;

    /**
     * The maximum number of skills that can be selected.
     * @type {number}
     */
    maxSkills;

    /**
     * The template for the skill counter.
     * @type {string}
     */
    skillCounterTemplate;

    /**
     * The DOM node for the skill counter.
     * @type {HTMLElement}
     */
    skillCounterNode;

    /**
     * Constructs a new OccupationSkillsStep object.
     * @param {Scout} scout - The scout object.
     * @param {Path} path - The parent path object.
     */
    constructor(scout, path) {
        super(scout, path);
        this.name = "occupationskills";

        // this.maxSkills = 5;
    }

    /**
     * Prepares the template data for the OccupationSkillsStep.
     */
    async prepareTemplate() {
        this.data.skillsfound = null;
        this.data.skills = null;

        if (this.maxSkills) {
            this.data.maxskills = this.maxSkills;
        }

        this.occupation = this.scout.account.getOccupation();
        if (!this.occupation) {
            console.error("no occupation selected");
            return;
        }

        this.data.occupation = this.occupation.label;

        const skills = await this.suggestSkills(this.occupation.uri);

        if (skills.length) {
            this.data.skillsfound = true;
            this.data.skills = skills;
        }
    }

    /**
     * Checks the prerequisites for the OccupationSkillsStep.
     * @returns {boolean} - True if prerequisites are met, false otherwise.
     */
    checkPrerequisites() {
        return (
            this.scout.account.getPath() == this.path.name &&
            this.scout.account.getOccupation() != null
        );
    }

    /**
     * Initializes the rendering of the OccupationSkillsStep.
     */
    initRender() {
        super.initRender();

        // Set up the UI components.
        this.occupationNode = this.node.querySelector(".selected-occupation");
        this.skillsNode = this.node.querySelector(".selectable-skills ul");

        if (!this.skillsNode) {
            const goToStepBtns = this.node.querySelectorAll("button");
            goToStepBtns.forEach((btn) =>
                btn.addEventListener("click", async () => {
                    this.scout.update(btn.getAttribute("stepname"));
                })
            );
        } else {
            this.checkboxes = this.skillsNode.querySelectorAll("input");
            // On change event, add or remove the skill associated with the checkbox and update the view.
            this.checkboxes.forEach((checkbox) =>
                checkbox.addEventListener("change", async () => {
                    if (checkbox.checked) {
                        const promise = this.scout.account.setSkill(
                            checkbox.getAttribute("skilllabel"),
                            checkbox.getAttribute("skilluri")
                        );
                        this.path.blockingProcesses.push(promise);
                    } else {
                        this.scout.account.removeSkill(
                            checkbox.getAttribute("skilluri")
                        );
                    }
                    this.updateSkillSelection(this.checkboxes);
                })
            );
        }

        this.skillCounterNode = this.node.querySelector(".skill-counter");

        if (this.maxSkills) {
            this.skillCounterTemplate = Lang.getString("skillcountmaxtemplate");
            this.node.querySelector(".max-skills").textContent = this.maxSkills;
        } else {
            this.skillCounterTemplate = Lang.getString("skillcounttemplate");
        }
    }

    /**
     * Updates the OccupationSkillsStep.
     */
    async update() {
        await super.update();

        if (this.skillsNode) {
            this.updateSkillSelection();
        }
    }

    /**
     * Suggests skills based on the given ESCO concept URI.
     * @param {string} uri - The URI to get skills for.
     * @returns {Promise<Array>} - An array of skills.
     * @throws {Error} - If the given URI is not a valid ESCO concept URI.
     */
    async suggestSkills(uri) {
        if (!uri.startsWith("http://data.europa.eu/esco/")) {
            throw new Error("invalid ESCO concept URI");
        }

        const limit = 10;
        const params = { uri: uri, limit: limit, onlyrelevant: true };

        const url = "./esco/getConceptSkills?" + new URLSearchParams(params);
        const response = await fetch(url);
        let result = [];

        if (response.ok) {
            const json = await response.json();
            result = Object.values(json.skills);
        } else {
            console.error("HTTP-Error: " + response.status);
        }
        return result;
    }

    /**
     * Updates the checkboxes' selection state according to the currently selected skills.
     * Disables the "add more skills" button if the maximum number of skills is already selected.
     */
    updateSkillSelection() {
        const skills = this.scout.account.getSkills();
        const skillcount = Object.keys(skills).length;

        this.checkboxes.forEach((checkbox) => {
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

    /**
     * Checks if the step has been rendered.
     * @returns {boolean} - True if the step has been rendered, false otherwise.
     */
    isRendered() {
        return (
            document.getElementById(this.name).children.length !== 0 &&
            this.occupation == this.scout.account.getOccupation()
        );
    }
}

/**
 * Represents the Step for searching, selecting and managing skills.
 *
 * @extends Step
 */
class SkillsStep extends Step {
    /**
     * The autocompleter used for skill suggestions.
     * @type {Autocompleter}
     */
    autocompleter;

    /**
     * The DOM node for the selected other skills.
     * @type {HTMLElement}
     */
    selectedOtherSkillsNode;

    /**
     * The DOM node for the selected occupation skills.
     * @type {HTMLElement}
     */
    selectedOccupationSkillsNode;

    /**
     * The maximum number of skills that can be selected.
     * @type {number}
     */
    maxSkills;

    /**
     * The template for the skill counter.
     * @type {string}
     */
    skillCounterTemplate;

    /**
     * The DOM node for the skill counter.
     * @type {HTMLElement}
     */
    skillCounterNode;

    /**
     * Constructs a new SkillsStep object.
     * @param {Scout} scout - The scout object.
     * @param {Path} path - The parent path object.
     */
    constructor(scout, path) {
        super(scout, path);
        this.name = "skills";
        // this.maxSkills = 5;
    }

    /**
     * Prepares the template data for the SkillsStep.
     */
    async prepareTemplate() {
        if (this.maxSkills) {
            this.data.maxskills = this.maxSkills;
        }
        if (this.scout.account.getOccupation()) {
            this.data.occupation = this.scout.account.getOccupation().label;
        }
    }

    /**
     * Checks the prerequisites for the SkillsStep.
     * @returns {boolean} - True if prerequisites are met, false otherwise.
     */
    checkPrerequisites() {
        return this.scout.account.getPath() == this.path.name;
    }

    /**
     * Initializes the rendering of the SkillsStep.
     */
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
            const promise = this.scout.account.setSkill(label, uri).then(() => {
                this.showSelectedSkills();
            });
            this.path.blockingProcesses.push(promise);
            this.autocompleter.clearInput();
            this.updateNavButtons();
        });

        if (this.maxSkills) {
            this.skillCounterTemplate = Lang.getString("skillcountmaxtemplate");
        } else {
            this.skillCounterTemplate = Lang.getString("skillcounttemplate");
        }
    }

    /**
     * Updates the SkillsStep.
     */
    async update() {
        await super.update();

        this.showSelectedSkills(true);
    }

    /**
     * Fills the suggestion list in the UI with the given suggestions.
     * @param {object} suggestions - The suggestions to be displayed.
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
            label.textContent = skill.label.replace(/ +\(ESCO\)/, "");
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
                    const promise = this.scout.account
                        .setSkill(skill.label, uri)
                        .then(() => {
                            this.showSelectedSkills();
                        });
                    this.path.blockingProcesses.push(promise);
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

    /**
     * Updates the "is empty" message for the selected skills.
     */
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
     * @param {Array} checkboxes - An array of the checkboxes for each skill.
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

    /**
     * Updates the skill counter on the webpage.
     * Retrieves the skills of the scout's account and calculates the number of skills.
     * Updates the HTML of the skill counter node with the rendered skill counter template.
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
 * Class representing the level goal step.
 * @extends Step
 */
class LevelGoalStep extends Step {
    /**
     * Represents the level goal selection list element.
     * @type {Element}
     */
    levelSelectionList;

    /**
     * Create a level goal step.
     * @param {Scout} scout - The scout object.
     * @param {Path} path - The parent path object.
     */
    constructor(scout, path) {
        super(scout, path);
        this.name = "levelgoal";
    }

    /**
     * Check the prerequisites for the level goal step.
     * @return {boolean} Return true if the current path is equal to the name of the path and the account has skills.
     */
    checkPrerequisites() {
        return (
            this.scout.account.getPath() == this.path.name &&
            Object.keys(this.scout.account.getSkills()).length
        );
    }

    /**
     * Initialize the rendering of the level goal step.
     */
    initRender() {
        super.initRender();

        // Create and update level goal selection list
        this.levelSelectionList = this.node.querySelector(
            "ul.level-goal-selection"
        );
    }

    /**
     * Update the level goal step.
     */
    async update() {
        await super.update();

        this.updateLevelSelection();
    }

    /**
     * Update the level selection list.
     */
    async updateLevelSelection() {
        while (this.levelSelectionList.lastChild) {
            this.levelSelectionList.removeChild(
                this.levelSelectionList.lastChild
            );
        }

        const skills = this.scout.account.getSkills();
        const data = { skills: [] };
        for (const uri in skills) {
            // Copy skills[uri] to const skill
            const skill = Object.assign({}, skills[uri]);
            skill.label = skill.label.replace(/ +\(ESCO\)/, "");
            data.skills.push(skill);
        }

        const response = await fetch(
            "core51/wisyki/templates/" + this.name + "-selection.mustache"
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
                    skill.isOccupationSkill,
                    skill.similarSkills
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
                    skill.isOccupationSkill,
                    skill.similarSkills
                );
            }
        });
    }
}

/**
 * Class representing a course list step.
 * @extends Step
 */
class CouseListStep extends Step {
    /**
     * Represents the filter menu of the course list step.
     * @type {FilterMenu}
     */
    filterMenu;

    /**
     * Represents the result list of the course list step.
     * @type {Element}
     */
    resultList;

    /**
     * Represents the last state of the course list step.
     * @type {Object}
     */
    lastState = {};

    /**
     * Represents the comp level mapping to the Lang strings.
     * @type {Object}
     */
    complevelmapping = {
        "Niveau A": Lang.getString("complevela"),
        "Niveau B": Lang.getString("complevelb"),
        "Niveau C": Lang.getString("complevelc"),
    };

    /**
     * Represents the language level mapping to the Lang strings.
     * @type {Object}
     */
    langlevelmapping = {
        A1: Lang.getString("languagelevela1"),
        A2: Lang.getString("languagelevela2"),
        B1: Lang.getString("languagelevelb1"),
        B2: Lang.getString("languagelevelb2"),
        C1: Lang.getString("languagelevelc1"),
        C2: Lang.getString("languagelevelc2"),
    };

    /**
     * Create a course list step.
     * @param {Object} scout - The scout object.
     * @param {Object} path - The parent path object.
     */
    constructor(scout, path) {
        super(scout, path);
        this.name = "courselist";

        this.filterMenu = new FilterMenu(this, () => {
            this.filterMenu.updateCourseCount(-1);
            debounce(async () => {
                this.updateCourseResults();
            }, 500)(); // Adjust the delay time (in milliseconds) as needed.
        });
    }

    /**
     * Check prerequisites for the course list step.
     * @returns {boolean} A boolean representing whether the prerequisites are met or not.
     */
    checkPrerequisites() {
        return (
            this.scout.account.getPath() == this.path.name &&
            Object.keys(this.scout.account.getSkills()).length
        );
    }

    /**
     * Prepare template for the course list step.
     */
    async prepareTemplate() {
        this.partials.filtermenu = await this.filterMenu.getTemplate();

        this.data = await this.filterMenu.getData();
    }

    /**
     * Initialize rendering for the course list step.
     */
    initRender() {
        super.initRender();
        this.filterMenu.initRender();

        this.resultList = this.node.querySelector(".result-list");
    }

    /**
     * Update the course list step.
     */
    async update() {
        await super.update();

        await this.updateCourseResults();
    }

    /**
     * Update course result for a specific skill.
     * @param {Object} skill - The skill object.
     * @param {Object} detailsNode - The details node object.
     */
    async updateCourseResult(skill, detailsNode) {
        const courselistNode = detailsNode.querySelector(".course-list");
        const courselistCountNode = detailsNode.querySelector(
            ".result-list__count"
        );
        hide(courselistNode, "opacity");

        let courselist;
        const skills = this.scout.account.getSkills();
        this.filterMenu.updateCourseCount(-1);
        const completedata = this.getTemplateData(skills);

        completedata.sets.forEach((set) => {
            if (set.skilllabel == skill.label) {
                courselist = set.results;
                return;
            }
        });
        const data = {
            results: courselist,
        };

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
        courselistCountNode.textContent = this.getKurseCountString(
            data.results.length
        );

        this.filterMenu.updateCourseCount(completedata.count);

        this.setupFavAction(courselistNode);
    }

    /**
     * Setup favourite action for a course list node.
     * @param {Object} courselistNode - The course list node object.
     */
    setupFavAction(courselistNode) {
        const favBtns = courselistNode.querySelectorAll(".bookmark-btn");
        favBtns.forEach((btn) => {
            // Mark favourites.
            const courseid = btn.getAttribute("courseid");
            const icon = btn.querySelector("i");
            if (fav_is_favourite(courseid)) {
                icon.classList.remove("star-icon");
                icon.classList.add("filled-star-icon");
            }

            btn.addEventListener("click", (event) => {
                if (!fav_is_favourite(courseid)) {
                    fav_click(event, courseid);

                    icon.classList.remove("star-icon");
                    icon.classList.add("filled-star-icon");
                } else {
                    fav_set_favourite(courseid, false);

                    icon.classList.add("star-icon");
                    icon.classList.remove("filled-star-icon");
                }

                this.scout.updateFav();
            });
        });
    }

    /**
     * Get template data for a set of skills.
     * @param {Object} skills - The skills object.
     * @returns {Object} The template data.
     */
    getTemplateData(skills) {
        const uniquecourses = new Set();
        const data = {
            count: this.results.count,
            sets: [],
        };
        this.results.sets.forEach((set) => {
            let currentLevelResults = [];
            let label = set.label.replace(/ +\(ESCO\)/, "");
            let currentSkill;

            if (!set.skill) {
                label = Lang.getString("courseliststep:" + set.label);

                const filteredResults = this.getFilteredCourselist(set.results);

                currentLevelResults = filteredResults[""];
            } else {
                for (let skill of Object.values(skills)) {
                    if (skill.label == set.skill.label) {
                        currentSkill = skill;
                        if (currentSkill.levelGoal === null) {
                            currentSkill.levelGoal = "";
                        }
                    }
                }

                let levels = [""].concat(Object.values(this.complevelmapping));
                if (currentSkill.isLanguageSkill) {
                    levels = [""].concat(Object.values(this.langlevelmapping));
                }
                const filteredResults = this.getFilteredCourselist(
                    set.results,
                    levels
                );

                currentSkill.levels = [];
                levels.forEach((level) => {
                    currentSkill.levels.push({
                        level: level,
                        levellabel: level ? level : Lang.getString("all"),
                        count: filteredResults[level].length,
                    });
                });

                console.log(currentSkill.levelGoal);
                currentLevelResults = filteredResults[currentSkill.levelGoal];
            }

            const filteredSet = {
                label: label,
                skilllabel: set.label,
                countstring: this.getKurseCountString(
                    currentLevelResults.length
                ),
                results: currentLevelResults,
            };
            if (currentSkill) {
                filteredSet.skill = currentSkill;
            }
            data.sets.push(filteredSet);
            // Add the id of everycourse in filteredResults to uniquercourses set.
            currentLevelResults.forEach((course) => {
                uniquecourses.add(course.id);
            });
        });

        data.count = uniquecourses.size;

        return data;
    }

    /**
     * Get string representation for the count of courses.
     * @param {number} count - The count of courses.
     * @returns {string} The string representation of the count.
     */
    getKurseCountString(count) {
        let countstring = count + " " + Lang.getString("courses");
        if (count == 1) {
            countstring = count + " " + Lang.getString("course");
        }
        return countstring;
    }

    /**
     * Get filtered course list based on the level.
     * @param {Array} courses - The array of courses.
     * @param {Array} levels - The levels to sort the results by.
     * @returns {Array} The filtered array of courses.
     */
    getFilteredCourselist(courses, levels = null) {
        const filteredResults = {};
        if (!levels) {
            filteredResults[""] = [];
            courses.forEach((course) => {
                const newcourse = Object.assign({}, course);
                newcourse.showLevels = true;
                filteredResults[""].push(newcourse);
            });
        } else {
            levels.forEach((level) => {
                filteredResults[level] = [];
                courses.forEach((course) => {
                    if (level == "" || course.levels.includes(level)) {
                        const newcourse = Object.assign({}, course);
                        newcourse.showLevels = false;
                        filteredResults[level].push(newcourse);
                    }
                });
            });
        }
        return filteredResults;
    }

    /**
     * Update course results for the course list step.
     */
    async updateCourseResults() {
        // Get course suggestions and set template data.
        const skills = this.scout.account.getSkills();

        const results = await this.search();
        if (results) {
            this.results = results;
        }

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

        this.setupFavAction(this.resultList);

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
                    skill.isOccupationSkill,
                    skill.similarSkills
                );

                this.updateCourseResult(skill, complevelFilter.parentNode);
            });

            // If not set, set default levelGoal.
            const currentSelection = complevelFilter.querySelector(
                'input[value="' + skill.levelGoal + '"'
            );
            if (skill.levelGoal && currentSelection) {
                currentSelection.checked = true;
            } else {
                const firstInput = complevelFilter.querySelector("input");
                firstInput.checked = true;
                this.scout.account.setSkill(
                    skill.label,
                    skill.uri,
                    firstInput.value,
                    skill.isLanguageSkill,
                    skill.isOccupationSkill,
                    skill.similarSkills
                );
            }
        });
    }

    /**
     * Search for courses based on the users skill goals.
     * @returns {Object} The response object containing the search ID and the number of courses found.
     */
    async search() {
        const params = {
            skills: JSON.stringify(this.scout.account.getSkills()),
            occupation: JSON.stringify(this.scout.account.getOccupation()),
            filters: JSON.stringify(this.scout.account.getFilters()),
        };

        const newState = JSON.stringify(params);

        if (this.lastState == newState) {
            return;
        }
        this.lastState = newState;

        const url = "./scout-search?" + new URLSearchParams(params);
        const response = await fetch(url);

        if (response.ok) {
            const results = await response.json();
            return this.cleanResults(results);
        } else {
            console.error("HTTP-Error: " + response.status);
        }
    }

    cleanResults(results) {
        // Go over every result.
        for (const setid in results.sets) {
            const set = results.sets[setid];
            for (const resultid in set.results) {
                const result = results.sets[setid].results[resultid];
                // Translate levels Niveau A, B, C to the level names definded by the language file.
                for (const levelid in result.levels) {
                    const level =
                        results.sets[setid].results[resultid].levels[levelid];
                    if (Object.keys(this.complevelmapping).includes(level)) {
                        results.sets[setid].results[resultid].levels[levelid] =
                            this.complevelmapping[level];
                    }
                }

                // Get LangString for ai reccomendation reasons.
                if (results.sets[setid].results[resultid].reason) {
                    const score = results.sets[setid].results[resultid].score;
                    const data = {};
                    if (score) {
                        data.score = Math.round(score * 100);
                    }
                    results.sets[setid].results[resultid].reason =
                        Mustache.render(
                            Lang.getString(
                                results.sets[setid].results[resultid].reason[0]
                            ),
                            data
                        );
                }
            }
        }

        return results;
    }
}

/**
 * Class representing a filter.
 */
class Filter {
    /**
     * Represents the menu of the filter.
     * @type {FilterMenu}
     */
    menu;

    /**
     * Represents the selected choice of the filter.
     * @type {Array}
     */
    selectedChoice = [];

    /**
     * Create a filter.
     * @param {Menu} menu - The menu object.
     */
    constructor(menu) {
        this.menu = menu;
    }

    /**
     * Render the filter.
     * @returns {Promise} A promise that resolves to the rendered template.
     */
    async render() {
        const response = await fetch(
            "core51/wisyki/templates/" + this.name + "-filter.mustache"
        );
        const template = await response.text();

        return Lang.render(template);
    }

    /**
     * Initialize rendering of the filter.
     */
    initRender() {
        this.node = this.menu.node.querySelector("#filter-" + this.name);
        this.selectedChoice = this.menu.step.scout.account.getFilter(this.name);

        this.node.addEventListener("change", (event) => this.onChange(event));
    }

    /**
     * Handle change event of the filter.
     * @param {Event} event - The change event.
     */
    onChange(event = null) {
        if (event) {
            this.storeChoice(event.target);
        } else {
            this.storeChoice();
        }
        this.loadChoice();

        this.menu.onChange();
        this.menu.update();
    }

    /**
     * Load the selected choice of the filter.
     */
    loadChoice() {}

    /**
     * Store the selected choice of the filter.
     * @param {Element} changed - The changed element.
     */
    storeChoice(_changed = null) {}

    /**
     * Reset the filter.
     */
    reset() {
        // this.menu.onChange();
        this.menu.update();
    }

    /**
     * Check if the filter is active.
     * @returns {boolean} A boolean representing whether the filter is active or not.
     */
    isActive() {}
}

/**
 * Class representing a checkbox filter.
 * @extends Filter
 */
class CheckboxFilter extends Filter {
    /**
     * Represents the choices of the checkbox filter.
     * @type {NodeList}
     */
    choices;

    /**
     * Represents the selected choice of the checkbox filter.
     * @type {Array}
     */
    selectedChoice;

    /**
     * Represents the default choice of the checkbox filter.
     * @type {Element}
     */
    defaultChoice;

    /**
     * Initialize rendering of the checkbox filter.
     */
    initRender() {
        super.initRender();

        this.choices = this.node.querySelectorAll("input");
        this.defaultChoice = this.choices[0];

        if (!this.selectedChoice) {
            this.selectedChoice = [];
        }

        this.loadChoice();
    }

    /**
     * Load the selected choice of the checkbox filter.
     */
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

    /**
     * Store the selected choice of the checkbox filter.
     * @param {Element} changed - The changed element.
     */
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

    /**
     * Reset the checkbox filter.
     */
    reset() {
        this.selectedChoice = [];
        this.menu.step.scout.account.setFilter(this.name, this.selectedChoice);
        this.loadChoice();

        super.reset();
    }

    /**
     * Check if the checkbox filter is active.
     * @returns {boolean} A boolean representing whether the checkbox filter is active or not.
     */
    isActive() {
        if (this.selectedChoice.length > 0) {
            return true;
        }
        return false;
    }
}

/**
 * Class representing a Coursemode filter.
 * @extends CheckboxFilter
 */
class CoursemodeFilter extends CheckboxFilter {
    /**
     * Represents the name of the filter.
     * @type {string}
     */
    name = "coursemode";
}

/**
 * Class representing a Timeofday filter.
 * @extends CheckboxFilter
 */
class TimeofdayFilter extends CheckboxFilter {
    /**
     * Represents the name of the filter.
     * @type {string}
     */
    name = "timeofday";
}

/**
 * Class representing a Funding filter.
 * @extends CheckboxFilter
 */
class FundingFilter extends CheckboxFilter {
    /**
     * Represents the name of the filter.
     * @type {string}
     */
    name = "funding";
}

/**
 * Class representing a Location filter.
 * @extends Filter
 */
class LocationFilter extends Filter {
    /**
     * Represents the name of the filter.
     * @type {string}
     */
    name = "location";

    /**
     * Represents the autocompleter of the filter.
     * @type {LocationAutocompleter}
     */
    autocompleter;

    /**
     * Initialize rendering of the location filter.
     */
    initRender() {
        this.node = this.menu.node.querySelector("#filter-" + this.name);
        this.selectedChoice = this.menu.step.scout.account.getFilter(this.name);

        if (!this.selectedChoice) {
            this.selectedChoice = {};
        }

        this.autocompleter = new LocationAutocompleter(
            this.menu.step,
            () => {
                this.onChange();
            },
            () => {
                this.onChange();
            }
        );

        this.loadChoice();
    }

    /**
     * Load the selected choice of the location filter.
     */
    loadChoice() {
        this.autocompleter.inputElm.value = this.selectedChoice.name;

        if (this.selectedChoice.perimiter) {
            const tobechecked = this.node.querySelector(
                'input[value="' + this.selectedChoice.perimiter + '"]'
            );
            if (tobechecked) {
                tobechecked.checked = true;
            }
        } else {
            this.node.querySelector('input[type="radio"]').checked = true;
        }
    }

    /**
     * Store the selected choice of the location filter.
     */
    storeChoice() {
        this.selectedChoice.name = this.autocompleter.inputElm.value ?? null;

        const selectedPerimiterInput = this.node.querySelector(
            'input[type="radio"]:checked'
        );
        this.selectedChoice.perimiter = selectedPerimiterInput.value;
        this.menu.step.scout.account.setFilter(this.name, this.selectedChoice);
    }

    /**
     * Check if the location filter is active.
     * @returns {boolean} A boolean representing whether the location filter is active or not.
     */
    isActive() {
        if (!this.selectedChoice.name) {
            return false;
        }
        return true;
    }

    /**
     * Reset the location filter.
     */
    reset() {
        this.selectedChoice = {};
        this.menu.step.scout.account.setFilter(this.name, this.selectedChoice);
        this.loadChoice();
        this.autocompleter.clearInput();

        super.reset();
    }
}

/**
 * Class representing a Price filter.
 * @extends Filter
 */
class PriceFilter extends Filter {
    /**
     * Represents the name of the filter.
     * @type {string}
     */
    name = "price";

    /**
     * Initialize rendering of the price filter.
     */
    initRender() {
        super.initRender();

        if (!this.selectedChoice) {
            this.selectedChoice = null;
        }

        this.choices = this.node.querySelectorAll("input");
        this.defaultChoice = this.choices[0];

        this.loadChoice();
    }

    /**
     * Load the selected choice of the price filter.
     */
    loadChoice() {
        if (!this.selectedChoice) {
            this.defaultChoice.checked = true;
        } else {
            this.choices.forEach((choice) => {
                if (this.selectedChoice == choice.value) {
                    choice.checked = true;
                }
            });
        }
    }

    /**
     * Store the selected choice of the price filter.
     * @param {Element} changed - The changed element.
     */
    storeChoice(changed) {
        if (changed == this.defaultChoice) {
            // Delete filter choices.
            this.selectedChoice = null;
        } else {
            this.selectedChoice = changed.value;
        }

        this.menu.step.scout.account.setFilter(this.name, this.selectedChoice);
    }

    /**
     * Reset the price filter.
     */
    reset() {
        this.selectedChoice = null;
        this.menu.step.scout.account.setFilter(this.name, this.selectedChoice);
        this.loadChoice();

        super.reset();
    }

    /**
     * Check if the price filter is active.
     * @returns {boolean} A boolean representing whether the price filter is active or not.
     */
    isActive() {
        if (this.selectedChoice) {
            return true;
        }
        return false;
    }
}

/**
 * Class representing a FilterMenu.
 */
class FilterMenu {
    /**
     * Represents the node of the filter menu.
     * @type {Element}
     */
    node;

    /**
     * Represents the step object associated with the filter menu.
     * @type {object}
     */
    step;

    /**
     * Represents the filters in the filter menu.
     * @type {Array}
     */
    filters;

    /**
     * Represents the callback function to execute when a filter change occurs.
     * @type {function}
     */
    onChange;

    /**
     * Represents the course count node in the filter menu.
     * @type {Element}
     */
    courseCountNode;

    /**
     * Create a FilterMenu.
     * @param {object} step - The step object.
     * @param {function} onChange - The callback to execute when a filter change occurs.
     */
    constructor(step, onChange) {
        this.step = step;
        this.onChange = onChange;

        this.filters = [
            new CoursemodeFilter(this),
            new LocationFilter(this),
            new PriceFilter(this),
            new TimeofdayFilter(this),
            new FundingFilter(this),
        ];
    }

    /**
     * Get data from filters.
     * @return {object} The data from filters.
     */
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

    /**
     * Get the template from a specific URL.
     * @return {string} The text response from the fetch request.
     */
    async getTemplate() {
        const response = await fetch(
            "core51/wisyki/templates/courselist-step-filter.mustache"
        );
        return await response.text();
    }

    /**
     * Initialize the rendering of the filter menu.
     */
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

        this.bubbleNode = document.querySelector(".filter-bubble");

        this.update();
    }

    /**
     * Update the filter menu based on the active filters.
     */
    update() {
        let filtercount = 0;

        this.filters.forEach((filter) => {
            if (filter.isActive()) {
                filtercount++;
            }
        });

        this.bubbleNode.textContent = filtercount;
        if (filtercount > 0) {
            show(this.bubbleNode);
        } else {
            hide(this.bubbleNode);
        }
    }

    /**
     * Update the course count display.
     * @param {number} courseCount - The current count of courses.
     */
    updateCourseCount(courseCount) {
        if (courseCount < 0) {
            hide(this.courseCountNode);
            show(this.courseCountLoaderNode);
            this.courseCountNode.textContent = "";
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

    /**
     * Update the filter navigation based on the selected filter.
     */
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

    /**
     * Reset all filters.
     */
    reset() {
        this.filters.forEach((filter) => filter.reset());
        // this.onChange();
    }

    /**
     * Navigate to the next filter in the filter navigation.
     */
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

/**
 * Autocompleter class for handling autocomplete functionality.
 * @class
 */
class Autocompleter {
    /**
     * The input element for autocomplete.
     * @type {HTMLElement}
     */
    inputElm;

    /**
     * The output element for displaying autocomplete results.
     * @type {HTMLElement}
     */
    outputElm;

    /**
     * The clear element for clearing the autocomplete input.
     * @type {HTMLElement}
     */
    clearElm;

    /**
     * A step this autocompleter belongs to.
     * @type {Step}
     */
    step;

    /**
     * The request ID for tracking autocomplete requests.
     * @type {number}
     */
    requestID = 0;

    /**
     * The function to be called when an autocomplete option is selected.
     * @type {function}
     */
    onSelect;

    /**
     * The function to be called when the autocomplete input is cleared.
     * @type {function|null}
     */
    onClear;

    /**
     * Creates an instance of Autocompleter.
     * @constructor
     * @param {number} step - The step number.
     * @param {function} onSelect - The function to be called when an autocomplete option is selected.
     * @param {function|null} onClear - The function to be called when the autocomplete input is cleared.
     */
    constructor(step, onSelect, onClear = null) {
        this.step = step;
        this.onSelect = onSelect;
        this.onClear = onClear;
        this.inputElm = step.node.querySelector(".scout-autocomplete");
        this.outputElm = step.node.querySelector(".autocomplete-box output");
        this.clearElm = step.node.querySelector(".clear-input");

        // Set up actions of autocomplete input.
        this.inputElm.addEventListener(
            "input",
            debounce(async () => {
                this.autocomplete();
            }, 300)
        );

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

    /**
     * Clears the output element by removing its child nodes.
     * @returns {void}
     */
    clearOutput() {
        while (this.outputElm.lastChild) {
            this.outputElm.removeChild(this.outputElm.lastChild);
        }
    }

    /**
     * Clears the input field and hides the output container.
     * @param {HTMLElement} input - The input field to clear.
     * @param {HTMLElement} output - The output container to clear and hide.
     * @returns {void}
     */
    clearInput() {
        this.inputElm.value = "";
        hide(this.outputElm);
        this.clearOutput();
        if (this.onClear) {
            this.onClear();
        }
    }

    /**
     * Updates the autocomplete completions based on the response.
     * @param {Array} completions - The autocomplete completions.
     * @param {number} requestid - The request ID.
     * @returns {void}
     */
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
                //  Remove " (ESCO)" from completion.label string
                btn.textContent = completion.label.replace(/ +\(ESCO\)/, "");
                li.appendChild(btn);
                ul.appendChild(li);

                btn.addEventListener("mousedown", () => {
                    this.inputElm.value = completion.label;
                    this.clearOutput();
                    this.onSelect(completion.label, completion.uri);
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

    /**
     * Performs an asynchronous request to retrieve autocomplete suggestions based on a search term.
     * @returns {Promise<Array>} An array of autocomplete suggestions.
     */
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

    /**
     * Performs an autocomplete function and updates the displayed autocomplete results.
     */
    async autocomplete() {
        this.requestID = ++this.requestID;
        // Get and display autocomplete results.
        const completions = await this.requestCompletion();
        this.updateCompletions(completions, this.requestID);
    }
}

/**
 * Represents a LocationAutocompleter object that extends Autocompleter.
 * @extends Autocompleter
 */
class LocationAutocompleter extends Autocompleter {
    /**
     * Updates the completions based on the provided completions and request ID.
     * @param {Array} completions - The completions to update.
     * @param {number} requestid - The request ID.
     * @returns {void}
     */
    updateCompletions(completions, requestid) {
        if (requestid < this.requestID) {
            return;
        }
        this.clearOutput();

        const ul = document.createElement("ul");
        // Iterate through each suggestion.
        if (completions.length) {
            for (const completion of completions) {
                // Create a button for the completion and add it to the list.
                const li = document.createElement("li");
                const btn = document.createElement("button");
                // Check if completion.label contains headline.
                if (completion.label.includes("headline")) {
                    btn.textContent = Lang.getString("plz");
                    btn.classList.add("headline");
                    // Zwischenueberschrift
                    btn.setAttribute("disabled", "");
                } else {
                    btn.textContent = completion.label;
                    btn.addEventListener("mousedown", () => {
                        this.inputElm.value = completion.label;
                        this.clearOutput();
                        this.onSelect(completion.label, completion.value);
                    });
                }
                li.appendChild(btn);
                ul.appendChild(li);
            }
        } else {
            // If no suggestions were found for this category, display a message.
            const li = document.createElement("li");
            const btn = document.createElement("button");
            btn.textContent = Lang.getString("noresults");
            btn.setAttribute("disabled", "");
            li.appendChild(btn);
            ul.appendChild(li);
        }

        // Add the list of suggestions to the output element.
        this.outputElm.appendChild(ul);
    }

    /**
     * Performs an asynchronous request to retrieve autocomplete suggestions based on a search term.
     * @returns {Promise<Array>} An array of autocomplete suggestions.
     */
    async requestCompletion() {
        // Extract search term from input field.
        const searchterm = this.inputElm.value;

        // If search term is too short, return an empty array.
        if (searchterm.length < 2) {
            return [];
        }

        const params = {
            q: searchterm,
            type: "ort",
            limit: 512,
            timestamp: new Date().getTime(),
        };

        // Build and make request.
        const url = "./autosuggestplzort?" + new URLSearchParams(params);
        const response = await fetch(url, {
            headers: {
                "Content-Type": "text/html; charset=utf-8",
            },
        });

        // If response is successful, parse and return the suggestions from the response.
        if (response.ok) {
            // Concert character encoding from ISO-8859-1 to utf8 for fetch result.
            const buffer = await response.arrayBuffer();
            const decoder = new TextDecoder("iso-8859-1");
            const encoder = new TextEncoder();
            const isoText = decoder.decode(buffer);
            const utf8Text = encoder.encode(isoText);
            const result = new TextDecoder("utf-8").decode(utf8Text);

            if (result.trim() == "Keine Ortsvorschl&auml;ge m&ouml;glich|") {
                return [];
            } else {
                const data = result.split("\n");
                const response_data = [];
                for (let i = 0; i < data.length; i++) {
                    if (data[i] != "") {
                        const row = data[i].split("|");
                        response_data.push({
                            label: row[0],
                            value: row[1],
                        });
                    }
                }
                return response_data;
            }
        } else {
            console.error("HTTP-Error: " + response.status);
        }
    }
}

/**
 * Represents a Lang object for handling language strings.
 */
class Lang {
    /**
     * Private static property to store language strings.
     * @private
     * @type {Object}
     */
    static #langstrings;
    /**
     * Private static property to store the current language code.
     * @private
     * @type {string}
     */
    static #langcode;

    /**
     * Initializes the Lang class with the specified language code.
     * If no language code is provided, it defaults to "de" (German).
     * @param {string} [langcode="de"] - The language code to initialize with.
     * @returns {Promise} - A promise that resolves when the initialization is complete.
     */
    static async init(langcode = "de") {
        Lang.#langcode = langcode;
        const filepath = "core51/wisyki/lang/" + Lang.#langcode + ".json";
        const response = await fetch(filepath);
        Lang.#langstrings = await response.json();
    }

    /**
     * Retrieves a language string based on the provided key.
     * @param {string} key - The key of the language string.
     * @returns {string} The language string.
     */
    static getString(key) {
        if (!Lang.#langstrings.hasOwnProperty(key)) {
            console.error(
                'Key "' +
                    key +
                    '" not found in lang file "' +
                    Lang.#langcode +
                    '"'
            );
            return key;
        }
        return Lang.#langstrings[key];
    }

    /**
     * Renders a Mustache template with the provided view and partials.
     * @param {string} template - The Mustache template.
     * @param {Object} view - The view object.
     * @param {Object|null} partials - The partials object.
     * @param {Object|null} config - The configuration object.
     * @returns {string} The rendered template.
     */
    static render(template, view = {}, partials = null, config = null) {
        view.lang = Lang.#langstrings;
        return Mustache.render(
            Mustache.render(template, view, partials, config),
            view
        );
    }
}

/**
 * Sets the CSS property "--doc-height" to the height of the app.
 * @returns {void}
 */
function setCSSPropertyDocHeight() {
    document.documentElement.style.setProperty(
        "--doc-height",
        `${window.innerHeight}px`
    );
}

/**
 * Sets the virtual keyboard status based on the provided event.
 * @param {Event} event - The event object.
 * @returns {void}
 */
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

/**
 * Creates a debounced version of a function that delays its execution until a certain delay has passed
 * since the last invocation.
 *
 * @param {Function} func - The function to be debounced.
 * @param {number} delay - The delay in milliseconds before invoking the function.
 * @returns {Function} - The debounced version of the function.
 */
function debounce(func, delay) {
    return function (...args) {
        clearTimeout(timerId);
        timerId = setTimeout(() => {
            func.apply(this, args);
        }, delay);
    };
}
let timerId;
