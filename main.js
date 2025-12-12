const form = document.getElementById("anmeldeForm");
const submitBtn = document.getElementById("submitBtn");
const statusBox = document.getElementById("statusAusgabe");
const checkBtn = document.getElementById("checkBtn");

const felder = {
    ausbildungsberuf: document.getElementById("ausbildungsberuf"),
    ausbildungsbetrieb: document.getElementById("ausbildungsbetrieb"),
    ausbildername: document.getElementById("ausbildername"),
    ausbildergeschlecht: document.getElementById("ausbildergeschlecht"),
    ausbilderemail: document.getElementById("ausbilderemail"),
    zustimmung: document.getElementById("zustimmung")
};

function checkText(value) {
    return value.trim() !== "";
}

function checkEmail(value) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
}

function checkSelect(value) {
    return value !== "waehlen";
}

function validate() {
    let allesOk = true;

    if (!checkSelect(felder.ausbildungsberuf.value)) { felder.ausbildungsberuf.classList.add("error"); allesOk = false; } 
    else { felder.ausbildungsberuf.classList.remove("error"); }

    if (!checkText(felder.ausbildungsbetrieb.value)) { felder.ausbildungsbetrieb.classList.add("error"); allesOk = false; } 
    else { felder.ausbildungsbetrieb.classList.remove("error"); }

    if (!checkText(felder.ausbildername.value)) { felder.ausbildername.classList.add("error"); allesOk = false; } 
    else { felder.ausbildername.classList.remove("error"); }

    if (!checkSelect(felder.ausbildergeschlecht.value)) { felder.ausbildergeschlecht.classList.add("error"); allesOk = false; } 
    else { felder.ausbildergeschlecht.classList.remove("error"); }

    if (!checkEmail(felder.ausbilderemail.value)) { felder.ausbilderemail.classList.add("error"); allesOk = false; } 
    else { felder.ausbilderemail.classList.remove("error"); }

    submitBtn.disabled = !(allesOk && felder.zustimmung.checked);
    return allesOk;
}

checkBtn.addEventListener("click", () => {
    validate();
    let fehlermeldungen = [];

    if (!checkSelect(felder.ausbildungsberuf.value)) fehlermeldungen.push("Ausbildungsberuf auswählen");
    if (!checkText(felder.ausbildungsbetrieb.value)) fehlermeldungen.push("Ausbildungsbetrieb fehlt");
    if (!checkText(felder.ausbildername.value)) fehlermeldungen.push("Ausbildername fehlt");
    if (!checkSelect(felder.ausbildergeschlecht.value)) fehlermeldungen.push("Ausbildergeschlecht auswählen");
    if (!checkEmail(felder.ausbilderemail.value)) fehlermeldungen.push("Ausbilder E-Mail ungültig");
    if (!felder.zustimmung.checked) fehlermeldungen.push("Zustimmung zur Datenspeicherung fehlt");

    if (fehlermeldungen.length === 0) {
        statusBox.style.color = "green";
        statusBox.innerHTML = "Alles korrekt!";
    } else {
        statusBox.style.color = "red";
        statusBox.innerHTML = fehlermeldungen.join("<br>");
    }
});

Object.values(felder).forEach(feld => {
    if (feld.type === "checkbox") feld.addEventListener("change", validate);
    else feld.addEventListener("input", validate);
});
