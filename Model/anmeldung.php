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
// Pflichtfelder prüfen
// ==================
$required = [
    'ausbildungsberuf',
    'ausbildungsbetrieb',
    'ausbildername',
    'ausbildergeschlecht',
    'ausbilderemail',
    'ausbildertelefon',
    'familienname',
    'vorname',
    'geburtsdatum',
    'ausbildungsbeginn',
    'ausbildungsende',
    'ausbildungsdauer',
    'warbsz',
    'selbeklasse'
];

foreach ($required as $feld) {
    if (!isset($_POST[$feld]) || trim($_POST[$feld]) === '') {
        abort("Fehlendes Feld: $feld");
    }
}

// ==================
// Werte aufbereiten
// ==================
$berufMapping = [
    'elektroniker' => 1,
    'fianwendung'  => 2,
    'fisystem'     => 3,
    'mechatroniker'=> 4
];

if (!isset($berufMapping[$_POST['ausbildungsberuf']])) {
    abort("Ungültiger Ausbildungsberuf");
}
$beruf_id = $berufMapping[$_POST['ausbildungsberuf']];

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

// Month → DATE
$beginn = $_POST['ausbildungsbeginn'] . "-01";
$ende   = $_POST['ausbildungsende'] . "-01";

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

    // Meta
    $bestaetigungslink = bin2hex(random_bytes(32));

    $stmt = $mysqli->prepare("
        INSERT INTO eingaben_meta
        (azubi_id, email_bestaetigung, bestaetigungslink, datenschutz_zustimmung)
        VALUES (?,?,?,1)
    ");
    $stmt->bind_param("iss", $azubi_id, $email, $bestaetigungslink);
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
    echo json_encode(["error" => "Speichern fehlgeschlagen"]);
}

$mysqli->close();
