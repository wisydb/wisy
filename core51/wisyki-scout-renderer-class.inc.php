<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/core51/wisyki-json-response.inc.php');

/**
 * This class provides all necessary methods to render the HTML needed for the feature "Weiterbildungsscout".
 * The "Weiterbildungsscout" provides a guided step-by-step process to capture the current skill profile and learning goals of a user 
 * for the purpose of generating skill-based course recommendations. Basically the project aims to enhance the search results of wisy,
 * by adding skills and competency levels to the search parameters and providing a new way of setting the search-parameters, 
 * that is more user-friendly than the basic keyword based search and filter options.
 * 
 * The "Weiterbildungsscout" was created by the project consortium "WISY@KI" as part of the Innovationswettbewerb INVITE 
 * and was funded by the Bundesinstitut für Berufsbildung and the Federal Ministry of Education and Research.
 *
 * @copyright   2023 ISy TH Lübeck <dev.ild@th-luebeck.de>
 * @author		Pascal Hürten <pascal.huerten@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class WISYKI_SCOUT_RENDERER_CLASS {
	/**
	 * The framework of the wisy frontend. Provides basic functionalities to navigate the system. 
	 *
	 * @var WISY_FRAMEWORK_CLASS
	 */
	private WISY_FRAMEWORK_CLASS $framework;

	/**
	 * The second part of the requested URI path.
	 * For the URI <host>/scout/occupationSkills $request would be "occupationSkills".
	 * This parameter is used to identify wich part of the scout the client is requesting to be rendered.
	 *
	 * @var string
	 */
	private string $request = '';

	/**
	 * Constructor.
	 *
	 * @param WISY_FRAMEWORK_CLASS $framework
	 * @param string|null $request
	 */
	function __construct(&$framework, string|null $request) {
		// constructor
		$this->framework = $framework;
		if (isset($request)) {
			$this->request = $request;
		}
	}

	/**
	 * Renders the footer and includes all necessary javascript and css.
	 *
	 * @return void
	 */
	function getPrologue() {
		$pageTitle = 'Weiterbildungsscout';
		global $wisyCore;

		// Get additional CSS styles from the portal config.
		$additional_css = explode(',', $this->framework->iniRead('scout.css', ''));
		// Get additional JS scripts from the portal config.
		$additional_js = explode(',', $this->framework->iniRead('scout.js', ''));
		// Get the filepath of the logo to be displayed in the navbar.
		$logo = $this->framework->iniRead('scout.logo', '');
?>

		<!DOCTYPE html>
		<html lang="en">

		<head>
			<meta charset="UTF-8">
			<meta http-equiv="X-UA-Compatible" content="IE=edge">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<link rel="stylesheet" href="<?php echo $wisyCore ?>/wisyki/css/scout.css">
			<?php
			if ($additional_css) {
				foreach ($additional_css as $cssfile) {
					echo ("<link rel='stylesheet' href='$cssfile'>");
				}
			}
			?>
			<title><?php echo $pageTitle ?></title>
		</head>

		<body>
			<script src="<?php echo $wisyCore ?>/wisyki/js/scout.js" async></script>
			<script src="<?php echo $wisyCore ?>/wisyki/js/main.js" async></script>
			<?php
			if ($additional_js) {
				foreach ($additional_js as $jsfile) {
					echo ('<script src="' . $jsfile . '" async></script>');
				}
			}
			?>
    		<script src="https://unpkg.com/mustache@latest"></script>
			<nav>
				<a href="/index.php" class="img-link"><img class="portal-logo" src="<?php echo ($logo) ?>" alt="Kursportal Schleswig Holstein" height="60px" width="220px"></a>
				<div class="action-bar">
					<a class="bookmarks-btn labeled-icon-btn" href="#"><i class="icon bookmarks-icon"></i>Merkliste</a>
					<a class="login-btn labeled-icon-btn" href="#"><i class="icon account-icon"></i>Login</a>
					<div class="menu-btn">
						<div class="menu-btn__burger"></div>
					</div>
				</div>
			</nav>
		<?php
	}


	/**
	 * Renders the header body including a sceleton for the scout steps and the footer.
	 *
	 * @return void
	 */
	function renderMain() {
		$this->getPrologue();
		?>

			<div id="path"></div>

		<?php
		$this->getEpilogue();
	}

	/**
	 * Renders the footer.
	 *
	 * @return void
	 */
	function getEpilogue() {
		?>

			<section class="bottom-nav">
				<button id="prev-step" class="hidden disabled" title="Go to previous step"><i class="icon arrow-icon"></i></button>
				<button id="next-step" class="hidden disabled" title="Go to next step"><i class="icon arrow-icon"></i></button>
			</section>
		</body>

		</html>

	<?php
	}


	/**
	 * Renders the occupation step.
	 *
	 * @return void
	 */
	function renderOccupationStep() {
	?>

		<p>Für welchen Beruf oder welche Tätigkeit suchst Du eine Weiterbildung?</p>
		<div class="autocomplete-box">
			<div class="autocomplete-box__input">
			<i class="icon search-icon"></i>
				<input type="text" placeholder="Beruf oder Tätigkeit finden" name="esco-occupation-select" id="esco-occupation-select" class="esco-autocomplete" esco-type="occupation" onlyrelevant=False>
				<button class="clear-input" title="Clear input"><i class="icon close-icon"></i></button>
			</div>
			<output name="esco-occupation-autocomplete" for="esco-occupation-select"></output>
		</div>
		<p>Du weißt welche Kompetenzen Du weiterentwickeln möchtest? <a class="link to-skill-step-btn" href="#">Klicke hier</a></p>

		<?php
	}


	/**
	 * Renders the occupationSkills step.
	 *
	 * @return void
	 */
	function renderOccupationSkillsStep() {
		?>

			<p>Du hast folgendes gewählt</p>
			<div class="selected-occupation"></div>
			<p>Ich habe Kurse zu folgenden passenden Kompetenzen gefunden. Wähle aus, welche Du erlangen oder weiterentwickeln möchtest. Beachte: Im nächsten Schritt kannst Du weitere Kompetenzen frei wählen. Wähle insgesamt max. <span class="maxSkills"></span>.</p>
			<ul class="selectable-skills"></ul>
			<button class="btn-link more-skills-btn"><span>weitere Kompetenzen anzeigen</span></button>
			<button class="btn-link less-skills-btn"><span>weniger Kompetenzen anzeigen</span></button>

		<?php
	}


	/**
	 * Renders the skill step.
	 *
	 * @return void
	 */
	function renderSkillsStep() {
		?>

			<p>Möchtest du noch andere Kompetenzen erlangen oder weiterentwickeln? Füge weitere Kompetenzen zu Deiner Liste hinzu. Wähle insgesamt max. <span class="maxSkills"></span>.</p>
			<div class="autocomplete-box">
				<div class="autocomplete-box__input">
					<i class="icon search-icon"></i>
					<input type="text" placeholder="Kompetenzen finden" name="esco-skill-select" id="esco-skill-select" class="esco-autocomplete" esco-scheme="member-skills,skills-hierarchy,sachstichwort" onlyrelevant=True>
					<button class="clear-input" title="Clear input"><i class="icon close-icon"></i></button>
				</div>
				<output name="esco-skill-select" for="esco-skill-select"></output>
			</div>
			<ul class="selectable-skills"></ul>

		<?php
	}


	/**
	 * Renders the currentLevel step.
	 *
	 * @return void
	 */
	function renderCurrentLevelStep() {
		?>

			<p>Um dir passende Kurse vorzuschlagen, beurteile Deine Kompetenzen bitte nach Deinem Können.</p>
			<button class="open-modal-btn btn-link"><span>Kompetenzstufen kurz erklärt</span><i class="icon help-icon"></i></button>
			<?php $this->renderLevelModal() ?>
			<ul class="current-level-selection"></ul>

		<?php
	}


	/**
	 * Renders the level explanation modal.
	 *
	 * @return void
	 */
	function renderLevelModal() {
		?>

			<section class="modal level-explanation display-none">
				<div class="backdrop"></div>
				<div class="modal__content">
					<button class="close-modal-btn" title="Close level explanation"><i class="icon close-icon"></i></button>
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

	/**
	 * Renders the levelGoal step.
	 *
	 * @return void
	 */
	function renderLevelGoalStep() {
		?>

			<p>Auf Basis Deiner Einschätzung schlage ich Dir folgende Lernziele vor. Du kannst sie hier noch anpassen.</p>
			<button class="open-modal-btn btn-link"><span>Kompetenzstufen kurz erklärt</span><i class="icon help-icon"></i></button>
			<?php $this->renderLevelModal() ?>
			<ul class="level-goal-selection"></ul>

	<?php
	}

	/**
	 * Renders the HTML for the Scout.
	 *
	 * Determines which step the user is on the value of $this->request and
	 * calls the corresponding rendering method to display the appropriate
	 * content.
	 * 
	 * @var string $this->request Determines the step to load. Valid values are:
	 *   - '': Just header, basic body, footer and the necessary scripts and stylesheets to load.
	 *   - 'occupation': the page where the user selects their occupation
	 *   - 'occupationSkills': the page where the user selects their occupation-related skills
	 *   - 'skills': the page where the user selects their non-occupation-related skills
	 *   - 'currentLevel': the page where the user selects their current skill level
	 *   - 'levelGoal': the page where the user selects their goal skill level
	 *
	 * @return void
	 */
	function render() {
		// Set the Content-Type header to specify that we're outputting HTML.
		header("Content-Type: text/html; charset=UTF-8");

		// Determine which step the user is on and call the appropriate rendering method.
		switch ($this->request) {
			case '':
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
				JSONResponse::error404();
				break;
		}
	}
};
