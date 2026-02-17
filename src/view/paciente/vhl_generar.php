<?php include('/var/www/html/view/includes/header.php');
include('/var/www/html/core/connection.php');

// Obtener la conexión a la base de datos
$dbconn = getConnectionFHIR();

//Buscamos si tenemos el header
$sql = "SELECT name, alpha_2, alpha_3 FROM public.country;";
$stmt = $dbconn->prepare($sql);
$stmt->execute();
$countries = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row justify-content-center ">
                <div class="col-sm-6 text-center">
                    <button type="button" class="btn btn-outline-danger mr-2 col-sm-3 mb-3 active"><strong>Generar
                            VHL</strong></button>
                    <a href="/paciente/vhl_ver" class="btn btn-outline-danger col-sm-3 mb-3">
                        <strong>Ver VHL</strong>
                    </a>
                    <h1><strong>Generar VHL</strong></h1>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <!-- Filtros -->
                    <div class="card card-danger">
                        <div class="card-header">
                            <div class="float-left">
                                <h3 class="card-title" style="display: inline-block;"><strong>Paciente</strong></h3>
                            </div>
                            <div class="float-right">
                                <div id="countdown2" style="font-size: 2em; "></div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <div class="row justify-content-center  ">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <div class="row">
                                                        <div class="col-sm-6">
                                                            <div class="form-group">
                                                                <label for="id" class="font-size-14">ID del
                                                                    Bundle</label><br>
                                                                <input type='text' id="id" name="id" placeholder=""
                                                                    class="form-control font-size-14"
                                                                    autocomplete="off" />
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-6">
                                                            <div class="form-group">
                                                                <label for="identifier" class="font-size-14">Servidor
                                                                    FHIR</label><br>
                                                                <select class="form-control select2bs4"
                                                                    style="width: 100%; height: 40px;" id="active"
                                                                    name="active">
                                                                    <option value="">Seleccione</option>
                                                                    <option selected value="1">https://fhir.mspbs.gov.py
                                                                    </option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row justify-content-center mt-3 ">
                                                        <div class="container-fluid">
                                                            <div class="row justify-content-center ">
                                                                <div class="col-sm-6 text-center">
                                                                    <button id="filtrar" class="btn btn-outline-danger">
                                                                        <strong>Buscar</strong> &nbsp;<i
                                                                            class="fa fa-search"></i>
                                                                    </button>
                                                                    <div id="loading" class="col-sm-12 text-center hide"
                                                                        style="display: none;">
                                                                        <i class="fa fa-spinner fa-spin"></i> Procesando
                                                                        consulta
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                </div><!-- /.form-group -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div><!-- /.row -->
                        </div><!-- /.card-body -->
                    </div><!-- /.card -->

                    <div class="card card-danger" id="datos" style="display: none;">
                        <div class="card-header mb-3">
                            <h3 class="card-title"><strong>Selecciona los recursos que se incluirán en el VHL</strong>
                            </h3>
                            <div id="countdown2" class="float-right" style="font-size: 2em;"></div>
                        </div>
                        <div style="padding: 0 90px;">
                            <div class="text-right mb-2">
                                <button id="selectAll" class="btn btn-sm btn-outline-primary mr-1" style="margin-bottom:5px;">
                                    <i class="fa fa-check-square"></i> Seleccionar todo
                                </button>
                                <button id="deselectAll" class="btn btn-sm btn-outline-secondary" style="margin-bottom:5px;">
                                    <i class="fa fa-square"></i> Deseleccionar todo
                                </button>
                            </div>
                        </div>

                        <div class="card-body p-0 mb-0" id="resourceContainer" style="padding: 15px 30px; margin-left: 10px; margin-right: 10px;">
                            <!-- Aquí se cargarán las tarjetas dinámicamente -->
                        </div>

                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <div class="row justify-content-center  ">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <div class="row">
                                                        <div class="col-sm-6">
                                                            <div class="form-group">
                                                                <label for="codigo_acceso" class="font-size-14">Código
                                                                    de acceso</label><br>
                                                                <input type='text' id="codigo_acceso"
                                                                    name="codigo_acceso" placeholder=""
                                                                    class="form-control font-size-14"
                                                                    autocomplete="off" />
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-6">
                                                            <div class="form-group">
                                                                <label for="periodo" class="font-size-14">Periodo de
                                                                    validez</label><br>
                                                                <select class="form-control select2bs4"
                                                                    style="width: 100%; height: 40px;" id="periodo"
                                                                    name="periodo">
                                                                    <option value="">Seleccione</option>
                                                                    <option value="1">1 dia</option>
                                                                    <option value="2">1 semana</option>
                                                                    <option value="3">1 mes</option>
                                                                    <option value="4">Sin expiracion</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row justify-content-center mt-3 ">
                                                        <div class="container-fluid">
                                                            <div class="row justify-content-center ">
                                                                <div class="col-sm-6 text-center">
                                                                    <button type="button" id="generarVHLButton"
                                                                        class="btn btn-outline-danger col-sm-3 ">
                                                                        <strong> Generar VHL </strong></button>
                                                                    <div id="loading" class="col-sm-12 text-center hide"
                                                                        style="display: none;">
                                                                        <i class="fa fa-spinner fa-spin"></i> Procesando
                                                                        consulta
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                </div><!-- /.form-group -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div><!-- /.row -->
                        </div><!-- /.card-body -->
                    </div><!-- /#datos -->

                    <div class="card card-danger" id="datosQR" style="display: none;">
                        <div class="card-header">
                            <div class="float-left">
                                <h3 class="card-title"><strong>Resultado</strong></h3>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="row">
                                <!-- 🟢 Columna izquierda: QR + Base64 + Botón -->
                                <div class="col-md-6 text-center" style="border-right: 1px solid #ccc;">
                                    <h5 style="color:#dc3545;"><strong>QR generado</strong></h5>

                                    <!-- Contenedor QR -->
                                    <div id="imageQR"
                                        style="display:flex; justify-content:center; align-items:center; margin:15px 0; max-width:100%; overflow:auto;">
                                    </div>

                                    <!-- Texto Base64 debajo del QR -->
                                    <div class="card mt-3"
                                        style="border-color: orange; border-width: 1px; border-style: solid;">
                                        <div style="margin:10px; background-color:#f8f9fa; max-height:150px; overflow-y:auto;">
                                            <pre id="codigo"
                                                style="white-space: pre-wrap; word-wrap: break-word; font-size:12px;"></pre>
                                        </div>
                                    </div>

                                    <!-- Botón descargar -->
                                    <div class="mt-3">
                                        <button type="button" id="descargarQrVhl" class="btn btn-outline-danger col-sm-8">
                                            <strong>Descargar QR</strong>
                                        </button>
                                    </div>
                                </div>

                                <!-- 🔵 Columna derecha: JSON legible -->
                                <div class="col-md-6 text-center">
                                    <h5 style="color:#dc3545;"><strong>JSON generado</strong></h5>
                                    <div class="row justify-content-center mt-4">
                                        <div class="col-sm-10">
                                            <div class="card" style="border: 1px solid #ccc;">
                                                <div class="card-header" style="background-color: #f8f9fa;">
                                                    <strong>Vista previa del JSON generado</strong>
                                                    <span class="incluye-badge" style="float:right; color:#666; font-size:13px;"></span>
                                                </div>
                                                <div class="card-body" style="max-height: 610px; overflow-y: auto; background-color: #f4f4f4;">
                                                    <pre id="jsonPreview" style="white-space: pre-wrap; word-wrap: break-word; font-size: 13px;"></pre>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div><!-- /.row -->
                        </div><!-- /.card-body -->
                    </div><!-- /#datosQR -->

                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<?php
include('/var/www/html/view/includes/footer.php');
?>

<script>
    let bundleData = {}; // JSON completo del bundle

    $(function() {
        // Inicializar Select2
        $('.select2bs4').select2({
            theme: 'bootstrap4'
        });

        // Manejar errores AJAX
        function handleAjaxError(xhr, textStatus) {
            if (textStatus === "timeout") toastr.warning('Error: Tiempo de espera agotado.');
            else toastr.warning('Error: No se pudo comunicar con el servidor.');
        }

        function clearResults() {
            // Limpieza visual de resultados previos
            $('#imageQR').empty();
            $('#codigo').empty();
            $('#jsonPreview').empty();
            $('#datosQR').hide();
            // Quitar resumen previo "Incluye ..."
            $('.incluye-badge').text('');
        }

        // === FUNCIÓN PRINCIPAL PARA CARGAR LOS RECURSOS ===
        function loadResources(id) {
            $('#datos').show();
            $('#resourceContainer').empty(); // limpiar antes de cargar
            clearResults();

            $.ajax({
                url: '../../backend/services/generate_vhl.php',
                method: 'GET',
                data: {
                    id: id
                },
                dataType: 'json',
                success: function(data) {
                    bundleData = data;

                    // Buscar el paciente
                    const patientData = data.entry.find(i => i.resource.resourceType.toLowerCase() === 'patient');
                    if (patientData) renderPatient(patientData.resource);
                    else toastr.warning('No se encontró el recurso Patient.');

                    // Buscar la Composition
                    //   const compositionData = data.entry.find(i => i.resource.resourceType.toLowerCase() === 'composition');
                    // if (compositionData) renderComposition(compositionData.resource);
                    //else toastr.warning('No se encontró el recurso Composition.');

                    // Tipos de recursos a mostrar
                    const resourceTypes = [
                        'Condition',
                        'AllergyIntolerance',
                        'Medication',
                        'Procedure',
                        'Immunization'
                    ];

                    // Colores y emojis por tipo
                    const typeStyles = {
                        Condition: {
                            color: '#dc3545',
                            emoji: '🩺'
                        },
                        AllergyIntolerance: {
                            color: '#fd7e14',
                            emoji: '⚠️'
                        },
                        Medication: {
                            color: '#17a2b8',
                            emoji: '💊'
                        },
                        Procedure: {
                            color: '#6f42c1',
                            emoji: '🔧'
                        },
                        Immunization: {
                            color: '#28a745',
                            emoji: '💉'
                        },
                    };

                    // Mostrar cada tipo existente (con append único por tipo)
                    resourceTypes.forEach(type => {
                        const filtered = data.entry.filter(
                            item => item.resource.resourceType.toLowerCase() === type.toLowerCase()
                        );
                        if (filtered.length > 0) renderResource(type, filtered, typeStyles[type]);
                    });
                },
                error: handleAjaxError
            });
        }

        // === PACIENTE SIN CHECKBOX ===
        function renderPatient(patient) {
            const container = $('#resourceContainer');
            container.append(`<hr><h5 style="text-align:center; color:#007bff;">👤 Paciente</h5>`);

            const name =
                patient.name?.[0]?.text ||
                `${patient.name?.[0]?.given?.join(' ') || ''} ${patient.name?.[0]?.family || ''}`;
            const gender = patient.gender || 'No especificado';
            const birthDate = patient.birthDate || 'Fecha no disponible';

            const card = `
              <div class="resource-card mb-3 p-3"
                   style="border:2px solid #007bff; border-radius:15px; box-shadow:0 0 5px rgba(0,0,0,0.15);
                          transition:0.2s ease-in-out; cursor:default;
                          display:flex; justify-content:space-between; align-items:center;
                          padding:15px 20px; margin:0 15px;">
                <div style="width:100%;">
                  <p style="color:#555; margin:0; font-size:15px;">
                    <strong>${name}</strong><br>
                    Género: ${gender} — Nacimiento: ${birthDate}
                  </p>
                </div>
              </div>
            `;
            container.append(card);
        }

        // === COMPOSITION SIN CHECKBOX ===
        function renderComposition(composition) {
            const container = $('#resourceContainer');
            container.append(`<hr><h5 style="text-align:center; color:#6f42c1;">📄 Composición</h5>`);

            const title = composition.title || composition.type?.coding?.[0]?.display || composition.type?.text || 'Sin título';
            const status = composition.status || 'estado no disponible';
            const date = composition.date || composition.meta?.lastUpdated || 'fecha no disponible';

            const card = `
                <div class="resource-card mb-3 p-3"
                     style="border:2px solid #6f42c1; border-radius:15px; box-shadow:0 0 5px rgba(0,0,0,0.15);
                            transition:0.2s ease-in-out; cursor:default;
                            display:flex; justify-content:space-between; align-items:center;
                            padding:15px 20px; margin:0 15px;">
                  <div style="width:100%;">
                    <p style="color:#555; margin:0; font-size:15px;">
                      <strong>${title}</strong><br>
                      Estado: ${status} — Fecha: ${date}
                    </p>
                  </div>
                </div>
            `;
            container.append(card);
        }

        // === RENDER GENÉRICO PARA CUALQUIER RECURSO CON CHECKBOX (append único por tipo) ===
        function renderResource(type, data, style) {
            const container = $('#resourceContainer');
            const {
                color,
                emoji
            } = style;

            let html = '';
            html += `<hr><h5 style="text-align:center; color:${color};">${emoji} ${type}</h5>`;

            data.forEach((item, index) => {
                const resource = item.resource;
                const code =
                    resource.code?.coding?.[0]?.code ||
                    resource.medicationCodeableConcept?.coding?.[0]?.code ||
                    resource.vaccineCode?.coding?.[0]?.code ||
                    'Código no disponible';

                const display =
                    resource.code?.coding?.[0]?.display ||
                    resource.medicationCodeableConcept?.coding?.[0]?.display ||
                    resource.vaccineCode?.coding?.[0]?.display ||
                    resource.code?.text ||
                    'Descripción no disponible';

                html += `
                    <div class="resource-card mb-2 p-3"
                         style="border:2px solid ${color}; border-radius:15px; box-shadow:0 0 5px rgba(0,0,0,0.15);
                                transition:0.2s ease-in-out; display:flex; align-items:center; justify-content:space-between;
                                padding:15px 20px; margin:0 15px;">
                      <div style="flex-grow:1;">
                        <h6 style="color:${color}; margin-bottom:5px;">${type}</h6>
                        <p style="color:#555; margin:0;">${code} — ${display}</p>
                      </div>
                      <div style="display:flex; align-items:center; justify-content:center; width:50px;">
                        <input class="form-check-input resource-checkbox" type="checkbox"
                               id="${type.toLowerCase()}_${index}" data-id="${item.fullUrl}"
                               style="width:22px; height:22px; cursor:pointer; transform:scale(1.3);">
                      </div>
                    </div>
                `;
            });

            container.append(html);
        }

        // === GENERAR VHL Y QR ===
        $('#generarVHLButton').on('click', function() {
            const passCode = $('#codigo_acceso').val().trim();
            const periodo = $('#periodo').val();

            // Limpieza visual previa
            clearResults();

            // Validaciones
            if (passCode === '') {
                toastr.warning('Debe completar el código de acceso.');
                return;
            }
            if (!periodo) {
                toastr.warning('Debe seleccionar un periodo de validez.');
                return;
            }

            // Recolectar recursos seleccionados (check)
            const selectedResources = $('#resourceContainer input[type="checkbox"]:checked')
                .map(function() {
                    const id = $(this).data('id');
                    return bundleData.entry.find(item => item.fullUrl === id);
                })
                .get();

            if (selectedResources.length === 0) {
                toastr.warning('Debe seleccionar al menos un recurso.');
                return;
            }

            // Asegurar que Patient y Composition estén siempre incluidos
            const ensureFixed = (typeLower) => {
                const fixed = bundleData.entry.find(
                    item => item.resource.resourceType.toLowerCase() === typeLower
                );
                if (fixed && !selectedResources.some(r => r.fullUrl === fixed.fullUrl)) {
                    selectedResources.unshift(fixed);
                }
            };
            ensureFixed('patient');
            ensureFixed('composition');



            // === 🔧 Filtrar y completar Composition según selección ===
            const compositionItem = bundleData.entry.find(item => item.resource.resourceType.toLowerCase() === 'composition');

            if (compositionItem && compositionItem.resource.section) {
                const selectedIds = selectedResources.map(r => r.fullUrl);

                // Mantener secciones originales solo si tienen referencias seleccionadas
                let filteredSections = compositionItem.resource.section
                    .filter(section =>
                        section.entry && section.entry.some(e => selectedIds.includes(e.reference))
                    )
                    .map(section => ({
                        ...section,
                        entry: section.entry.filter(e => selectedIds.includes(e.reference))
                    }));

                // Detectar tipos de recursos seleccionados sin sección correspondiente
                const existingRefs = filteredSections.flatMap(s => (s.entry || []).map(e => e.reference));
                const missingResources = selectedResources.filter(r => !existingRefs.includes(r.fullUrl));

                // Generar secciones nuevas si faltan
                missingResources.forEach(r => {
                    const type = r.resource.resourceType;
                    const newSection = {
                        title: type,
                        code: {
                            coding: [{
                                system: "http://loinc.org",
                                code: type === "Condition" ? "11450-4" : type === "Medication" ? "10160-0" : type === "Immunization" ? "11369-6" : type === "Procedure" ? "47519-4" : "26436-6",
                                display: `${type} Section`
                            }]
                        },
                        text: {
                            status: "generated",
                            div: `<div xmlns="http://www.w3.org/1999/xhtml">Sección generada para ${type}.</div>`
                        },
                        entry: [{
                            reference: r.fullUrl
                        }]
                    };
                    filteredSections.push(newSection);
                });

                // Actualizar el Composition
                compositionItem.resource.section = filteredSections;
            }

            // 🔹 Crear bundle final con Composition actualizado
            const filteredBundle = {
                ...bundleData,
                entry: selectedResources
            };


            // Vista previa coloreada por tipo
            const groupedByType = selectedResources.reduce((acc, item) => {
                const type = item.resource.resourceType || "Desconocido";
                if (!acc[type]) acc[type] = [];
                acc[type].push(item.resource);
                return acc;
            }, {});

            const typeStyles = {
                Condition: {
                    color: "#dc3545",
                    emoji: "🩺"
                },
                AllergyIntolerance: {
                    color: "#fd7e14",
                    emoji: "⚠️"
                },
                Medication: {
                    color: "#17a2b8",
                    emoji: "💊"
                },
                Procedure: {
                    color: "#6f42c1",
                    emoji: "🔧"
                },
                Immunization: {
                    color: "#28a745",
                    emoji: "💉"
                },
                Composition: {
                    color: "#6f42c1",
                    emoji: "📄"
                },
                Patient: {
                    color: "#007bff",
                    emoji: "👤"
                },
                default: {
                    color: "#333",
                    emoji: "📄"
                }
            };

            // === 🔢 Ordenar los tipos en el orden deseado ===
            // Orden específico que queremos mantener
            const customOrder = ["Patient", "Composition", "Condition", "AllergyIntolerance", "Medication", "Procedure", "Immunization"];

            const orderedKeys = Object.keys(groupedByType).sort((a, b) => {
                const aIndex = customOrder.indexOf(a);
                const bIndex = customOrder.indexOf(b);
                // Si ambos están en la lista, orden por índice
                if (aIndex !== -1 && bIndex !== -1) return aIndex - bIndex;
                // Si solo uno está, ese va primero
                if (aIndex !== -1) return -1;
                if (bIndex !== -1) return 1;
                // Si ninguno está, ordenar alfabéticamente
                return a.localeCompare(b);
            });


            let formattedOutput = "";
            orderedKeys.forEach(type => {
                const resources = groupedByType[type];
                const {
                    color,
                    emoji
                } = typeStyles[type] || typeStyles.default;
                formattedOutput += `<h5 style="color:${color}; font-weight:bold; margin-top:10px;">${emoji} ${type}</h5>`;
                resources.forEach(resource => {
                    formattedOutput += `<pre style="background:#f9f9f9; border-left:4px solid ${color}; padding:8px; font-size:13px;">${JSON.stringify(resource, null, 2)}</pre>`;
                });
            });

            $('#jsonPreview').html(formattedOutput);
            toastr.info('Vista previa del JSON actualizada con colores.');

            // Resumen (limpiar el anterior y colocar el nuevo)
            const summary = selectedResources.reduce((acc, item) => {
                const type = item.resource.resourceType || 'Desconocido';
                acc[type] = (acc[type] || 0) + 1;
                return acc;
            }, {});
            const summaryText = Object.entries(summary)
                .map(([type, count]) => `${count} × ${type}`)
                .join(', ');
            $('.incluye-badge').text(`Incluye: ${summaryText}`);

          

            filteredBundle.entry.sort((a, b) => {
                const aType = a.resource.resourceType;
                const bType = b.resource.resourceType;

                const aIndex = customOrder.indexOf(aType);
                const bIndex = customOrder.indexOf(bType);

                if (aIndex === -1 && bIndex === -1) return aType.localeCompare(bType);
                if (aIndex === -1) return 1;
                if (bIndex === -1) return -1;
                return aIndex - bIndex;
            });

            // === 🧾 Generar JSON formateado ===
            const jsonContent = JSON.stringify(filteredBundle, null, 2);

            // Calcular expiración
            const fechaActual = new Date();
            let expiresOn = '';
            if (periodo === '1') fechaActual.setDate(fechaActual.getDate() + 1);
            else if (periodo === '2') fechaActual.setDate(fechaActual.getDate() + 7);
            else if (periodo === '3') fechaActual.setDate(fechaActual.getDate() + 30);
            if (periodo !== '4') expiresOn = fechaActual.toISOString();

            // Datos finales a enviar
            const dataToSend = {
                expiresOn,
                jsonContent,
                passCode
            };

            console.log('🧾 JSON que se enviará:', dataToSend);
            if (expiresOn) console.log('Expira el:', new Date(expiresOn).toLocaleString());


            // Enviar y generar QR
            $.ajax({
                url: '../../backend/services/proxy_vhl.php',
                method: 'POST',
                data: JSON.stringify(dataToSend),
                contentType: 'application/json',
                success: function(response) {
                    if (!response || response.error) {
                        toastr.error('Error del servidor al generar el QR.');
                        console.log('Respuesta del servidor (error):', response);
                        return;
                    }

                    $('#datosQR').show();
                    const qrData = response.base64Image;

                    const qrCodeElement = document.getElementById('imageQR');
                    qrCodeElement.innerHTML = '';
                    const qrCode = new QRCode(qrCodeElement, {
                        text: qrData,
                        width: 500,
                        height: 500,
                        margin: 4
                    });

                    setTimeout(() => {
                        const canvas = qrCodeElement.querySelector('canvas');
                        if (!canvas) return;
                        const newCanvas = document.createElement('canvas');
                        const ctx = newCanvas.getContext('2d');
                        const border = 20;
                        newCanvas.width = canvas.width + border * 2;
                        newCanvas.height = canvas.height + border * 2;
                        ctx.fillStyle = '#fff';
                        ctx.fillRect(0, 0, newCanvas.width, newCanvas.height);
                        ctx.drawImage(canvas, border, border);

                        const imgData = newCanvas.toDataURL('image/png');
                        $('#descargarQrVhl').off('click').on('click', function() {
                            const link = document.createElement('a');
                            link.href = imgData;
                            link.download = 'VHL-QR.png';
                            link.click();
                        });
                    }, 300);

                    $('#codigo').text(qrData);
                    toastr.success('VHL generado correctamente.');
                },
                error: function() {
                    toastr.error('Error al generar el VHL.');
                }
            });
        });

        // === BOTONES SELECCIONAR / DESELECCIONAR ===
        $('#selectAll').on('click', () => {
            $('.resource-checkbox').prop('checked', true);
            toastr.info('Todos los recursos han sido seleccionados.');
        });

        $('#deselectAll').on('click', () => {
            $('.resource-checkbox').prop('checked', false);
            toastr.info('Todos los recursos han sido desmarcados.');
        });

        // === BOTÓN BUSCAR ===
        $('#filtrar').on('click', function() {
            const id = $('#id').val();
            if (!id || !id.trim()) {
                toastr.warning('Debe ingresar un ID de Bundle.');
                return;
            }
            loadResources(id.trim());
        });
    });
</script>