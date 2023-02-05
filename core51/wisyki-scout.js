window.onload = init;

// Reload page when navigating to scout using history traversal.
addEventListener("pageshow", function (event) {
    if (event.persisted && requestReload) {
        window.location.reload();
    }
});

let requestReload = false;
let nextButton;
let prevButton;
let stepsNode;
let currentStepNode;
let currentStepName;
let scoutNavSteps;
let skillProfile;
const amountOfSkillSuggestionsShownByDefault = 6;
const maxSkills = 5;

const steps = [
    "occupation",
    "occupationSkills",
    "skills",
    "currentLevel",
    "levelGoal",
    "resultOverview",
];
const compLevels = {
    O: "ohne Vorkenntnisse",
    A: "Grundstufe",
    B: "Aufbaustufe",
    C: "Fortgeschrittenenstufe",
    D: "Expert*innenstufe",
};

function init() {
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

    scoutNavSteps = document.querySelectorAll(".scout-nav__progress ul li");
    const scoutNavBtns = document.querySelectorAll(
        ".scout-nav__progress ul li button"
    );
    scoutNavBtns.forEach((btn) => {
        btn.addEventListener("click", () =>
            showStep(btn.getAttribute("to-step"))
        );
    });
    const scoutAbortBtn = document.querySelector("#scout-abort");
    scoutAbortBtn.addEventListener("click", () => {
        localStorage.clear();
        requestReload = true;
        window.location.href = "/";
    });

    nextButton = document.querySelector("#next-step");
    prevButton = document.querySelector("#prev-step");
    disable(nextButton);
    hide(nextButton, true);
    disable(prevButton);
    hide(prevButton, true);
    nextButton.addEventListener("click", () => showStep(nextStep()));
    prevButton.addEventListener("click", () => showStep(prevStep()));

    if (window.localStorage.skillProfile) {
        skillProfile = JSON.parse(window.localStorage.skillProfile);
    } else {
        skillProfile = { occupation: {}, skills: {} };
    }
    addEventListener(
        "storage",
        () => (skillProfile = JSON.parse(window.localStorage.skillProfile))
    );

    showStep(currentStep());
}

async function showStep(step) {
    step = checkStep(step);
    if (currentStepName == steps[step]) {
        return;
    }
    currentStepName = steps[step];
    localStorage.setItem("currentStep", step);

    currentStepNode = stepsNode.querySelector("#" + currentStepName);
   
    const loader = currentStepNode.parentNode.querySelector('.loader:not(.hidden)');
    const isLoaded = currentStepNode.firstChild != null;


    scrollToStep(step);
    hide(currentStepNode, true);

    if (!isLoaded) {
        await loadStep(currentStepName);
    }

    switch (currentStepName) {
        case "occupation":
            initOccupationStep(isLoaded);
            break;
        case "occupationSkills":
            await initOccupationSkillsStep(isLoaded);
            break;
        case "skills":
            initSkillsStep(isLoaded);
            break;
        case "currentLevel":
            initCurrentLevelStep(isLoaded);
            break;
        case "levelGoal":
            initLevelGoalStep(isLoaded);
            break;
        case "resultOverview":
            initResultOverviewStep(isLoaded);
            break;
    }
    
    if (loader) {
        hide(loader, true);
        setTimeout(() => hide(loader), 300);
        setTimeout(() => show(currentStepNode), 100);
    } else {
        show(currentStepNode);
    }
    updateScoutNav(step);
}

async function loadStep(stepName) {
    const url = "./scout?action=" + stepName;
    const response = await fetch(url);
    if (response.ok) {
        const result = await response.text();
        currentStepNode.insertAdjacentHTML("beforeend", result);
    } else {
        alert("HTTP-Error: " + response.status);
    }
}

function initOccupationStep(isOld) {
    const autocompleteInput =
        currentStepNode.querySelector(".esco-autocomplete");
    const clearInputButton = currentStepNode.querySelector(".clear-input");
    const autocompleteOutput = currentStepNode.querySelector(
        ".autocomplete-box output"
    );
    const toSkillStepBtn = currentStepNode.querySelector(".to-skill-step-btn");

    if (!isOld) {
        toSkillStepBtn.addEventListener("click", () => showStep(2));
        autocompleteInput.addEventListener("keyup", async () => {
            const requestID = ++currentRequestID;
            const concepts = await autocomplete(autocompleteInput, requestID);
            const conceptBtns = showAutocompleteResult(
                concepts,
                autocompleteOutput,
                requestID
            );
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

        clearInputButton.addEventListener("click", () => {
            clearAutocompleteInput(autocompleteInput, autocompleteOutput);

            skillProfile.occupation = {};
            localStorage.setItem("skillProfile", JSON.stringify(skillProfile));
            disable(nextButton);
            autocompleteInput.focus();
        });

        hide(autocompleteOutput);
        autocompleteInput.addEventListener("focus", () =>
            show(autocompleteOutput)
        );
        autocompleteInput.addEventListener("blur", () =>
            hide(autocompleteOutput)
        );
    }

    hide(prevButton, true);
    disable(prevButton);
    show(nextButton);
    disable(nextButton);

    if (skillProfile.occupation.label && skillProfile.occupation.uri) {
        completeStepOccupation(
            autocompleteInput,
            skillProfile.occupation.label,
            skillProfile.occupation.uri
        );
    } else {
        if (!autocompleteInput.value) {
            focusDelayed(autocompleteInput);
        }
    }
}

async function initOccupationSkillsStep(isOld) {
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

    if (occupationNode.textContent != skillProfile.occupation.label) {
        while (skillsNode.lastChild) {
            skillsNode.removeChild(skillsNode.lastChild);
        }

        occupationNode.textContent = skillProfile.occupation.label;
        const suggestions = await suggestSkills(skillProfile.occupation.uri);
        showSkillSuggestions(suggestions, skillsNode);
    }

    const skills = skillsNode.querySelectorAll(".selectable-skills li");
    if (skills.length > amountOfSkillSuggestionsShownByDefault) {
        showLessSkills(skills, more, less);
    }

    const checkboxes = skillsNode.querySelectorAll(".selectable-skills input");
    updateSkillSelection(checkboxes);

    if (!isOld) {
        more.addEventListener("click", () =>
            showMoreSkills(skills, more, less)
        );
        less.addEventListener("click", () =>
            showLessSkills(skills, more, less)
        );
        // document.addEventListener("mousedown", (event) => {
        //     if (!currentStepNode.contains(event.target)) {
        //         showLessSkills(skills, more, less);
        //     }
        // });
    }
}

function initSkillsStep(isOld) {
    const autocompleteInput =
        currentStepNode.querySelector(".esco-autocomplete");
    const clearInputButton = currentStepNode.querySelector(".clear-input");
    const autocompleteOutput = currentStepNode.querySelector(
        ".autocomplete-box output"
    );
    const skillsNode = currentStepNode.querySelector(".selectable-skills");
    currentStepNode.querySelector(".maxSkills").textContent = maxSkills;

    if (!isOld) {
        autocompleteInput.addEventListener("keyup", async () => {
            const requestID = ++currentRequestID;
            const concepts = await autocomplete(autocompleteInput, requestID);
            const conceptBtns = showAutocompleteResult(
                concepts,
                autocompleteOutput,
                requestID
            );
            conceptBtns.forEach((btn) =>
                btn.addEventListener("mousedown", () => {
                    addSelectedSkill(btn.id, btn.name);
                    updateSelectedSkills(skillsNode, autocompleteInput);
                    clearAutocompleteInput(
                        autocompleteInput,
                        autocompleteOutput
                    );
                    autocompleteInput.focus();
                })
            );
        });

        clearInputButton.addEventListener("click", () => {
            clearAutocompleteInput(autocompleteInput, autocompleteOutput);
        });

        hide(autocompleteOutput);
        autocompleteInput.addEventListener("focus", () =>
            show(autocompleteOutput)
        );
        autocompleteInput.addEventListener("blur", () =>
            hide(autocompleteOutput)
        );
    }

    show(prevButton);
    enable(prevButton);
    show(nextButton);
    disable(nextButton);

    updateSelectedSkills(skillsNode, autocompleteInput, (rebuild = true));
    if (!autocompleteInput.value) {
        focusDelayed(autocompleteInput);
    }
}

function initCurrentLevelStep(_isOld) {
    show(prevButton);
    enable(prevButton);
    show(nextButton);
    enable(nextButton);

    const levelSelectionList = currentStepNode.querySelector(
        "ul.current-level-selection"
    );
    while (levelSelectionList.lastChild) {
        levelSelectionList.removeChild(levelSelectionList.lastChild);
    }

    for (uri in skillProfile.skills) {
        const skill = skillProfile.skills[uri];
        const li = document.createElement("li");
        const label = document.createElement("label");
        label.setAttribute("for", skill.uri);
        label.textContent = skill.label;
        if (!skill.currentLevel) {
            label.classList.add("level-O");
        } else {
            label.classList.add("level-" + skill.currentLevel);
        }
        const select = getCurrentLevelSelectElement(skill);
        li.appendChild(label);
        li.appendChild(select);
        levelSelectionList.appendChild(li);

        select.addEventListener("change", () => {
            skillProfile.skills[select.getAttribute("id")].currentLevel =
                select.value;
            label.className = "";
            label.classList.add("level-" + select.value);
            localStorage.setItem("skillProfile", JSON.stringify(skillProfile));
        });
    }
}

function initLevelGoalStep(_isOld) {
    show(prevButton);
    enable(prevButton);
    show(nextButton);
    enable(nextButton);

    const levelSelectionList = currentStepNode.querySelector(
        "ul.level-goal-selection"
    );
    while (levelSelectionList.lastChild) {
        levelSelectionList.removeChild(levelSelectionList.lastChild);
    }

    for (uri in skillProfile.skills) {
        const skill = skillProfile.skills[uri];
        const li = document.createElement("li");
        const skillTitle = document.createElement("p");
        skillTitle.classList.add("skill-title");
        skillTitle.textContent = skill.label;
        li.appendChild(skillTitle);
        const levelDiv = document.createElement("div");
        const currentLevelDiv = document.createElement("div");
        const currentLevelLabel = document.createElement("label");
        currentLevelLabel.textContent = "Deine Einschätzung";
        currentLevelDiv.appendChild(currentLevelLabel);
        const currentLevel = document.createElement("p");
        currentLevel.classList.add("current-level");
        if (!skill.currentLevel) {
            skill.currentLevel = "O";
        }
        currentLevel.classList.add("level-" + skill.currentLevel);
        currentLevel.textContent = compLevels[skill.currentLevel];
        currentLevelDiv.appendChild(currentLevel);
        levelDiv.appendChild(currentLevelDiv);
        const levelGoalDiv = document.createElement("div");
        const levelGoalLabel = document.createElement("label");
        const levelGoal = document.createElement("p");
        levelGoal.classList.add("level-goal");
        if (skill.levelGoal) {
            levelGoalLabel.textContent = "Dein Ziel";
            levelGoal.textContent = compLevels[skill.levelGoal];
            levelGoal.classList.add("level-" + skill.levelGoal);
        } else {
            levelGoalLabel.textContent = "Mein Vorschlag";
            let levelIndex =
                Object.keys(compLevels).indexOf(skill.currentLevel) + 1;
            if (levelIndex >= Object.keys(compLevels).length) {
                levelIndex = Object.keys(compLevels).length - 1;
            }
            levelGoal.textContent =
                compLevels[Object.keys(compLevels)[levelIndex]];
            levelGoal.classList.add(
                "level-" + Object.keys(compLevels)[levelIndex]
            );
        }
        const select = getLevelGoalSelectElement(skill);
        levelGoalDiv.appendChild(levelGoalLabel);
        levelGoalDiv.appendChild(levelGoal);
        levelGoalDiv.appendChild(select);
        levelDiv.appendChild(levelGoalDiv);
        li.appendChild(levelDiv);
        levelSelectionList.appendChild(li);

        select.addEventListener("change", (event) => {
            skillProfile.skills[select.getAttribute("id")].levelGoal =
                select.value;
            levelGoal.textContent = compLevels[select.value];
            levelGoal.className = "";
            levelGoal.classList.add("level-goal");
            levelGoal.classList.add("level-" + select.value);
            select.value = "";
            localStorage.setItem("skillProfile", JSON.stringify(skillProfile));
            event.preventDefault();
        });
    }
}

function initResultOverviewStep(_isOld) {
    show(prevButton);
    enable(prevButton);
    hide(nextButton);
    disable(nextButton);


    const resultList = currentStepNode.querySelector('ul.result-list');
    while (resultList.lastChild) {
        resultList.removeChild(resultList.lastChild);
    }

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
        levelGoalNode.textContent = compLevels[skill.levelGoal];
        li.appendChild(levelGoalNode);

        const resultLink = document.createElement('a');
        resultLink.classList.add('result-link');
        resultLink.classList.add('link');
        resultLink.setAttribute('id', skill.uri);
        const loader = document.createElement('span');
        loader.classList.add('loader');
        resultLink.appendChild(loader);
        const resultText = document.createElement('span');
        resultText.textContent = 'Suche Kurse';
        resultLink.appendChild(resultText);
        li.appendChild(resultLink);
        resultList.appendChild(li);
        getSearchResult(resultLink, resultText, loader, skill);
    }
    // TODO remove this feature.
    temp_loader_counter = 1;
}

async function getSearchResult(output, text, loader, skill) {
    output.setAttribute('href', await search(skill));
    output.setAttribute('target', '_blank');
    text.textContent = Math.floor(Math.random() * 9 + 1) + ' Kurse gefunden';
    loader.remove();
}

let temp_loader_counter = 1;

function search(skill) {
    return new Promise((res, _rej) => {
        let url = 'https://sh.kursportal.info/search';
        const param = new URLSearchParams({qs: skill.label});
        url = encodeURI(url + '?' + param);
        setTimeout(() => res(url), 500 * temp_loader_counter++);
    });
}

function getCurrentLevelSelectElement(skill) {
    const select = document.createElement("select");
    select.setAttribute("name", skill.label);
    select.setAttribute("id", skill.uri);
    const optionHtml = `
    <option value="" style="display: none;">Stufe auswählen</option>
    <option value="O">ohne Vorkentnisse</option>
    <option value="A">Grundstufe</option>
    <option value="B">Aufbaustufe</option>
    <option value="C">Fortgeschrittenenstufe</option>
    <option value="D">Expert*innenstufe</option>
    `;
    select.insertAdjacentHTML("afterbegin", optionHtml);
    if (skill.currentLevel) {
        select
            .querySelector("option[value=" + skill.currentLevel + "]")
            .setAttribute("selected", "selected");
    }
    return select;
}

function getLevelGoalSelectElement(skill) {
    const select = document.createElement("select");
    select.setAttribute("name", skill.label);
    select.setAttribute("id", skill.uri);
    const optionHtml = `
    <option value="" style="display: none;">Stufe ändern</option>
    <option value="A">Grundstufe</option>
    <option value="B">Aufbaustufe</option>
    <option value="C">Fortgeschrittenenstufe</option>
    <option value="D">Expert*innenstufe</option>
    `;
    select.insertAdjacentHTML("afterbegin", optionHtml);
    return select;
}

function clearAutocompleteInput(input, output) {
    input.value = "";
    hide(output);
    while (output.lastChild) {
        output.removeChild(output.lastChild);
    }
}

function updateSelectedSkills(output, input, rebuild = false) {
    const checkboxes = [];

    if (rebuild) {
        while (output.lastChild) {
            output.removeChild(output.lastChild);
        }
    }

    for (uri in skillProfile.skills) {
        skill = skillProfile.skills[uri];
        let checkbox = output.querySelector(
            'input[name="' + skill.label + '"]'
        );
        if (checkbox) {
            checkboxes.push(checkbox);
            continue;
        } else {
            checkbox = document.createElement("input");
        }
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

        checkbox.addEventListener("change", () => {
            if (checkbox.checked) {
                addSelectedSkill(
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

function checkSkillStepCompletion() {
    const skillCount = Object.keys(skillProfile.skills).length;
    if (skillCount > 0) {
        enable(nextButton);
    } else {
        disable(nextButton);
    }
}

function showMoreSkills(skills, more, less) {
    skills.forEach((skill) => show(skill));
    hide(more);
    show(less);
}

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

function checkStep(step) {
    // Check conditions.
    if (step == 1 && (!skillProfile || !skillProfile.occupation.label)) {
        return 0;
    }
    if (
        step > 2 &&
        (!skillProfile || !Object.keys(skillProfile.skills).length)
    ) {
        return 0;
    }
    // Check overflow.
    if (step >= steps.length) {
        return steps.length - 1;
    } else if (step < 0) {
        return 0;
    }

    return step;
}

function currentStep() {
    const currentStep = parseInt(localStorage.getItem("currentStep"));
    if (!Number.isInteger(currentStep)) {
        return 0;
    }
    return currentStep;
}

function nextStep() {
    const currentStep = parseInt(localStorage.getItem("currentStep"));
    if (!Number.isInteger(currentStep)) {
        return 0;
    }
    return currentStep + 1;
}

function prevStep() {
    const currentStep = parseInt(localStorage.getItem("currentStep"));
    if (!Number.isInteger(currentStep)) {
        return 0;
    }
    return currentStep - 1;
}

function scrollToStep(step, delay = 0) {
    return new Promise((res, _rej) => {
        setTimeout(() => stepsNode.style.left = document.body.offsetWidth * step * -1 + "px", delay);
        res();
    });
}

function updateScoutNav(currentStep) {
    scoutNavSteps.forEach((element) => {
        element.classList.remove("done");
        element.classList.remove("current-step");
        // disable(element.firstChild);
    });

    for (i = 0; i <= currentStep; i++) {
        scoutNavSteps[i].classList.add("done");
        // enable(scoutNavSteps[i].firstChild);
    }
    scoutNavSteps[currentStep].classList.add("current-step");
}

async function suggestSkills(uri) {
    if (!uri.startsWith("http://data.europa.eu/esco/")) {
        return { uri: uri };
    }

    const limit = 10;

    const params = { action: "skill-suggest", uri: uri, limit: limit };

    const url = "./esco?" + new URLSearchParams(params);
    const response = await fetch(url);

    if (response.ok) {
        const result = await response.json();
        return result;
    } else {
        alert("HTTP-Error: " + response.status);
    }
}

function showSkillSuggestions(suggestions, output) {
    if (!suggestions.title) {
        return;
    }

    while (output.lastChild) {
        output.removeChild(output.lastChild);
    }

    const checkboxes = [];

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

    checkboxes.forEach((checkbox) =>
        checkbox.addEventListener("change", () => {
            if (checkbox.checked) {
                addSelectedSkill(
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

function addSelectedSkill(uri, label) {
    const skill = {
        label: label,
        currentLevel: null,
        levelGoal: null,
        uri: uri,
    };

    skillProfile.skills[uri] = skill;
    localStorage.setItem("skillProfile", JSON.stringify(skillProfile));
}

function removeSelectedSkill(uri) {
    delete skillProfile.skills[uri];
    localStorage.setItem("skillProfile", JSON.stringify(skillProfile));
}

let currentRequestID = 0;

async function autocomplete(input, requestID) {
    const searchterm = input.value;
    let limit = 10;
    if (input.getAttribute("max")) {
        limit = input.getAttribute("max");
    }

    if (searchterm.length < 3) {
        return [];
    }
    const params = {
        action: "autocomplete",
        term: searchterm,
        limit: limit,
        requestID: requestID,
    };

    if (input.getAttribute("esco-type")) {
        params.type = input.getAttribute("esco-type").split(" ");
    }
    if (input.getAttribute("esco-scheme")) {
        params.scheme = input.getAttribute("esco-scheme").split(" ");
    }
    if (input.getAttribute("onlyrelevant") === "False") {
        params.onlyrelevant = false;
    }

    const url = "./esco?" + new URLSearchParams(params);
    const response = await fetch(url);

    if (response.ok) {
        const result = await response.json();
        return result;
    } else {
        alert("HTTP-Error: " + response.status);
    }
}

function showAutocompleteResult(suggestions, output, id = null) {
    const skillsBtns = [];
    if (id) {
        if (id < currentRequestID) {
            return skillsBtns;
        }
    }

    while (output.lastChild) {
        output.removeChild(output.lastChild);
    }

    for (category in suggestions) {
        const ul = document.createElement("ul");
        ul.setAttribute("name", category);

        for (const uri in suggestions[category]) {
            if (uri in skillProfile.skills) {
                continue;
            }
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

        if (skillsBtns.length == 0) {
            const li = document.createElement("li");
            const button = document.createElement("button");
            button.textContent = "Keine Ergebnisse";
            button.setAttribute("disabled", "");
            li.appendChild(button);
            ul.appendChild(li);
        }

        output.appendChild(ul);
    }

    return skillsBtns;
}

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

function hide(node, onlyVisibility = false) {
    if (onlyVisibility) {
        node.classList.add("hidden");
    } else {
        node.classList.add("display-none");
    }
}

function show(node) {
    node.classList.remove("hidden");
    node.classList.remove("display-none");
}

function disable(node) {
    node.classList.add("disabled");
    node.setAttribute("disabled", "True");
}

function enable(node) {
    node.classList.remove("disabled");
    node.removeAttribute("disabled");
    show(node);
}

function focusDelayed(element, delay = 200) {
    const stepNow = currentStepName;
    return new Promise((res, _rej) => {
        setTimeout(() => {
            if (currentStepName == stepNow) {
                element.focus();
                console.log('focus completed');
            } else {
                console.log('focus aborted');
            }
            res();
        }, delay);
    });
}