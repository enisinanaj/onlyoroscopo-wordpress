<?php
if (! defined('WP_DEBUG')) {
	die( 'Direct access forbidden.' );
}
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
});

// Funzione che crea uno shortcode utile per aggiungere in modo automatico l'immagine scelta per amore con url combinato
function img_amore_affinity($atts) { 
	$custom_field = get_field("amore");
	return '<div class="img_affinity"><a href="https://onlyoroscopo.com/'. $custom_field .'"> 
	<img src="https://onlyoroscopo.com/wp-content/uploads/2020/12/'. $custom_field .'.png"
	alt="immagine_affinità">
	<p> '. $custom_field .' </p>
	</a></div>';
} 
add_shortcode('amore', 'img_amore_affinity'); 

// Funzione che crea uno shortcode utile per aggiungere in modo automatico l'immagine scelta per lavoro con url combionat
function img_lavoro_affinity($atts) { 
	$custom_field = get_field("lavoro");
	return '<div class="img_affinity"><a href="https://onlyoroscopo.com/'. $custom_field .'"> 
	<img src="https://onlyoroscopo.com/wp-content/uploads/2020/12/'. $custom_field .'.png"
	alt="immagine_affinità">
	<p> '. $custom_field .' </p>
	</a></div>';
} 
add_shortcode('lavoro', 'img_lavoro_affinity'); 

// Funzione che crea uno shortcode utile per aggiungere in modo automatico l'immagine scelta per amicizia con url combionat
function img_amicizia_affinity($atts) { 
	$custom_field = get_field("amicizia");
	return '<div class="img_affinity"><a href="https://onlyoroscopo.com/'. $custom_field .'"> 
	<img src="https://onlyoroscopo.com/wp-content/uploads/2020/12/'. $custom_field .'.png"
	alt="immagine_affinità">
	<p> '. $custom_field .' </p>
	</a></div>';
} 
add_shortcode('amicizia', 'img_amicizia_affinity'); 

// Funzione per inserire cose nell'head
//    <?php
function hook_head() {
    ?>

        <!-- Global site tag (gtag.js) - Google Analytics -->
		<script async src="https://www.googletagmanager.com/gtag/js?id=UA-125169828-1"></script>
		<script>
		  window.dataLayer = window.dataLayer || [];
		  function gtag(){dataLayer.push(arguments);}
		  gtag('js', new Date());

		  gtag('config', 'UA-125169828-1');
		</script>

        <!-- Iubenda -->
		<script type="text/javascript">var _iub = _iub || {}; _iub.cons_instructions = _iub.cons_instructions || []; _iub.cons_instructions.push(["init", {api_key: "hl5CqC4jyN1AdfkYcGvDZfnRhwI97CKw"}]);</script><script type="text/javascript" src="https://cdn.iubenda.com/cons/iubenda_cons.js" async></script>

		<script src="https://track.eadv.it/onlyoroscopo.php" async=""></script>
<script type="text/javascript" src="https://track.eadv.it/contact-card.js" async defer id="eadv-contact-card"></script>

    <?php
}
add_action('wp_head', 'hook_head');
