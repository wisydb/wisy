window.onload = init;

const selectedSkills = [];


function init() {
    const autocompleteInput =
        document.querySelector(".esco-autocomplete");
    const clearInputButton = document.querySelector(".clear-input");
    const autocompleteOutput = document.querySelector(
        ".autocomplete-box output"
    );
    const skillsNode = document.querySelector(".selectable-skills");
    
    autocompleteInput.addEventListener("keyup", async () => {
        const requestID = ++currentRequestID;
        const concepts = await autocomplete_input(autocompleteInput, requestID);
        const conceptBtns = showAutocompleteResult(
            concepts,
            autocompleteOutput,
            requestID
        );
        conceptBtns.forEach((btn) =>
            btn.addEventListener("mousedown", () => {
                addSelectedSkill(btn.id, btn.name);
                updateSkillCloud();
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

    updateSkillCloud();
}

let skillCloudNode;
let skillCloudSearchtermNode;
let skillSuggestions;

async function updateSkillCloud() {
    if (!skillCloudNode) {
        skillCloudNode = document.querySelector('.skill-cloud');
    }
    if (!skillCloudSearchtermNode) {
        skillCloudSearchtermNode = document.querySelector('.skill-cloud-searchterm');
    }

    if (!skillSuggestions) {
        skillSuggestions = await suggestSkills(document.querySelector('#course-title').textContent, document.querySelector('#course-description').textContent);
    }

    let searchterms = skillSuggestions.searchterms;

    while (skillCloudNode.lastChild) {
        skillCloudNode.removeChild(skillCloudNode.lastChild);
    }

    const skillsShown = [];

    for (const skillUri in selectedSkills) {
        const skill = selectedSkills[skillUri];
        
        const skillHtml = `<li><button skill-uri="${skillUri}" class='btn selected-skill'>${skill['label']}</button></li>`;
        skillCloudNode.insertAdjacentHTML('afterbegin', skillHtml);
        skillsShown.push(skill['label']);

        if (!skill.suggestions) {
            const response = await autocomplete(skill.label);
            if (response.skill) {
                skill.suggestions = response.skill;
            }
        }
        searchterms += ", " + skill.label
    }

    for (const skillUri in selectedSkills) {
        const skill = selectedSkills[skillUri];
        for (const suggestionUri in skill.suggestions) {
            const suggestion = skill.suggestions[suggestionUri];
            if (skillsShown.includes(suggestion['label'])) {
                continue;
            }
            const skillHtml = `<li><button skill-uri="${suggestionUri}" class='btn'>${suggestion['label']}</button></li>`;
            skillCloudNode.insertAdjacentHTML('beforeend', skillHtml);
            skillsShown.push(suggestion['label']);
        }
    }

    for (const skillUri in skillSuggestions.result) {
        const skill = skillSuggestions.result[skillUri];
        if (skillsShown.includes(skill['label'])) {
            continue;
        }
        const skillHtml = `<li><button skill-uri="${skillUri}" class='btn'>${skill['label']}</button></li>`;
        skillCloudNode.insertAdjacentHTML('beforeend', skillHtml);
        skillsShown.push(skill['label']);
    }
    
    skillCloudSearchtermNode.textContent = searchterms;

    const suggestionBtns = document.querySelectorAll('.skill-cloud button');
    suggestionBtns.forEach((btn) =>
        btn.addEventListener("click", () => {
            if (btn.getAttribute('skill-uri') in selectedSkills) {
                removeSelectedSkill(btn.getAttribute('skill-uri'), btn.textContent);
            } else {
                addSelectedSkill(btn.getAttribute('skill-uri'), btn.textContent);
            }
            updateSkillCloud();
        })
    );

    console.log(skillsShown);
}

async function suggestSkills(title, description) {
    const data = {
        title: title,
        description: description,
    };

    const url = "../esco/suggest-skills";
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data),
    });

    if (response.ok) {
        const result = await response.json();
        return result;
    } else {
        alert("HTTP-Error: " + response.status);
    }
}

function clearAutocompleteInput(input, output) {
    input.value = "";
    hide(output);
    while (output.lastChild) {
        output.removeChild(output.lastChild);
    }
}

function addSelectedSkill(uri, label) {
    const skill = {
        label: label,
        currentLevel: null,
        levelGoal: null,
        uri: uri,
    };

    selectedSkills[uri] = skill;
}

function removeSelectedSkill(uri) {
    delete selectedSkills[uri];
}

let currentRequestID = 0;

async function autocomplete_input(input, requestID) {
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

    const url = "../esco/autocomplete?" + new URLSearchParams(params);
    const response = await fetch(url);

    if (response.ok) {
        const result = await response.json();
        return result;
    } else {
        alert("HTTP-Error: " + response.status);
    }
}
async function autocomplete(searchterm) {
    const params = {
        term: searchterm,
        limit: 5,
        type: 'skill',
        onlyrelevant: false,
    };

    const url = "../esco/autocomplete?" + new URLSearchParams(params);
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
            if (uri in selectedSkills) {
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