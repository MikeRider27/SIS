<?php
include('/var/www/html/view/includes/header.php');
include('/var/www/html/core/connection.php');

// Obtener la conexión a la base de datos
$dbconn = getConnectionFHIR();
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12 text-center">
                    <h1><strong>IPS Viewer</strong></h1>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="container">
                <div class="left-panel">
                    <h1></h1>
                    <textarea id="jsonInput" placeholder="Pega tu JSON aquí..."></textarea><br>
                </div>
                <div class="right-panel" id="jsonDisplay">
                    <h1></h1>
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

<!-- CodeMirror CSS & JS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/material.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>

<script>
    var editor = CodeMirror.fromTextArea(document.getElementById("jsonInput"), {
        mode: "application/json",
        theme: "material",
        lineNumbers: true,
        tabSize: 2,
        matchBrackets: true,
    });

    // Asignar el evento 'blur' para procesar el JSON al salir del campo
    editor.on('blur', processJSON);

    function processJSON() {
        const jsonInput = editor.getValue();
        const jsonDisplay = document.getElementById('jsonDisplay');

        try {
            const data = JSON.parse(jsonInput);
            jsonDisplay.innerHTML = ''; // Limpiar contenido anterior

            let patient = null,
                organization = null,
                composition = null;
            let allergies = [],
                condition = [],
                medication = [],
                inmunization = [],
                observation = [];

            // Procesar cada recurso del JSON
            data.entry.forEach(entry => {
                switch (entry.resource.resourceType) {
                    case 'Patient':
                        patient = entry.resource;
                        break;
                    case 'Organization':
                        organization = entry.resource;
                        break;
                    case 'Composition':
                        composition = entry.resource;
                        break;
                    case 'AllergyIntolerance':
                        allergies.push(entry.resource);
                        break;
                    case 'Condition':
                        condition.push(entry.resource);
                        break;
                    case 'Medication':
                        medication.push(entry.resource);
                        break;
                    case 'Immunization':
                        inmunization.push(entry.resource);
                        break;
                    case 'Observation':
                        observation.push(entry.resource);
                        break;
                }
            });

            // Mostrar las secciones, independientemente del contenido de observation
            console.log("Observaciones:", observation); // Depuración

            displayPatientInfo(patient, organization, composition, jsonDisplay);
            displayAllergies(allergies, jsonDisplay);
            displayConditions(condition, jsonDisplay);
            displayConditions2(condition, jsonDisplay);
            displayMedication(medication, jsonDisplay);
            displayInmunization(inmunization, jsonDisplay);
          // displayObservation(observation, jsonDisplay);

        } catch (error) {
            console.error("Error al procesar JSON:", error);
            jsonDisplay.innerHTML = `<p style="color: red;">Error al procesar JSON: ${error.message}</p>`;
        }
    }


    function displayPatientInfo(patient, organization, composition, container) {
        const patientName = patient.name[0]?.text || 'No disponible';
        const birthDate = new Date(patient.birthDate + 'T00:00:00Z');
        const formattedDate = `${birthDate.getUTCDate().toString().padStart(2, '0')}/${(birthDate.getUTCMonth() + 1).toString().padStart(2, '0')}/${birthDate.getUTCFullYear()}`;
        const age = new Date().getFullYear() - birthDate.getUTCFullYear();

        const organizationName = organization?.name || 'No disponible';
        const organizationCountry = organization.address && organization.address.length > 0 ?
            organization.address[0].country || 'No disponible' : 'No disponible';
        const compositionDate = composition?.date || 'No disponible';

        const patientInfoDiv = document.createElement('div');
        patientInfoDiv.className = 'card mb-3';
        patientInfoDiv.innerHTML = `
            <div class="card-header">
                <h3 class="card-title"><strong>${patientName}</strong></h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <p><strong>Fecha de nacimiento:</strong> ${formattedDate} (${age} años)</p>
                <p><strong>Dominio:</strong> ${organizationCountry}</p>
                <p><strong>Última actualización:</strong> ${compositionDate}</p>
                <p><strong>Autor:</strong> ${organizationName}</p>               
            </div>`;
        container.appendChild(patientInfoDiv);
    }

    function displayAllergies(allergies, container) {
        const allergyDiv = document.createElement('div');
        allergyDiv.className = 'card mb-3';
        let content = `<div class="card-header"><h3 class="card-title"><strong>Alergias</strong></h3> <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div></div><div class="card-body">`;

        allergies.forEach(allergy => {
            const substance = allergy.code.coding[0]?.display || 'No disponible';
            const code = allergy.code.coding[0].code || 'No disponible';
            let status = allergy.criticality || 'No disponible';

            const statusMap = {
                'low': 'bajo',
                'high': 'alto',
                'unable-to-assess': 'incapaz de evaluar'
            };

            // Si el estado está en el mapa, se usa la traducción, de lo contrario 'indefinido'
            status = statusMap[status] || 'indefinido';
            content += `<p>- ${substance} (${code})</p>
           <spam>alergia - Criticidad: ${status}</spam> 
            <hr>`;
        });

        content += '</div>';
        allergyDiv.innerHTML = content;
        container.appendChild(allergyDiv);
    }

    function displayConditions(conditions, container) {
        const conditionDiv = document.createElement('div');
        conditionDiv.className = 'card mb-3';
        let content = `<div class="card-header"><h3 class="card-title"><strong>Diagnósticos / Problemas activos</strong></h3><div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div></div><div class="card-body">`;

        conditions.forEach(cond => {
            const system = cond.code.coding[0]?.display || 'No disponible';
            const code = cond.code.coding[0].code || 'No disponible';
            const year = cond.onsetDateTime || 'No disponible';
            content += `<p>- ${system} (${code})</p>
            <spam>${year}</spam> 
            <hr>`;
        });

        content += '</div>';
        conditionDiv.innerHTML = content;
        container.appendChild(conditionDiv);
    }

    function displayConditions2(conditions, container) {
        const condition2Div = document.createElement('div');
        condition2Div.className = 'card mb-3';
        let content = `<div class="card-header"><h3 class="card-title"><strong>Diagnósticos / Problemas pasados</strong></h3><div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div></div><div class="card-body">`;

        conditions.forEach(cond => {

            content += `<p></p><hr>`;
        });

        content += '</div>';
        condition2Div.innerHTML = content;
        container.appendChild(condition2Div);
    }

    function displayMedication(medication, container) {
        const medicationDiv = document.createElement('div');
        medicationDiv.className = 'card mb-3';
        let content = `<div class="card-header"><h3 class="card-title"><strong>Medicamentos activos</strong></h3><div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div></div><div class="card-body">`;

        medication.forEach(med => {
            const system = med.code.coding[0]?.display || 'No disponible';
            const code = med.code.coding[0].code || 'No disponible';

            content += `<p>- ${system} (${code})</p><hr>`;
        });

        content += '</div>';
        medicationDiv.innerHTML = content;
        container.appendChild(medicationDiv);
    }

    function displayInmunization(inmunization, container) {
        const inmunizationDiv = document.createElement('div');
        inmunizationDiv.className = 'card mb-3';
        let content = `<div class="card-header"><h3 class="card-title"><strong>Inmunizaciones</strong></h3><div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div></div><div class="card-body">`;

        inmunization.forEach(inm => {
            const system = inm.vaccineCode.coding[0].display || 'No disponible';
            const code = inm.vaccineCode.coding[0].code || 'No disponible';
            const status = inm.status || 'No disponible';
            const aplytime = inm.occurrenceDateTime || 'No disponible';


            content += `<p>- ${code} ${system} - (${status}) </p>
            <strong>Fecha:</strong> ${aplytime}
            <hr>`;
        });

        content += '</div>';
        inmunizationDiv.innerHTML = content;
        container.appendChild(inmunizationDiv);
    }

    function displayObservation(observation, container) {
        // Crear la estructura básica de la tabla, siempre se mostrará
        let tableContent = `
    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title"><strong>Observaciones</strong></h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-dark table-striped">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Fecha</th>
                        <th>Valor</th>
                        <th>Categoría</th>
                    </tr>
                </thead>
                <tbody>`;

        // Verificar si hay observaciones, si no hay mostrar una fila indicando que no hay datos
        if (Array.isArray(observation) && observation.length > 0) {
            observation.forEach(obs => {
                const name = obs.code?.coding?.[0]?.display || 'No disponible';
                const dateObs = obs.effectiveDateTime || 'No disponible';
                const valueObs = obs.valueQuantity?.value || 'No disponible';
                const category = obs.category?.[0]?.coding?.[0]?.display || 'No disponible';

                tableContent += `
            <tr>
                <td>${name}</td>
                <td>${dateObs}</td>
                <td>${valueObs}</td>
                <td>${category}</td>
            </tr>`;
            });
        } else {
            // Mostrar una fila indicando que no hay datos disponibles
            tableContent += `
        <tr>
            <td colspan="4" class="text-center">No hay observaciones disponibles.</td>
        </tr>`;
        }

        // Cerrar la tabla y el contenedor
        tableContent += `
                </tbody>
            </table>
        </div>
    </div>`;

        // Asignar el contenido al contenedor
        container.innerHTML = tableContent;
    }
</script>

<style>
    .container {
        display: flex;
        height: 100vh;
        margin: 0;
    }

    .left-panel,
    .right-panel {
        width: 50%;
        padding: 10px;
        overflow-y: auto;
    }

    .card {
        margin-bottom: 10px;
    }

    .CodeMirror {
        height: calc(100vh - 100px);
    }
</style>