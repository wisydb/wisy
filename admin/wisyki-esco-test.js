window.onload = init;


function init() {
    const autocompleteInput = document.querySelectorAll(".esco-autocomplete");
    autocompleteInput.forEach((input) => input.addEventListener('keyup', () => autocomplete(input)))

    const skillSuggestInput = document.querySelectorAll(".esco-skill-suggest");
    skillSuggestInput.forEach((input) => input.addEventListener('keyup', () => suggestSkills(input.value)))

    const tablinks = document.querySelectorAll(".tablink");
    tablinks.forEach((tablink) => tablink.addEventListener('click', (evt) => openTab(evt)))

    const activeInput = document.querySelector(".tabcontent.active input")
    activeInput.focus()
}

async function suggestSkills(uri) {
    if(!uri.startsWith("http://data.europa.eu/esco/")) {
        showSkillSuggestions({"uri": uri});
        return;
    }

    const params = {uri: uri, limit: 10};

    const url = "./wisyki-esco-skill-suggest.inc.php?" + new URLSearchParams(params);
	const response = await fetch(url);

	if (response.ok) {
		const result = await response.json();
        console.log(result);
        showSkillSuggestions(result);
	} else {
		alert("HTTP-Error: " + response.status);
	}
}

function showSkillSuggestions(suggestions) {
    const input = document.querySelector("#tab-skill-suggestions input");
    input.value = suggestions.uri;

    const title = document.querySelector("#tab-skill-suggestions label");

    if (!suggestions.title) {
        title.textContent = "Nicht gefunden";
        return;
    }

    title.textContent = suggestions.title;

    const output = document.querySelector("#tab-skill-suggestions output");
    while(output.lastChild) {
        output.removeChild(output.lastChild);
    }

    const ul = document.createElement("ul");
    ul.setAttribute("name", category);

    for (const suggestionURI in suggestions.skills) {
        const suggestion = suggestions.skills[suggestionURI]
        const li = document.createElement("li");
        const span = document.createElement("span");
        span.textContent = suggestion.label;
        li.appendChild(span);

        const a = document.createElement("a");
        a.setAttribute("href", "https://esco.ec.europa.eu/de/classification/skills?uri=" + suggestionURI);
        a.setAttribute("target", "_blank");
        a.innerHTML = "&#x1F6C8;";
        li.appendChild(a);

        ul.appendChild(li);
    }

    output.appendChild(ul);
}

async function autocomplete(input) {
    const searchterm = input.value;

    if(searchterm.length < 3) {
        showAutocompleteResult([])
        return;
    }

    const params = {term: searchterm, limit: 10};

    if (input.getAttribute("esco-type")) {
        params.type = input.getAttribute("esco-type").split(" ");
    }
    if (input.getAttribute("esco-scheme")) {
        params.scheme = input.getAttribute("esco-scheme").split(" ");
    }

    const url = "./wisyki-esco-autocomplete.inc.php?" + new URLSearchParams(params);
	const response = await fetch(url);

	if (response.ok) {
		const result = await response.json();
		showAutocompleteResult(result);
	} else {
		alert("HTTP-Error: " + response.status);
	}
}

function showAutocompleteResult(suggestions) {
    const output = document.querySelector(".tabcontent.active output");
    while(output.lastChild) {
        output.removeChild(output.lastChild);
    }

    for (category in suggestions) {
        const label = document.createElement("label");
        label.setAttribute("for", category);
        label.textContent = category;
        output.appendChild(label);
        const ul = document.createElement("ul");
        ul.setAttribute("name", category);

        for (const suggestionURI in suggestions[category]) {
            const suggestion = suggestions[category][suggestionURI]
            const li = document.createElement("li");
            const span = document.createElement("span");
            span.textContent = suggestion.label;
            li.appendChild(span);

            if (category == "occupation" || category == "skills-hierarchy" || category == "member-occupations") {
                const suggestButton = document.createElement("button");
                suggestButton.className = "suggest";
                suggestButton.textContent = "Kompetenzvorschläge";
                suggestButton.setAttribute("value", suggestionURI);
                li.appendChild(suggestButton);

                suggestButton.addEventListener('click', () => suggestSkills(suggestButton.value))
            }

            if (suggestionURI.startsWith('http')) {
                const a = document.createElement("a");
                a.setAttribute("href", "https://esco.ec.europa.eu/de/classification/skills?uri=" + suggestionURI);
                a.setAttribute("target", "_blank");
                a.innerHTML = "&#x1F6C8;";
                li.appendChild(a);
            }

            ul.appendChild(li);
        }

        output.appendChild(ul);
    }
}

function openTab(evt) {
    const activeTablink = evt.currentTarget;
    const tabs = activeTablink.parentNode.parentNode;

    // Get all elements with class="tabcontent" and hide them
    const tabcontent = tabs.querySelectorAll(".tabcontent");
    for (let i = 0; i < tabcontent.length; i++) {
        tabcontent[i].className = tabcontent[i].className.replace(" active", "");
    }

    // Get all elements with class="tablinks" and remove the class "active"
    const tablinks = tabs.querySelectorAll(".tablink");
    for (let i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }

    // Show the current tab, and add an "active" class to the button that opened the tab
    const activeTab = tabs.querySelector("#" + activeTablink.value);
    activeTab.className += " active";
    activeTablink.className += " active";

    const input = activeTab.querySelector(".esco-autocomplete");
    input.focus()
}