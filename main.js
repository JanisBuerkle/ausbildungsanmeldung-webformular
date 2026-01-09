document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("anmeldeForm");
  const checkBtn = document.getElementById("checkBtn");
  const submitBtn = document.getElementById("submitBtn");
  
  const requiredIds = [
    "ausbildungsberuf",
    "ausbildungsbetrieb",
    "ausbildername",
    "ausbildergeschlecht",
    "ausbilderemail",
    "ausbilderemailwdh",
    "ausbildertelefon",
    "ausbildungsbeginn",
    "ausbildungsende",
    "ausbildungsdauer",
    "familienname",
    "vorname",
    "warbsz",
    "geburtsdatum",
    "bestaetigungslink",
    "bestaetigungslinkwdh",
    "zustimmung",
  ];

  const requiredFields = requiredIds
    .map((id) => document.getElementById(id))
    .filter(Boolean);

  function getLabelTextFor(el) {
    if (!el || !el.id) return el?.name || "Unbekanntes Feld";
    const label = form.querySelector(`label[for="${el.id}"]`);
    if (!label) return el.name || el.id;
    return label.textContent.replace("*", "").trim();
  }

  function setInvalid(el) {
    if (!el) return;
    el.classList.add("error");
    el.style.borderColor = "red";
    el.style.backgroundColor = "";
  }

  function setValid(el) {
    if (!el) return;
    el.classList.remove("error");
    el.style.borderColor = "#28a745";
    el.style.backgroundColor = "";
  }

  function clearState(el) {
    if (!el) return;
    el.classList.remove("error");
    el.style.borderColor = "";
    el.style.backgroundColor = "";
  }

  function isEmptyValue(el) {
    if (!el) return true;

    if (el.type === "checkbox") {
      return !el.checked;
    }

    return el.value.trim() === "";
  }

  function isValidEmailFormat(value) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
  }

  function validateField(el) {
    if (!el) return { ok: true, message: "" };

    if (el.type === "checkbox") {
      if (!el.checked) {
        setInvalid(el);
        return { ok: false, message: "Zustimmung zur Speicherung fehlt." };
      }
      setValid(el);
      return { ok: true, message: "" };
    }

    if (isEmptyValue(el)) {
      setInvalid(el);
      return { ok: false, message: `${getLabelTextFor(el)} ist nicht ausgefüllt.` };
    }

    if (el.type === "email") {
      const v = el.value.trim();
      if (!isValidEmailFormat(v)) {
        setInvalid(el);
        return { ok: false, message: `${getLabelTextFor(el)} ist keine gültige E-Mail-Adresse.` };
      }
    }

    if (el.id === "ausbilderemailwdh") {
      const a = document.getElementById("ausbilderemail")?.value.trim() || "";
      const b = el.value.trim();
      if (a !== b) {
        setInvalid(el);
        setInvalid(document.getElementById("ausbilderemail"));
        return { ok: false, message: "Ausbilder E-Mail und Wiederholung stimmen nicht überein." };
      }
    }

    if (el.id === "bestaetigungslinkwdh") {
      const a = document.getElementById("bestaetigungslink")?.value.trim() || "";
      const b = el.value.trim();
      if (a !== b) {
        setInvalid(el);
        setInvalid(document.getElementById("bestaetigungslink"));
        return { ok: false, message: "E-Mail Bestätigungslink und Wiederholung stimmen nicht überein." };
      }
    }

    setValid(el);
    return { ok: true, message: "" };
  }

  function validateAll() {
    const errors = [];

    requiredFields.forEach((el) => clearState(el));

    requiredFields.forEach((el) => {
      const res = validateField(el);
      if (!res.ok && res.message) errors.push(res.message);
    });

    return errors;
  }

  function updateSubmitState() {
    const errors = validateAll();
    submitBtn.disabled = errors.length > 0;
  }

  requiredFields.forEach((el) => {
    const eventName =
      el.tagName.toLowerCase() === "select" || el.type === "checkbox" ? "change" : "input";

    el.addEventListener(eventName, updateSubmitState);

    if (el.type === "email") {
      el.addEventListener("change", updateSubmitState);
    }
  });

  checkBtn.addEventListener("click", () => {
    const errors = validateAll();

    if (errors.length === 0) {
      alert("✅ Alles ausgefüllt. Du kannst jetzt absenden.");
      submitBtn.disabled = false;
      return;
    }

    const firstInvalid = requiredFields.find((el) => el.classList.contains("error"));
    if (firstInvalid) {
      firstInvalid.scrollIntoView({ behavior: "smooth", block: "center" });
      firstInvalid.focus?.();
    }

    alert(
      "❌ Es fehlt noch etwas:\n\n- " + errors.join("\n- ")
    );

    submitBtn.disabled = true;
  });

  form.addEventListener("submit", (e) => {
    const errors = validateAll();
    if (errors.length > 0) {
      e.preventDefault();
      submitBtn.disabled = true;

      const firstInvalid = requiredFields.find((el) => el.classList.contains("error"));
      if (firstInvalid) {
        firstInvalid.scrollIntoView({ behavior: "smooth", block: "center" });
        firstInvalid.focus?.();
      }

      alert(
        "❌ Bitte erst alles korrekt ausfüllen:\n\n- " + errors.join("\n- ")
      );
    }
  });

  updateSubmitState();
});

