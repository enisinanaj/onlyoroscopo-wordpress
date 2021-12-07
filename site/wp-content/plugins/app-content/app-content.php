<?php
/*
Plugin Name: OO App Content
Description: 
Donate link: 
Author: Eni Sinanaj
Author URI: https://digitalhawks.it
Version: 1.0.2
Requires at least: 4.1
Tested up to: 5.4
Requires PHP: 7.0
Domain Path: languages
Text Domain: app-content
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// Plugin constants
define( 'APP_CONTENT_VERSION', '1.5.6' );
define( 'APP_CONTENT_FOLDER', 'app-content' );

define( 'APP_CONTENT_URL', plugin_dir_url( __FILE__ ) );
define( 'APP_CONTENT_DIR', plugin_dir_path( __FILE__ ) );
define( 'APP_CONTENT_BASENAME', plugin_basename( __FILE__ ) );

//echo APP_CONTENT_DIR . 'stats.php';
include(APP_CONTENT_DIR . 'stats.php');

add_action( 'plugins_loaded', 'plugins_loaded_app_content_plugin' );
function plugins_loaded_app_content_plugin() {

	// here do stuff

}

add_action('admin_menu', 'add_plugin_menu');
 
function add_plugin_menu(){
	add_menu_page( 'App Content', 'App Content', 'manage_options', 'app-content', 'app_content_init' );
	wp_enqueue_style('jqueryuicss', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css', false, '1.1', 'all');
	wp_enqueue_script('jqueryui', 'https://code.jquery.com/ui/1.12.1/jquery-ui.js', false, '1.2', 'all');
	wp_enqueue_script( 'main-content', plugin_dir_url( __FILE__ ) . 'js/main.appcontent.js', array("jqueryui"), '1.10.30', 'all');
}


function app_content_init() {
	?>

	<div class="wrap">

		<h1>Contenuti App</h1>

		<div class="updated">
			<ul style="margin-left: 20px; list-style-type: disc !important;">
				<li><strong>Consensi GDPR</strong> - TestiDomandeRegistrazione</li>
				<li>BiscottoDellaFortuna - array di frasi</li>
				<li>La collection Classifiche contiene 5 documenti per le 5 diverse classifiche.
					<ul style="margin-left: 20px; list-style-type: disc !important;">
						<li>La classifica consiste in un array di 12 stringhe che identificano il segno (importante mettere il segno con iniziale maiuscola).</li>
					</ul>
				</li>
				<li>La collection Oggi è composta da 4 documenti.</li>
				<ul style="margin-left: 20px; list-style-type: disc !important;">
					<li>La frase del giorno
						<ul style="margin-left: 20px; list-style-type: disc !important;">
							<li>il campo autore</li>
							<li>il campo frase.</li>
							<li>un oggetto "voti". <strong>La votazione si sblocca quando cambia il campo frase</strong>.</li>
						</ul>
					</li>
					<li>la curiosità</li>
					<li>il sondaggio. Per il sondaggio c'è la domanda e un array di risposte, si possono inserire fino a un massimo di 4 risposte. Per sbloccare il sondaggio nell'app basta cambiare la domanda.</li>
					<li>Per i numeri del giorno infine c'è un array di 6 numeri. i primi tre vengono visualizzati sopra e gli ultimi 3 di sotto.</li>
				</ul>
			</ul>
		</div>


		<div class="accordion">

			<h3>Consensi GDPR</h3>
			<div>
				<form method="post">
					<table class="form-table">
						<tbody>
							<tr>
								<th><label for="1Privacy">Domanda Privacy Policy</label></th>
								<td><textarea style="width: 100%" id="1Privacy"></textarea></td>
							</tr>
							<tr>
								<th><label for="2Termini">Domanda Termini e Condizioni</label></th>
								<td><textarea style="width: 100%" id="2Termini"></textarea></td>
							</tr>
							<tr>
								<th><label for="3CommercialeNostro">Domanda "Comunicazioni di tipo commerciale da parte nostra"</label></th>
								<td><textarea style="width: 100%" id="3CommercialeNostro"></textarea></td>
							</tr>
							<tr>
								<th><label for="4Commerciale3Parti">Domanda "Comunicazioni di tipo commerciale da parte di terzi"</label></th>
								<td><textarea style="width: 100%" id="4Commerciale3Parti"></textarea></td>
							</tr>
						</tbody>
					</table>
					<p class="submit"><input type="button" value="Salva" class="button-primary" id="salva-consensi" name="Salva"></p>
				</form>
			</div>

			<h3>Biscotto della fortuna</h3>
			<div>
				<textarea style="width: 100%; height: 400px" id="frasi-biscotto"></textarea>
				<span><i>Scrivere una frase per riga.</i></span>
				<p class="submit"><input type="button" value="Salva" class="button-primary" id="salva-frasi-biscotto" name="Salva"></p>
			</div>

			<h3>Classifiche</h3>
			<div>
				<div class="classifiche">

					<h4>Sexy</h4>
					<div>
						<form method="post">
							<textarea style="width:30%; height: 200px" id="segni-sexy"></textarea>
							<div>
								<i>Scrivere un segno per riga. <strong>Nota: Il segno deve cominciare con la lettera maiuscola</strong></i>
							</div>
							<p class="submit"><input type="button" value="Salva" class="button-primary" id="salva-segni-sexy" name="Salva"></p>
						</form>
					</div>

					<h4>Ansiosi</h4>
					<div>
						<form method="post">
							<textarea style="width:30%; height: 200px" id="segni-ansiosi"></textarea>
							<div>
								<i>Scrivere un segno per riga. <strong>Nota: Il segno deve cominciare con la lettera maiuscola</strong></i>
							</div>
							<p class="submit"><input type="button" value="Salva" class="button-primary" id="salva-segni-ansiosi" name="Salva"></p>
						</form>
					</div>

					<h4>Pigri</h4>
					<div>
						<form method="post">
							<textarea style="width:30%; height: 200px" id="segni-pigri"></textarea>
							<div>
								<i>Scrivere un segno per riga. <strong>Nota: Il segno deve cominciare con la lettera maiuscola</strong></i>
							</div>
							<p class="submit"><input type="button" value="Salva" class="button-primary" id="salva-segni-pigri" name="Salva"></p>
						</form>
					</div>

					<h4>Simpatici</h4>
					<div>
						<form method="post">
							<textarea style="width:30%; height: 200px" id="segni-simpatici"></textarea>
							<div>
								<i>Scrivere un segno per riga. <strong>Nota: Il segno deve cominciare con la lettera maiuscola</strong></i>
							</div>
							<p class="submit"><input type="button" value="Salva" class="button-primary" id="salva-segni-simpatici" name="Salva"></p>
						</form>
					</div>

					<h4>Teneri</h4>
					<div>
						<form method="post">
							<textarea style="width:30%; height: 200px" id="segni-teneri"></textarea>
							<div>
								<i>Scrivere un segno per riga. <strong>Nota: Il segno deve cominciare con la lettera maiuscola</strong></i>
							</div>
							<p class="submit"><input type="button" value="Salva" class="button-primary" id="salva-segni-teneri" name="Salva"></p>
						</form>
					</div>

				</div>
			</div>

			<h3>Oggi</h3>
			<div>

				<div class="oggi-accordion">

					<h4>Frase del giorno</h4>
					<div>
						<table class="form-table">
							<tbody>
								<tr>
									<th><label for="autore-frase-oggi">Autore</label></th>
									<td><textarea style="width: 100%" id="autore-frase-oggi"></textarea></td>
								</tr>
							</tbody>
						</table>
						<textarea id="frase-giorno" style="width:100%"></textarea>
						<p class="submit"><input type="button" value="Salva" class="button-primary" id="salva-frase-giorno" name="Salva"></p>
					</div>

					<h4>Numeri del giorno</h4>
					<div>
						<div>
							<input type="number" class="numeri-giorno" style="width: 50px; height: 50px" name="number1" />
							<input type="number" class="numeri-giorno" style="width: 50px; height: 50px" name="number2" />
							<input type="number" class="numeri-giorno" style="width: 50px; height: 50px" name="number3" />
						</div>
						<div>
							<input type="number" class="numeri-giorno" style="width: 50px; height: 50px" name="number4" />
							<input type="number" class="numeri-giorno" style="width: 50px; height: 50px" name="number5" />
							<input type="number" class="numeri-giorno" style="width: 50px; height: 50px" name="number6" />
						</div>

						<p class="submit"><input type="button" value="Salva" class="button-primary" id="salva-numeri-oggi" name="Salva"></p>
					</div>

					<h4>Curiosità del giorno</h4>
					<div>
						<textarea id="curiosita-giorno" style="width:100%"></textarea>
						<p class="submit"><input type="button" value="Salva" class="button-primary" id="salva-curiosita-giorno" name="Salva"></p>
					</div>

					<h4>Sondaggio del giorno</h4>
					<div>
						<div><strong>Domanda</strong></div>
						<textarea id="domanda-sondaggio-giorno" style="width:100%"></textarea>
						
						<div><strong>Risposte</strong></div>
						<textarea class="risposta-sondaggio-giorno" style="width:100%"></textarea>
						<textarea class="risposta-sondaggio-giorno" style="width:100%"></textarea>
						<textarea class="risposta-sondaggio-giorno" style="width:100%"></textarea>
						<textarea class="risposta-sondaggio-giorno" style="width:100%"></textarea>

						<p class="submit"><input type="button" value="Salva" class="button-primary" id="salva-sondaggio-giorno" name="Salva"></p>
					</div>

				</div>

			</div>

		</div>

	</div>

	<!-- The core Firebase JS SDK is always required and must be listed first -->
	<script src="https://www.gstatic.com/firebasejs/7.14.1/firebase-app.js"></script>
	<script src="https://www.gstatic.com/firebasejs/7.14.1/firebase-firestore.js"></script>

	<!-- TODO: Add SDKs for Firebase products that you want to use
		https://firebase.google.com/docs/web/setup#available-libraries -->
	<script src="https://www.gstatic.com/firebasejs/7.14.1/firebase-analytics.js"></script>

	<script>
		// Your web app's Firebase configuration
		var firebaseConfig = {
			apiKey: "AIzaSyB6_dBY6sm0MmN-6sCIp_AM4Hwxd-gxSUc",
			authDomain: "onlyoroscopo-app.firebaseapp.com",
			databaseURL: "https://onlyoroscopo-app.firebaseio.com",
			projectId: "onlyoroscopo-app",
			storageBucket: "onlyoroscopo-app.appspot.com",
			messagingSenderId: "487324420819",
			appId: "1:487324420819:web:ec5a9e6454d8a37f19a226",
			measurementId: "G-EJ0C8S17YF"
		};
		// Initialize Firebase
		firebase.initializeApp(firebaseConfig);
		firebase.analytics();
	</script>

	<?php
}