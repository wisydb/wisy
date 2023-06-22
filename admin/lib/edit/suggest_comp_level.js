window.onload = init

function init() { 
	const descInput = document.querySelector('textarea[name="f_beschreibung"]'); 
	const titelInput = document.querySelector('input[name="f_titel"]'); 
	titelInput.addEventListener('blur', () => predict(titelInput.value, descInput.value)) 
	descInput.addEventListener('blur', () => predict(titelInput.value, descInput.value)) 
 
	titelInput.addEventListener('focus', hideLevelSuggestion); 
	descInput.addEventListener('focus', hideLevelSuggestion); 
 
	showLevelSuggestionLoad(); 
} 
 
async function predict(title, description) { 
	const levelLoadTag = document.querySelector('.level-loading'); 
	if(levelLoadTag && !levelLoadTag.hidden) { 
		return; 
	} 
	if (description.length < 3 && title.length < 3) { 
		return; 
	} 
 
	showLevelSuggestionLoad() 
 
	const data = {title: title, description: description}; 
	// const url = "./wisyki-predict-comp-level.inc.php?" 
	const url = "https://wbhessen.eu.pythonanywhere.com/predictCompLevel"
	const response = await fetch(url, {
		method: "POST",
		body: JSON.stringify(data),
		headers: {"Content-type": "application/json; charset=UTF-8"}
	}); 
 
	if (response.ok) {
		const result = await response.json(); 
		showLevelSuggestion(result); 
	} else { 
		alert("HTTP-Error: " + response.status); 
	} 
} 
 
function showLevelSuggestionLoad() { 
	const levelInput = document.querySelector('select[name="f_level"]'); 
	let levelLoadTag = document.querySelector('.level-loading'); 
	if(!levelLoadTag) { 
		levelLoadTag = document.createElement("span"); 
		levelLoadTag.className = "level-loading" 
		levelLoadTag.style.margin = "0 0 0 1em" 
		levelInput.parentNode.appendChild(levelLoadTag); 
		levelLoadTag.hidden = true 
 
		let i = 0; 
		setInterval(function() { 
			i = ++i % 4; 
			levelLoadTag.innerHTML = 'KI empfielt ' + Array(i + 1).join('.'); 
		}, 600); 
	} else { 
		levelLoadTag.className = "level-loading" 
		levelLoadTag.hidden = false 
	} 
} 
 
function hideLevelSuggestionLoad() { 
	const levelLoadTag = document.querySelector('.level-loading'); 
	if(levelLoadTag) { 
		levelLoadTag.hidden = true 
	} 
} 
 
function showLevelSuggestion(suggestion) { 
	hideLevelSuggestionLoad(); 
 
	const levelInput = document.querySelector('select[name="f_level"]'); 
	let suggestionTag = document.querySelector('.level-suggestion'); 
	if(!suggestionTag) { 
		suggestionTag = document.createElement("span"); 
		suggestionTag.style.margin = "0 0 0 1em" 
		levelInput.parentNode.appendChild(suggestionTag); 
	} else { 
		suggestionTag.hidden = false 
	}
	suggestionTag.className = "level-suggestion" 
	suggestionTag.innerHTML = "KI empfielt <strong>Niveau "+ suggestion.level + " (" + (suggestion.target_probability*100).toFixed(2) + "%)</strong>" 
} 
 
function hideLevelSuggestion() { 
	const suggestionTag = document.querySelector('.level-suggestion'); 
	if(suggestionTag) { 
		suggestionTag.hidden = true 
	} 
}
