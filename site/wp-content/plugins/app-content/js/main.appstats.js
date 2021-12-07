
jQuery(() => {
    loadFraseDelGiorno()
    loadCuriositaDelGiorno()
    loadSondaggioDelGiorno()
    loadStatUtenti();
})

function loadStatUtenti() {
    var db = firebase.firestore();
    db.collection("Utenti").where('commerciale3PartiAccettato', '==', true).get()
        .then(ref => jQuery("#totale_utenti_comm").html(ref.size));

    db.collection("Utenti").get().then((querySnapshot) => {
        jQuery("#totale_utenti").html(querySnapshot.size);
    });
}

function loadFraseDelGiorno() {
    var db = firebase.firestore();
    db.collection("Oggi").doc("1FraseDelGiorno").get().then((doc) => {
        
        jQuery("#frase-giorno").html(doc.data().frase)
        jQuery("#autore-frase-giorno").html(doc.data().autore)
        jQuery("#conteggio-like-fg").html(doc.data().voti.like.length)
        jQuery("#conteggio-dislike-fg").html(doc.data().voti.dislike.length)
    });
}

function loadCuriositaDelGiorno() {
    var db = firebase.firestore();
    db.collection("Oggi").doc("3CuriositaDelGiorno").get().then((doc) => {
        
        jQuery("#curiosita-giorno").html(doc.data().curiosita)
        jQuery("#conteggio-like-cg").html(doc.data().voti.like.length)
        jQuery("#conteggio-dislike-cg").html(doc.data().voti.dislike.length)
    });
}

function loadSondaggioDelGiorno() {
    var db = firebase.firestore();
    db.collection("Oggi").doc("4SondaggioDelGiorno").get().then((doc) => {
        jQuery("#domanda-sondaggio").html(doc.data().domanda)

        doc.data().risposte.forEach((el, i) => {
            var row = jQuery(".risposta-template").clone();
            jQuery(row).children("#risposta-giorno").html(doc.data().risposte[i])
            jQuery(row).children("#risposta-voti").html(doc.data().voti[i].length)
            jQuery(".risposte-sondaggio").append(row);
            jQuery(row).removeClass("risposta-template")
            jQuery(row).show();
        })
    });
}