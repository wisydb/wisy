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

    async addSkill(label, uri=null, levelGoal = null) {
        const skill = {
            uri: uri,
            label: label,
            levelGoal: levelGoal,
            isLanguageSkill: await this.isLanguageSkill(uri),
        };
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
    async isLanguageSkill(uri) {
        // Return false if uri does not start with http://data.europa.eu/esco/skill/ and is therefore not an actual esco uri.
        if (!uri || !uri.startsWith("http://data.europa.eu/esco/skill/")) {
            return false;
        }
        const params = { uri: uri };
        const url = "./esco/isLanguageSkill?" + new URLSearchParams(params);
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
        hide(this.nextButton, true);
        disable(this.prevButton);
        hide(this.prevButton, true);
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

    selectPath(pathname) {
        this.account.setPath(pathname);
        if (this.currentPath === this.getPath(pathname)) {
            // Nothing changes.
            return;
        }
        this.currentPath = this.getPath(pathname);

        console.log("Switch to " + pathname);
        this.currentPath.update(this.account.getStep());
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
        this.node = document.getElementById("path");
        this.node.innerHTML = html;
        this.node.setAttribute("currentPath", this.name);

        this.init();
    }

    init() {
        // Init #steps.
        this.stepsNode = this.node.querySelector("#steps");

        // Define UI action for aborting the scout.
        const scoutAbortBtn = this.node.querySelector("#scout-abort");
        scoutAbortBtn.addEventListener("click", () => this.scout.abort());

        // Define UI-action for ging to a specific step.
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
    }

    async update(index) {
        if (!this.isRendered()) {
            await this.#render();
        }

        this.updateStep(index);
    }

    async updateStep(stepIndex) {
        stepIndex = this.scout.checkStep(stepIndex);

        const step = this.steps[stepIndex];

        this.currentStep = step;
        this.scout.account.setStep(stepIndex);

        // Scroll to the step and hide the current step node
        this.scrollToStep(stepIndex);

        // Get the node for the current step and loader.
        const currentStepNode = this.stepsNode.querySelector(
            "#" + this.currentStep.name
        );
        const loader = currentStepNode.parentNode.querySelector(
            ".loader:not(.hidden)"
        );

        // Hide the step while it is beeing updated/rendered.
        hide(currentStepNode, true); // Check for a loader element and determine if the step content has already been loaded.

        // Update step.
        await this.currentStep.update();

        // If a loader element was found, hide it and show the current step node with a delay.
        if (loader) {
            hide(loader, true);
            setTimeout(() => hide(loader), 300);
            setTimeout(() => show(currentStepNode), 100);
        } else {
            show(currentStepNode);
        }

        // Update navigation bar.
        this.updateScoutNav();
    }

    updateScoutNav() {
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
    constructor(scout) {
        super(scout);
        this.name = "occupation";
        this.steps = [
            new PathStep(this.scout, this),
            new OccupationStep(this.scout, this),
            new OccupationSkillsStep(this.scout, this),
            new SkillsStep(this.scout, this),
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

    constructor(scout, path) {
        if (this.constructor == Step) {
            throw new Error("Abstract classes can't be instantiated.");
        }

        this.scout = scout;
        this.path = path;
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

    init() {}

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

    async update() {
        if (!this.isRendered()) {
            console.log("is not rendered");
            await this.#render();
        }
        this.updateNavButtons();
    }

    updateNavButtons() {
        if (this.isFirst()) {
            hide(this.scout.prevButton, true);
            disable(this.scout.prevButton);
        } else {
            show(this.scout.prevButton);
            enable(this.scout.prevButton);
        }

        if (this.isLast()) {
            hide(this.scout.nextButton, true);
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

    init() {
        super.init();
        const pathBtns = this.node.querySelectorAll(".path-button-list button");
        pathBtns.forEach((btn) => {
            btn.addEventListener("click", () => {
                this.scout.selectPath(btn.getAttribute("pathname"));
                this.scout.update("next");
            });
        });
    }

    updateNavButtons() {
        if (this.isFirst()) {
            hide(this.scout.prevButton, true);
            disable(this.scout.prevButton);
        } else {
            show(this.scout.prevButton);
            enable(this.scout.prevButton);
        }

        if (this.isLast()) {
            hide(this.scout.nextButton, true);
            disable(this.scout.nextButton);
        } else {
            show(this.scout.nextButton);
            disable(this.scout.nextButton);
        }
    }

    prepareData() {
        const data = super.prepareData();
        data.paths = [];
        for (const key in this.scout.paths) {
            data.paths.push(this.scout.paths[key].prepareData());
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
    /**
     * Autocompleter
     * @type {Autocompleter}
     */
    autocompleter;

    constructor(scout, path) {
        super(scout, path);
        this.name = "occupation";
    }

    init() {
        super.init();
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
        return this.scout.account.getPath() != null;
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

    constructor(scout, path) {
        super(scout, path);
        this.name = "occupation-skills";
    }

    checkPrerequisites() {
        return (
            this.scout.account.getPath() != null &&
            this.scout.account.getOccupation() != null
        );
    }

    init() {
        super.init();

        // Set up the UI components.
        this.occupationNode = this.node.querySelector(".selected-occupation");
        this.skillsNode = this.node.querySelector(".selectable-skills");
    }

    async update() {
        await super.update();

        const occupation = this.scout.account.getOccupation();
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
            checkbox.setAttribute("id", uri);
            li.appendChild(checkbox);
            const label = document.createElement("label");
            label.setAttribute("for", suggestion.uri);
            label.textContent = suggestion.label;
            li.appendChild(label);
            checkboxes.push(checkbox);

            // On change event, add or remove the skill associated with the checkbox and update the view.
            checkbox.addEventListener("change", async () => {
                if (checkbox.checked) {
                    await this.scout.account.addSkill(suggestion.label, uri);
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
    updateSkillSelection(checkboxes) {
        const skills = this.scout.account.getSkills();
        checkboxes.forEach((checkbox) => {
            if (checkbox.getAttribute("id") in skills) {
                checkbox.setAttribute("checked", "");
            } else {
                checkbox.removeAttribute("checked");
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
class SkillsStep extends Step {
    /**
     * Autocompleter
     * @type {Autocompleter}
     */
    autocompleter;
    selectedSkillsNode;

    constructor(scout, path) {
        super(scout, path);
        this.name = "skills";
    }

    checkPrerequisites() {
        return this.scout.account.getPath() != null;
    }

    init() {
        super.init();

        // Set up UI-elements.
        this.selectedSkillsNode = this.node.querySelector(".selectable-skills");

        this.autocompleter = new Autocompleter(
            this,
            async (label, uri) => {
                await this.scout.account.addSkill(label, uri);
                this.showSelectedSkills();
                this.autocompleter.clearInput();
            }
        );
    }

    async update() {
        await super.update();

        this.showSelectedSkills();
    }

    /**
     * Fills the suggestion list in the UI with the given suggestions.
     *
     * @param {object} suggestions The suggestions to be displayed.
     *
     * @returns {void}
     */
    showSelectedSkills() {
        // while (this.skillsNode.lastChild) {
        //     this.skillsNode.removeChild(this.skillsNode.lastChild);
        // }

        const checkboxes = [];

        // For every selected skill, display a checkbox.
        // Check if there are skills that are already selected and set the checkbox state accordingly.
        const skills = this.scout.account.getSkills();
        for (const uri in skills) {
            let unchecked = null;
            const skill = skills[uri];
            // Check if the checkbox already exists.
            let checkbox = this.selectedSkillsNode.querySelector('input[name="' + skill.label + '"]');
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
            checkbox.setAttribute("id", uri);
            li.appendChild(checkbox);
            const label = document.createElement("label");
            label.setAttribute("for", skill.uri);
            label.textContent = skill.label;
            li.appendChild(label);
            if (unchecked) {
                this.selectedSkillsNode.insertBefore(
                    li,
                    unchecked.parentNode
                );
                this.selectedSkillsNode.removeChild(unchecked.parentNode);
            } else {
                this.selectedSkillsNode.insertBefore(
                    li,
                    this.selectedSkillsNode.firstChild
                );
            }
            checkboxes.push(checkbox);

            // On change event, add or remove the skill associated with the checkbox and update the view.
            checkbox.addEventListener("change", async (event) => {
                if (event.target.checked) {
                    await this.scout.account.addSkill(skill.label, uri);
                } else {
                    this.scout.account.removeSkill(uri);
                }
                this.showSelectedSkills();
                this.updateSkillSelection(checkboxes);
            });
        }

        this.updateSkillSelection(checkboxes);
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
        checkboxes.forEach((checkbox) => {
            if (checkbox.getAttribute("id") in skills) {
                checkbox.setAttribute("checked", "");
            } else {
                checkbox.removeAttribute("checked");
            }
        });
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
        console.log(completions);
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
                    console.log(completion);
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
        return Mustache.render(template, view, partials, config);
    }
}
