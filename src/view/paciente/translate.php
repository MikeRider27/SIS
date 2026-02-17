<?php
include('/var/www/html/view/includes/header.php');
?>

<!-- Content Wrapper -->
<div class="content-wrapper">
  <section class="content-header text-center">
    <div class="container-fluid">
      <h1><strong>Terminology Translate ($translate)</strong></h1>
      <p class="text-muted">Traducción de códigos y listado de ConceptMaps disponibles en el servidor FHIR</p>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">

      <!-- Parámetros -->
      <div class="card card-danger">
        <div class="card-header">
          <h3 class="card-title"><strong>Acciones</strong></h3>
        </div>
        <div class="card-body">

          <div class="row justify-content-center mb-4">
            <div class="col-sm-4">
              <label for="tipo" class="font-size-14">Tipo de traducción:</label>
              <select id="tipo" class="form-control select2bs4">
                <option value="">Seleccione</option>
                <option value="alergiasLR">Alergias LOCAL → RACSEL</option>
                <option value="alergiasLS">Alergias LOCAL → SNOMED</option>
                <option value="alergiasRL">Alergias RACSEL → LOCAL</option>               
                <option value="alergiasRS">Alergias RACSEL → SNOMED</option>
                <option value="alergiasSL">Alergias SNOMED → LOCAL</option>
                <option value="alergiasSR">Alergias SNOMED → RACSEL</option>
                <option value="antecedentesSL">Antecedentes SNOMED → LOCAL</option>
                <option value="diagnosticosSL">Diagnósticos SNOMED → LOCAL</option>
                <option value="vacunasLR">Vacunas LOCAL → RACSEL</option>
                <option value="vacunasLS">Vacunas LOCAL → SNOMED</option>
                <option value="vacunasL11">Vacunas LOCAL → CIE-11</option>
                <option value="vacunasLP">Vacunas LOCAL → PREQUAL</option>
                <option value="vacunasRL">Vacunas RACSEL → LOCAL</option>
                <option value="vacunasRS">Vacunas RACSEL → SNOMED</option>
                <option value="vacunasSL">Vacunas SNOMED → LOCAL</option>
                <option value="vacunasSR">Vacunas SNOMED → RACSEL</option>
                <option value="vacunasSP">Vacunas SNOMED → PREQUAL</option>
                <option value="vacunas11L">Vacunas CIE-11 → LOCAL</option>
                <option value="vacunas11P">Vacunas CIE-11 → PREQUAL</option>
                <option value="vacunasPL">Vacunas PREQUAL → LOCAL</option>
                <option value="vacunasP11">Vacunas PREQUAL → CIE-11</option>
                <option value="vacunasPS">Vacunas PREQUAL → SNOMED</option>
                <option value="medicacion_snomed">Medicación LOCAL → SNOMED</option>
                <option value="procedimientos">Procedimientos LOCAL → SNOMED</option>
              </select>
            </div>

            <div class="col-sm-4">
              <label for="codigo" class="font-size-14">Código local:</label>
              <input type="text" id="codigo" class="form-control font-size-14" placeholder="Ej: vac-1, med-2..." autocomplete="off">
            </div>

            <div class="col-sm-4 text-center mt-4">
              <button id="btnTraducir" class="btn btn-outline-danger mt-2 mr-2">
                <i class="fa fa-language"></i> <strong>Traducir</strong>
              </button>
              <button id="btnListar" class="btn btn-outline-dark mt-2">
                <i class="fa fa-list"></i> <strong>Listar ConceptMaps</strong>
              </button>
            </div>
          </div>

          <div id="loading" class="text-center mt-3" style="display:none;">
            <i class="fa fa-spinner fa-spin"></i> Procesando solicitud...
          </div>
        </div>
      </div>

      <!-- Resultados de Traducción -->
      <div class="card card-danger" id="resultCard" style="display:none;">
        <div class="card-header">
          <h3 class="card-title"><strong>Resultado de la traducción</strong></h3>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table id="tablaResultados" class="table table-bordered table-striped text-center">
              <thead class="bg-danger text-white">
                <tr>
                  <th>System</th>
                  <th>Code</th>
                  <th>Display</th>
                  <th>Equivalence</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
          <pre id="jsonRaw" class="bg-light p-2 mt-3" style="display:none; text-align:left; border-radius:6px;"></pre>
        </div>
      </div>

      <!-- Listado de ConceptMaps -->
      <div class="card card-dark" id="conceptMapCard" style="display:none;">
        <div class="card-header">
          <h3 class="card-title"><strong>ConceptMaps disponibles</strong></h3>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table id="tablaConceptMaps" class="table table-bordered table-hover text-center">
              <thead class="bg-dark text-white">
                <tr>
                  <th>Name</th>
                  <th>Status</th>
                  <th>Source</th>
                  <th>Target</th>
                  <th>URL</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
          <pre id="jsonRawMaps" class="bg-light p-2 mt-3" style="display:none; text-align:left; border-radius:6px;"></pre>
        </div>
      </div>

    </div>
  </section>
</div>

<?php include('/var/www/html/view/includes/footer.php'); ?>

<script>
$(function() {
  $('.select2bs4').select2({ theme: 'bootstrap4' });

  const proxy = "/view/paciente/translate_proxy.php"; // usa tu proxy local
  const fhirBase = "https://snowstorm.mspbs.gov.py/fhir";

  const mapConceptos = {
    alergiasLR: {
      url: "http://racsel.org/fhir/ConceptMap/vs-alergias-local-to-racsel",
      system: "http://node-acme.org/terminology",
      source: "http://racsel.org/fhir/ValueSet/alergias-local-vs",
      target: "http://racsel.org/fhir/ValueSet/alergias-racsel-vs"
    },
    alergiasRL: {
      url: "http://racsel.org/fhir/ConceptMap/vs-alergias-racsel-to-local",
      system: "http://racsel.org/connectathon",
      source: "http://racsel.org/fhir/ValueSet/alergias-racsel-vs",
      target: "http://racsel.org/fhir/ValueSet/alergias-local-vs"
    },
    alergiasLS: {
      url: "http://racsel.org/fhir/ConceptMap/vs-alergias-local-to-snomed",
      system: "http://node-acme.org/terminology",
      source: "http://racsel.org/fhir/ValueSet/alergias-local-vs",
      target: "http://racsel.org/fhir/ValueSet/alergias-vs"
    },
    alergiasSL: {
      url: "http://racsel.org/fhir/ConceptMap/vs-alergias-snomed-to-local",
      system: "http://snomed.info/sct",
      source: "http://racsel.org/fhir/ValueSet/alergias-vs",
      target: "http://racsel.org/fhir/ValueSet/alergias-local-vs"
    },
    alergiasSR: {
      url: "http://racsel.org/fhir/ConceptMap/vs-alergias-snomed-to-racsel",
      system: "http://snomed.info/sct",
      source: "http://racsel.org/fhir/ValueSet/alergias-vs",
      target: "http://racsel.org/fhir/ValueSet/alergias-racsel-vs"
    },
    alergiasRS: {
      url: "http://racsel.org/fhir/ConceptMap/vs-alergias-racsel-to-snomed",
      system: "http://racsel.org/connectathon",
      source: "http://racsel.org/fhir/ValueSet/alergias-racsel-vs",
      target: "http://racsel.org/fhir/ValueSet/alergias-vs"
    },
    antecedentesSL: {
      url: "http://racsel.org/fhir/ConceptMap/vs-antecedentes-snomed-to-local",
      system:  "http://snomed.info/sct",
      source: "http://racsel.org/fhir/ValueSet/antecedentes-personales-vs",
      target: "http://racsel.org/fhir/ValueSet/antecedentes-personales-local-vs"
    },
    diagnosticosSL: {
      url:  "http://racsel.org/fhir/ConceptMap/vs-diagnosticos-snomed-to-local",
      system: "http://snomed.info/sct",
      source: "http://racsel.org/fhir/ValueSet/diagnosticos-vs",
      target:"http://racsel.org/fhir/ValueSet/diagnosticos-local-vs"
    },
     medicacion_snomed: {
      url: "http://racsel.org/fhir/ConceptMap/vs-medicacion-local-to-snomed",
      system: "http://node-acme.org/terminology",
      source: "http://racsel.org/fhir/ValueSet/medicacion-local-vs",
      target: "http://racsel.org/fhir/ValueSet/medicacion-vs"
     },
    vacunasLR: {
      url: "http://racsel.org/fhir/ConceptMap/vs-vacunas-local-to-racsel",
      system: "http://node-acme.org/terminology",
      source: "http://racsel.org/fhir/ValueSet/vacunas-local-vs",
      target: "http://racsel.org/fhir/ValueSet/vacunas-racsel-vs"
    },
    vacunasLS: {
      url: "http://racsel.org/fhir/ConceptMap/vs-vacunas-local-to-snomed",
      system: "http://node-acme.org/terminology",
      source: "http://racsel.org/fhir/ValueSet/vacunas-local-vs",
      target: "http://racsel.org/fhir/ValueSet/vacunas-vs"
    },
    vacunasL11: {
      url: "http://racsel.org/fhir/ConceptMap/vs-vacunas-local-to-cie11",
      system: "http://node-acme.org/terminology",
      source: "http://racsel.org/fhir/ValueSet/vacunas-local-vs",
      target: "http://racsel.org/fhir/ValueSet/cie11-vs"
    },
    vacunasLP: {
      url: "http://racsel.org/fhir/ConceptMap/vs-vacunas-local-to-prequal",
      system: "http://node-acme.org/terminology",
      source: "http://racsel.org/fhir/ValueSet/vacunas-local-vs",
      target: "http://racsel.org/fhir/ValueSet/prequal-vs"
    },
    vacunasRL: {
      url: "http://racsel.org/fhir/ConceptMap/vs-vacunas-racsel-to-local",
      system: "http://racsel.org/connectathon",
      source: "http://racsel.org/fhir/ValueSet/vacunas-racsel-vs",
      target: "http://racsel.org/fhir/ValueSet/vacunas-local-vs"
    },
    vacunasRS: {
      url: "http://racsel.org/fhir/ConceptMap/vs-vacunas-racsel-to-snomed",
      system: "http://racsel.org/connectathon",
      source: "http://racsel.org/fhir/ValueSet/vacunas-vs",
      target: "http://racsel.org/fhir/ValueSet/vacunas-local-vs"
    },
    vacunasSL: {
      url: "http://racsel.org/fhir/ConceptMap/vs-vacunas-snomed-to-local",
      system: "http://snomed.info/sct",
      source: "http://racsel.org/fhir/ValueSet/vacunas-racsel-vs",
      target: "http://racsel.org/fhir/ValueSet/vacunas-vs"
    },
    vacunasSR: {
      url: "http://racsel.org/fhir/ConceptMap/vs-vacunas-snomed-to-racsel",
      system: "http://snomed.info/sct",
      source: "http://racsel.org/fhir/ValueSet/vacunas-vs",
      target: "http://racsel.org/fhir/ValueSet/vacunas-racsel-vs"
    },
    vacunasSP: {
      url: "http://racsel.org/fhir/ConceptMap/vs-vacunas-snomed-to-prequal",
      system: "http://snomed.info/sct",
      source: "http://racsel.org/fhir/ValueSet/snomed-vs",
      target: "http://racsel.org/fhir/ValueSet/prequal-vs"
    },
    vacunasPL: {
      url: "http://racsel.org/fhir/ConceptMap/vs-vacunas-prequal-to-local",
      system: "http://smart.who.int/pcmt-vaxprequal/CodeSystem/PreQualProductIDs",
      source: "http://racsel.org/fhir/ValueSet/prequal-vs",
      target: "http://racsel.org/fhir/ValueSet/vacunas-local-vs"
    }

  };

  function toggleLoader(show) {
    $('#loading').toggle(show);
  }

  // === BOTÓN TRADUCIR ===
  $('#btnTraducir').on('click', function(e) {
    e.preventDefault();
    const tipo = $('#tipo').val();
    const codigo = $('#codigo').val().trim();
    if (!tipo || !codigo) {
      toastr.warning('Seleccione un tipo de traducción y escriba el código.');
      return;
    }

    const conf = mapConceptos[tipo];
    const params = new URLSearchParams({
      url: conf.url,
      system: conf.system,
      source: conf.source,
      target: conf.target,
      code: codigo
    });

    const fullUrl = `${proxy}?${params.toString()}`;
    console.log("→ Request GET (via proxy):", fullUrl);

    toggleLoader(true);
    $('#resultCard, #conceptMapCard').hide();

    fetch(fullUrl)
      .then(res => res.json())
      .then(data => {
        toggleLoader(false);
        $('#resultCard').show();
        $('#jsonRaw').text(JSON.stringify(data, null, 2)).show();

        const results = [];
        if (data.parameter) {
          data.parameter.forEach(p => {
            if (p.name === "match" && p.part) {
              let match = {};
              p.part.forEach(part => {
                if (part.name === "concept") {
                  match.system = part.valueCoding?.system;
                  match.code = part.valueCoding?.code;
                  match.display = part.valueCoding?.display;
                }
                if (part.name === "equivalence") {
                  match.equivalence = part.valueCode;
                }
              });
              results.push(match);
            }
          });
        }

        const $tbody = $('#tablaResultados tbody').empty();
        if (results.length > 0) {
          results.forEach(r => {
            $tbody.append(`
              <tr>
                <td>${r.system || '-'}</td>
                <td>${r.code || '-'}</td>
                <td>${r.display || '-'}</td>
                <td>${r.equivalence || '-'}</td>
              </tr>
            `);
          });
          toastr.success(`Se encontraron ${results.length} resultado(s).`);
        } else {
          toastr.info('No se encontraron equivalencias.');
        }
      })
      .catch(err => {
        toggleLoader(false);
        toastr.error('Error al comunicarse con el servidor FHIR.');
        console.error(err);
      });
  });

  // === BOTÓN LISTAR CONCEPTMAPS ===
  $('#btnListar').on('click', function() {
    toggleLoader(true);
    $('#resultCard, #conceptMapCard').hide();

    fetch(`${fhirBase}/ConceptMap`)
      .then(res => res.json())
      .then(bundle => {
        toggleLoader(false);
        $('#conceptMapCard').show();
        $('#jsonRawMaps').text(JSON.stringify(bundle, null, 2)).show();

        const entries = bundle.entry || [];
        const $tbody = $('#tablaConceptMaps tbody').empty();

        if (entries.length === 0) {
          toastr.info('No se encontraron ConceptMaps disponibles.');
          return;
        }

        entries.forEach(e => {
          const r = e.resource || {};
          $tbody.append(`
            <tr>
              <td>${r.name || '-'}</td>
              <td>${r.status || '-'}</td>
              <td>${r.sourceUri || '-'}</td>
              <td>${r.targetUri || '-'}</td>
              <td><a href="${r.url}" target="_blank">${r.url}</a></td>
            </tr>
          `);
        });

        toastr.success(`Se listaron ${entries.length} ConceptMaps.`);
      })
      .catch(err => {
        toggleLoader(false);
        toastr.error('Error al obtener los ConceptMaps.');
        console.error(err);
      });
  });
});
</script>
