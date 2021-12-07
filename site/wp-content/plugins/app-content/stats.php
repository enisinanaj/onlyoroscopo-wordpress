<?php

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

add_action('admin_menu', 'add_stats_plugin_menu');
 
function add_stats_plugin_menu(){
	add_menu_page( 'App Stats', 'App Stats', 'manage_options', 'app-stats', 'app_stats_init' );
	wp_enqueue_style('jqueryuicss', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css', false, '1.1', 'all');
	wp_enqueue_script('jqueryui', 'https://code.jquery.com/ui/1.12.1/jquery-ui.js', false, '1.2', 'all');
	wp_enqueue_script( 'app-stats', plugin_dir_url( __FILE__ ) . 'js/main.appstats.js', array("jqueryui"), '1.10.15', 'all');
}


function app_stats_init() {
	?>

	<div class="wrap">

		<h1>Statistiche App</h1>

		<div class="">

            <h2>Frase del giorno</h2>
            <table class="wp-list-table widefat fixed striped pages">
                <thead>
                    <tr>
                        <td>Frase</td>
                        <td>Autore</td>
                        <td>Like</td>
                        <td>Dislike</td>
                    </tr>
                </thead>
            
                <tbody>
                    <tr>
                        <td id="frase-giorno">Frase</td>
                        <td id="autore-frase-giorno">Autore</td>
                        <td id="conteggio-like-fg">Like</td>
                        <td id="conteggio-dislike-fg">Dislike</td>
                    </tr>
                </tbody>
            </table>

		</div>

        <div class="">

            <h2>Curiosità del giorno</h2>
            <table class="wp-list-table widefat fixed striped pages">
                <thead>
                    <tr>
                        <td>Curiosità</td>
                        <td>Like</td>
                        <td>Dislike</td>
                    </tr>
                </thead>
            
                <tbody>
                    <tr>
                        <td id="curiosita-giorno">Frase</td>
                        <td id="conteggio-like-cg">Like</td>
                        <td id="conteggio-dislike-cg">Dislike</td>
                    </tr>
                </tbody>
            </table>

		</div>

        <div class="">

            <h2>Sondaggio del giorno</h2>
            <strong id="domanda-sondaggio"></strong>
            <table class="wp-list-table widefat fixed striped pages">
                <thead>
                    <tr>
                        <td>Risposta</td>
                        <td># Voti</td>
                    </tr>
                </thead>

                <tbody class="risposte-sondaggio">
                    <tr class="risposta-template" style="display: none">
                        <td id="risposta-giorno">Frase</td>
                        <td id="risposta-voti"></td>
                    </tr>
                </tbody>
            </table>

        </div>
       
        <div class="">

            <h2>Utenti</h2>
            <span>Totale utenti: <strong id="totale_utenti"></strong></span>
            <br />
            <span>Totale utenti con "commerciale 3 parti" attivo: <strong id="totale_utenti_comm"></strong></span>
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