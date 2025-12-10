const felder = {
    vorname: document.getElementById("vorname"),
    nachname: document.getElementById("nachname"),
    email: document.getElementById("email"),
    plz: document.getElementById("plz"),
    zustimmung: document.getElementById("zustimmung")
};

const submitBtn = document.getElementById("submitBtn");
const statusBox = document.getElementById("statusAusgabe");

// Validierungsfunktionen
function checkName(v) {
    return /^[A-Za-zÄÖÜäöüß\s-]+$/.test(v.trim());
}

function checkEmail(v) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
}

function checkPLZ(v) {
    return /^[0-9]{5}$/.test(v);
}

// Prüfen aller Felder
function validate() {
    let allesOk = true;

    // Vorname
    if (!checkName(felder.vorname.value)) {
        markError(felder.vorname);
        allesOk = false;
    } else unmarkError(felder.vorname);

    // Nachname
    if (!checkName(felder.nachname.value)) {
        markError(felder.nachname);
        allesOk = false;
    } else unmarkError(felder.nachname);

    // Email
    if (!checkEmail(felder.email.value)) {
        markError(felder.email);
        allesOk = false;
    } else unmarkError(felder.email);

    // PLZ
    if (!checkPLZ(felder.plz.value)) {
        markError(felder.plz);
        allesOk = false;
    } else unmarkError(felder.plz);

    // Absenden erlauben?
    submitBtn.disabled = !(allesOk && felder.zustimmung.checked);
}

// Fehler markieren
function markError(el) {
    el.classList.add("error");
}

function unmarkError(el) {
    el.classList.remove("error");
}

// „Was fehlt noch?“ Button
document.getElementById("checkBtn").addEventListener("click", () => {
    validate();

    let fehlermeldungen = [];

    if (!checkName(felder.vorname.value)) fehlermeldungen.push("Vorname ungültig");
    if (!checkName(felder.nachname.value)) fehlermeldungen.push("Nachname ungültig");
    if (!checkEmail(felder.email.value)) fehlermeldungen.push("E-Mail ungültig");
    if (!checkPLZ(felder.plz.value)) fehlermeldungen.push("PLZ ungültig");
    if (!felder.zustimmung.checked) fehlermeldungen.push("Zustimmung fehlt");

    if (fehlermeldungen.length === 0) {
        statusBox.style.color = "green";
        statusBox.innerHTML = "Alles korrekt!";
    } else {
        statusBox.style.color = "red";
        statusBox.innerHTML = fehlermeldungen.join("<br>");
    }
});

// Live-Validierung
Object.values(felder).forEach(feld => {
    if (feld.type !== "checkbox") {
        feld.addEventListener("input", validate);
    } else {
        feld.addEventListener("change", validate);
    }
});
