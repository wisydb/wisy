window.onload = init;

// Reload page when navigating to scout using history traversal.
addEventListener("pageshow", function (event) {
    if (event.persisted && requestReload) {
        window.location.reload();
    }
});

addEventListener('resize', () => {
    scrollToStep(currentStep());
});

let requestReload = false;
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
        case "resultList":
            initResultListStep(isLoaded);
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
    const url = "./scout/" + stepName;
    const response = await fetch(url);
    if (response.ok) {
        const result = await response.text();
        currentStepNode.insertAdjacentHTML("beforeend", result);
    } else {
        console.error("HTTP-Error: " + response.status);
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

function toggleModal(modal) {
    if (modal.classList.contains('display-none')) {
        show(modal);
    } else {
        hide(modal);
    }
}

function showModalTab(tabBtns, tabs, tabBtn) {
    console.log(tabBtn);
    tabBtns.forEach((btn) => {
        btn.classList.remove('selected');
        disable(btn);
    });

    const tabName = tabBtn.getAttribute('for');
    currentStepNode.querySelector('.tabs [for="' + tabName + '"]');
    tabBtn.classList.add('selected');
    tabs.forEach((tab) => {
        if (tab.getAttribute('name') === tabName) {
            show(tab);
        } else {
            hide(tab);
        }
    });
    enable(tabBtn);
}

function initCurrentLevelStep(isOld) {
    show(prevButton);
    enable(prevButton);
    show(nextButton);
    enable(nextButton);

    if (!isOld) {
        const tabNavBtns = currentStepNode.querySelectorAll('.modal .tab-nav li');
        const tabs = currentStepNode.querySelectorAll('.tab');
        console.log(tabNavBtns);
        tabNavBtns.forEach((btn) => btn.addEventListener('click', () => showModalTab(tabNavBtns, tabs, btn)));

        const modalBtn = currentStepNode.querySelector('.open-modal-btn');
        const modal = currentStepNode.querySelector('.modal.level-explanation');
        modalBtn.addEventListener('click', () => toggleModal(modal));
        modal.addEventListener(
            'click',
            (event) => {
                if (currentStepName !== 'currentLevel') {
                    return;
                }
                if ((event.target.matches('.close-modal-btn') || !event.target.closest('.modal')  || event.target.matches('.backdrop')) && !event.target.closest('.open-modal-btn')) {
                    hide(modal);
                }
            },
            false
          )
    }

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

function initLevelGoalStep(isOld) {
    show(prevButton);
    enable(prevButton);
    show(nextButton);
    enable(nextButton);
    

    if (!isOld) {
        const tabNavBtns = currentStepNode.querySelectorAll('.modal .tab-nav li');
        const tabs = currentStepNode.querySelectorAll('.tab');
        console.log(tabNavBtns);
        tabNavBtns.forEach((btn) => btn.addEventListener('click', () => showModalTab(tabNavBtns, tabs, btn)));
        
        const modalBtn = currentStepNode.querySelector('.open-modal-btn');
        const modal = currentStepNode.querySelector('.modal.level-explanation');
        modalBtn.addEventListener('click', () => toggleModal(modal));
        document.addEventListener(
            'click',
            (event) => {
                if (currentStepName !== 'levelGoal') {
                    return;
                }
                if ((event.target.matches('.close-modal-btn') || !event.target.closest('.modal__content')) && !event.target.closest('.open-modal-btn')) {
                    hide(modal);
                }
            },
            false
          )
    }

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
        let skillLevels = compLevels;
        if (skill.isLanguageSkill) {
            skillLevels = languageLevels;
        }
        currentLevel.textContent = skillLevels[skill.currentLevel];
        currentLevelDiv.appendChild(currentLevel);
        levelDiv.appendChild(currentLevelDiv);
        const levelGoalDiv = document.createElement("div");
        const levelGoalLabel = document.createElement("label");
        const levelGoal = document.createElement("p");
        levelGoal.classList.add("level-goal");
        if (skill.levelGoal) {
            levelGoalLabel.textContent = "Dein Ziel";
            levelGoal.textContent = skillLevels[skill.levelGoal];
            levelGoal.classList.add("level-" + skill.levelGoal);
        } else {
            levelGoalLabel.textContent = "Mein Vorschlag";
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
            localStorage.setItem("skillProfile", JSON.stringify(skillProfile));
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
            levelGoal.textContent = skillLevels[select.value];
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
        if (skill.isLanguageSkill) {
            levelGoalNode.textContent = languageLevels[skill.levelGoal];
        } else {
            levelGoalNode.textContent = compLevels[skill.levelGoal];
        }
        li.appendChild(levelGoalNode);

        const resultLink = document.createElement('button');
        resultLink.classList.add('result-link');
        resultLink.classList.add('btn-link');
        resultLink.setAttribute('skill-uri', skill.uri);
        disable(resultLink);
        const loader = document.createElement('span');
        loader.classList.add('loader');
        resultLink.appendChild(loader);
        const resultText = document.createElement('span');
        resultText.textContent = 'Suche Kurse';
        resultLink.appendChild(resultText);
        li.appendChild(resultLink);
        resultList.appendChild(li);

        getSearchResultPreview(resultLink, resultText, loader, skill);
    }
}

async function getSearchResultPreview(output, text, loader, skill) {
    const response = await prepareSearch(skill);
    output.setAttribute('search-id', response.id);
    if (response.count == 1) {
        text.textContent = '1 Kurs gefunden';
    } else {
        text.textContent = response.count + ' Kurse gefunden';
    }
    loader.remove();

    if (response.count > 0) {
        enable(output);
        output.addEventListener('click', () => {
            currentSearchResult = response.query;
            showStep(steps.indexOf('resultList'));
        });
    }
}

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

async function updateSearchResult(search) {
    const params = { label: search.skill.label, level: search.skill.levelGoal, limit: 3 };

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

function initResultListStep(_isOld) {
    show(prevButton);
    enable(prevButton);
    hide(nextButton);
    disable(nextButton);

    const preview = searchResults[currentSearchResult];

    const countNode = currentStepNode.querySelector('.result-count');
    if (preview.count == 1) {
        countNode.textContent = '1 Kurs';
    } else {
        countNode.textContent = preview.count + ' Kurse';
    }
    const skillTitle = currentStepNode.querySelector('.skill-title');
    skillTitle.textContent = preview.skill.label;

    const resultList = currentStepNode.querySelector('ul.course-list');
    while (resultList.lastChild) {
        resultList.removeChild(resultList.lastChild);
    }

    updateSearchResult(preview).then((result) => {
        for (const courseID in result.result) {
            const course = result.result[courseID];
            const li = document.createElement('li');
            li.classList.add('course-preview');

            const p = document.createElement('p');
            p.classList.add('course-title');
            p.textContent = course.title;
            li.appendChild(p);

            if (course.level) {
                const level = document.createElement("p");
                level.classList.add("level-goal");
                level.classList.add("level-" + course.level);
                if (result.skill.levelGoal.includes('Niveau')) {
                    level.textContent = compLevels[course.level];
                } else {
                    level.textContent = languageLevels[course.level];
                }
                li.appendChild(level);
            }

            const courseDetailsNode = document.createElement('details');
            const summaryNode = document.createElement('summary');
            summaryNode.textContent = 'Kursdetails';
            courseDetailsNode.appendChild(summaryNode);
            const courseDetails = document.createElement('div');
            
            const providerNode = document.createElement('p');
            providerNode.classList.add('provider-title');
            providerNode.textContent = course.provider;
            courseDetails.appendChild(providerNode);
            
            const nextDateNode = document.createElement('p');
            nextDateNode.classList.add('next-date');
            nextDateNode.textContent = course.nextDate;
            courseDetails.appendChild(nextDateNode);
            
            const workloadNode = document.createElement('p');
            workloadNode.classList.add('workload');
            workloadNode.textContent = course.workload;
            courseDetails.appendChild(workloadNode);
            
            const courseModeNode = document.createElement('p');
            courseModeNode.classList.add('course-mode');
            courseModeNode.textContent = course.mode;
            courseDetails.appendChild(courseModeNode);
            
            const priceNode = document.createElement('p');
            priceNode.classList.add('price');
            priceNode.textContent = course.price;
            courseDetails.appendChild(priceNode);
            
            const locationNode = document.createElement('p');
            locationNode.classList.add('location');
            locationNode.textContent = course.location;
            courseDetails.appendChild(locationNode);

            courseDetailsNode.appendChild(courseDetails);
            li.appendChild(courseDetailsNode);

            li.insertAdjacentHTML('beforeend', getCoursePreviewActions(courseID));

            resultList.appendChild(li);
        }
    });
}

function getCoursePreviewActions(courseid) {
    return `
        <div class="course-preview__actions">
            <button class="bookmark-btn labeled-icon-btn"><i class="material-symbols-rounded">star</i>Merken</button>
            <button class="share-btn labeled-icon-btn"><i class="material-symbols-rounded">share</i>Teilen</button>
            <a class="to-course-btn btn" href="/k${courseid}"><span>Kurs ansehen</span></a>
        </div>
    `;
}

function getCurrentLevelSelectElement(skill) {
    const select = document.createElement("select");
    select.setAttribute("name", skill.label);
    select.setAttribute("id", skill.uri);
    let optionHtml = "";
    if (skill.isLanguageSkill) {
        optionHtml = `
            <option value="" style="display: none;">Stufe auswählen</option>
            <option value="O">ohne Vorkentnisse</option>
            <option class="level-A1" value="A1">A1</option>
            <option class="level-A2" value="A2">A2</option>
            <option class="level-B1" value="B1">B1</option>
            <option class="level-B2" value="B2">B2</option>
            <option class="level-C1" value="C1">C1</option>
            <option class="level-C2" value="C2">C2</option>
            `;
    } else {
        optionHtml = `
            <option value="" style="display: none;">Stufe auswählen</option>
            <option class="level-O" value="O">ohne Vorkentnisse</option>
            <option class="level-A" value="A">Grundstufe</option>
            <option class="level-B" value="B">Aufbaustufe</option>
            <option class="level-C" value="C">Fortgeschrittenenstufe</option>
            <option class="level-D" value="D">Expert*innenstufe</option>
            `;
    }
    
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
    let optionHtml = "";
    if (skill.isLanguageSkill) {
        optionHtml = `
            <option value="" style="display: none;">Stufe auswählen</option>
            <option class="level-A1" value="A1">A1</option>
            <option class="level-A2" value="A2">A2</option>
            <option class="level-B1" value="B1">B1</option>
            <option class="level-B2" value="B2">B2</option>
            <option class="level-C1" value="C1">C1</option>
            <option class="level-C2" value="C2">C2</option>
            `;
    } else {
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
    if (step == 6 && (!currentSearchResult && searchResults.length == 0)) {
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

    for (i = 0; i <= Math.min(currentStep, scoutNavSteps.length-1); i++) {
        scoutNavSteps[i].classList.add("done");
        // enable(scoutNavSteps[i].firstChild);
    }

    scoutNavSteps[Math.min(currentStep, scoutNavSteps.length-1)].classList.add("current-step");
}

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
}

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

    const url = "./esco/autocomplete?" + new URLSearchParams(params);
    const response = await fetch(url);

    if (response.ok) {
        const result = await response.json();
        return result;
    } else {
        console.error("HTTP-Error: " + response.status);
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