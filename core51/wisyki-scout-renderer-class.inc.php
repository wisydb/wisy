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
			<?php
			echo $this->framework->getFaviconTags() . $this->framework->getCSSTags();
			?>
			<link rel="stylesheet" href="<?php echo $wisyCore ?>/wisyki/css/scout.css">
			<?php
			if ($additional_css) {
				foreach ($additional_css as $cssfile) {
					echo ("<link rel='stylesheet' href='$cssfile'>");
				}
			}
			echo $this->framework->getJSHeadTags();
			?>
			<title><?php echo $pageTitle ?></title>
		</head>

		<body>
			<script src="<?php echo $wisyCore ?>/wisyki/js/scout.js"></script>
			<script src="<?php echo $wisyCore ?>/wisyki/js/main.js"></script>
			<?php
			if ($additional_js) {
				foreach ($additional_js as $jsfile) {
					echo ('<script src="' . $jsfile . '"></script>');
				}
			}
			?>
			<script src="https://unpkg.com/mustache@latest"></script>
			<nav>
				<a href="/index.php" class="img-link"><img class="portal-logo" src="<?php echo ($logo) ?>" alt="Kursportal Schleswig Holstein" height="60px" width="220px"></a>
				<div class="action-bar">
					<a class="bookmarks-btn labeled-icon-btn" href="/search?q=Fav:"><i class="icon bookmarks-icon"></i>Merkliste<span class="bubble display-none"></span></a>
					<a class="login-btn labeled-icon-btn" href="#"><i class="icon account-icon"></i>Login</a>
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

			<main></main>

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

			<footer class="bottom-nav button-list">
				<button id="prev-step" class="hidden disabled btn-primary labeled-icon-btn" title="Go to previous step"><i class="icon arrow-icon"></i></button>
				<button id="next-step" class="hidden disabled btn-primary labeled-icon-btn" title="Go to next step"><i class="icon arrow-icon"></i></button>
			</footer>
		</body>

		</html>

<?php
	}

	/**
	 * Renders the HTML for the Scout.
	 *
	 * @return void
	 */
	function render() {
		// Set the Content-Type header to specify that we're outputting HTML.
		header("Content-Type: text/html; charset=UTF-8");

		$this->renderMain();
	}
}