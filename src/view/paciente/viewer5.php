<?php
include('/var/www/html/view/includes/header.php');
include('/var/www/html/core/connection.php');

// Obtener la conexión a la base de datos
$dbconn = getConnectionFHIR();
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12 text-center">
                    <h1><strong>IPS Viewer</strong></h1>
                </div>

            </div>
        </div><!-- /.container-fluid -->
    </section>


    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">


            <!-- JSON Input and Display -->
            <div class="container">
                <div class="left-panel">
                    <h1></h1>

                    <!-- CodeMirror will replace this textarea -->
                    <textarea id="jsonInput" placeholder="Pega tu JSON aquí..."></textarea><br>

                    <button class="btn btn-outline-danger" onclick="processJSON()">
                        <i class="fas fa-file"></i> <strong>Mostrar Datos</strong>
                    </button>
                </div>
                <div class="right-panel" id="jsonDisplay">
                    <h1></h1>
                </div>
            </div>
        </div>
        <!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<?php
include('/var/www/html/view/includes/footer.php');
?>

<!-- Page specific script -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Incluir los archivos de CodeMirror -->
<!-- CodeMirror CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/material.min.css">

<!-- CodeMirror JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>

<script>
    // Initialize CodeMirror on the textarea
    // CodeMirror toma el textarea con id "jsonInput" y lo reemplaza con su propio editor
    var editor = CodeMirror.fromTextArea(document.getElementById("jsonInput"), {
        mode: "application/json", // Especifica que el modo es JSON para resaltar la sintaxis
        theme: "material", // Tema visual para CodeMirror
        lineNumbers: true, // Mostrar números de línea
        tabSize: 2, // Tamaño de las tabs
        matchBrackets: true, // Resaltar los corchetes correspondientes
    });

    function processJSON() {
        // Obtener el valor del editor
        const jsonInput = editor.getValue();
        const jsonDisplay = document.getElementById('jsonDisplay');

        try {
            const data = JSON.parse(jsonInput);

            // Limpiar contenido anterior
            jsonDisplay.innerHTML = '';

            // Verificar si hay entradas en los datos
            if (data.entry && data.entry.length > 0) {
                console.log(data.entry);
                // Inicializar variables para el paciente, la organización, la composición y las alergias
                let patient = null;
                let organization = null;
                let composition = null;
                let allergies = [];
                let condition = [];

                // Recorrer las entradas
                data.entry.forEach(entry => {
                    // Verificar el tipo de recurso
                    if (entry.resource.resourceType === 'Patient') {
                        patient = entry.resource; // Guardar el paciente
                    }
                    if (entry.resource.resourceType === 'Organization') {
                        organization = entry.resource; // Guardar la organización
                    }
                    if (entry.resource.resourceType === 'Composition') {
                        composition = entry.resource; // Guardar la composición
                    }
                    if (entry.resource.resourceType === 'AllergyIntolerance') {
                        allergies.push(entry.resource); // Guardar las alergias
                    }
                    if (entry.resource.resourceType === 'Condition') {
                        condition.push(entry.resource); // Guardar las condiciones
                    }
                });

                // Si hay datos del paciente, crear una tarjeta
                if (patient && patient.name && patient.name.length > 0) {
                    const patientName = patient.name[0].text || 'No disponible';
                    const patientBirthDate = patient.birthDate || 'No disponible';

                    // Asegúrate de que birthDate esté en el formato correcto
                    const fechaNacimiento = new Date(patient.birthDate + 'T00:00:00Z'); // Añadir hora en UTC

                    // Convertimos la fecha a dd/mm/yyyy
                    const dia = String(fechaNacimiento.getUTCDate()).padStart(2, '0');
                    const mes = String(fechaNacimiento.getUTCMonth() + 1).padStart(2, '0');
                    const anio = fechaNacimiento.getUTCFullYear();
                    const fechaNacimientoFormat = `${dia}/${mes}/${anio}`;

                    // Calculamos la edad
                    const hoy = new Date();
                    const diferencia = hoy.getFullYear() - fechaNacimiento.getFullYear();
                    const edad = diferencia - (hoy < new Date(hoy.getFullYear(), fechaNacimiento.getUTCMonth(), fechaNacimiento.getUTCDate()) ? 1 : 0);

                    // Manejo de la organización, si existe
                    let organizationName = 'No disponible';
                    let organizationCountry = 'No disponible';
                    let organizationAddress = 'No disponible';

                    if (organization) {
                        organizationName = organization.name || 'No disponible';
                        organizationCountry = organization.address && organization.address.length > 0 ?
                            organization.address[0].country || 'No disponible' : 'No disponible';
                        organizationAddress = organization.address && organization.address.length > 0 ?
                            organization.address[0].text || 'No disponible' : 'No disponible';
                    }

                    // Manejo de la composición, si existe
                    let compositionDate = 'No disponible';
                    if (composition) {
                        compositionDate = composition.date || 'No disponible';
                    }

                    // Crear la tarjeta con la información del paciente
                    const patientInfoDiv = document.createElement('div');
                    patientInfoDiv.className = 'card mb-3';
                    patientInfoDiv.innerHTML = `
                <div class="card-header">
                    <h3 class="card-title">${patientName}</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body" style="display: block;">
                    <div class="row">
                        <div class="col-md-12">
                            <p><strong>Birthdate:</strong> ${patientBirthDate} (${edad || 'N/A'} años)</p>
                            <p><strong>Dominio:</strong> ${organizationCountry}</p>                             
                            <p><strong>Última actualización:</strong> ${compositionDate}</p>
                            <p><strong>Autor, Encargado:</strong> ${organizationName}</p>
                        </div>
                    </div>
                </div>`;

                    jsonDisplay.appendChild(patientInfoDiv);
                } else {
                    jsonDisplay.innerHTML = '<p>No se encontraron datos de pacientes en el JSON.</p>';
                }

                // Si hay alergias, crear una tarjeta separada
                if (allergies.length > 0) {
                    const allergyInfoDiv = document.createElement('div');
                    allergyInfoDiv.className = 'card mb-3';

                    let allergyContent = `
                <div class="card-header">
                    <h3 class="card-title">Alergias</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body" style="display: block;">`;

                    allergies.forEach((allergy, index) => {
                        const substance = allergy.code.coding[0].display || 'No disponible';
                        let status = allergy.criticality || 'No disponible';

                        const statusMap = {
                            'low': 'bajo',
                            'high': 'alto',
                            'unable-to-assess': 'incapaz de evaluar'
                        };

                        // Si el estado está en el mapa, se usa la traducción, de lo contrario 'indefinido'
                        status = statusMap[status] || 'indefinido';


                        const code = allergy.code.coding[0].code || 'No disponible';

                        allergyContent += `
                    <p>${substance}</p>
                    <p><strong>${code}</strong></p>
                    <p><strong>alergia - Criticidad:</strong> ${status} </p>
                    <hr>`;
                    });

                    allergyContent += `</div>`; // Cerrar el card-body

                    allergyInfoDiv.innerHTML = allergyContent;
                    jsonDisplay.appendChild(allergyInfoDiv);
                }
                // Si hay alergias, crear una tarjeta separada
                if (condition.length > 0) {
                    const conditionInfoDiv = document.createElement('div');
                    conditionInfoDiv.className = 'card mb-3';

                    let conditionContent = `
                <div class="card-header">
                    <h3 class="card-title">Diagnósticos / Problemas activos</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body" style="display: block;">`;

                    condition.forEach((cond, index) => {
                        const system = cond.code.coding[0].display || 'No disponible';
                        const code = cond.code.coding[0].code || 'No disponible';
                        const year = cond.onsetDateTime || 'No disponible';

                        conditionContent += `
                    <p>${system} (<strong>${code}</strong>)</p>
                    <p><strong>${year}</strong></p>                  
                    <hr>`;
                    });

                    conditionContent += `</div>`; // Cerrar el card-body

                    conditionInfoDiv.innerHTML = conditionContent;
                    jsonDisplay.appendChild(conditionInfoDiv);
                }


            } else {
                jsonDisplay.innerHTML = '<p>No se encontraron datos en el JSON.</p>';
            }

        } catch (error) {
            jsonDisplay.innerHTML = '<p style="color: red;">Error al procesar JSON. Verifica el formato.</p>';
        }
    }
</script>

<style>
    .container {
        display: flex;
        width: 100%;
        height: 100vh;
        margin: 0;
        box-sizing: border-box;
    }

    .left-panel,
    .right-panel {
        width: 50%;
        padding: 10px;
        box-sizing: border-box;
        overflow-y: auto;
    }

    .left-panel {
        border-right: 1px solid #ddd;
    }

    .right-panel {
        border-left: 1px solid #ddd;
    }

    .card {
        margin-bottom: 10px;
    }

    .card-header {
        cursor: pointer;
    }

    .card-body {
        display: none;
    }

    /* Aplicar altura específica para el editor CodeMirror */
    .CodeMirror {
        height: calc(100vh - 100px);
    }

    button {
        margin-top: 10px;
        padding: 10px 20px;
        font-size: 16px;
        cursor: pointer;
    }
</style>