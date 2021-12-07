
jQuery(() => {

    jQuery(".accordion").accordion({
        active: false,
        collapsible: true,
        heightStyle: "content"
    })

    jQuery(".classifiche").accordion({
        active: false,
        collapsible: true,
        header: "h4",
        heightStyle: "content"
    })

    jQuery(".oggi-accordion").accordion({
        active: false,
        collapsible: true,
        header: "h4",
        heightStyle: "content"
    })


    loadConsensi()
    handleSalvaConsensiAction()

    loadBiscottiDellaFortuna()
    handleSalvaFrasiBiscotto()

    loadClassifiche()
    handleSalvaClassifiche()

    loadNumeriOggi()
    handleSalvaNumeriOggi()

    loadCuriositaGiorno()
    handleSalvaCuriositaGiorno()

    loadFraseGiorno()
    handleSalvaFraseGiorno()

    loadSondaggioGiorno()
    handleSalvaSondaggioGiorno()
})

function loadConsensi() {
    var db = firebase.firestore();
    db.collection("TestiDomandeRegistrazione").get().then((querySnapshot) => {
        querySnapshot.forEach((doc) => {
            jQuery("#" + doc.id).val(doc.data().testo)
            console.log(`${doc.id} => ${doc.data().testo}`);
        });
    });
}

function handleSalvaConsensiAction() {
    jQuery("#salva-consensi").on('click', e => {

        var db = firebase.firestore();
        var collectionRef = db.collection("TestiDomandeRegistrazione")
        
        collectionRef.doc("1Privacy").set({
            testo: jQuery("#1Privacy").val()
        }).then(() => {
            console.log("Document 1Privacy successfully written!")
        })

        collectionRef.doc("2Termini").set({
            testo: jQuery("#2Termini").val()
        }).then(() => {
            console.log("Document 2Termini successfully written!")
        })

        collectionRef.doc("3CommercialeNostro").set({
            testo: jQuery("#3CommercialeNostro").val()
        }).then(() => {
            console.log("Document 3CommercialeNostro successfully written!")
        })

        collectionRef.doc("4Commerciale3Parti").set({
            testo: jQuery("#4Commerciale3Parti").val()
        }).then(() => {
            console.log("Document 4Commerciale3Parti successfully written!")
        })

    })
}

function loadBiscottiDellaFortuna() {
    var db = firebase.firestore();
    db.collection("BiscottoDellaFortuna").doc("ListaBiscotti").get().then((querySnapshot) => {
        var tutteLeFrasi = "";
        querySnapshot.data().frasi.forEach((doc) => {
            tutteLeFrasi += doc + "\n";
        });

        jQuery("#frasi-biscotto").val(tutteLeFrasi)
    });
}

function handleSalvaFrasiBiscotto() {
    jQuery("#salva-frasi-biscotto").on("click", () => {
        var tutteLeFrasi = jQuery("#frasi-biscotto").val().split("\n");
        var db = firebase.firestore();
        db.collection("BiscottoDellaFortuna").doc("ListaBiscotti").set({
            frasi: tutteLeFrasi.filter(el => el != "")
        }).then(() => console.log("Frasi salvate su firebase"))
    })
}

function loadClassifiche() {
    var db = firebase.firestore();
    db.collection("Classifiche").doc("1Sexy").get().then((querySnapshot) => {
        var segniSexy = "";
        querySnapshot.data().classifica.forEach((doc) => {
            segniSexy += doc + "\n";
        });

        jQuery("#segni-sexy").val(segniSexy)
    });

    db.collection("Classifiche").doc("2Ansiosi").get().then((querySnapshot) => {
        var segniAnsiosi = "";
        querySnapshot.data().classifica.forEach((doc) => {
            segniAnsiosi += doc + "\n";
        });

        jQuery("#segni-ansiosi").val(segniAnsiosi)
    });

    db.collection("Classifiche").doc("3Pigri").get().then((querySnapshot) => {
        var segniPigri = "";
        querySnapshot.data().classifica.forEach((doc) => {
            segniPigri += doc + "\n";
        });

        jQuery("#segni-pigri").val(segniPigri)
    });

    db.collection("Classifiche").doc("4Simpatici").get().then((querySnapshot) => {
        var segniSimpatici = "";
        querySnapshot.data().classifica.forEach((doc) => {
            segniSimpatici += doc + "\n";
        });

        jQuery("#segni-simpatici").val(segniSimpatici)
    });

    db.collection("Classifiche").doc("5Teneri").get().then((querySnapshot) => {
        var segniTeneri = "";
        querySnapshot.data().classifica.forEach((doc) => {
            segniTeneri += doc + "\n";
        });

        jQuery("#segni-teneri").val(segniTeneri)
    });
}

function handleSalvaClassifiche() {
    jQuery("#salva-segni-sexy").on("click", () => {
        var classifica = jQuery("#segni-sexy").val().split("\n");
        var db = firebase.firestore();
        db.collection("Classifiche").doc("1Sexy").set({
            classifica: classifica.filter(el => el != ""),
            titolo: "Sexy"
        }).then(() => console.log("Classifica segni sexy salvata su firebase"))
    })

    jQuery("#salva-segni-ansiosi").on("click", () => {
        var classifica = jQuery("#segni-ansiosi").val().split("\n");
        var db = firebase.firestore();
        db.collection("Classifiche").doc("2Ansiosi").set({
            classifica: classifica.filter(el => el != ""),
            titolo: "Ansiosi"
        }).then(() => console.log("Classifica segni ansiosi salvata su firebase"))
    })

    jQuery("#salva-segni-pigri").on("click", () => {
        var classifica = jQuery("#segni-pigri").val().split("\n");
        var db = firebase.firestore();
        db.collection("Classifiche").doc("3Pigri").set({
            classifica: classifica.filter(el => el != ""),
            titolo: "Pigri"
        }).then(() => console.log("Classifica segni pigri salvata su firebase"))
    })

    jQuery("#salva-segni-simpatici").on("click", () => {
        var classifica = jQuery("#segni-simpatici").val().split("\n");
        var db = firebase.firestore();
        db.collection("Classifiche").doc("4Simpatici").set({
            classifica: classifica.filter(el => el != ""),
            titolo: "Simpatici"
        }).then(() => console.log("Classifica segni simpatici salvata su firebase"))
    })

    jQuery("#salva-segni-teneri").on("click", () => {
        var classifica = jQuery("#segni-teneri").val().split("\n");
        var db = firebase.firestore();
        db.collection("Classifiche").doc("5Teneri").set({
            classifica: classifica.filter(el => el != ""),
            titolo: "Teneri"
        }).then(() => console.log("Classifica segni teneri salvata su firebase"))
    })
}

function loadNumeriOggi() {
    var db = firebase.firestore()
    db.collection("Oggi").doc("2NumeriDelGiorno").get().then((querySnapshot) => {
        var index = 0;
        querySnapshot.data().numeri.forEach((doc) => {
            jQuery(jQuery(".numeri-giorno")[index]).val(doc)
            index++;
        });
    });
}

function handleSalvaNumeriOggi() {
    jQuery("#salva-numeri-oggi").on('click', () => {
        var db = firebase.firestore()
        var numeri = [];
        [0,1,2,3,4,5].forEach(el => {
            numeri.push(jQuery(jQuery(".numeri-giorno")[el]).val())
        })
    
    
        db.collection("Oggi").doc("2NumeriDelGiorno").set({
            numeri
        })
        .then(() => {
            console.log("Salvati i numeri del giorno!")
        });
    })
}

function loadCuriositaGiorno() {
    var db = firebase.firestore();

    db.collection("Oggi").doc("3CuriositaDelGiorno").get().then(doc => {
        jQuery("#curiosita-giorno").val(doc.data().curiosita)
    })
}

function handleSalvaCuriositaGiorno() {
    jQuery("#salva-curiosita-giorno").on("click", () => {
        var db = firebase.firestore()

        db.collection("Oggi").doc("3CuriositaDelGiorno").set({
            curiosita: jQuery("#curiosita-giorno").val(),
            voti: {}
        })
        .then(_ => {
            console.log("Curiosità del giorno salvata correttamente")
        })
    })
}

function loadFraseGiorno() {
    var db = firebase.firestore();

    db.collection("Oggi").doc("1FraseDelGiorno").get().then(doc => {
        jQuery("#frase-giorno").val(doc.data().frase)
        jQuery("#autore-frase-oggi").val(doc.data().autore)
    })
}

function handleSalvaFraseGiorno() {
    jQuery("#salva-frase-giorno").on("click", () => {
        var db = firebase.firestore()

        db.collection("Oggi").doc("1FraseDelGiorno").set({
            frase: jQuery("#frase-giorno").val(),
            autore: jQuery("#autore-frase-oggi").val(),
            voti: {}
        })
        .then(_ => {
            console.log("Frase del giorno salvata correttamente")
        })
    })
}

function loadSondaggioGiorno() {
    var db = firebase.firestore();

    db.collection("Oggi").doc("4SondaggioDelGiorno").get().then(doc => {
        jQuery("#domanda-sondaggio-giorno").val(doc.data().domanda)

        jQuery(jQuery(".risposta-sondaggio-giorno")[0]).val(doc.data().risposte[0])
        jQuery(jQuery(".risposta-sondaggio-giorno")[1]).val(doc.data().risposte[1])
        jQuery(jQuery(".risposta-sondaggio-giorno")[2]).val(doc.data().risposte[2])
        jQuery(jQuery(".risposta-sondaggio-giorno")[3]).val(doc.data().risposte[3])
    })
}

function handleSalvaSondaggioGiorno() {
    jQuery("#salva-sondaggio-giorno").on("click", () => {
        var db = firebase.firestore()
        var risposte = []

        risposte.push(jQuery(jQuery(".risposta-sondaggio-giorno")[0]).val())
        risposte.push(jQuery(jQuery(".risposta-sondaggio-giorno")[1]).val())
        if (jQuery(jQuery(".risposta-sondaggio-giorno")[2]).val()) {
            risposte.push(jQuery(jQuery(".risposta-sondaggio-giorno")[2]).val())
        }

        if (jQuery(jQuery(".risposta-sondaggio-giorno")[3]).val()) {
            risposte.push(jQuery(jQuery(".risposta-sondaggio-giorno")[3]).val())
        }

        db.collection("Oggi").doc("4SondaggioDelGiorno").set({
            domanda: jQuery("#domanda-sondaggio-giorno").val(),
            risposte: risposte,
            voti: {}
        })
        .then(_ => {
            console.log("Sondaggio del giorno salvata correttamente")
        })
        .catch(e => {
            console.error(e)
        })
    })
}