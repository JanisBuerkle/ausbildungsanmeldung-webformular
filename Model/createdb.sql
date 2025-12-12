CREATE database IF NOT EXISTS ausbildungs_anmeldung;

CREATE TABLE ausbildungs_anmeldung.ausbildungsberufe (
    beruf_id INT AUTO_INCREMENT PRIMARY KEY,
    beruf_name VARCHAR(150) NOT NULL
);

INSERT INTO ausbildungs_anmeldung.ausbildungsberufe (beruf_name) VALUES
('Elektroniker/in Energie- und Gebäudetechnik'),
('Fachinformatiker/in Anwendungsentwicklung'),
('Fachinformatiker/in Systemintegration'),
('Mechatroniker/in');


CREATE TABLE ausbildungs_anmeldung.betriebe (
    betrieb_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL
);


CREATE TABLE ausbildungs_anmeldung.ausbilder (
    ausbilder_id INT AUTO_INCREMENT PRIMARY KEY,
    betrieb_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    geschlecht ENUM('m','w','d') NOT NULL,
    email VARCHAR(255) NOT NULL,
    telefon VARCHAR(50) NOT NULL,
    FOREIGN KEY (betrieb_id) REFERENCES betriebe(betrieb_id)
);

CREATE TABLE ausbildungs_anmeldung.auszubildende (
    azubi_id INT AUTO_INCREMENT PRIMARY KEY,
    beruf_id INT NOT NULL,
    betrieb_id INT NOT NULL,
    ausbilder_id INT NOT NULL,

    familienname VARCHAR(150) NOT NULL,
    vorname VARCHAR(150) NOT NULL,
    war_bereits_bsz BOOLEAN NOT NULL,
    geburtsdatum DATE NOT NULL,

    ausbildungsbeginn DATE NOT NULL,
    ausbildungsende DATE NOT NULL,
    ausbildungsdauer VARCHAR(50) NOT NULL,
    selbe_klasse BOOLEAN NOT NULL,

    FOREIGN KEY (beruf_id) REFERENCES ausbildungsberufe(beruf_id),
    FOREIGN KEY (betrieb_id) REFERENCES betriebe(betrieb_id),
    FOREIGN KEY (ausbilder_id) REFERENCES ausbilder(ausbilder_id)
);


CREATE TABLE ausbildungs_anmeldung.dokumente (
    dokument_id INT AUTO_INCREMENT PRIMARY KEY,
    azubi_id INT NOT NULL,

    kammer ENUM(
        'Ludwigsburg',
        'Heilbronn',
        'Stuttgart',
        'Rems-Murr',
        'Nordschwarzwald',
        'Sonstige Kammer'
    ) NULL,

    weitere_infos TEXT NULL,

    dateipfad VARCHAR(500) NULL,
    FOREIGN KEY (azubi_id) REFERENCES auszubildende(azubi_id)
);

CREATE TABLE ausbildungs_anmeldung.eingaben_meta (
    meta_id INT AUTO_INCREMENT PRIMARY KEY,
    azubi_id INT NOT NULL,
    email_bestaetigung VARCHAR(255) NOT NULL,
    bestaetigungslink VARCHAR(500) NOT NULL,
    datenschutz_zustimmung BOOLEAN NOT NULL,
    FOREIGN KEY (azubi_id) REFERENCES auszubildende(azubi_id)
);