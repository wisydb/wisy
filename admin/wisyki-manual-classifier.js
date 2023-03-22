window.onload = init;

const selectedSkills = {};
let skillInput;

function init() {
    const autocompleteInput =
        document.querySelector(".esco-autocomplete");
    const clearInputButton = document.querySelector(".clear-input");
    const autocompleteOutput = document.querySelector(
        ".autocomplete-box output"
    );
    skillInput = document.querySelector('input[name="skills"]');
    
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

    initSkillCloud().then(() => updateSkillCloud());
}

let skillCloudNode;
let skillCloudSearchtermNode;
let skillSuggestions;
let titleSuggestions;
let themaSuggestions;
const abschluesse = [];
const sachstichworte = [];

async function initSkillCloud() {
    skillCloudNode = document.querySelector('.skill-cloud');
    skillCloudSearchtermNode = document.querySelector('.skill-cloud-searchterm');

    const abschlussTagNodes = skillCloudNode.querySelectorAll('.tag_abschluss');
    const sachstichwortTagNodes = skillCloudNode.querySelectorAll('.tag_sachstichwort');
    abschlussTagNodes.forEach((node) => abschluesse.push(node.textContent));
    sachstichwortTagNodes.forEach((node) => {
        // addSelectedSkill(node.textContent, node.textContent);
        sachstichworte.push(node.textContent)
    });

    if (!skillSuggestions) { 
        skillSuggestions = await suggestSkills(document.querySelector('#course-title').textContent,  document.querySelector('#course-description').textContent, document.querySelector('#course-thema').textContent, sachstichworte, abschluesse); 
        if (!skillSuggestions) { 
            skillSuggestions = {
                'searchterms': '',
                'result': null,
            }
        } else {
            skillSuggestions.searchterms = skillSuggestions.searchterms.keywords.join(', ');
        }
    }
}

async function updateSkillCloud() {

    let searchterms = skillSuggestions.searchterms;

    while (skillCloudNode.lastChild) {
        skillCloudNode.removeChild(skillCloudNode.lastChild);
    }

    const skillsShown = [];

    for (const sachstichwort of sachstichworte) {
        const skillHtml = `<li><button class='btn selected-skill'>${sachstichwort}</button></li>`;
        skillCloudNode.insertAdjacentHTML('afterbegin', skillHtml);
        skillsShown.push(sachstichwort);
    }

    for (const skillUri in selectedSkills) {
        const skill = selectedSkills[skillUri];
        
        const skillHtml = `<li><button skill-uri="${skillUri}" class='btn selected-skill'>${skill.label}</button></li>`;
        skillCloudNode.insertAdjacentHTML('afterbegin', skillHtml);
        skillsShown.push(skill.label);

        if (!skill.suggestions) {
            const response = await autocomplete(skill.label);
            if (response && response) {
                selectedSkills[skillUri].suggestions = response;
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

    for (const skillUri in skillSuggestions.results) {
        const skill = skillSuggestions.results[skillUri];
        if (skillsShown.includes(skill['title'])) {
            continue;
        }
        const skillHtml = `<li><button skill-uri="${skillUri}" class='btn'>${skill['title']}</button></li>`;
        skillCloudNode.insertAdjacentHTML('beforeend', skillHtml);
        skillsShown.push(skill['title']);
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
    console.log(selectedSkills);
}

async function suggestSkills(title, text, thema, abschluesse, sachstichworte) {
    const wisytags = [...abschluesse, ...sachstichworte];
    const keywords = [title, thema, ...wisytags];
    text += ' ' + title + ' ' + wisytags.join(', ') + wisytags.join(', ') + ' ' + thema;

    const data = {
        searchterms: {
            keywords: keywords
        },
        doc: text,
        extract_keywords: wisytags.length == 0,
        exclude_irrelevant: true
    };

    console.log(data);

    const url = "https://wisyki.eu.pythonanywhere.com/predictESCO";
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
        console.error("HTTP-Error: " + response.status);
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
        suggestions: null,
    };

    selectedSkills[uri] = skill;
    skillInput.value = JSON.stringify(selectedSkills);
}

function removeSelectedSkill(uri) {
    delete selectedSkills[uri];
    skillInput.value = JSON.stringify(selectedSkills);
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
        params.type = input.getAttribute("esco-type");
    }
    if (input.getAttribute("esco-scheme")) {
        params.scheme = input.getAttribute("esco-scheme");
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
        console.error("HTTP-Error: " + response.status);
    }
}

async function autocomplete(searchterm) {
    const params = {
        term: searchterm,
        limit: 3,
        scheme: 'member-skills,extended-skills-hierarchy',
        onlyrelevant: false,
    };

    const url = "../esco/autocomplete?" + new URLSearchParams(params);
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


    const ul = document.createElement("ul");
    for (const uri in suggestions) {
        if (uri in selectedSkills) {
            continue;
        }
        const suggestion = suggestions[uri];
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