<?php
header('Content-Type: application/json');

// ==================
// DB Verbindung
// ==================
$mysqli = new mysqli("localhost", "root", "", "ausbildungs_anmeldung");
if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "DB-Verbindung fehlgeschlagen"]);
    exit;
}

$mysqli->set_charset("utf8");

// ==================
// Hilfsfunktionen
// ==================
function abort($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(["error" => $msg]);
    exit;
}

function getOrCreateBetrieb(mysqli $db, string $name): int {
    $stmt = $db->prepare("SELECT betrieb_id FROM betriebe WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $stmt->bind_result($id);

    if ($stmt->fetch()) {
        $stmt->close();
        return $id;
    }
    $stmt->close();

    $stmt = $db->prepare("INSERT INTO betriebe (name) VALUES (?)");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();

    return $id;
}

function getOrCreateAusbilder(
    mysqli $db,
    int $betrieb_id,
    string $name,
    string $geschlecht,
    string $email,
    string $telefon
): int {

    $stmt = $db->prepare("
        SELECT ausbilder_id
        FROM ausbilder
        WHERE betrieb_id = ? AND email = ?
    ");
    $stmt->bind_param("is", $betrieb_id, $email);
    $stmt->execute();
    $stmt->bind_result($id);

    if ($stmt->fetch()) {
        $stmt->close();
        return $id;
    }
    $stmt->close();

    $stmt = $db->prepare("
        INSERT INTO ausbilder
        (betrieb_id, name, geschlecht, email, telefon)
        VALUES (?,?,?,?,?)
    ");
    $stmt->bind_param("issss", $betrieb_id, $name, $geschlecht, $email, $telefon);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();

    return $id;
}

// ==================
// Pflichtfelder
// ==================
$required = [
    'ausbildungsberuf',
    'ausbildungsbetrieb',
    'ausbildername',
    'ausbildergeschlecht',
    'ausbilderemail',
    'ausbilderemailwdh',
    'ausbildertelefon',
    'ausbildungsbeginn',
    'ausbildungsende',
    'ausbildungsdauer',
    'selbeklasse',
    'familienname',
    'vorname',
    'warbsz',
    'geburtsdatum',
    'bestaetigungslink',
    'bestaetigungslinkwdh',
    'zustimmung'
];

foreach ($required as $feld) {
    if (!isset($_POST[$feld]) || trim($_POST[$feld]) === '') {
        abort("Fehlendes Feld: $feld");
    }
}

// ==================
// E-Mail Wiederholung prüfen
// ==================
if ($_POST['ausbilderemail'] !== $_POST['ausbilderemailwdh']) {
    abort("Ausbilder-E-Mail stimmt nicht überein");
}

if ($_POST['bestaetigungslink'] !== $_POST['bestaetigungslinkwdh']) {
    abort("Bestätigungs-E-Mail stimmt nicht überein");
}

// ==================
// Mapping & Werte
// ==================
$berufMap = [
    'elektroniker' => 1,
    'fianwendung'  => 2,
    'fisystem'     => 3,
    'mechatroniker'=> 4
];

if (!isset($berufMap[$_POST['ausbildungsberuf']])) {
    abort("Ungültiger Ausbildungsberuf");
}

$beruf_id = $berufMap[$_POST['ausbildungsberuf']];

$betrieb_name  = trim($_POST['ausbildungsbetrieb']);
$ausbildername = trim($_POST['ausbildername']);
$email         = trim($_POST['ausbilderemail']);
$telefon       = trim($_POST['ausbildertelefon']);

$geschlechtMap = [
    'maennlich' => 'm',
    'weiblich'  => 'w',
    'divers'    => 'd'
];

if (!isset($geschlechtMap[$_POST['ausbildergeschlecht']])) {
    abort("Ungültiges Geschlecht");
}
$geschlecht = $geschlechtMap[$_POST['ausbildergeschlecht']];

$familienname = trim($_POST['familienname']);
$vorname      = trim($_POST['vorname']);

$war_bsz     = $_POST['warbsz'] === 'ja' ? 1 : 0;
$selbeKlasse = $_POST['selbeklasse'] === 'ja' ? 1 : 0;

$beginn = $_POST['ausbildungsbeginn'] . "-01";
$ende   = $_POST['ausbildungsende'] . "-01";

$weitere_infos = trim($_POST['wichtig'] ?? null);
$email_bestaetigung = trim($_POST['bestaetigungslink']);

// ==================
// TRANSAKTION
// ==================
$mysqli->begin_transaction();

try {

    // Betrieb
    $betrieb_id = getOrCreateBetrieb($mysqli, $betrieb_name);

    // Ausbilder
    $ausbilder_id = getOrCreateAusbilder(
        $mysqli,
        $betrieb_id,
        $ausbildername,
        $geschlecht,
        $email,
        $telefon
    );

    // Azubi
    $stmt = $mysqli->prepare("
        INSERT INTO auszubildende
        (beruf_id, betrieb_id, ausbilder_id,
         familienname, vorname, war_bereits_bsz, geburtsdatum,
         ausbildungsbeginn, ausbildungsende, ausbildungsdauer, selbe_klasse)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)
    ");

    $stmt->bind_param(
        "iiississsss",
        $beruf_id,
        $betrieb_id,
        $ausbilder_id,
        $familienname,
        $vorname,
        $war_bsz,
        $_POST['geburtsdatum'],
        $beginn,
        $ende,
        $_POST['ausbildungsdauer'],
        $selbeKlasse
    );

    $stmt->execute();
    $azubi_id = $stmt->insert_id;
    $stmt->close();

    // Dokumente (weitere Infos)
    if (!empty($weitere_infos)) {
        $stmt = $mysqli->prepare("
            INSERT INTO dokumente (azubi_id, weitere_infos)
            VALUES (?,?)
        ");
        $stmt->bind_param("is", $azubi_id, $weitere_infos);
        $stmt->execute();
        $stmt->close();
    }

    // Meta
    $stmt = $mysqli->prepare("
        INSERT INTO eingaben_meta
        (azubi_id, email_bestaetigung, datenschutz_zustimmung)
        VALUES (?,?,1)
    ");
    $stmt->bind_param("is", $azubi_id, $email_bestaetigung);
    $stmt->execute();
    $stmt->close();

    $mysqli->commit();

    echo json_encode([
        "success" => true,
        "azubi_id" => $azubi_id
    ]);

} catch (Exception $e) {
    $mysqli->rollback();
    http_response_code(500);
    echo json_encode(["error" => "Fehler beim Speichern"]);
}

$mysqli->close();

