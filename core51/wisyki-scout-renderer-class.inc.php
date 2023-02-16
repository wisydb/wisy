<?php 
if (!defined('IN_WISY')) die('!IN_WISY');

class WISYKI_SCOUT_RENDERER_CLASS
{
	var $framework;
	var $request;

	function __construct(&$framework, $request)
	{
		// constructor
		$this->framework	=& $framework;
		$this->request = $request;
	}

	function getPrologue() {
		$pageTitle = 'Weiterbildungsscout';
		global $wisyCore;

		$additional_css = explode(',', $this->framework->iniRead('scout.css', ''));
		$logo = $this->framework->iniRead('scout.logo', '');
		?>

		<!DOCTYPE html>
		<html lang="en">
		<head>
			<meta charset="UTF-8">
			<meta http-equiv="X-UA-Compatible" content="IE=edge">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<link rel="stylesheet" href="<?php echo $wisyCore ?>/wisyki-scout.css">
			<?php 
			if ($additional_css) {
				foreach ($additional_css as $cssfile) {
					echo("<link rel='stylesheet' href='$cssfile'>");
				}
			} 
			?>
			<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
			<script src="<?php echo $wisyCore ?>/wisyki-scout.js"></script>
			<title><?php echo $pageTitle ?></title>
		</head>
		<body>
			<nav>
				<a href="/index.php" class="img-link"><img class="portal-logo" src="<?php echo($logo) ?>" alt="Kursportal Schleswig Holstein"></a>
				<div class="action-bar">
					<a class="bookmarks-btn labeled-icon-btn" href="#"><i class="material-symbols-rounded">assignment</i>Merkliste</a>
					<a class="login-btn labeled-icon-btn" href="#"><i class="material-symbols-rounded">account_circle</i>Login</a>
					<div class="menu-btn"><div class="menu-btn__burger"></div></div>
				</div>
			</nav>
			<header>
				<section class="tab-nav">
					<a href="/index.php"><span>Kurssuche</span></a>
					<div class="selected"><span>Scout</span></div>
				</section>
				<section class="scout-nav">
					<div class="scout-nav__progress">
						<span>Schritt</span>
						<ul>
							<li class="done"><button to-step="0">1</button></li>
							<li><button to-step="1">2</button></li>
							<li><button to-step="2">3</button></li>
							<li><button to-step="3">4</button></li>
							<li><button to-step="4">5</button></li>
							<li><button to-step="5">6</button></li>
						</ul>
					</div>
					<button class="btn-link" id="scout-abort"><span>abbrechen</span></button>
				</section>
			</header>
		
		<?php
	}
	
	function renderMain()
	{
		$this->getPrologue();
		?>
			<main>
				<section id="steps">
					<div class="step-container">
						<article class="step" id="occupation"></article>
					</div>
					<div class="step-container">
						<div class="loader"></div>
						<article class="step" id="occupationSkills"></article>
					</div>
					<div class="step-container">
						<article class="step" id="skills"></article>
					</div>
					<div class="step-container">
						<article class="step" id="currentLevel"></article>
					</div>
					<div class="step-container">
						<article class="step" id="levelGoal"></article>
					</div>
					<div class="step-container">
						<article class="step" id="resultOverview">
							<p><strong>Geschafft!</strong> Ich habe folgende Kurse f&uuml;r Dich gefunden.</p>
							<ul class="result-list"></ul>
						</article>
					</div>
					<div class="step-container">
						<article class="step" id="resultList">
							<p><span class="result-count"></span> gefunden zu:</p>
							<p class="skill-title"></p>
							<ul class="course-list"></ul>
						</article>
					</div>
				</section>
			</main>

		<?php
		$this->getEpilogue();
	}

	function getEpilogue() {
		?>
		
			<section class="bottom-nav">
				<button id="prev-step" class="hidden disabled"><span class="material-symbols-rounded">arrow_forward_ios</span></button>
				<button id="next-step" class="hidden disabled"><span class="material-symbols-rounded">arrow_forward_ios</span></button>
			</section>
		</body>
		</html>

		<?php
	}
	
	function renderOccupationStep()
	{
		?>

		<h3>Für welchen Beruf oder welche Tätigkeit suchst Du eine Weiterbildung?</h2>
		<div class="autocomplete-box">
			<div class="autocomplete-box__input">
				<span class="material-symbols-rounded">search</span>
				<input type="text" placeholder="Beruf oder Tätigkeit finden" name="esco-occupation-select" id="esco-occupation-select" class="esco-autocomplete" esco-type="occupation" onlyrelevant=False>
				<button class="clear-input material-symbols-rounded">close</button>
			</div>
			<output name="esco-occupation-autocomplete" for="esco-occupation-select"></output>
		</div>
		<p>Du weißt welche Kompetenzen Du weiterentwickeln möchtest? <a class="link to-skill-step-btn" href="#">Klicke hier</a></p>

		<?php
	}
	
	function renderOccupationSkillsStep()
	{
		?>
	
		<p>Du hast folgendes gewählt</p>
		<div class="selected-occupation"></div>
		<p>Ich habe Kurse zu folgenden passenden Kompetenzen gefunden. Wähle aus, welche Du erlangen oder weiterentwickeln möchtest. Beachte: Im nächsten Schritt kannst Du weitere Kompetenzen frei wählen. Wähle insgesamt max. <span class="maxSkills"></span>.</p>
		<ul class="selectable-skills"></ul>
		<button class="btn-link more-skills-btn"><span>weitere Kompetenzen anzeigen</span></button>
		<button class="btn-link less-skills-btn"><span>weniger Kompetenzen anzeigen</span></button>

		<?php
	}
	
	function renderSkillsStep()
	{
		?>
	
		<p>Möchtest du noch andere Kompetenzen erlangen oder weiterentwickeln? Füge weitere Kompetenzen zu Deiner Liste hinzu. Wähle insgesamt max. <span class="maxSkills"></span>.</p>
		<div class="autocomplete-box">
			<div class="autocomplete-box__input">
				<span class="material-symbols-rounded">search</span>
				<input type="text" placeholder="Kompetenzen finden" name="esco-skill-select" id="esco-skill-select" class="esco-autocomplete" esco-type="skill" onlyrelevant=False>
				<button class="clear-input material-symbols-rounded">close</button>
			</div>
			<output name="esco-skill-select" for="esco-skill-select"></output>
		</div>
		<ul class="selectable-skills"></ul>

		<?php
	}
	
	function renderCurrentLevelStep()
	{
		?>
	
		<p>Um dir passende Kurse vorzuschlagen, beurteile Deine Kompetenzen bitte nach Deinem Können.</p>
		<button class="open-modal-btn btn-link"><span>Kompetenzstufen kurz erklärt</span><i class="material-symbols-rounded">info</i></button>
		<?php $this->renderLevelModal() ?>
		<ul class="current-level-selection"></ul>

		<?php
	}

	function renderLevelModal() {
		?>

		<section class="modal level-explanation display-none">
			<div class="backdrop"></div>
			<div class="modal__content">
				<button class="close-modal-btn material-symbols-rounded">close</button>
				<div class="modal__header">
					<p class="modal__heading">Niveaustufen erklärt!</p>
					<p class="modal__subheading"><em>Hinweis:</em> Alle Stufen beinhalten jeweils das Niveau der Vorstufe.</p>
				</div>

				<div class="level-explantaion__tabs">
					<ul class="tab-nav">
						<li for="Kompetenzniveau" class="selected level-A" disabled>Kompetenzniveau</li>
						<li for="Sprachniveau">Sprachniveau</li>
					</ul>

					<div class="tabs">
						<article class="tab" name="Kompetenzniveau">
							<ul>
								<li class="level-explanation__item level-A">
									<p class="level-title">Grundstufe</p>
									<div>
										<p class="comp-type">Wissen und Fertigkeiten</p>
										<p class="comp-explanation">Ich kann diese Kompetenzen für <em>grundlegende Aufgaben nach anleitung</em> einsetzen.</p>
									</div>
									<div>
										<p class="comp-type">Personelle Kompetenzen</p>
										<p class="comp-explanation">Ich kann diese Kompetenzen einsetzen, um Handlungen <em>wahrzunehmen</em> und einzuschätzen.</p>
									</div>
								</li>
								<li class="level-explanation__item level-B">
									<p class="level-title">Aufbaustufe</p>
									<div>
										<p class="comp-type">Wissen und Fertigkeiten</p>
										<p class="comp-explanation">Ich kann diese Kompetenzen für <em>erweiterte Aufgaben überwiegend selbstständig</em> einsetzen.</p>
									</div>
									<div>
										<p class="comp-type">Personelle Kompetenzen</p>
										<p class="comp-explanation">Ich kann diese Kompetenzen einsetzen, um in einer Gruppe <em>mitzuwirken</em>.</p>
									</div>
								</li>
								<li class="level-explanation__item level-C">
									<p class="level-title">Fortgeschrittenenstufe</p>
									<div>
										<p class="comp-type">Wissen und Fertigkeiten</p>
										<p class="comp-explanation">Ich kann diese Kompetenzen für <em>spezialisierte Aufgaben selbstständig</em> einsetzen.</p>
									</div>
									<div>
										<p class="comp-type">Personelle Kompetenzen</p>
										<p class="comp-explanation">Ich kann diese Kompetenzen einsetzen, um Prozesse <em>kooperativ zu gestalten</em> und andere anzuleiten.</p>
									</div>
								</li>
								<li class="level-explanation__item level-D">
									<p class="level-title">Expert*innenstufe</p>
									<div>
										<p class="comp-type">Wissen und Fertigkeiten</p>
										<p class="comp-explanation">Ich kann diese Kompetenzen für <em>komplexe Aufgaben eigenverantwortlich</em> einsetzen.</p>
									</div>
									<div>
										<p class="comp-type">Personelle Kompetenzen</p>
										<p class="comp-explanation">Ich kann diese Kompetenzen einsetzen, um Personen oder Organisationen <em>zu führen</em>.</p>
									</div>
								</li>
							</ul>
						</article>
						<article class="tab display-none" name="Sprachniveau">
							<ul>
								<li class="level-explanation__item level-A1">
									<p class="level-title">A1 - Anfänger</p>
									<p class="comp-explanation">Ich kann ganz einfache Sätze verstehen und verwenden.</p>
								</li>
								<li class="level-explanation__item level-A2">
									<p class="level-title">A2 - Grundlegende Kentnisse</p>
									<p class="comp-explanation">Ich kann elementare Sätze und häufig gebrauchte Ausdrücke verstehen und verwenden.</p>
								</li>
								<li class="level-explanation__item level-B1">
									<p class="level-title">B1 - Fortgeschrittene Sprachverwendung</p>
									<p class="comp-explanation">Ich kann klare Standardsprache verstehen und verwenden.</p>
								</li>
								<li class="level-explanation__item level-B2">
									<p class="level-title">B2 - Selbständige Sprachverwendung</p>
									<p class="comp-explanation">Ich kann die Sprache selbständig in einem breiten Themenspektrum verwenden.</p>
								</li>
								<li class="level-explanation__item level-C1">
									<p class="level-title">C1 - Fachkundige Sprachkenntnisse</p>
									<p class="comp-explanation">Ich kann die Sprache und anspruchsvolle Texte verstehen und fließend, strukturiert kommunizieren.</p>
								</li>
								<li class="level-explanation__item level-C2">
									<p class="level-title">C2 - Annähernd muttersprachliche Kenntnisse</p>
									<p class="comp-explanation">Ich kann die gesprochene Sprache und geschriebene Texte mühelos verstehen und selbst anwenden.</p>
								</li>
							</ul>
						</article>
						<article class="tab" name="Sprachniveau"></article>
					</div>
				</div>
			</div>
		</section>

		<?php
	}
	
	function renderLevelGoalStep()
	{
		?>
	
		<p>Auf Basis Deiner Einschätzung schlage ich Dir folgende Lernziele vor. Du kannst sie hier noch anpassen.</p>
		<button class="open-modal-btn btn-link"><span>Kompetenzstufen kurz erklärt</span><i class="material-symbols-rounded">info</i></button>
		<?php $this->renderLevelModal() ?>
		<ul class="level-goal-selection"></ul>

		<?php
	}
	
	function render()
	{
		header("Content-Type: text/html; charset=UTF-8");

		switch( $this->request ) {
			case 'main':
				$this->renderMain();
				break;
			case 'occupation':
				$this->renderOccupationStep();
				break;
			case 'occupationSkills':
				$this->renderOccupationSkillsStep();
				break;
			case 'skills':
				$this->renderSkillsStep();
				break;
			case 'currentLevel':
				$this->renderCurrentLevelStep();
				break;
			case 'levelGoal':
				$this->renderLevelGoalStep();
				break;
			default:
				$this->renderMain();
				break;
		}
	}
	
};
