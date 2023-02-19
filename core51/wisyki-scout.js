/**
 * Client logic for the WISY-KI feature "Weiterbildungsscoiut".
 *
 * A step-by-step guided process to access the skill profile of the user and their lerning goals
 * for the purpose of making well fitting skill-based course recommendations.
 *
 * @copyright   2023 ISy TH Lübeck <dev.ild@th-luebeck.de>
 * @author		Pascal Hürten <pascal.huerten@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

window.onload = init;

// Reload page when navigating to scout using history traversal.
let requestReload = false;
addEventListener("pageshow", function (event) {
    if (event.persisted && requestReload) {
        window.location.reload();
    }
});

// Scroll the current step back into view, when user resizes the window.
addEventListener("resize", () => {
    scrollToStep(currentStep());
});

let nextButton;
let prevButton;
let stepsNode;
let currentStepNode;
let currentStepName;
let scoutNavSteps;
let skillProfile;
const searchResults = [];
let currentSearchResult;
const amountOfSkillSuggestionsShownByDefault = 6;
const amountOfCoursesToRecommend = 4;
const maxSkills = 5;

const steps = [
    "occupation",
    "occupationSkills",
    "skills",
    "currentLevel",
    "levelGoal",
    "resultOverview",
    "resultList",
];

const compLevels = {
    O: "ohne Vorkenntnisse",
    A: "Grundstufe",
    B: "Aufbaustufe",
    C: "Fortgeschrittenenstufe",
    D: "Expert*innenstufe",
};

const languageLevels = {
    O: "ohne Vorkenntnisse",
    A1: "A1",
    A2: "A2",
    B1: "B1",
    B2: "B2",
    C1: "C1",
    C2: "C2",
};

/**
 * Initializes the web application.
 *
 * This function is typically called when the web page finishes loading.
 * Initialise properties that are used throughout the scout independent of the current step
 * and add EventListeners to establish basic user ineractivity with the UI.
 *
 * @returns {void}
 */
function init() {
    // Get skillProfile from localStorage or set a new empty one if there is none stored yet.
    skillProfile = parseLocalStorage("skillProfile", {
        occupation: {},
        skills: {},
    });
    localStorage.setItem("skillProfile", JSON.stringify(skillProfile));
    // Update the vaiable skillProfile whenever the localStorage is updated.
    addEventListener("storage", () => {
        skillProfile = parseLocalStorage("skillProfile", {
            occupation: {},
            skills: {},
        });
    });

    stepsNode = document.querySelector("#steps");

    // Burger animation.
    const menuBtn = document.querySelector(".menu-btn");
    menuBtn.addEventListener("click", () => {
        if (!menuBtn.classList.contains("open")) {
            menuBtn.classList.add("open");
        } else {
            menuBtn.classList.remove("open");
        }
    });

    // Define UI action for aborting the scout.
    const scoutAbortBtn = document.querySelector("#scout-abort");
    scoutAbortBtn.addEventListener("click", () => {
        localStorage.clear();
        requestReload = true;
        window.location.href = "/";
    });

    // Define UI-action for ging to a specific step.
    scoutNavSteps = document.querySelectorAll(".scout-nav__progress ul li");
    const scoutNavBtns = document.querySelectorAll(
        ".scout-nav__progress ul li button"
    );
    scoutNavBtns.forEach((btn) => {
        btn.addEventListener("click", () =>
            showStep(btn.getAttribute("to-step"))
        );
    });

    // Define UI-action for going to the next or previous step.
    nextButton = document.querySelector("#next-step");
    prevButton = document.querySelector("#prev-step");
    nextButton.addEventListener("click", () => showStep(nextStep()));
    prevButton.addEventListener("click", () => showStep(prevStep()));

    // Disable and hide the "next"- and "prev"-buttons by default
    disable(nextButton);
    hide(nextButton, true);
    disable(prevButton);
    hide(prevButton, true);

    // Load and show the current step.
    showStep(currentStep());
}

/**
 * Retrieves a value from the browser's localStorage, parses it as JSON, and returns it.
 * If the value does not exist or cannot be parsed as JSON, the default value is returned instead.
 *
 * @param {string} key The key of the value to retrieve from localStorage.
 * @param {*} defaultValue The default value to return if the value for the specified
 * key is not found or cannot be parsed as JSON.
 *
 * @returns {*} The parsed value retrieved from localStorage,
 * or the default value if the specified key is not found or cannot be parsed.
 */
function parseLocalStorage(key, defaultValue) {
    let value;
    try {
        value = JSON.parse(localStorage.getItem(key));
    } catch (err) {
        console.error(`Error parsing ${key} from localStorage:`, err);
    }
    return value || defaultValue;
}

/**
 * Shows the specified step by loading the corresponding content and updating the display,
 * while also updating the state of the application.
 *
 * @param {number} step The index of the step to show.
 *
 * @returns {Promise} A Promise that resolves when the step is displayed and the state is updated.
 */
async function showStep(step) {
    // Ensure the step index is valid and all the requirements are met.
    step = checkStep(step);
    // If the current step is already the same as the requested step, do nothing.
    if (currentStepName == steps[step]) {
        return;
    }
    // Update the current step name and save it to localStorage.
    currentStepName = steps[step];
    localStorage.setItem("currentStep", step);

    // Get the node for the current step
    currentStepNode = stepsNode.querySelector("#" + currentStepName);

    // Check for a loader element and determine if the step content has already been loaded.
    const loader = currentStepNode.parentNode.querySelector(
        ".loader:not(.hidden)"
    );
    const isStale = currentStepNode.firstChild != null;

    // Scroll to the step and hide the current step node
    scrollToStep(step);
    hide(currentStepNode, true);

    // If the step content has not been loaded yet, load it.
    if (!isStale) {
        await loadStep(currentStepName);
    }

    // Initialize the step based on its name.
    switch (currentStepName) {
        case "occupation":
            initOccupationStep(isStale);
            break;
        case "occupationSkills":
            await initOccupationSkillsStep(isStale);
            break;
        case "skills":
            initSkillsStep(isStale);
            break;
        case "currentLevel":
            initCurrentLevelStep(isStale);
            break;
        case "levelGoal":
            initLevelGoalStep(isStale);
            break;
        case "resultOverview":
            initResultOverviewStep(isStale);
            break;
        case "resultList":
            initResultListStep(isStale);
            break;
    }

    // If a loader element was found, hide it and show the current step node with a delay.
    if (loader) {
        hide(loader, true);
        setTimeout(() => hide(loader), 300);
        setTimeout(() => show(currentStepNode), 100);
    } else {
        show(currentStepNode);
    }

    // Update the navigation display.
    updateScoutNav(step);
}

/**
 * Loads the HTML content for the specified step and adds it to the current step node in the DOM.
 *
 * @param {string} stepName The name of the step to load, used to construct the URL for the HTML content.
 *
 * @returns {Promise} A Promise that resolves when the step content has been loaded and added to the current step node.
 */
async function loadStep(stepName) {
    const url = "./scout/" + stepName;
    const response = await fetch(url);

    // If the response is successful, get the HTML content and add it to the current step node.
    if (response.ok) {
        const result = await response.text();
        currentStepNode.insertAdjacentHTML("beforeend", result);
    } else {
        // If the response is not successful, log an error message with the HTTP status code.
        console.error(
            "An HTTP-Error occured while loading a step: " + response.status
        );
    }
}

/**
 * Initializes the "occupation" step of the wizard or updates the display if it wasnt loaded in freshly.
 *
 * @param {boolean} isOld A boolean indicating whether the step is already loaded or not.
 *
 * @returns {void}
 */
function initOccupationStep(isStale) {
    // Set up UI-elements.
    const autocompleteInput =
        currentStepNode.querySelector(".esco-autocomplete");
    const clearInputButton = currentStepNode.querySelector(".clear-input");
    const autocompleteOutput = currentStepNode.querySelector(
        ".autocomplete-box output"
    );
    const toSkillStepBtn = currentStepNode.querySelector(".to-skill-step-btn");

    // Adds EventListeners only if they haven't benn set yet.
    if (!isStale) {
        // Set click-action to skip to step 2.
        toSkillStepBtn.addEventListener("click", () => showStep(2));

        // Set up actions of autocomplete input.
        autocompleteInput.addEventListener("keyup", async () => {
            const requestID = ++currentRequestID;
            // Get and display autocomplete results.
            const concepts = await autocomplete(autocompleteInput, requestID);
            const conceptBtns = showAutocompleteResult(
                concepts,
                autocompleteOutput,
                requestID
            );

            // Update step completion state on selecting an occupation from the autocomplete reults.
            conceptBtns.forEach((btn) =>
                btn.addEventListener("mousedown", () =>
                    completeStepOccupation(
                        autocompleteInput,
                        btn.getAttribute("name"),
                        btn.getAttribute("id")
                    )
                )
            );
        });

        // Set up action to clear autocomplete-input.
        clearInputButton.addEventListener("click", () => {
            // Reset autocomplete input and reults.
            clearAutocompleteInput(autocompleteInput, autocompleteOutput);

            // Unset a previously selected occupation.
            skillProfile.occupation = {};
            localStorage.setItem("skillProfile", JSON.stringify(skillProfile));

            // Update step completeion state.
            disable(nextButton);
            autocompleteInput.focus();
        });

        // Show autocomplete reults only while the input is focused.
        hide(autocompleteOutput);
        autocompleteInput.addEventListener("focus", () =>
            show(autocompleteOutput)
        );
        autocompleteInput.addEventListener("blur", () =>
            hide(autocompleteOutput)
        );
    }

    // Set default state for navigation buttons.
    hide(prevButton, true);
    disable(prevButton);
    show(nextButton);
    disable(nextButton);

    // Set initial state of step completion.
    if (skillProfile.occupation.label && skillProfile.occupation.uri) {
        // Step is completed when an occupation is set and stored in the localStorage.
        completeStepOccupation(
            autocompleteInput,
            skillProfile.occupation.label,
            skillProfile.occupation.uri
        );
    }

    // Focus the autocomplete input with a delay.
    // Delay is necessary to prevent window-scrolling issues
    // that can happen when the node is foused while still offscreen.
    if (!autocompleteInput.value) {
        focusDelayed(autocompleteInput);
    }
}

/**
 * Initializes the "occupation skills" step, including setting up the UI components and event listeners,
 * as well as fetching and displaying suggested skills for the selected occupation.
 *
 * @param {boolean} isStale Indicates if the occupation skills data is stale and needs to be refreshed.
 *
 * @returns {Promise} A Promise that resolves when the initialization is complete.
 */
async function initOccupationSkillsStep(isStale) {
    // Set up the UI components.
    const occupationNode = currentStepNode.querySelector(
        ".selected-occupation"
    );
    const skillsNode = currentStepNode.querySelector(".selectable-skills");
    const more = currentStepNode.querySelector(".more-skills-btn");
    const less = currentStepNode.querySelector(".less-skills-btn");
    currentStepNode.querySelector(".maxSkills").textContent = maxSkills;

    show(prevButton);
    enable(prevButton);
    show(nextButton);
    enable(nextButton);
    hide(more);
    hide(less);

    // Check if the step is loaded for the first time or
    // the selected occupation has changed, since the step was shown last.
    // Then update the displayed skillSiggestions for the selected occupation.
    if (
        !isStale ||
        occupationNode.textContent !== skillProfile.occupation.label
    ) {
        while (skillsNode.lastChild) {
            skillsNode.removeChild(skillsNode.lastChild);
        }

        occupationNode.textContent = skillProfile.occupation.label;
        const suggestions = await suggestSkills(skillProfile.occupation.uri);
        showSkillSuggestions(suggestions, skillsNode);
    }

    // Display a reduced amount of skills by default,
    // when the amount of skill-suggestions is bigger than a set number.
    const skills = skillsNode.querySelectorAll(".selectable-skills li");
    if (skills.length > amountOfSkillSuggestionsShownByDefault) {
        showLessSkills(skills, more, less);
    }

    // Setup the inital state of the selectable skill sugestions.
    const checkboxes = skillsNode.querySelectorAll(".selectable-skills input");
    updateSkillSelection(checkboxes);

    // Set up UI-actions to show or hide more skill suggestions.
    if (!isStale) {
        more.addEventListener("click", () =>
            showMoreSkills(skills, more, less)
        );
        less.addEventListener("click", () =>
            showLessSkills(skills, more, less)
        );
    }
}

/**
 * Initializes the "skill" step, including setting up the UI components and event listeners,
 * as well as fetching and displaying already selected skills.
 *
 * @param {boolean} isStale Indicates if the occupation skills data is stale and needs to be refreshed.
 *
 * @returns {Promise} A Promise that resolves when the initialization is complete.
 */
function initSkillsStep(isStale) {
    const autocompleteInput =
        currentStepNode.querySelector(".esco-autocomplete");
    const clearInputButton = currentStepNode.querySelector(".clear-input");
    const autocompleteOutput = currentStepNode.querySelector(
        ".autocomplete-box output"
    );
    const skillsNode = currentStepNode.querySelector(".selectable-skills");
    currentStepNode.querySelector(".maxSkills").textContent = maxSkills;

    // Set up UI-actions if the step was loaded freshly.
    if (!isStale) {
        // Get and disply autocomplete results for skills based on user input.
        autocompleteInput.addEventListener("keyup", async () => {
            const requestID = ++currentRequestID;
            const concepts = await autocomplete(autocompleteInput, requestID);
            const conceptBtns = showAutocompleteResult(
                concepts,
                autocompleteOutput,
                requestID
            );

            // Select a skill from autocomplete reults and reset the auocomplete-nodes state.
            conceptBtns.forEach((btn) =>
                btn.addEventListener("mousedown", async () => {
                    await addSelectedSkill(btn.id, btn.name);
                    updateSelectedSkills(skillsNode, autocompleteInput);
                    clearAutocompleteInput(
                        autocompleteInput,
                        autocompleteOutput
                    );
                    autocompleteInput.focus();
                })
            );
        });

        // Set UI-action for clearing the autocomplete input.
        clearInputButton.addEventListener("click", () => {
            clearAutocompleteInput(autocompleteInput, autocompleteOutput);
        });

        // Show autocomplete results only while the input is focused.
        hide(autocompleteOutput);
        autocompleteInput.addEventListener("focus", () =>
            show(autocompleteOutput)
        );
        autocompleteInput.addEventListener("blur", () =>
            hide(autocompleteOutput)
        );
    }

    // Show previous and next buttons and enable them.
    show(prevButton);
    enable(prevButton);
    show(nextButton);
    disable(nextButton);

    // Set and show wich skills are selected.
    updateSelectedSkills(skillsNode, autocompleteInput, (rebuild = true));

    // Focus the auocomplete input, if empty.
    if (!autocompleteInput.value) {
        focusDelayed(autocompleteInput);
    }
}

/**
 * Toggles the display of a modal.
 *
 * @param {HTMLElement} modal The modal to toggle the display of.
 */
function toggleModal(modal) {
    if (modal.classList.contains("display-none")) {
        show(modal);
    } else {
        hide(modal);
    }
}

/**
 * Displays a specific tab in a modal and sets the corresponding button as selected.
 *
 * @param {HTMLElement[]} tabBtns An array of buttons to select from.
 * @param {HTMLElement[]} tabs An array of tabs to choose from.
 * @param {HTMLElement} tabBtn The button that was clicked to show a specific tab.
 *
 * @returns {void}
 */
function showModalTab(tabBtns, tabs, tabBtn) {
    // Unselect and enable all buttons but the one that was clicked.
    tabBtns.forEach((btn) => {
        if (btn === tabBtn) {
            disable(tabBtn);
            tabBtn.classList.add("selected");
        } else {
            btn.classList.remove("selected");
            enable(btn);
        }
    });

    // Show the selected tab, hide the other tabs.
    const tabName = tabBtn.getAttribute("for");
    currentStepNode.querySelector('.tabs [for="' + tabName + '"]');
    tabs.forEach((tab) => {
        if (tab.getAttribute("name") === tabName) {
            show(tab);
        } else {
            hide(tab);
        }
    });
}

/**
 * Sets up the "current level" step, which displays the user's current skill levels
 * and allows them to update those levels.
 *
 * @param {boolean} isStale Flag indicating whether the step should be refreshed.
 */
function initCurrentLevelStep(isStale) {
    // Show previous and next buttons and enable them.
    show(prevButton);
    enable(prevButton);
    show(nextButton);
    enable(nextButton);

    // If the step is not stale, set up the level explanation modal.
    if (!isStale) {
        // Set up tab navigation for the modal
        const tabNavBtns =
            currentStepNode.querySelectorAll(".modal .tab-nav li");
        const tabs = currentStepNode.querySelectorAll(".tab");
        tabNavBtns.forEach((btn) =>
            btn.addEventListener("click", () =>
                showModalTab(tabNavBtns, tabs, btn)
            )
        );

        // Set up the level explanation modal and its associated button.
        const modalBtn = currentStepNode.querySelector(".open-modal-btn");
        const modal = currentStepNode.querySelector(".modal.level-explanation");
        modalBtn.addEventListener("click", () => toggleModal(modal));

        // Close the modal if the close button is clicked or somewhere outside of the modal.
        modal.addEventListener(
            "click",
            (event) => {
                if (currentStepName !== "currentLevel") {
                    return;
                }
                if (
                    (event.target.matches(".close-modal-btn") ||
                        !event.target.closest(".modal") ||
                        event.target.matches(".backdrop")) &&
                    !event.target.closest(".open-modal-btn")
                ) {
                    hide(modal);
                }
            },
            false
        );
    }

    // Clear the current-level-selection list.
    const levelSelectionList = currentStepNode.querySelector(
        "ul.current-level-selection"
    );
    while (levelSelectionList.lastChild) {
        levelSelectionList.removeChild(levelSelectionList.lastChild);
    }

    // For each skill in the user's skill profile, create an li element with a label displaying the skill name
    // and its current level, and a select element allowing the user to update the skill's level.
    for (uri in skillProfile.skills) {
        const skill = skillProfile.skills[uri];
        const li = document.createElement("li");

        // Create skill title.
        const skillTitle = document.createElement("p");
        skillTitle.classList.add("skill-title");
        skillTitle.textContent = skill.label;
        li.appendChild(skillTitle);

        const currentLevel = document.createElement("p");
        currentLevel.classList.add("current-level");
        // If no current level is set yet, choose "O" by default.
        if (!skill.currentLevel) {
            skill.currentLevel = "O";
        }
        currentLevel.classList.add("level-" + skill.currentLevel);
        let skillLevels = compLevels;
        // If the skill is a language skill, show language levels.
        if (skill.isLanguageSkill) {
            skillLevels = languageLevels;
        }
        currentLevel.textContent = skillLevels[skill.currentLevel];

        const select = getCurrentLevelSelectElement(skill);
        li.appendChild(currentLevel);
        li.appendChild(select);
        levelSelectionList.appendChild(li);

        // Add UI-action for all select elements to update the skill's level.
        select.addEventListener("change", (event) => {
            event.preventDefault();
            skillProfile.skills[select.getAttribute("id")].currentLevel =
                select.value;
            currentLevel.textContent = skillLevels[select.value];
            currentLevel.className = "";
            currentLevel.classList.add("current-level");
            currentLevel.classList.add("level-" + select.value);
            select.value = "";
            localStorage.setItem("skillProfile", JSON.stringify(skillProfile));
        });
    }
}

/**
 * Sets up the "level goal" step, which displays the user's current skill level and a level-goal
 * and allows them to update those level-goals.
 *
 * @param {boolean} isStale Flag indicating whether the step should be refreshed.
 */
function initLevelGoalStep(isStale) {
    // Display and enable previous and next buttons.
    show(prevButton);
    enable(prevButton);
    show(nextButton);
    enable(nextButton);

    // If modal content is not stale, set up level explantion modal.
    if (!isStale) {
        // Set up event listeners for modal tabs.
        const tabNavBtns =
            currentStepNode.querySelectorAll(".modal .tab-nav li");
        const tabs = currentStepNode.querySelectorAll(".tab");
        tabNavBtns.forEach((btn) =>
            btn.addEventListener("click", () =>
                showModalTab(tabNavBtns, tabs, btn)
            )
        );

        // Set up event listener for modal open button.
        const modalBtn = currentStepNode.querySelector(".open-modal-btn");
        const modal = currentStepNode.querySelector(".modal.level-explanation");
        modalBtn.addEventListener("click", () => toggleModal(modal));

        // Close the modal if the close button is clicked or somewhere outside of the modal.
        document.addEventListener(
            "click",
            (event) => {
                if (currentStepName !== "levelGoal") {
                    return;
                }
                if (
                    (event.target.matches(".close-modal-btn") ||
                        !event.target.closest(".modal__content")) &&
                    !event.target.closest(".open-modal-btn")
                ) {
                    hide(modal);
                }
            },
            false
        );
    }

    // Create and update level goal selection list
    const levelSelectionList = currentStepNode.querySelector(
        "ul.level-goal-selection"
    );
    while (levelSelectionList.lastChild) {
        levelSelectionList.removeChild(levelSelectionList.lastChild);
    }

    // For each skill in the user's skill profile, create an li element with a label displaying the skill name
    // and its current level, the level goal and a select element allowing the user to update the skill's level goal.
    for (uri in skillProfile.skills) {
        const skill = skillProfile.skills[uri];
        const li = document.createElement("li");

        // Create skill title.
        const skillTitle = document.createElement("p");
        skillTitle.classList.add("skill-title");
        skillTitle.textContent = skill.label;
        li.appendChild(skillTitle);

        // Create current level section.
        const levelDiv = document.createElement("div");
        const currentLevelDiv = document.createElement("div");
        const currentLevelLabel = document.createElement("label");
        currentLevelLabel.textContent = "Deine Einschätzung";
        currentLevelDiv.appendChild(currentLevelLabel);
        const currentLevel = document.createElement("p");
        currentLevel.classList.add("current-level");
        // If no current level is set yet, choose "O" by default.
        if (!skill.currentLevel) {
            skill.currentLevel = "O";
        }
        currentLevel.classList.add("level-" + skill.currentLevel);
        let skillLevels = compLevels;
        // If the skill is a language skill, show language levels.
        if (skill.isLanguageSkill) {
            skillLevels = languageLevels;
        }
        currentLevel.textContent = skillLevels[skill.currentLevel];
        currentLevelDiv.appendChild(currentLevel);
        levelDiv.appendChild(currentLevelDiv);

        // Create level goal section.
        const levelGoalDiv = document.createElement("div");
        const levelGoalLabel = document.createElement("label");
        const levelGoal = document.createElement("p");
        levelGoal.classList.add("level-goal");
        // If a level goal is set already, show that.
        if (skill.levelGoal) {
            levelGoalLabel.textContent = "Dein Ziel";
            levelGoal.textContent = skillLevels[skill.levelGoal];
            levelGoal.classList.add("level-" + skill.levelGoal);
        } else {
            // Otherwise show a suggestions wich is by default the next level from the current level.
            levelGoalLabel.textContent = "Mein Vorschlag";
            // Get the level after the current level.
            let levelIndex =
                Object.keys(skillLevels).indexOf(skill.currentLevel) + 1;
            if (levelIndex >= Object.keys(skillLevels).length) {
                levelIndex = Object.keys(skillLevels).length - 1;
            }
            levelGoal.textContent =
                skillLevels[Object.keys(skillLevels)[levelIndex]];
            levelGoal.classList.add(
                "level-" + Object.keys(skillLevels)[levelIndex]
            );
            skill.levelGoal = Object.keys(skillLevels)[levelIndex];
            skillProfile.skills[skill.uri].levelGoal = skill.levelGoal;
            // Update the skillProfile with the suggested level goals.
            localStorage.setItem("skillProfile", JSON.stringify(skillProfile));
        }
        // Build the level goal selection.
        const select = getLevelGoalSelectElement(skill);
        // Add everything to the document.
        levelGoalDiv.appendChild(levelGoalLabel);
        levelGoalDiv.appendChild(levelGoal);
        levelGoalDiv.appendChild(select);
        levelDiv.appendChild(levelGoalDiv);
        li.appendChild(levelDiv);
        levelSelectionList.appendChild(li);

        // Set UI-action to update the level goal when the user makes a selection.
        select.addEventListener("change", (event) => {
            event.preventDefault();
            skillProfile.skills[select.getAttribute("id")].levelGoal =
                select.value;
            levelGoal.textContent = skillLevels[select.value];
            levelGoal.className = "";
            levelGoal.classList.add("level-goal");
            levelGoal.classList.add("level-" + select.value);
            select.value = "";
            localStorage.setItem("skillProfile", JSON.stringify(skillProfile));
        });
    }
}

/**
 * Updates the result overview step with the given skill profile by
 * creating a list of skills, their corresponding level goals, and
 * a button that triggers a search for courses associated with each skill.
 *
 * @param {boolean} _isStale Flag indicating whether or not the
 * current step is stale.
 *
 * @returns {void}
 */
function initResultOverviewStep(_isStale) {
    // Show and enable the previous button, and hide and disable the next button.
    show(prevButton);
    enable(prevButton);
    hide(nextButton);
    disable(nextButton);

    // Select the list element that will display the search information.
    const resultList = currentStepNode.querySelector("ul.result-list");
    // Remove all stale children from the list.
    while (resultList.lastChild) {
        resultList.removeChild(resultList.lastChild);
    }

    // Iterate through the skills in the skill profile and create a list item
    // for each skill, including its title, level goal, and search button.
    for (uri in skillProfile.skills) {
        const skill = skillProfile.skills[uri];

        const li = document.createElement("li");
        const skillTitle = document.createElement("p");
        skillTitle.classList.add("skill-title");
        skillTitle.textContent = skill.label;
        li.appendChild(skillTitle);

        const levelGoalNode = document.createElement("p");
        levelGoalNode.classList.add("level-goal");
        if (!skill.levelGoal) {
            skill.levelGoal = "A";
        }
        levelGoalNode.classList.add("level-" + skill.levelGoal);
        if (skill.isLanguageSkill) {
            levelGoalNode.textContent = languageLevels[skill.levelGoal];
        } else {
            levelGoalNode.textContent = compLevels[skill.levelGoal];
        }
        li.appendChild(levelGoalNode);

        const resultLink = document.createElement("button");
        resultLink.classList.add("result-link");
        resultLink.classList.add("btn-link");
        resultLink.setAttribute("skill-uri", skill.uri);
        disable(resultLink);
        const loader = document.createElement("span");
        loader.classList.add("loader");
        resultLink.appendChild(loader);
        const resultText = document.createElement("span");
        resultText.textContent = "Suche Kurse";
        resultLink.appendChild(resultText);
        li.appendChild(resultLink);
        resultList.appendChild(li);

        // Populate the course-search button with a preview of the searchresults.
        getSearchResultPreview(resultLink, resultText, loader, skill);
    }
}

/**
 * Gets the search results preview for a given skill and updates the UI accordingly.
 *
 * @param {HTMLElement} output The output element to update with the search results preview.
 * @param {HTMLElement} text The text element to update with the search results preview.
 * @param {HTMLElement} loader The loader element to remove after the search is prepared.
 * @param {Object} skill A skill object to use for the search.
 *
 * @returns {void}
 */

async function getSearchResultPreview(output, text, loader, skill) {
    // Prepare the search by calling the prepareSearch function and getting the response.
    const response = await prepareSearch(skill);

    // Set the search id to the output element's attribute.
    output.setAttribute("search-id", response.id);
    if (response.count == 1) {
        text.textContent = "1 Kurs gefunden";
    } else {
        text.textContent = response.count + " Kurse gefunden";
    }
    // Remove the loader spinner from the output element.
    loader.remove();

    // If there is at least one search result, enable the output element
    // and add a click listener to show the search result in the next step.
    if (response.count > 0) {
        enable(output);
        output.addEventListener("click", () => {
            currentSearchResult = response.query;
            showStep(steps.indexOf("resultList"));
        });
    }
}

/**
 * Gets a search preview for courses based on a given skill and a level goal.
 *
 * @param {Object} skill A skill object with the label and levelGoal properties.
 *
 * @returns {Object} The response object containing the search ID and the number of courses found.
 */
async function prepareSearch(skill) {
    let levelGoal = skill.levelGoal;
    if (!skill.isLanguageSkill) {
        levelGoal = "Niveau " + levelGoal;
    }
    const params = { prepare: true, label: skill.label, level: levelGoal };

    const url = "./scout-search?" + new URLSearchParams(params);
    const response = await fetch(url);

    if (response.ok) {
        const result = await response.json();
        searchResults[result.query] = result;
        return result;
    } else {
        console.error("HTTP-Error: " + response.status);
    }
}

/**
 * This function takes in a search object and performs a search with the specified skill label, level goal, and amount of courses to recommend.
 * If the request is successful, the resulting data is parsed into JSON format and saved to the searchResults object with the response's ID as the key.
 * @param {Object} search The search object containing the skill label and level goal.
 * @returns {Object} The resulting data from the search, including the search ID and recommended courses.
 */
async function search(search) {
    const params = {
        label: search.skill.label,
        level: search.skill.levelGoal,
        limit: amountOfCoursesToRecommend,
    };

    const url = "./scout-search?" + new URLSearchParams(params);
    const response = await fetch(url);

    if (response.ok) {
        const result = await response.json();
        searchResults[result.id] = result;
        return result;
    } else {
        console.error("HTTP-Error: " + response.status);
    }
}

/**
 * Initializes the result list step, updating the DOM to show the
 * search results for the currently searched skill and level.
 *
 * @param {boolean} _isStale Flag indicating whether or not the current step is stale and needs to be refreshed.
 *
 * @returns {void}
 */
function initResultListStep(_isStale) {
    // Show previous button and enable it, hide next button and disable it.
    show(prevButton);
    enable(prevButton);
    hide(nextButton);
    disable(nextButton);

    // Get the cached current/previous search result.
    const preview = searchResults[currentSearchResult];

    // Set the count of search results.
    const countNode = currentStepNode.querySelector(".result-count");
    if (preview.count == 1) {
        countNode.textContent = "1 Kurs";
    } else {
        countNode.textContent = preview.count + " Kurse";
    }

    // Set skill title.
    const skillTitle = currentStepNode.querySelector(".skill-title");
    skillTitle.textContent = preview.skill.label;

    // Clear the existing list of search results.
    const resultList = currentStepNode.querySelector("ul.course-list");
    while (resultList.lastChild) {
        resultList.removeChild(resultList.lastChild);
    }

    // Perform the search based on the preview and update the DOM with the results.
    search(preview).then((result) => {
        for (const courseID in result.result) {
            const course = result.result[courseID];
            const li = document.createElement("li");
            li.classList.add("course-preview");

            // Add course title.
            const p = document.createElement("p");
            p.classList.add("course-title");
            p.textContent = course.title;
            li.appendChild(p);

            // Add course level if available.
            if (course.level) {
                const level = document.createElement("p");
                level.classList.add("level-goal");
                level.classList.add("level-" + course.level);
                if (result.skill.levelGoal.includes("Niveau")) {
                    level.textContent = compLevels[course.level];
                } else {
                    level.textContent = languageLevels[course.level];
                }
                li.appendChild(level);
            }

            // Add course details.
            // const courseDetailsNode = document.createElement('details');
            // const summaryNode = document.createElement('summary');
            // summaryNode.textContent = 'Kursdetails';
            // courseDetailsNode.appendChild(summaryNode);
            const courseDetails = document.createElement("div");
            courseDetails.classList.add("course-details");

            const providerNode = document.createElement("p");
            providerNode.classList.add("provider-title");
            providerNode.textContent = course.provider;
            courseDetails.appendChild(providerNode);

            const nextDateNode = document.createElement("p");
            nextDateNode.classList.add("next-date");
            nextDateNode.insertAdjacentHTML("beforeend", course.nextDate);
            courseDetails.appendChild(nextDateNode);

            const workloadNode = document.createElement("p");
            workloadNode.classList.add("workload");
            workloadNode.insertAdjacentHTML("beforeend", course.workload);
            courseDetails.appendChild(workloadNode);

            const courseModeNode = document.createElement("p");
            courseModeNode.classList.add("course-mode");
            courseModeNode.textContent = course.mode;
            courseDetails.appendChild(courseModeNode);

            const priceNode = document.createElement("p");
            priceNode.classList.add("price");
            priceNode.insertAdjacentHTML("beforeend", course.price);
            courseDetails.appendChild(priceNode);

            const locationNode = document.createElement("p");
            locationNode.classList.add("location");
            locationNode.textContent = course.location;
            courseDetails.appendChild(locationNode);

            // courseDetailsNode.appendChild(courseDetails);
            li.appendChild(courseDetails);

            // Add course actions.
            li.insertAdjacentHTML("beforeend", getCourseActions(courseID));

            resultList.appendChild(li);
        }
    });
}

/**
 * Generates HTML for the course actions, including bookmark, share, and course view buttons.
 *
 * @param {string} courseid The ID of the course to generate actions for.
 *
 * @returns {string} The HTML for the course actions.
 */
function getCourseActions(courseid) {
    return `
        <div class="course-preview__actions">
            <button class="bookmark-btn labeled-icon-btn"><i class="material-symbols-rounded">star</i>Merken</button>
            <button class="share-btn labeled-icon-btn"><i class="material-symbols-rounded">share</i>Teilen</button>
            <a class="to-course-btn btn" href="/k${courseid}" target="_blank" rel="noreferrer noopener"><span>Kurs ansehen</span></a>
        </div>
    `;
}

/**
 * Creates a current level selection element for a given skill.
 *
 * @param {Object} skill The skill for which to create a level selection element.
 *
 * @returns {HTMLSelectElement} The level selection element.
 */
function getCurrentLevelSelectElement(skill) {
    // Create a new select element.
    const select = document.createElement("select");
    select.setAttribute("name", skill.label);
    select.setAttribute("id", skill.uri);
    let optionHtml = "";
    // If the skill is a language skill, create language-specific level options.
    if (skill.isLanguageSkill) {
        optionHtml = `
            <option value="" style="display: none;">Stufe ändern</option>
            <option value="O">ohne Vorkentnisse</option>
            <option class="level-A1" value="A1">A1</option>
            <option class="level-A2" value="A2">A2</option>
            <option class="level-B1" value="B1">B1</option>
            <option class="level-B2" value="B2">B2</option>
            <option class="level-C1" value="C1">C1</option>
            <option class="level-C2" value="C2">C2</option>
            `;
    } else {
        // If the skill is not a language skill, create generic level options.
        optionHtml = `
            <option value="" style="display: none;">Stufe ändern</option>
            <option class="level-O" value="O">ohne Vorkentnisse</option>
            <option class="level-A" value="A">Grundstufe</option>
            <option class="level-B" value="B">Aufbaustufe</option>
            <option class="level-C" value="C">Fortgeschrittenenstufe</option>
            <option class="level-D" value="D">Expert*innenstufe</option>
            `;
    }

    // Insert the option HTML into the select element.
    select.insertAdjacentHTML("afterbegin", optionHtml);
    return select;
}

/**
 * Creates a level goal selection element for a given skill.
 *
 * @param {Object} skill The skill for which to create a level selection element.
 *
 * @returns {HTMLSelectElement} The level selection element.
 */
function getLevelGoalSelectElement(skill) {
    // Create a new select element.
    const select = document.createElement("select");
    select.setAttribute("name", skill.label);
    select.setAttribute("id", skill.uri);
    let optionHtml = "";
    // If the skill is a language skill, create language-specific level options.
    if (skill.isLanguageSkill) {
        optionHtml = `
            <option value="" style="display: none;">Stufe ändern</option>
            <option class="level-A1" value="A1">A1</option>
            <option class="level-A2" value="A2">A2</option>
            <option class="level-B1" value="B1">B1</option>
            <option class="level-B2" value="B2">B2</option>
            <option class="level-C1" value="C1">C1</option>
            <option class="level-C2" value="C2">C2</option>
            `;
    } else {
        // If the skill is not a language skill, create generic level options.
        optionHtml = `
            <option value="" style="display: none;">Stufe ändern</option>
            <option class="level-A" value="A">Grundstufe</option>
            <option class="level-B" value="B">Aufbaustufe</option>
            <option class="level-C" value="C">Fortgeschrittenenstufe</option>
            <option class="level-D" value="D">Expert*innenstufe</option>
            `;
    }
    select.insertAdjacentHTML("afterbegin", optionHtml);
    return select;
}

/**
 * This function clears the given input field and hides the output container by removing its child nodes.
 *
 * @param {HTMLElement} input The input field to clear.
 * @param {HTMLElement} output The output container to clear and hide.
 *
 * @returns {void}
 */
function clearAutocompleteInput(input, output) {
    input.value = "";
    hide(output);
    while (output.lastChild) {
        output.removeChild(output.lastChild);
    }
}

/**
 * Selects all checkboxes related to available skills and listens for changes on checkboxes.
 * If rebuild is true, rebuilds the entire list of checkboxes for selected skills.
 *
 * @param {HTMLElement} output the element where the selected skills will be displayed
 * @param {HTMLElement} input the element where the user can search and filter the available skills
 * @param {boolean} rebuild (optional) true if the list of checkboxes for selected skills should be rebuilt
 *
 * @returns {void}
 */
function updateSelectedSkills(output, input, rebuild = false) {
    const checkboxes = [];

    // Remove all present checkboxes of selected skills if rebuild is true.
    if (rebuild) {
        while (output.lastChild) {
            output.removeChild(output.lastChild);
        }
    }

    // Iterate over selected skills and build checkbox for each.
    for (uri in skillProfile.skills) {
        skill = skillProfile.skills[uri];

        // Check if the checkbox already exists.
        let checkbox = output.querySelector(
            'input[name="' + skill.label + '"]'
        );
        // If checkbox exist, skip making a new one.
        if (checkbox) {
            checkboxes.push(checkbox);
            continue;
        } else {
            checkbox = document.createElement("input");
        }
        // Make checkbox for skill info.
        const li = document.createElement("li");
        checkbox.setAttribute("type", "checkbox");
        checkbox.setAttribute("name", skill.label);
        checkbox.setAttribute("id", skill.uri);
        checkbox.setAttribute("checked", "True");
        checkboxes.push(checkbox);
        li.appendChild(checkbox);
        const label = document.createElement("label");
        label.setAttribute("for", skill.uri);
        label.textContent = skill.label;
        li.appendChild(label);
        output.insertBefore(li, output.firstChild);

        // On change add or remove the skill and update view and step completion state.
        checkbox.addEventListener("change", async () => {
            if (checkbox.checked) {
                await addSelectedSkill(
                    checkbox.getAttribute("id"),
                    checkbox.getAttribute("name")
                );
            } else {
                removeSelectedSkill(checkbox.getAttribute("id"));
            }
            updateSelectedSkills(output, input);
            checkSkillStepCompletion();
            updateSkillSelection(checkboxes, input);
        });
    }

    checkSkillStepCompletion();
    updateSkillSelection(checkboxes, input);
}

/**
 * Check whether the current skill selection step is complete or not.
 * If there are more than on skill selected, the step is completed and the next button is enabled.
 *
 * @returns {void}
 */
function checkSkillStepCompletion() {
    const skillCount = Object.keys(skillProfile.skills).length;
    if (skillCount > 0) {
        enable(nextButton);
    } else {
        disable(nextButton);
    }
}

/**
 * This function shows more skills by hiding the "show more" button and displaying all previously hidden skills,
 * while displaying the "show less" button to enable the user to hide the previously hidden skills again.
 *
 * @param {NodeList} skills The NodeList of skills to show
 * @param {HTMLElement} more The "show more" button to hide
 * @param {HTMLElement} less The "show less" button to show
 *
 * @returns {void}
 */
function showMoreSkills(skills, more, less) {
    skills.forEach((skill) => show(skill));
    hide(more);
    show(less);
}

/**
 * Hides the skill suggestions that come after the default number of skills shown
 * and displays the "more" button to enable the user to show the previously hidden skills again.
 *
 * @param {Array} skills An array of HTML elements representing suggested skills.
 * @param {Element} more An HTML element representing the "more" button that displays additional skill suggestions.
 * @param {Element} less An HTML element representing the "less" button that hides additional skill suggestions.
 *
 * @returns {void}
 */
function showLessSkills(skills, more, less) {
    for (
        let i = amountOfSkillSuggestionsShownByDefault;
        i < skills.length;
        i++
    ) {
        hide(skills[i]);
    }
    show(more);
    hide(less);
}

/**
 * Check if the given step is allowed and returns the allowed step number.
 *
 * @param {number} step The current step number to check.
 *
 * @returns {number} The closest allowed step number.
 */
function checkStep(step) {
    // Check conditions.
    // To access step 1-"occupationSkills" an occupation has to be set. Otherwise go to step 0.
    if (step == 1 && (!skillProfile || !skillProfile.occupation.label)) {
        return 0;
    }
    // To access steps after 2-"skills", at least 1 skill has to be set. Otherwise return to step 0.
    if (
        step > 2 &&
        (!skillProfile || !Object.keys(skillProfile.skills).length)
    ) {
        return 0;
    }
    // To access step 6-"resultsList", there has to be a search activated and results available.
    // Otherwise return to step 5-"resultOverview".
    if (step == 6 && !currentSearchResult && searchResults.length == 0) {
        return 5;
    }

    // Check overflow.
    if (step >= steps.length) {
        return steps.length - 1;
    } else if (step < 0) {
        return 0;
    }

    return step;
}

/**
 * Retrieves the current step of the scout process from local storage.
 * If the current step cannot be found or is not an integer, returns 0.
 *
 * @returns {number} The current step of the scout process, or 0 if it cannot be found.
 */
function currentStep() {
    const currentStep = parseInt(localStorage.getItem("currentStep"));
    if (!Number.isInteger(currentStep)) {
        return 0;
    }
    return currentStep;
}

/**
 * Retrieves the current step from local storage and returns it incremented by 1.
 *
 * @return {number} The next step, or 0 if the current step is not a number.
 */
function nextStep() {
    const currentStep = parseInt(localStorage.getItem("currentStep"));
    if (!Number.isInteger(currentStep)) {
        return 0;
    }
    return currentStep + 1;
}

/**
 * Retrieves the current step from local storage and returns it decremented by 1.
 *
 * @return {number} The next step, or 0 if the current step is not a number.
 */
function prevStep() {
    const currentStep = parseInt(localStorage.getItem("currentStep"));
    if (!Number.isInteger(currentStep)) {
        return 0;
    }
    return currentStep - 1;
}

/**
 * Scrolls to the specified step with an optional delay.
 *
 * @param {number} step The step to scroll to.
 * @param {number} [delay=0] The delay, in milliseconds, to wait before scrolling.
 *
 * @returns {Promise} A promise that resolves after the scroll was started.
 */
function scrollToStep(step, delay = 0) {
    return new Promise((res, _rej) => {
        setTimeout(
            () =>
                (stepsNode.style.left =
                    document.body.offsetWidth * step * -1 + "px"),
            delay
        );
        res();
    });
}

/**
 * Updates the navigation steps in the scout section to show the current step.
 * This function takes in the current step number and adds or removes
 * the "done" and "current-step" classes from the navigation steps to indicate
 * the current progress in the scout section.
 *
 * @param {number} currentStep The current step number.
 *
 * @returns {void}
 */
function updateScoutNav(currentStep) {
    scoutNavSteps.forEach((element) => {
        element.classList.remove("done");
        element.classList.remove("current-step");
    });

    for (i = 0; i <= Math.min(currentStep, scoutNavSteps.length - 1); i++) {
        scoutNavSteps[i].classList.add("done");
    }

    scoutNavSteps[
        Math.min(currentStep, scoutNavSteps.length - 1)
    ].classList.add("current-step");
}

/**
 * Suggests skills based on the given ESCO concept URI.
 *
 * @param {string} uri the URI to get skills for.
 *
 * @returns {object} An object containing a URI and an array of skills.
 */
async function suggestSkills(uri) {
    if (!uri.startsWith("http://data.europa.eu/esco/")) {
        return { uri: uri };
    }

    const limit = 10;
    const params = { uri: uri, limit: limit, onlyrelevant: false };

    const url = "./esco/getConceptSkills?" + new URLSearchParams(params);
    const response = await fetch(url);

    if (response.ok) {
        const result = await response.json();
        return result;
    } else {
        console.error("HTTP-Error: " + response.status);
    }
}

/**
 * Fills the suggestion list in the UI with the given suggestions.
 *
 * @param {object} suggestions The suggestions to be displayed.
 * @param {HTMLElement} output The DOM element where the suggestions will be appended.
 *
 * @returns {void}
 */
function showSkillSuggestions(suggestions, output) {
    if (!suggestions.title) {
        return;
    }

    while (output.lastChild) {
        output.removeChild(output.lastChild);
    }

    const checkboxes = [];

    // For every suggested skill, display a checkbox.
    // Check if there are skills that are already selected and set the checkbox state accordingly.
    for (const uri in suggestions.skills) {
        const suggestion = suggestions.skills[uri];
        const li = document.createElement("li");
        const checkbox = document.createElement("input");
        checkbox.setAttribute("type", "checkbox");
        checkbox.setAttribute("name", suggestion.label);
        checkbox.setAttribute("id", uri);
        li.appendChild(checkbox);
        const label = document.createElement("label");
        label.setAttribute("for", uri);
        label.textContent = suggestion.label;
        li.appendChild(label);

        checkboxes.push(checkbox);

        if (uri in skillProfile.skills) {
            checkbox.setAttribute("checked", "True");
            output.insertBefore(li, output.firstChild);
        } else {
            output.appendChild(li);
        }
    }

    // On change event, add or remove the skill associated with the checkbox and update the view.
    checkboxes.forEach((checkbox) =>
        checkbox.addEventListener("change", async () => {
            if (checkbox.checked) {
                await addSelectedSkill(
                    checkbox.getAttribute("id"),
                    checkbox.getAttribute("name")
                );
            } else {
                removeSelectedSkill(checkbox.getAttribute("id"));
            }
            updateSkillSelection(checkboxes);
        })
    );
}

/**
 * Updates the checkboxes' selection state according to the currently selected skills.
 * Disables the "add more skills" button if the maximum number of skills is already selected.
 *
 * @param {Array} checkboxes An array of the checkboxes for each skill.
 * @param {HTMLElement} [input=false] An optional input element to disable if the maximum number of skills has been selected.
 *
 * @returns {void}
 */
function updateSkillSelection(checkboxes, input = false) {
    const skillCount = Object.keys(skillProfile.skills).length;

    if (input) {
        if (skillCount >= maxSkills) {
            disable(input);
        } else {
            enable(input);
        }
    }

    checkboxes.forEach((checkbox) => {
        if (skillCount >= maxSkills) {
            if (!checkbox.checked) {
                disable(checkbox);
            }
        } else {
            enable(checkbox);
        }

        if (checkbox.getAttribute("id") in skillProfile.skills) {
            checkbox.setAttribute("checked", "");
        } else {
            checkbox.removeAttribute("checked");
        }
    });
}

/**
 * Adds a new skill to the user's skill profile and update local storage.
 *
 * @param {string} uri The URI of the skill to be added.
 * @param {string} label The label of the skill to be added.
 *
 * @returns {Promise} A Promise that resolves with no value.
 */
async function addSelectedSkill(uri, label) {
    const skill = {
        label: label,
        currentLevel: null,
        levelGoal: null,
        isLanguageSkill: await isLanguageSkill(uri),
        uri: uri,
    };

    skillProfile.skills[uri] = skill;
    localStorage.setItem("skillProfile", JSON.stringify(skillProfile));
    return;
}

/**
 * Remove a selected skill from the skill profile and update local storage.
 *
 * @param {string} uri - The URI of the skill to be removed.
 *
 * @returns {void}
 */
function removeSelectedSkill(uri) {
    delete skillProfile.skills[uri];
    localStorage.setItem("skillProfile", JSON.stringify(skillProfile));
}

/**
 * Determines if a given skill URI is a language skill by making an HTTP request to the server.
 *
 * @param {string} uri - The URI of the skill to check.
 *
 * @returns {Promise} - A Promise that resolves to true if the skill is a language skill, false otherwise.
 */
async function isLanguageSkill(uri) {
    const params = { uri: uri };
    const url = "./esco/isLanguageSkill?" + new URLSearchParams(params);
    const response = await fetch(url);

    if (response.ok) {
        return await response.json();
    } else {
        console.error("HTTP-Error: " + response.status);
    }
}

let currentRequestID = 0;

/**
 * Uses the ESCO API to generate autocomplete reults based on a given input field.
 *
 * @param {HTMLInputElement} input - The input field to autocomplete.
 * @param {number} requestID - A unique request ID for the autocomplete request.
 *
 * @returns {Object} - An object containing the suggestions retrieved from the ESCO API.
 */
async function autocomplete(input, requestID) {
    // Extract search term from input field.
    const searchterm = input.value;

    // If search term is too short, return an empty array.
    if (searchterm.length < 3) {
        return [];
    }

    let limit = 10;
    // Set limit to set maximum number of suggestions if specified by the input field, defaults to 10.
    if (input.getAttribute("max")) {
        limit = input.getAttribute("max");
    }

    const params = {
        term: searchterm,
        limit: limit,
        requestID: requestID,
    };

    // Set ESCO concept type to limit the search to, if specified by input field.
    if (input.getAttribute("esco-type")) {
        params.type = input.getAttribute("esco-type").split(" ");
    }
    // Set ESCO concept scheme to limit the search to, if specified by input field.
    if (input.getAttribute("esco-scheme")) {
        params.scheme = input.getAttribute("esco-scheme").split(" ");
    }
    // Set wether to only retrieve suggestions, that are deamed relevant, if specified by the input field.
    if (input.getAttribute("onlyrelevant") === "False") {
        params.onlyrelevant = false;
    }

    // Build and make request.
    const url = "./esco/autocomplete?" + new URLSearchParams(params);
    const response = await fetch(url);

    // If response is successful, parse and return the suggestions from the response.
    if (response.ok) {
        const result = await response.json();
        return result;
    } else {
        console.error("HTTP-Error: " + response.status);
    }
}

/**
 * Generates a list of suggestions and creates a button for each suggestion. Adds the suggestions list to the output element.
 *
 * @param {Object} suggestions - The list of suggestions to display.
 * @param {HTMLElement} output - The element to which the suggestions list will be added.
 * @param {Number} [id=null] - The ID of the current request, if any.
 *
 * @returns {Array} An array of buttons that were created.
 */
function showAutocompleteResult(suggestions, output, id = null) {
    const skillsBtns = [];
    // Check if this is the current request.
    if (id) {
        if (id < currentRequestID) {
            return skillsBtns;
        }
    }

    // Clear the output element.
    while (output.lastChild) {
        output.removeChild(output.lastChild);
    }

    // Iterate through each category of suggestions.
    for (category in suggestions) {
        const ul = document.createElement("ul");
        ul.setAttribute("name", category);

        // Iterate through each suggestion in the category.
        for (const uri in suggestions[category]) {
            // Skip suggestion if it has already been selected.
            if (uri in skillProfile.skills) {
                continue;
            }
            // Create a button for the suggestion and add it to the list.
            const suggestion = suggestions[category][uri];
            const li = document.createElement("li");
            const button = document.createElement("button");
            button.textContent = suggestion.label;
            button.setAttribute("name", suggestion.label);
            button.setAttribute("id", uri);
            skillsBtns.push(button);
            li.appendChild(button);
            ul.appendChild(li);
        }

        // If no suggestions were found for this category, display a message.
        if (skillsBtns.length == 0) {
            const li = document.createElement("li");
            const button = document.createElement("button");
            button.textContent = "Keine Ergebnisse";
            button.setAttribute("disabled", "");
            li.appendChild(button);
            ul.appendChild(li);
        }

        // Add the list of suggestions to the output element.
        output.appendChild(ul);
    }

    // Return the list of buttons that were created.
    return skillsBtns;
}

/**
 * Assigns an occupation to the skill profile and updates the local storage with the selected occupation.
 *
 * @param {HTMLInputElement} inputNode - The input element to be updated with the label of the selected occupation.
 * @param {string} label - The label of the selected occupation to be assigned to the inputNode.
 * @param {string} uri - The uri of the selected occupation to be assigned to the skill profile.
 *
 * @returns {void}
 */

function completeStepOccupation(inputNode, label, uri) {
    inputNode.value = label;
    const occupation = {
        label: label,
        uri: uri,
    };

    skillProfile.occupation = occupation;
    localStorage.setItem("skillProfile", JSON.stringify(skillProfile));
    enable(nextButton);
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

/**
 * This function focuses on the given element after a delay of the specified amount of time.
 * The current step is checked before focusing to make sure the user has not navigated away from the current step.
 *
 * @param {Element} element - The element to focus on.
 * @param {number} delay - The delay time in milliseconds.
 *
 * @returns {Promise} - A Promise that resolves when the focus has been set.
 */
function focusDelayed(element, delay = 500) {
    const stepNow = currentStepName;
    return new Promise((res, _rej) => {
        setTimeout(() => {
            if (currentStepName == stepNow) {
                element.focus();
            }
            res();
        }, delay);
    });
}
