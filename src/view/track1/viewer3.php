<?php
include('/var/www/html/view/includes/header.php');
include('/var/www/html/core/connection.php');

// Obtener la conexión a la base de datos (no se usa aquí, pero se mantiene por consistencia)
$dbconn = getConnectionFHIR();

$id = isset($_GET['param1']) ? $_GET['param1'] : '';
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-12 text-center">
          <h1><strong>RDA Viewer</strong></h1>
        </div>
      </div>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="row">

        <!-- Panel izquierdo: Editor JSON -->
        <div class="col-lg-6 col-12">
          <div class="card card-outline card-info">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h3 class="card-title mb-0"><strong>JSON</strong> (Bundle)</h3>
              <div class="btn-group btn-group-sm" role="group">
                <button id="btnProcess" class="btn btn-primary"><i class="fas fa-play"></i> Procesar</button>
                <button id="btnDownload" class="btn btn-dark"><i class="fas fa-file-download"></i> Descargar</button>
              </div>
            </div>
            <div class="card-body p-0">
              <textarea id="jsonInput" class="form-control" rows="20" placeholder="Pega tu JSON aquí..."></textarea>
            </div>
            <div class="card-footer py-2">
              <small class="text-muted">Sugerencia: pegá un Bundle IPS válido o usa la URL: <code>https://fhir.mspbs.gov.py/fhir/Bundle/<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?></code></small>
            </div>
          </div>
        </div>

        <!-- Panel derecho: Render -->
        <div class="col-lg-6 col-12">
          <div id="jsonDisplay"></div>
        </div>

      </div>
    </div>
  </section>
</div>

<?php
include('/var/www/html/view/includes/footer.php');
?>

<!-- Page specific script -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- FontAwesome (por los íconos del botón de colapsar) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"/>

<!-- CodeMirror CSS & JS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/material.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>

<style>
  .CodeMirror { 
    height: auto !important; 
    min-height: 400px;
    font-size: 14px; 
    border: 1px solid #ddd;
  }
  
  .CodeMirror-scroll {
    height: auto !important;
    overflow-y: hidden !important;
    overflow-x: auto !important;
  }
  
  .CodeMirror-sizer {
    min-height: auto !important;
  }
  
  .cm-line {
    line-height: 1.4;
  }
  
  .card + .card { margin-top: 1rem; }
  .badge-tight { font-size: 0.75rem; }
  .table-sm th, .table-sm td { padding: .3rem .5rem; }
</style>

<script>
  // --- Inicialización CodeMirror ---
  var editor;
  document.addEventListener('DOMContentLoaded', () => {
    editor = CodeMirror.fromTextArea(document.getElementById('jsonInput'), {
      mode: 'application/json',
      theme: 'material',
      lineNumbers: true,
      tabSize: 2,
      matchBrackets: true,
      viewportMargin: Infinity,
      lineWrapping: true
    });

    // Forzar el redimensionamiento para eliminar scroll
    setTimeout(() => {
      editor.refresh();
    }, 100);

    const bundleId = <?php echo $id ? '"' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' : '""'; ?>;

    if (bundleId) {
      const url = `https://fhir-conectaton.mspbs.gov.py/fhir/Bundle/${bundleId}`;
      fetch(url)
        .then(r => { if (!r.ok) throw new Error('Error al obtener el Bundle'); return r.json(); })
        .then(data => {
          const pretty = JSON.stringify(data, null, 2);
          editor.setValue(pretty);
          // Refrescar después de cargar el contenido
          setTimeout(() => {
            editor.refresh();
          }, 200);
          processJSON();
        })
        .catch(err => {
          console.error(err);
          setDisplayHTML(`<div class="alert alert-danger">No se pudo cargar el Bundle: ${escapeHtml(err.message)}</div>`);
        });
    }
  });

  // --- Botones ---
  document.addEventListener('click', (ev) => {
    if (ev.target.closest('#btnProcess')) processJSON();
    if (ev.target.closest('#btnDownload')) downloadJSON();
  });

  // --- Utilidades ---
  function setDisplayHTML(html) { document.getElementById('jsonDisplay').innerHTML = html; }
  function appendDisplayHTML(html) { document.getElementById('jsonDisplay').insertAdjacentHTML('beforeend', html); }

  function escapeHtml(str) {
    return String(str).replace(/[&<>"]/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s]));
  }

  function downloadJSON() {
    const blob = new Blob([editor.getValue()], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = 'bundle_ips.json'; a.click();
    URL.revokeObjectURL(url);
  }

  // --- PROCESAR JSON ---
  function processJSON() {
    const jsonDisplay = document.getElementById('jsonDisplay');
    jsonDisplay.innerHTML = '';

    let data;
    try {
      // 1) Leer texto actual
      let rawText = editor.getValue();

      // 2) Reemplazar "fullUrl": "ResourceType/uuid" → "urn:uuid:uuid"
      //    y "reference": "ResourceType/uuid" → "urn:uuid:uuid"
      //    (admite UUID v4 y similares con guiones)
      const reFullUrl = /"fullUrl"\s*:\s*"([A-Za-z][A-Za-z0-9]+)\/([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12})"/g;
      const reRef     = /"reference"\s*:\s*"([A-Za-z][A-Za-z0-9]+)\/([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12})"/g;

      let countFullUrl = 0, countRef = 0;
      rawText = rawText.replace(reFullUrl, (m, t, id) => { countFullUrl++; return `"fullUrl": "urn:uuid:${id}"`; });
      rawText = rawText.replace(reRef,     (m, t, id) => { countRef++;     return `"reference": "urn:uuid:${id}"`; });

      // 3) Actualizar visualmente el JSON en CodeMirror si hubo cambios
      if (countFullUrl || countRef) {
        editor.setValue(rawText);
        // Refrescar después de modificar el contenido
        setTimeout(() => {
          editor.refresh();
        }, 100);
      }

      // 4) Parsear JSON corregido
      data = JSON.parse(rawText);
    } catch (e) {
      setDisplayHTML(`<div class="alert alert-danger">Error al procesar JSON: ${escapeHtml(e.message)}</div>`);
      return;
    }

    // --- Validar estructura básica ---
    if (!data || data.resourceType !== 'Bundle') {
      setDisplayHTML('<div class="alert alert-warning">No es un Bundle FHIR válido.</div>');
      return;
    }
    if (!Array.isArray(data.entry)) {
      setDisplayHTML('<div class="alert alert-warning">El Bundle no contiene <code>entry</code> válidas.</div>');
      return;
    }

    // --- Indexar recursos por fullUrl y por "type/id" ---
    const index = new Map();
    data.entry.forEach(e => {
      const res = e && e.resource; if (!res) return;
      if (e.fullUrl) index.set(e.fullUrl, res);
      if (res.id && res.resourceType) index.set(`${res.resourceType}/${res.id}`, res);
    });

    const deref = (ref) => {
      if (!ref) return null;
      const key = typeof ref === 'string' ? ref : (ref.reference || '');
      return index.get(key) || null;
    };

    // --- Recursos clave ---
    const composition = data.entry.map(e => e.resource).find(r => r && r.resourceType === 'Composition') || null;
    const patient = composition && composition.subject ? deref(composition.subject) : (data.entry.map(e => e.resource).find(r => r.resourceType === 'Patient') || null);
    const custodian = composition && composition.custodian ? deref(composition.custodian) : (data.entry.map(e => e.resource).find(r => r.resourceType === 'Organization') || null);

    // Encabezado
    displayPatientHeader(patient, custodian, composition);

    // Render según secciones declaradas
    if (composition && Array.isArray(composition.section) && composition.section.length) {
      composition.section.forEach(section => renderSection(section, deref));
    } else {
      // Sin secciones: agrupar por tipo
      renderAllByType(data.entry.map(e => e.resource || {}), deref);
    }
  }

  // === RENDER POR SECCIÓN ===
  function renderSection(section, deref) {
    const title = section.title || 'Sección';
    const code = section.code && section.code.coding && section.code.coding[0] ? section.code.coding[0] : null;
    const entries = Array.isArray(section.entry) ? section.entry : [];

    // Secciones vacías con emptyReason o con narrative
    if ((!entries.length) && (section.emptyReason || (section.text && section.text.div))) {
      const reason = section.emptyReason && section.emptyReason.coding && section.emptyReason.coding[0]
        ? (section.emptyReason.coding[0].display || section.emptyReason.coding[0].code)
        : '';
      const safeDiv = section.text && section.text.div ? section.text.div : '';
      appendDisplayHTML(cardWrap(title, (reason ? `<p class="mb-2"><span class="badge badge-warning badge-tight">${escapeHtml(reason)}</span></p>` : '') + (safeDiv || '')));
      return;
    }

    // Desreferenciar entradas
    const resources = entries.map(e => deref(e)).filter(Boolean);

    // Heurística por LOINC
    const loinc = code ? code.code : '';
      
    // Si el título o el código corresponde a Encounter Summary, no mostrar nada
    if ((section.title && /encounter/i.test(section.title)) || (code && code.code === '46240-8')) {
      return; // ignora completamente esta sección
    }
    
    switch (loinc) {
      case '11450-4': // Problem list
        displayConditions(resources);
        break;
      case '10160-0': // Medications
        displayMedications(resources, deref);
        break;
      case '48765-2': // Allergies
        displayAllergies(resources);
        break;
      case '11369-6': // Immunizations
        displayImmunizations(resources);
        break;
      case '47519-4': // Procedures
        displayProcedures(resources);
        break;
      default:
        appendDisplayHTML(cardWrap(title, genericList(resources)));
    }
  }

  // === RENDER SIN SECCIONES: AGRUPAR POR TIPO ===
  function renderAllByType(resources, deref) {
    const byType = resources.reduce((acc, r) => {
      if (!r || !r.resourceType) return acc;
      (acc[r.resourceType] = acc[r.resourceType] || []).push(r);
      return acc;
    }, {});

    if (byType.Condition) displayConditions(byType.Condition);
    if (byType.Medication || byType.MedicationStatement) displayMedications([...(byType.Medication || []), ...(byType.MedicationStatement || [])], deref);
    if (byType.AllergyIntolerance) displayAllergies(byType.AllergyIntolerance);
    if (byType.Immunization) displayImmunizations(byType.Immunization);
    if (byType.Procedure) displayProcedures(byType.Procedure);
    if (byType.Observation) displayObservations(byType.Observation);
  }

  // === COMPONENTES VISUALES ===
  function cardWrap(title, bodyHtml) {
    const hasNarrativeNotAvailable = /Narrative\s+not\s+available/i.test(bodyHtml || '');
    const collapsed = hasNarrativeNotAvailable ? 'collapsed-card' : '';
    const icon = hasNarrativeNotAvailable ? 'fa-plus' : 'fa-minus';

    return `
      <div class="card ${collapsed}">
        <div class="card-header">
          <h3 class="card-title">${escapeHtml(title)}</h3>
          <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
              <i class="fas ${icon}"></i>
            </button>
          </div>
        </div>
        <div class="card-body">${bodyHtml || '<em>Sin datos</em>'}</div>
      </div>`;
  }

  function displayPatientHeader(patient, org, composition) {
    const patientName = patient && patient.name && patient.name[0]
      ? (patient.name[0].text || `${(patient.name[0].given || []).join(' ')} ${patient.name[0].family || ''}`.trim())
      : 'No disponible';

    // Parseo robusto de fecha sin desfase UTC
    function parseFlexibleDate(input) {
      if (!input) return null;

      // YYYY-MM-DD
      const isoMatch = input.match(/^(\d{4})-(\d{2})-(\d{2})$/);
      if (isoMatch) {
        const [_, y, m, d] = isoMatch;
        return new Date(Number(y), Number(m) - 1, Number(d)); // local
      }

      // Remover tiempo si viene con T
      const cleaned = input.replace(/T.*$/, '').replace(/\.\d+Z?$/, '').trim();

      // dd/mm/yyyy o yyyy/mm/dd
      const parts = cleaned.split(/[-/]/);
      if (parts.length === 3) {
        if (parts[0].length === 4) return new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
        if (parts[2].length === 4) return new Date(Number(parts[2]), Number(parts[1]) - 1, Number(parts[0]));
      }
      return null;
    }

    const birthDateRaw = patient && patient.birthDate ? patient.birthDate : null;
    const birthDate = parseFlexibleDate(birthDateRaw);

    let formattedDate = 'No disponible';
    let age = 'No disponible';
    if (birthDate) {
      const day = String(birthDate.getDate()).padStart(2, '0');
      const month = String(birthDate.getMonth() + 1).padStart(2, '0');
      const year = birthDate.getFullYear();
      formattedDate = `${day}/${month}/${year}`;

      const today = new Date();
      age = today.getFullYear() - year;
      const m = today.getMonth() - birthDate.getMonth();
      if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) age--;
    }

    const organizationName = org && org.name ? org.name : 'No disponible';
    const organizationCountry = (org && org.address && org.address[0] && org.address[0].country)
      ? org.address[0].country
      : 'PY';
    const compositionDate = composition && composition.date ? composition.date : 'No disponible';

    const html = `
      <div class="card card-outline card-primary">
        <div class="card-header">
          <h3 class="card-title"><strong>${escapeHtml(patientName)}</strong></h3>
          <div class="card-tools"><span class="badge badge-info badge-tight">IPS</span></div>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-sm-6">
              <p class="mb-1"><strong>Fecha de nacimiento:</strong> ${escapeHtml(formattedDate)} (${escapeHtml(String(age))} años)</p>
              <p class="mb-1"><strong>Dominio:</strong> ${escapeHtml(String(organizationCountry))}</p>
            </div>
            <div class="col-sm-6">
              <p class="mb-1"><strong>Última actualización:</strong> ${escapeHtml(String(compositionDate))}</p>
              <p class="mb-1"><strong>Autor / Custodio:</strong> ${escapeHtml(String(organizationName))}</p>
            </div>
          </div>
        </div>
      </div>`;
    appendDisplayHTML(html);
  }

  // === RENDER: Alergias ===
  function displayAllergies(allergies) {
    if (!allergies || !allergies.length) return;

    const rows = allergies
      .filter(a => a && a.resourceType === 'AllergyIntolerance')
      .map(a => {
        const coding = a.code && a.code.coding && a.code.coding[0] ? a.code.coding[0] : {};
        const clinical = a.clinicalStatus && a.clinicalStatus.coding && a.clinicalStatus.coding[0] ? a.clinicalStatus.coding[0].code : '';
        const verify = a.verificationStatus && a.verificationStatus.coding && a.verificationStatus.coding[0] ? a.verificationStatus.coding[0].code : '';
        return `<tr>
          <td>${escapeHtml(coding.display || (a.code && a.code.text) || 'No disponible')}</td>          <td>${escapeHtml(coding.code || '')}</td>
   
          <td>${escapeHtml(clinical)}</td>
          <td>${escapeHtml(verify)}</td>
        </tr>`;
      }).join('');

    const body = `
      <div class="table-responsive">
        <table class="table table-sm table-striped">
          <thead><tr><th>Sustancia</th><th>Código</th><th>Estado</th><th>Verificación</th></tr></thead>
          <tbody>${rows}</tbody>
        </table>
      </div>`;

    appendDisplayHTML(cardWrap('Alergias e Intolerancias', body));
  }

  // === RENDER: Condiciones (activas/pasadas) ===
  function displayConditions(conditions) {
    if (!conditions || !conditions.length) return;

    const actives = [], past = [];
    conditions.forEach(c => {
      if (c.resourceType !== 'Condition') return;
      const clinical = c.clinicalStatus && c.clinicalStatus.coding && c.clinicalStatus.coding[0] ? c.clinicalStatus.coding[0].code : '';
      (clinical === 'active' ? actives : past).push(c);
    });

    const renderTable = (arr) => {
      if (!arr.length) return '<em>Sin datos</em>';
      const rows = arr.map(c => {
        const coding = c.code && c.code.coding && c.code.coding[0] ? c.code.coding[0] : {};
        const onset = c.onsetDateTime || (c.onsetPeriod && (c.onsetPeriod.start || c.onsetPeriod.end)) || '';
        const note = (c.note && c.note[0] && c.note[0].text) || '';
        return `<tr>
          <td>${escapeHtml(coding.display || (c.code && c.code.text) || 'No disponible')}</td>
          <td>${escapeHtml(coding.code || '')}</td>
          <td>${escapeHtml(onset)}</td>
          <td>${escapeHtml(note)}</td>
        </tr>`;
      }).join('');
      return `
        <div class="table-responsive">
          <table class="table table-sm table-striped">
            <thead><tr><th>Diagnóstico</th><th>Código</th><th>Inicio</th><th>Nota</th></tr></thead>
            <tbody>${rows}</tbody>
          </table>
        </div>`;
    };

    appendDisplayHTML(cardWrap('Diagnósticos / Problemas activos', renderTable(actives)));
    appendDisplayHTML(cardWrap('Diagnósticos / Problemas pasados', renderTable(past)));
  }

 // === RENDER: Medicación (Medication + MedicationStatement) ===
function displayMedications(resources, deref) {
  if (!resources || !resources.length) return;

  const meds = [];
  resources.forEach(r => {
    if (!r) return;
    if (r.resourceType === 'Medication') {
      meds.push({
        name: (r.code && r.code.coding && r.code.coding[0] && (r.code.coding[0].display || r.code.text)) || 'No disponible',
        code: (r.code && r.code.coding && r.code.coding[0] && r.code.coding[0].code) || '',
        form: r.form && (r.form.text || (r.form.coding && r.form.coding[0] && (r.form.coding[0].display || r.form.coding[0].code))) || ''
      });
    } else if (r.resourceType === 'MedicationStatement') {
      // CORRECCIÓN: Manejar tanto medicationReference como medicationCodeableConcept
      let name = 'No disponible';
      let code = '';
      
      if (r.medicationReference) {
        // Caso con referencia a Medication
        const med = deref(r.medicationReference);
        name = med && med.code && med.code.coding && med.code.coding[0] 
          ? (med.code.coding[0].display || med.code.text) 
          : 'No disponible';
        code = med && med.code && med.code.coding && med.code.coding[0] 
          ? (med.code.coding[0].code || '') 
          : '';
      } else if (r.medicationCodeableConcept) {
        // CORRECCIÓN: Caso con concept codificado directamente
        name = r.medicationCodeableConcept.text 
          || (r.medicationCodeableConcept.coding && r.medicationCodeableConcept.coding[0] 
            ? (r.medicationCodeableConcept.coding[0].display || r.medicationCodeableConcept.coding[0].code)
            : 'No disponible');
        code = r.medicationCodeableConcept.coding && r.medicationCodeableConcept.coding[0] 
          ? r.medicationCodeableConcept.coding[0].code 
          : '';
      }
      
      const route = r.dosage && r.dosage[0] && r.dosage[0].route 
        ? (r.dosage[0].route.text || (r.dosage[0].route.coding && r.dosage[0].route.coding[0] 
          ? (r.dosage[0].route.coding[0].display || r.dosage[0].route.coding[0].code)
          : ''))
        : '';
      
      const dose = r.dosage && r.dosage[0] && r.dosage[0].doseAndRate && r.dosage[0].doseAndRate[0] && r.dosage[0].doseAndRate[0].doseQuantity
        ? ((r.dosage[0].doseAndRate[0].doseQuantity.value || '') + ' ' + (r.dosage[0].doseAndRate[0].doseQuantity.unit || '')).trim()
        : (r.dosage && r.dosage[0] && r.dosage[0].text || ''); // CORRECCIÓN: Usar text si está disponible
      
      const eff = r.effectiveDateTime || '';
      
      meds.push({ 
        name, 
        code, 
        form: route, 
        extra: dose, 
        when: eff 
      });
    }
  });

  if (!meds.length) return;

  const rows = meds.map(m => `<tr>
    <td>${escapeHtml(m.name)}</td>
    <td>${escapeHtml(m.form || '')}</td>
    <td>${escapeHtml(m.extra || '')}</td>
    <td>${escapeHtml(m.when || '')}</td>
  </tr>`).join('');

  const body = `
    <div class="table-responsive">
      <table class="table table-sm table-striped">
        <thead><tr><th>Medicamento</th><th>Vía</th><th>Dosis</th><th>Fecha</th></tr></thead>
        <tbody>${rows}</tbody>
      </table>
    </div>`;

  appendDisplayHTML(cardWrap('Medicamentos', body));
}

  // === RENDER: Inmunizaciones ===
  function displayImmunizations(imms) {
    if (!imms || !imms.length) return;

    const rows = imms
      .filter(i => i && i.resourceType === 'Immunization')
      .map(i => {
        const coding = i.vaccineCode && i.vaccineCode.coding && i.vaccineCode.coding[0] ? i.vaccineCode.coding[0] : {};
        const vaccineText = (coding.display && coding.display.trim()) || (i.vaccineCode && i.vaccineCode.text) || '(sin descripción)';
        const code = coding.code || '';
        const status = i.status || '';
        const when = i.occurrenceDateTime || '';
        return `
          <tr>
            <td>${escapeHtml(vaccineText)}</td>
            <td>${escapeHtml(code)}</td>
            <td>${escapeHtml(status)}</td>
            <td>${escapeHtml(when)}</td>
          </tr>`;
      }).join('');

    const body = rows
      ? `
        <div class="table-responsive">
          <table class="table table-sm table-striped">
            <thead><tr><th>Vacuna</th><th>Código</th><th>Estado</th><th>Fecha</th></tr></thead>
            <tbody>${rows}</tbody>
          </table>
        </div>`
      : '<em>Sin datos</em>';

    appendDisplayHTML(cardWrap('Inmunizaciones', body));
  }

  // === RENDER: Procedimientos ===
  function displayProcedures(list) {
    if (!list || !list.length) return;

    const rows = list
      .filter(p => p && p.resourceType === 'Procedure')
      .map(p => {
        const coding = p.code && p.code.coding && p.code.coding[0] ? p.code.coding[0] : {};
        const date = p.performedDateTime || (p.performedPeriod && (p.performedPeriod.start || p.performedPeriod.end)) || '';
        const status = p.status || '';
        return `<tr>
          <td>${escapeHtml(coding.display || (p.code && p.code.text) || 'No disponible')}</td>
          <td>${escapeHtml(coding.code || '')}</td>
          <td>${escapeHtml(status)}</td>
          <td>${escapeHtml(date)}</td>
        </tr>`;
      }).join('');

    const body = `
      <div class="table-responsive">
        <table class="table table-sm table-striped">
          <thead><tr><th>Procedimiento</th><th>Código</th><th>Estado</th><th>Fecha</th></tr></thead>
          <tbody>${rows}</tbody>
        </table>
      </div>`;

    appendDisplayHTML(cardWrap('Procedimientos', body));
  }

  // === RENDER: Observaciones ===
  function displayObservations(obs) {
    if (!obs || !obs.length) return;

    const rows = obs
      .filter(o => o && o.resourceType === 'Observation')
      .map(o => {
        const coding = o.code && o.code.coding && o.code.coding[0] ? o.code.coding[0] : {};
        const date = o.effectiveDateTime || (o.effectivePeriod && (o.effectivePeriod.start || o.effectivePeriod.end)) || '';
        let value = '';
        if (o.valueQuantity) value = `${o.valueQuantity.value || ''} ${o.valueQuantity.unit || ''}`.trim();
        else if (o.valueString) value = o.valueString;
        else if (o.valueCodeableConcept && o.valueCodeableConcept.coding && o.valueCodeableConcept.coding[0]) value = o.valueCodeableConcept.coding[0].display || o.valueCodeableConcept.coding[0].code || '';
        const category = o.category && o.category[0] && o.category[0].coding && o.category[0].coding[0] ? (o.category[0].coding[0].display || o.category[0].coding[0].code) : '';
        return `<tr>
          <td>${escapeHtml(coding.display || (o.code && o.code.text) || 'No disponible')}</td>
          <td>${escapeHtml(date)}</td>
          <td>${escapeHtml(value)}</td>
          <td>${escapeHtml(category)}</td>
        </tr>`;
      }).join('');

    const body = `
      <div class="table-responsive">
        <table class="table table-sm table-striped">
          <thead><tr><th>Nombre</th><th>Fecha</th><th>Valor</th><th>Categoría</th></tr></thead>
          <tbody>${rows}</tbody>
        </table>
      </div>`;

    appendDisplayHTML(cardWrap('Observaciones', body));
  }

  // === RENDER: Genérico (fallback) ===
  function genericList(resources) {
    if (!resources || !resources.length) return '<em>Sin datos</em>';
    const rows = resources.map(r => `<tr>
      <td>${escapeHtml(r.resourceType || '')}</td>
      <td>${escapeHtml((r.id || ''))}</td>
      <td><code>${escapeHtml(
        ((r.code && r.code.text) ||
        (r.code && r.code.coding && r.code.coding[0] && r.code.coding[0].display) ||
        (r.name && r.name[0] && (r.name[0].text || ((r.name[0].given || []).join(' ') + ' ' + (r.name[0].family || '')))) ||
        '')
      )}</code></td>
    </tr>`).join('');

    return `
      <div class="table-responsive">
        <table class="table table-sm table-striped">
          <thead><tr><th>Tipo</th><th>ID</th><th>Etiqueta</th></tr></thead>
          <tbody>${rows}</tbody>
        </table>
      </div>`;
  }
</script>