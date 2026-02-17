<?php
include('/var/www/html/view/includes/header.php');
include('/var/www/html/core/connection.php');

// Obtener la conexión a la base de datos
$dbconn = getConnectionFHIR();
$codigo_usuario = $_SESSION['idUsuario'];

// Consulta para obtener los hospitales
$sql = "SELECT id_establecimiento, nombre FROM establecimiento2025;";
$stmt = $dbconn->prepare($sql);
$stmt->execute();
$hospitals = $stmt->fetchAll(PDO::FETCH_ASSOC);




?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <section class="content-header">
    <div class="container-fluid">
  <div class="row mb-2">
    <div class="col-sm-12 text-center">
      <h1><strong>Consulta Ambulatoria(ITI-65) – Envío de Documento Clínico</strong></h1>
      <p class="text-muted mb-0">
        Transacción <code>Provide Document Bundle</code> del perfil 
        <strong>Mobile Access to Health Documents (MHD)</strong>.
      </p>     
    </div>
  </div>
</div><!-- /.container-fluid -->

  </section>

  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">
      <div class="row">
        <!-- left column -->
        <div class="col-md-12">
          <form id="form" class="user">
            <!-- general form elements -->
            <div class="card shadow-sm border-0">
  <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
    <h2 class="card-title mb-0 h4">
      <i class="fas fa-user-md me-2"></i>
      <strong>Datos del Médico actuante en el acto clínico</strong>
    </h2>
    <div id="countdown2" class="fs-4 fw-bold" role="timer" aria-label="Tiempo restante"></div>
  </div>

  <div class="card-body bg-light">
    <!-- Sección de datos del médico -->
    <section aria-labelledby="datos-medico-heading">
      <h3 id="datos-medico-heading" class="h5 text-secondary mb-3">Información del profesional</h3>
      
      <div class="row g-3 mb-3">
        <div class="col-lg-4 col-md-6">
          <label for="id_pasaporte" class="form-label fw-semibold">
            Documento de identidad
            <span class="text-danger" aria-hidden="true">*</span>
          </label>
          <div class="input-group">
            <input type="text" 
                   class="form-control rounded-start" 
                   id="id_pasaporte" 
                   name="id_pasaporte" 
                   placeholder="Ingrese número de documento"
                   aria-describedby="search-doc-help"
                   >
            <input type="hidden" name="id_medico" id="id_medico">
            <button id="getDatosProfesional" 
                    type="button" 
                    class="btn btn-danger d-flex align-items-center" 
                    aria-label="Buscar datos del profesional">
              <i class="fas fa-search me-1" aria-hidden="true"></i>
              <span class="d-none d-sm-inline">Buscar</span>
            </button>
          </div>        
        </div>

        <div class="col-lg-4 col-md-6">
          <label for="nombre_medico" class="form-label fw-semibold">Nombre</label>
          <input type="text" 
                 class="form-control" 
                 id="nombre_medico" 
                 name="nombre_medico" 
                 readonly
                 aria-describedby="nombre-help">        
        </div>

        <div class="col-lg-4 col-md-6">
          <label for="apellido_medico" class="form-label fw-semibold">Apellido</label>
          <input type="text" 
                 class="form-control" 
                 id="apellido_medico" 
                 name="apellido_medico" 
                 readonly
                 aria-describedby="apellido-help">       
        </div>
      </div>
    </section>

    <hr class="my-4" aria-hidden="true">

    <!-- Sección de lugar de atención -->
    <section aria-labelledby="lugar-atencion-heading">
      <h3 id="lugar-atencion-heading" class="h5 text-danger mb-3">
        <i class="fas fa-hospital me-2"></i>
        <strong>Datos del lugar de atención</strong>
      </h3>

      <div class="row g-3">
        <div class="col-lg-5 col-md-6">
          <label for="id_establecimiento" class="form-label fw-semibold">
            Centro de atención
            <span class="text-danger" aria-hidden="true">*</span>
          </label>
          <input type="hidden" name="id_servicio" id="id_servicio">
          <select class="form-control" 
                  id="id_establecimiento" 
                  name="id_establecimiento" 
                  aria-describedby="centro-help"
                  >
            <option value="" selected disabled>Seleccione un centro</option>
            <?php
            foreach ($hospitals as $hospital) {
              echo '<option value="' . $hospital['id_establecimiento'] . '">' . $hospital['nombre'] . '</option>';
            }
            ?>
          </select>
       
        </div>
        
        <div class="col-lg-3 col-md-6">
          <label for="tipo" class="form-label fw-semibold">Tipo de establecimiento</label>
          <input type="text" 
                 class="form-control" 
                 id="tipo" 
                 name="tipo" 
                 readonly
                 aria-describedby="tipo-help">
       
        </div>
        
        <div class="col-lg-4 col-md-6">
          <label for="fecha_atencion" class="form-label fw-semibold">
            Fecha de atención
            <span class="text-danger" aria-hidden="true">*</span>
          </label>
          <input type="date" 
                 class="form-control" 
                 id="fecha_atencion" 
                 name="fecha_atencion"
                 
                    max="<?php echo date('Y-m-d'); ?>"
                       value="<?php echo date('Y-m-d'); ?>">
        </div>
      </div>
    </section>
  </div> 
</div>

            <!-- DATOS DEL/LA PACIENTE -->
           <div class="card card-danger shadow-sm border-0">
  <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center py-3">
    <div>
      <h2 class="card-title h4 mb-0">
        <i class="fas fa-user-injured me-2"></i>
        <strong>DATOS DEL/LA PACIENTE</strong>
      </h2>
    </div>
    <div id="countdown2" class="fs-2 fw-bold" role="timer" aria-label="Tiempo restante para completar el formulario"></div>
  </div>
  
  <div class="card-body bg-light">
    <section aria-labelledby="datos-paciente-heading">
      <h3 id="datos-paciente-heading" class="h5 text-secondary mb-4 d-none">Información personal del paciente</h3>
      
      <!-- Fila 1: Documento de identidad -->
      <div class="row g-3 mb-4">
        <div class="col-lg-6 col-md-12">
          <label for="tipo_documento" class="form-label fw-semibold">
            Tipo de Documento de Identidad
            <span class="text-danger" aria-hidden="true">*</span>
          </label>
          <select class="form-control" id="tipo_documento" name="tipo_documento"  aria-describedby="tipo-doc-help">
            <option value="" selected disabled>Seleccione el tipo de documento</option>
            <option value="01">Cédula de Identidad</option>
            <option value="02">Cédula Extranjera</option>
            <option value="03">Pasaporte</option>
          </select>   
        </div>
        
        <div class="col-lg-6 col-md-12">
          <label for="cedula" class="form-label fw-semibold">
            Número de Documento
            <span class="text-danger" aria-hidden="true">*</span>
          </label>
          <div class="input-group">
            <input type="text" 
                   class="form-control rounded-start" 
                   name="cedula" 
                   id="cedula" 
                   placeholder="Ingrese el número de documento"
                   aria-describedby="buscar-paciente-help"
                   
                   pattern="[0-9]+"
                   minlength="6">
            <input type="hidden" name="id_paciente" id="id_paciente">
            <button id="getDatosCedula" 
                    type="button" 
                    class="btn btn-danger d-flex align-items-center"
                    aria-label="Buscar datos del paciente por documento">
              <i class="fas fa-search me-1" aria-hidden="true"></i>
              <span class="d-none d-sm-inline">Buscar</span>
            </button>
          </div>      
        </div>
      </div>

      <!-- Fila 2: Nombre y Apellido -->
      <div class="row g-3 mb-4">
        <div class="col-lg-6 col-md-12">
          <label for="nombre_paciente" class="form-label fw-semibold">Nombre</label>
          <div class="input-group">
            <span class="input-group-text bg-light">
              <i class="fas fa-id-card text-muted"></i>
            </span>
            <input type="text" 
                   class="form-control" 
                   name="nombre_paciente" 
                   id="nombre_paciente" 
                   readonly
                   aria-describedby="nombre-paciente-help">
          </div>
          <div id="nombre-paciente-help" class="form-text">Nombre autocompletado a partir del documento</div>
        </div>
        
        <div class="col-lg-6 col-md-12">
          <label for="apellido_paciente" class="form-label fw-semibold">Apellido</label>
          <div class="input-group">
            <span class="input-group-text bg-light">
              <i class="fas fa-id-card text-muted"></i>
            </span>
            <input type="text" 
                   class="form-control" 
                   name="apellido_paciente" 
                   id="apellido_paciente" 
                   readonly
                   aria-describedby="apellido-paciente-help">
          </div>      
        </div>
      </div>

      <!-- Fila 3: Fecha de nacimiento y Sexo -->
      <div class="row g-3">
        <div class="col-lg-6 col-md-12">
          <label for="fecha_nacimiento_paciente" class="form-label fw-semibold">Fecha de Nacimiento</label>
          <div class="input-group">
            <span class="input-group-text bg-light">
              <i class="fas fa-birthday-cake text-muted"></i>
            </span>
            <input type="text" 
                   class="form-control" 
                   name="fecha_nacimiento_paciente" 
                   id="fecha_nacimiento_paciente" 
                   readonly
                   aria-describedby="fecha-nacimiento-help">
          </div>     
        </div>
        
        <div class="col-lg-6 col-md-12">
          <div class="form-group">
            <label for="sexo_paciente" class="form-label fw-semibold">Sexo</label>
            <div class="input-group">
              <span class="input-group-text bg-light">
                <i class="fas fa-venus-mars text-muted"></i>
              </span>
              <input type="text" 
                     class="form-control" 
                     name="sexo_paciente" 
                     id="sexo_paciente" 
                     readonly
                     aria-describedby="sexo-paciente-help">
            </div>       
          </div>
        </div>
      </div>
    </section>
  </div>
</div>

            <!-- Diagnosticos -->
           <div class="card card-danger shadow-sm border-0">
  <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center py-3">
    <div>
      <h2 class="card-title h4 mb-0">
        <i class="fas fa-stethoscope me-2"></i>
        <strong>Diagnósticos CIE-10</strong>
      </h2>
    </div>
    <div id="countdown2" class="fs-2 fw-bold" role="timer" aria-label="Tiempo restante para completar los diagnósticos"></div>
  </div>
  
  <div class="card-body bg-light">
    <section aria-labelledby="diagnosticos-heading">
      <h3 id="diagnosticos-heading" class="h5 text-secondary mb-4 d-none">Registro de diagnósticos médicos</h3>
      


      <div class="table-responsive">
        <table id="cie10Table" class="table table-bordered table-hover align-middle" aria-describedby="table-description">      
          <thead class="table-light">
            <tr  style="background-color: #F1E1FF;">
              <th scope="col" width="12%" class="ps-3">Código CIE-10</th>
              <th scope="col" width="35%">Nombre del Diagnóstico</th>
              <th scope="col" width="12%">Fecha</th>
              <th scope="col" width="12%">Estado</th>
              <th scope="col" width="24%">Nota Adicional</th>
              <th scope="col" width="5%" class="text-center">Acciones</th>
            </tr>
          </thead>
          <tbody id="cieTable_tbody">
            <tr>
              <td>
                <input type="text" 
                       name="codigoCIE10[]" 
                       class="form-control textupper labitemcod10" 
                       placeholder="Ej: A00.0"
                       aria-label="Código CIE-10"
                       maxlength="10"
                       pattern="[A-Z][0-9]{2}(\.[0-9])?"
                       title="Formato: Letra seguida de dos números y opcionalmente punto y un número (Ej: A00.0)">
              </td>
              <td>
                <input type="text" 
                       name="labitemName10[]" 
                       class="form-control textupper labitemname10" 
                       placeholder="Descripción del diagnóstico"
                       aria-label="Nombre del diagnóstico CIE-10">
              </td>
              <td>
                <input type="date" 
                       name="fechaCIE10[]" 
                       class="form-control labitemfecha10" 
                       aria-label="Fecha del diagnóstico"
                       max="<?php echo date('Y-m-d'); ?>">
              </td>
              <td>
                <select class="form-select labitemestado10" 
                        name="estadoCIE10[]" 
                        aria-label="Estado del diagnóstico">
                  <option value="confirmed" selected>Confirmado</option>
                  <option value="provisional">Provisional</option>
                  <option value="refuted">Refutado</option>
                </select>
              </td>
              <td>
                <input type="text" 
                       name="notaCIE10[]" 
                       class="form-control textupper labitemnota10" 
                       placeholder="Observaciones adicionales"
                       aria-label="Nota adicional del diagnóstico"
                       maxlength="200">
              </td>             
              <td   class="text-center"><i title="removeRow" style="cursor:pointer;font-size:16px;" class="fa fa-times rmLabRow2" aria-hidden="true"></i></td>
            </tr>
          </tbody>
        </table>
      </div>     
    </section>
  </div>
  

</div>




            <!-- Alergias -->
           <div class="card card-danger shadow-sm border-0">
  <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center py-3">
    <div>
      <h2 class="card-title h4 mb-0">
        <i class="fas fa-allergies me-2"></i>
        <strong>Registro de Alergias</strong>
      </h2>
    </div>
    <div id="countdown2" class="fs-2 fw-bold" role="timer" aria-label="Tiempo restante para completar el registro de alergias"></div>
  </div>
  
  <div class="card-body bg-light">
    <section aria-labelledby="alergias-heading">
      <h3 id="alergias-heading" class="h5 text-secondary mb-4 d-none">Registro de alergias del paciente</h3>   
    

      <div class="table-responsive">
        <table id="labItemAlergia" class="table table-bordered table-hover align-middle" aria-describedby="table-description-alergias">       
          <thead class="table-light">
            <tr   style="background-color: #F1E1FF;">
              <th scope="col" width="25%" class="ps-3">Código de Alergia</th>
              <th scope="col" width="45%">Nombre de la Alergia</th>
              <th scope="col" width="25%">Tipo de Alergia</th>
              <th scope="col" width="5%" class="text-center">Acciones</th>
            </tr>
          </thead>
          <tbody id="labTbodyAlergia">
            <tr>
              <td>
                <input type="text" 
                       id="labItemNo1" 
                       name="codigoAlergia[]" 
                       class="form-control textupper labitemcodigo" 
                       placeholder="Ej: ALG001"
                       aria-label="Código de la alergia"
                       >
              </td>
              <td>
                <input type="text" 
                       id="labitem1" 
                       name="labitem[]" 
                       class="form-control textupper labitemname" 
                       placeholder="Ej: Penicilina, Maní, Polen de abedul"
                       aria-label="Nombre de la alergia"                      
                       >
              </td>
              <td>
                <select class="form-select textupper labitemtype" 
                        id="tipoAlergia1" 
                        name="tipoAlergia[]" 
                        aria-label="Tipo de alergia">
                  <option value="" selected disabled>Seleccione el tipo</option>
                  <option value="OTRO">Alergias inespecíficas / anafilaxia</option>
                  <option value="ALIMENTO">Alimentarias</option>
                  <option value="MEDICAMENTO">Medicamentos / vacunas</option>
                  <option value="RESPIRATORIA">Respiratorias</option>
                  <option value="ANIMAL">Animales / picaduras</option>
                </select>
              </td>             
                <td   class="text-center"><i title="removeRow" style="cursor:pointer;font-size:16px;" class="fa fa-times rmLabRow" aria-hidden="true"></i></td>
            </tr>
          </tbody>
        </table>
      </div>      
    </section>
  </div>
  
  
</div>



<!-- Template para nuevas filas de alergias -->
<template id="alergia-row-template">
  <tr>
    <td>
      <input type="text" 
             name="codigoAlergia[]" 
             class="form-control textupper labitemcodigo" 
             placeholder="Ej: ALG001"
             aria-label="Código de la alergia"
             >
    </td>
    <td>
      <input type="text" 
             name="labitem[]" 
             class="form-control textupper labitemname" 
             placeholder="Ej: Penicilina, Maní, Polen de abedul"
             aria-label="Nombre de la alergia"
             >
    </td>
    <td>
      <select class="form-control textupper labitemtype" 
              name="tipoAlergia[]" 
              aria-label="Tipo de alergia"
             >
        <option value="" selected disabled>Seleccione el tipo</option>
        <option value="OTRO">Alergias inespecíficas / anafilaxia</option>
        <option value="ALIMENTO">Alimentarias</option>
        <option value="MEDICAMENTO">Medicamentos / vacunas</option>
        <option value="RESPIRATORIA">Respiratorias</option>
        <option value="ANIMAL">Animales / picaduras</option>
      </select>
    </td>
    <td   class="text-center"><i title="removeRow" style="cursor:pointer;font-size:16px;" class="fa fa-times rmLabRow" aria-hidden="true"></i></td>
  </tr>
</template>


         

  <!-- Medicacion -->
          <div class="card card-danger shadow-sm border-0">
  <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center py-3">
    <div>
      <h2 class="card-title h4 mb-0">
        <i class="fas fa-pills me-2"></i>
        <strong>Prescripción de Medicación</strong>
      </h2>
    </div>
    <div id="countdown2" class="fs-2 fw-bold" role="timer" aria-label="Tiempo restante para completar la prescripción médica"></div>
  </div>
  
  <div class="card-body bg-light">
    <section aria-labelledby="medicacion-heading">
      <h3 id="medicacion-heading" class="h5 text-secondary mb-4 d-none">Registro de medicación prescrita</h3>     
    

      <div class="table-responsive">
        <table id="prescripcion_table" class="table table-bordered table-hover align-middle" aria-describedby="table-description-medicacion">      
          <thead class="table-light">
            <tr   style="background-color: #F1E1FF;">
              <th scope="col" width="15%" class="ps-3">Código Medicamento</th>
              <th scope="col" width="28%">Nombre del Medicamento</th>
              <th scope="col" width="22%">Dosis y Frecuencia</th>
              <th scope="col" width="15%">Vía de Administración</th>
              <th scope="col" width="12%">Fecha Prescripción</th>
              <th scope="col" width="8%" class="text-center">Acciones</th>
            </tr>
          </thead>
          <tbody id="tbodyrecmed">
            <tr>
              <td>
                <input type="text" 
                       id="codigo_medicamento0" 
                       name="codigo_medicamento[]" 
                       class="form-control textupper cod" 
                       placeholder="Ej: MED001"
                       aria-label="Código del medicamento"
                      >
              </td>
              <td>
                <input type="text" 
                       id="nombre_droga0" 
                       name="nombre_droga[]" 
                       class="form-control textupper des" 
                       placeholder="Ej: Paracetamol 500mg"
                       aria-label="Nombre del medicamento"
                       >
              </td>
              <td>
                <div class="input-group">
                  <input type="text" 
                         id="dosis_medicamento0" 
                         name="dosis_medicamento[]" 
                         class="form-control textupper dosis" 
                         placeholder="Ej: 1 comp cada 8h"
                         aria-label="Dosis y frecuencia"
                        >
                  <button type="button" 
                          class="btn btn-outline-secondary btn-sm" 
                          data-bs-toggle="tooltip" 
                          title="Ejemplos: 1 comp cada 8h, 500mg cada 12h, 10ml cada 24h">
                    <i class="fas fa-question"></i>
                  </button>
                </div>
              </td>
              <td>
                <select class="form-select textupper via" 
                        id="via_medicamento0" 
                        name="via_medicamento[]" 
                        aria-label="Vía de administración"
                        >
                  <option value="" selected disabled>Seleccione vía</option>
                  <option value="Oral">Oral</option>
                  <option value="Intravenosa">Intravenosa (IV)</option>
                  <option value="Intramuscular">Intramuscular (IM)</option>
                  <option value="Subcutánea">Subcutánea (SC)</option>
                  <option value="Tópica">Tópica</option>
                  <option value="Inhalatoria">Inhalatoria</option>
                  <option value="Rectal">Rectal</option>
                  <option value="Sublingual">Sublingual</option>
                  <option value="Ótica">Ótica</option>
                  <option value="Oftálmica">Oftálmica</option>
                  <option value="Nasal">Nasal</option>
                </select>
              </td>
              <td>
                <input type="date" 
                       id="fecha_medicamento0" 
                       name="fecha_medicamento[]" 
                       class="form-control fecha" 
                       aria-label="Fecha de prescripción"
                       
                       max="<?php echo date('Y-m-d'); ?>"
                       value="<?php echo date('Y-m-d'); ?>">
              </td>
              <td  class="text-center"><i title="removeRow" style="cursor:pointer;font-size:16px;" class="fa fa-times rmLabRow4" aria-hidden="true"></i></td>
            </tr>
          </tbody>
        </table>
      </div>

     
    </section>
  </div>
  

</div>



<!-- Template para nuevas filas de medicamentos -->
<template id="medicamento-row-template">
  <tr>
    <td>
      <input type="text" 
             name="codigo_medicamento[]" 
             class="form-control textupper cod" 
             placeholder="Ej: MED001"
             aria-label="Código del medicamento"
            >
    </td>
    <td>
      <input type="text" 
             name="nombre_droga[]" 
             class="form-control textupper des" 
             placeholder="Ej: Paracetamol 500mg"
             aria-label="Nombre del medicamento"
            >
    </td>
    <td>
      <div class="input-group">
        <input type="text" 
               name="dosis_medicamento[]" 
               class="form-control textupper dosis" 
               placeholder="Ej: 1 comp cada 8h"
               aria-label="Dosis y frecuencia"
               >
        <button type="button" 
                class="btn btn-outline-secondary btn-sm" 
                data-bs-toggle="tooltip" 
                title="Ejemplos: 1 comp cada 8h, 500mg cada 12h, 10ml cada 24h">
          <i class="fas fa-question"></i>
        </button>
      </div>
    </td>
    <td>
      <select class="form-select textupper via" 
              name="via_medicamento[]" 
              aria-label="Vía de administración"
              >
        <option value="" selected disabled>Seleccione vía</option>
        <option value="Oral">Oral</option>
        <option value="Intravenosa">Intravenosa (IV)</option>
        <option value="Intramuscular">Intramuscular (IM)</option>
        <option value="Subcutánea">Subcutánea (SC)</option>
        <option value="Tópica">Tópica</option>
        <option value="Inhalatoria">Inhalatoria</option>
        <option value="Rectal">Rectal</option>
        <option value="Sublingual">Sublingual</option>
        <option value="Ótica">Ótica</option>
        <option value="Oftálmica">Oftálmica</option>
        <option value="Nasal">Nasal</option>
      </select>
    </td>
    <td>
      <input type="date" 
             name="fecha_medicamento[]" 
             class="form-control fecha" 
             aria-label="Fecha de prescripción"
             
             max="<?php echo date('Y-m-d'); ?>"
             value="<?php echo date('Y-m-d'); ?>">
    </td>
    <td  class="text-center"><i title="removeRow" style="cursor:pointer;font-size:16px;" class="fa fa-times rmLabRow4" aria-hidden="true"></i></td>
  </tr>
</template>


            <!-- Botones -->
            <div class="card-footer">

              <div class="float-left">
                <a href="#" class="btn btn-outline-danger">
                  <i class="fas fa-times"></i> <strong>CERRAR</strong>
                </a>
              </div>

              <div class="float-right">
                <input type="hidden" name="accion" value="agregar">
                <button id="guardar" type="submit" class="btn btn-outline-danger">
                  <i class="fas fa-check"></i> <strong>Guardar</strong>
                </button>
              </div>
            </div>

          </form>


          <!-- /.card-body -->




        </div>
      </div>
      <!-- /.row -->
    </div><!-- /.container-fluid -->
  </section>
  <!-- Modal para mostrar JSON -->
<!-- Modal para mostrar JSON con pestañas Request y Response -->
<!-- Modal para mostrar JSON con pestañas Request y Response - CORREGIDO PARA BOOTSTRAP 4 -->
<div class="modal fade" id="jsonModal" tabindex="-1" role="dialog" aria-labelledby="jsonModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="jsonModalLabel">
                    <i class="fas fa-code me-2"></i>
                    Transacción FHIR Bundle
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" class="text-white">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>¡Transacción procesada exitosamente!</strong> La operación FHIR se completó correctamente.
                </div>
                
                <!-- Pestañas - CORREGIDO PARA BOOTSTRAP 4 -->
                <ul class="nav nav-tabs" id="jsonTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="request-tab" data-toggle="tab" href="#request" role="tab" aria-controls="request" aria-selected="true">
                            <i class="fas fa-paper-plane me-2"></i>Request
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="response-tab" data-toggle="tab" href="#response" role="tab" aria-controls="response" aria-selected="false">
                            <i class="fas fa-reply me-2"></i>Response
                        </a>
                    </li>
                </ul>

                <!-- Contenido de las pestañas -->
                <div class="tab-content border border-top-0 p-3">
                    <!-- Pestaña Request -->
                    <div class="tab-pane fade show active" id="request" role="tabpanel" aria-labelledby="request-tab">
                        <div class="mb-3">
                            <h6 class="text-muted">Datos enviados al servidor FHIR:</h6>
                            <p class="mb-1"><strong>Endpoint:</strong> <span id="requestEndpoint" class="font-monospace">FHIR Server</span></p>
                            <p class="mb-0"><strong>Método:</strong> <span class="badge badge-primary">POST</span></p>
                        </div>
                        <div id="requestViewer" class="json-viewer bg-dark text-light p-3 rounded" style="max-height: 400px; overflow-y: auto; font-family: 'Courier New', monospace; font-size: 0.875rem;"></div>
                    </div>

                    <!-- Pestaña Response -->
                    <div class="tab-pane fade" id="response" role="tabpanel" aria-labelledby="response-tab">
                        <div class="mb-3">
                            <h6 class="text-muted">Respuesta del servidor FHIR:</h6>
                            <p class="mb-1"><strong>Mensaje:</strong> <span id="responseMessage"></span></p>
                            <p class="mb-0"><strong>Status:</strong> <span class="badge badge-success" id="responseStatus">success</span></p>
                        </div>
                        <div id="responseViewer" class="json-viewer bg-dark text-light p-3 rounded" style="max-height: 400px; overflow-y: auto; font-family: 'Courier New', monospace; font-size: 0.875rem;"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="btn-group" role="group">
                    <button id="copyRequest" class="btn btn-outline-primary">
                        <i class="fas fa-copy me-2"></i>Copiar Request
                    </button>
                    <button id="copyResponse" class="btn btn-outline-info">
                        <i class="fas fa-copy me-2"></i>Copiar Response
                    </button>
                </div>
                <div class="btn-group" role="group">
                    <button id="downloadRequest" class="btn btn-outline-success">
                        <i class="fas fa-download me-2"></i>Request
                    </button>
                    <button id="downloadResponse" class="btn btn-outline-warning">
                        <i class="fas fa-download me-2"></i>Response
                    </button>
                </div>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
  <!-- /.content -->
</div>

<?php
include('/var/www/html/view/includes/footer.php');
?>

<!-- Page specific script -->
<script>
  $(function() {



    //Initialize Select2 Elements
    $('.select2bs4').select2({
      theme: 'bootstrap4'
    });

    function handleAjaxRequest(requestConfig, timeout = 10000) {
      const deferred = $.Deferred();
      let t;

      const ajaxRequest = $.ajax(requestConfig)
        .done((data) => {
          clearTimeout(t);
          deferred.resolve(data);
        })
        .fail((xhr, ajaxOptions, thrownError) => {
          if (xhr.readyState === 0) {
            toastr.warning('Advertencia, Ocurrió un error al intentar comunicarse con el servidor. Por favor, contacte con el administrador de la red');
          } else {
            toastr.warning('Advertencia, Ocurrió un error al procesar la solicitud. Por favor, contacte con el administrador del sistema');
          }
          console.log(thrownError);
          deferred.reject(xhr, ajaxOptions, thrownError);
        });     

      return deferred.promise();
    }

    function obtenerDatosProfesional() {
      const documento = $('#id_pasaporte').val();
      if (!documento) return;

      $('#getDatosProfesional').attr('disabled', true);

      const requestConfig = {
        url: '/backend/services/datos/profesional.php',
        method: 'POST',
        data: {
          documento: documento,
          tipo: 'medico',
          accion: 'search'
        }
      };

      return handleAjaxRequest(requestConfig)
        .done((data) => {
          const response = JSON.parse(data);
          if (response.status === "success") {
            $('#id_medico').val(response.data.id);
            $('#nombre_medico').val(response.data.pnombre+' '+response.data.snombre);
            $('#apellido_medico').val(response.data.papellido+' '+response.data.sapellido);
            // Convertir fecha yyyy-mm-dd a dd/mm/yyyy
            const fechaNacimiento = new Date(response.data.fechanac);

            // Extraer componentes
            const dia = String(fechaNacimiento.getDate()).padStart(2, '0');
            const mes = String(fechaNacimiento.getMonth() + 1).padStart(2, '0'); // +1 porque los meses van de 0 a 11
            const anio = fechaNacimiento.getFullYear();

            // Armar la fecha formateada
            const formattedDate = `${dia}/${mes}/${anio}`;

            // Insertar en el campo
            $('#fecha_nacimiento').val(formattedDate);
            $('#sexo_medico').val(response.data.sexo == 'male' ? 'Masculino' : 'Femenino');
            $('#nacionalidad').val(response.data.pais);
            $('#id_colegio').val(response.data.id_colegio);
           }
        })
        .always(() => {
          $('#getDatosProfesional').removeAttr('disabled');
        });
    }


    function obtenerDatosServicios() {
      const id_establecimiento = $('#id_establecimiento').val();
      console.log(id_establecimiento);
 
      const requestConfig = {
        url: '/backend/services/datos/servicio.php',
        method: 'POST',
        data: {
          id_establecimiento: id_establecimiento,
          accion: 'search'
        }
      };

      return handleAjaxRequest(requestConfig)
        .done((data) => {
          const response = JSON.parse(data);
          if (response.status === "success") {
            $('#id_servicio').val(response.data.id);
            $('#tipo').val(response.data.tipo);                    
           }
        })
        
    }

    function obtenerDatosCedulaPaciente() {
      const cedula = $('#cedula').val();
      const tipo = $('#tipo_documento').val();
      if (!cedula) return;

    
      const requestConfig = {
        url: '/backend/services/datos/paciente.php',
        method: 'POST',
        data: {
          type: tipo,
          documento: cedula,
          accion: 'search'
        }
      };

      return handleAjaxRequest(requestConfig)
    .done((data) => {
        const response = JSON.parse(data);
        if (response.status === "success") {
            $('#id_paciente').val(response.data.id);
            $('#nombre_paciente').val(response.data.pnombre + ' ' + response.data.snombre);
            $('#apellido_paciente').val(response.data.papellido + ' ' + response.data.sapellido);
            
            // SOLUCIÓN CORREGIDA - Convertir fecha yyyy-mm-dd a dd/mm/yyyy
            const fechaParts = response.data.fechanac.split('-');
            if (fechaParts.length === 3) {
                const anio = fechaParts[0];
                const mes = fechaParts[1]; // Ya viene como 05 (string)
                const dia = fechaParts[2]; // Ya viene como 01 (string)
                
                const formattedDate = `${dia}/${mes}/${anio}`;
                $('#fecha_nacimiento_paciente').val(formattedDate);
            } else {
                // Fallback por si acaso
                const fechaNacimiento = new Date(response.data.fechanac);
                const dia = String(fechaNacimiento.getDate()).padStart(2, '0');
                const mes = String(fechaNacimiento.getMonth() + 1).padStart(2, '0');
                const anio = fechaNacimiento.getFullYear();
                $('#fecha_nacimiento_paciente').val(`${dia}/${mes}/${anio}`);
            }
            
            $('#sexo_paciente').val(response.data.sexo == 'male' ? 'Masculino' : 'Femenino');       
        }
    })
    .always(() => {
          $('#getDatosCedula').removeAttr('disabled');
        });
    }


 






    $('#getDatosCedula').click(function() {
      event.preventDefault(); // Previene el envío del formulario

      obtenerDatosCedulaPaciente();
      
    });

    $('#getDatosProfesional').click(function() {
      event.preventDefault(); // Previene el envío del formulario

      obtenerDatosProfesional();
    });

    $('#id_establecimiento').change(function() {
      event.preventDefault(); // Previene el envío del formulario

      obtenerDatosServicios();
    });

   

    // Función para inicializar los autocompletados
    function initializeAutocompleteCIE10() {
      // Autocompletar para el nombre de CIE10
      $(".labitemname10").autocomplete({
        source: function(request, response) {
          if (request.term.length >= 5) {
            $.ajax({
              url: '/backend/CIE10.php', // Ruta al archivo PHP que maneja la solicitud
              type: 'POST',
              dataType: 'json',
              data: {
                accion: 'CIE10List',
                term: request.term
              },
              success: function(data) {
                var limitedResults = data.slice(0, 5);
                response($.map(limitedResults, function(item) {
                  return {
                    label: item.nombre.toUpperCase(),
                    value: item.nombre.toUpperCase(),
                    codigo: item.codigo
                  };
                }));
              }
            });
          }
        },
        select: function(event, ui) {
          var $row = $(this).closest('tr');
          $row.find('.labitemcod10').val(ui.item.codigo).prop('disabled', false);
          $row.find('.labitemname10').val(ui.item.value).prop('disabled', false);
          addNewRowCIE10();
        }
      });

      // Autocompletar para el código de CIE10
      $(".labitemcod10").off('blur').on('blur', function() {
        var $input = $(this);
        var codigo = $input.val().toUpperCase();

        if (codigo) {
          $.ajax({
            url: '/backend/CIE10.php',
            type: 'POST',
            dataType: 'json',
            data: {
              accion: 'CIE10List',
              term: codigo
            },
            success: function(data) {
              if (data.length) {
                var item = data[0];
                $input.closest('tr').find('.labitemname10').val(item.nombre.toUpperCase()).prop('disabled', false);
                $input.prop('disabled', false);
                addNewRowCIE10();
              } else {
                alert('Código CIE10 no encontrado.');
                $input.val('').focus();
              }
            }
          });
        }
      });
    }

    // Función para agregar una nueva fila
    function addNewRowCIE10() {
      const rowCount = $('#cieTable_tbody tr').length;
      var newRow = $('#cieTable_tbody tr:first').clone();
      newRow.find('input').val('').prop('disabled', false); // Limpiar valores     

      newRow.appendTo('#cieTable_tbody');

      initializeAutocompleteCIE10(); // Inicializar autocompletado en la nueva fila

      newRow.find('.rmLabRow2').on('click', function() {
        var $row = $(this).closest('tr');
        var codigo = $row.find('.labitemcod10').val();
        var nombre = $row.find('.labitemname10').val();
        var nota = $row.find('.labitemnota10').val();
        if (codigo || nombre || nota) {
          $row.remove();
        }
      });
    }

    // Evento de eliminar fila existente
    $('#cieTable_tbody').on('click', '.rmLabRow2', function() {
      var $row = $(this).closest('tr');
      var codigo = $row.find('.labitemcod10').val();
      var nombre = $row.find('.labitemname10').val();
      var nota = $row.find('.labitemnota10').val();
      if (codigo || nombre || nota) {
        $row.remove();
      }
    });

    // Inicializar en la carga
    initializeAutocompleteCIE10();

    // Función para inicializar los autocompletados
    function initializeAutocompleteAlergia() {
      // Autocompletar para el nombre de alergia
      $(".labitemname").autocomplete({
        source: function(request, response) {
          $.ajax({
            url: '/backend/alergias.php', // Ruta al archivo PHP que maneja la solicitud
            type: 'POST',
            dataType: 'json',
            data: {
              accion: 'AlergiasList',
              term: request.term // El término que el usuario escribió
            },
            success: function(data) {
              response($.map(data, function(item) {
                return {
                  label: item.nombre.toUpperCase(), // Muestra el nombre en mayúsculas
                  value: item.nombre.toUpperCase(), // Inserta el nombre en mayúsculas
                  codigo: item.codigo, // Información adicional (código de la alergia)
                  type: item.type // Información adicional (tipo de la alergia)
                };
              }));
            }
          });
        },
        select: function(event, ui) {
          // Al seleccionar, completar el campo de código de alergia y bloquear el campo de nombre
          var $row = $(this).closest('tr');
          $row.find('.labitemcodigo').val(ui.item.codigo).prop('disabled', false);
          $row.find('.labitemname').val(ui.item.value).prop('disabled', false);

          // Seleccionar el tipo en el <select>
          $row.find('select.labitemtype').val(ui.item.type);

          // Agregar una nueva fila después de completar el nombre
          addNewRow();
        }
      });

      // Autocompletar para el código de alergia - solo autocompletar al salir del campo
      $(".labitemcodigo").off('blur').on('blur', function() {
        var $input = $(this);
        var codigo = $input.val().toUpperCase(); // Convierte el código a mayúsculas

        if (codigo) {
          $.ajax({
            url: '/backend/alergias.php', // Ruta al archivo PHP que maneja la solicitud
            type: 'POST',
            dataType: 'json',
            data: {
              accion: 'AlergiasList',
              term: codigo // El código de alergia que el usuario escribió
            },
            success: function(data) {
              if (data.length) {
                // Completar el campo de nombre con el primer resultado y bloquear el campo de nombre
                var item = data[0];
                $input.closest('tr').find('.labitemname').val(item.nombre.toUpperCase()).prop('disabled', false);
                $input.prop('disabled', false); // Bloquear el campo de código

                // Seleccionar el tipo correcto en el <select>
                $input.closest('tr').find('select.labitemtype').val(item.type);

                // Agregar una nueva fila después de completar el código
                addNewRow();
              } else {
                // Mostrar una alerta si el código no se encuentra
                alert('Código de alergia no encontrado.');
                $input.val('').focus(); // Limpiar el campo y dar foco nuevamente
              }
            }
          });
        }
      });
    }

    // Función para agregar una nueva fila
    function addNewRow() {
      // Clonar la primera fila y limpiar sus valores
      var newRow = $('#labTbodyAlergia tr:first').clone();
      newRow.find('input').val('').prop('disabled', false); // Limpia los valores y habilita los campos
      newRow.appendTo('#labTbodyAlergia');

      // Inicializar los eventos para la nueva fila
      initializeAutocompleteAlergia();
      newRow.find('.rmLabRow').on('click', function() {
        // Solo eliminar si los campos no están vacíos
        var $row = $(this).closest('tr');
        var codigo = $row.find('.labitemcodigo').val();
        var nombre = $row.find('.labitemname').val();
        var tipo = $row.find('select.labitemtype').val();
        if (codigo || nombre || tipo) {
          $row.remove();
        }
      });
    }

    // Agregar el evento de clic al icono de eliminar en las filas existentes
    $('#labTbodyAlergia').on('click', '.rmLabRow', function() {
      // Solo eliminar si los campos no están vacíos
      var $row = $(this).closest('tr');
      var codigo = $row.find('.labitemcodigo').val();
      var nombre = $row.find('.labitemname').val();
      var tipo = $row.find('select.labitemtype').val();
      if (codigo || nombre || tipo) {
        $row.remove();
      }
    });

    // Inicializar autocompletado en la carga inicial
    initializeAutocompleteAlergia();

    // Convertir a mayúsculas mientras se escribe
    $(document).on('input', '.labitemcodigo, .labitemname', function() {
      $(this).val($(this).val().toUpperCase());
    });
/*
    // Función para inicializar los autocompletados
    function initializeAutocompleteProcedimiento() {
      // Autocompletar para el nombre de procedimiento
      $(".labitemnamep").autocomplete({
        source: function(request, response) {
          var term = request.term;

          // Solo realizar la búsqueda si el término tiene al menos 5 caracteres
          if (term.length >= 5) {
            $.ajax({
              url: '/backend/procedimiento.php', // Ruta al archivo PHP que maneja la solicitud
              type: 'POST',
              dataType: 'json',
              data: {
                accion: 'ProcedimientoList',
                term: term // El término que el usuario escribió
              },
              success: function(data) {
                // Limitar el número de resultados a 5
                var limitedData = data.slice(0, 5);

                response($.map(limitedData, function(item) {
                  return {
                    label: item.nombre.toUpperCase(), // Muestra el nombre en mayúsculas
                    value: item.nombre.toUpperCase(), // Inserta el nombre en mayúsculas
                    codigo: item.codigo
                  };
                }));
              }
            });
          } else {
            // No mostrar sugerencias si el término es menor a 5 caracteres
            response([]);
          }
        },
        select: function(event, ui) {
          // Al seleccionar, completar el campo de código de procedimiento y bloquear el campo de nombre
          var $row = $(this).closest('tr');
          $row.find('.labitemcodigop').val(ui.item.codigo).prop('disabled', false);
          $row.find('.labitemnamep').val(ui.item.value).prop('disabled', false);

          // Agregar una nueva fila después de completar el nombre
          addNewRowProcedimiento();
        }
      });

      // Autocompletar para el código de procedimiento - solo autocompletar al salir del campo
      $(".labitemcodigop").off('blur').on('blur', function() {
        var $input = $(this);
        var codigo = $input.val().toUpperCase(); // Convierte el código a mayúsculas

        if (codigo) {
          $.ajax({
            url: '/backend/procedimiento.php', // Ruta al archivo PHP que maneja la solicitud
            type: 'POST',
            dataType: 'json',
            data: {
              accion: 'ProcedimientoList',
              term: codigo // El código de procedimiento que el usuario escribió
            },
            success: function(data) {
              if (data.length) {
                // Completar el campo de nombre con el primer resultado y bloquear el campo de nombre
                var item = data[0];
                $input.closest('tr').find('.labitemnamep').val(item.nombre.toUpperCase()).prop('disabled', false);
                $input.prop('disabled', false); // Bloquear el campo de código

                // Agregar una nueva fila después de completar el código
                addNewRowProcedimiento();
              } else {
                // Mostrar una alerta si el código no se encuentra
                alert('Código Procedimiento no encontrado.');
                $input.val('').focus(); // Limpiar el campo y dar foco nuevamente
              }
            }
          });
        }
      });
    }

    // Función para agregar una nueva fila
    function addNewRowProcedimiento() {
      // Clonar la primera fila y limpiar sus valores
      var newRow = $('#labTbodyprocedimiento tr:first').clone();
      newRow.find('input').val('').prop('disabled', false); // Limpia los valores y habilita los campos
      newRow.appendTo('#labTbodyprocedimiento');

      // Inicializar los eventos para la nueva fila
      initializeAutocompleteProcedimiento();
      newRow.find('.rmLabRow4').on('click', function() {
        // Solo eliminar si los campos no están vacíos
        var $row = $(this).closest('tr');
        var codigo = $row.find('.labitemcodigop').val();
        var nombre = $row.find('.labitemnamep').val();
        if (codigo || nombre) {
          $row.remove();
        }
      });
    }

    // Agregar el evento de clic al icono de eliminar en las filas existentes
    $('#labTbodyprocedimiento').on('click', '.rmLabRow4', function() {
      // Solo eliminar si los campos no están vacíos
      var $row = $(this).closest('tr');
      var codigo = $row.find('.labitemcodigop').val();
      var nombre = $row.find('.labitemnamep').val();
      if (codigo || nombre) {
        $row.remove();
      }
    });

    // Inicializar autocompletado en la carga inicial
    initializeAutocompleteProcedimiento();

    // Convertir a mayúsculas mientras se escribe
    $(document).on('input', '.labitemcodigop, .labitemnamep', function() {
      $(this).val($(this).val().toUpperCase());
    });
*/

    // Función para inicializar los autocompletados
    function initializeAutocompleteMedicamentos() {
      // Autocompletar para la descripción del medicamento
      $(".des").autocomplete({
        source: function(request, response) {
          $.ajax({
            url: '/backend/medicacion.php',
            type: 'POST',
            dataType: 'json',
            data: {
              accion: 'MedicamentosList',
              term: request.term
            },
            success: function(data) {
              var limitedResults = data.slice(0, 5);
              response($.map(limitedResults, function(item) {
                return {
                 label: item.nombre.toUpperCase(), // Muestra el nombre en mayúsculas
                    value: item.nombre.toUpperCase(), // Inserta el nombre en mayúsculas
                    codigo: item.codigo
                };
              }));
            }
          });
        },
        select: function(event, ui) {
          var $row = $(this).closest('tr');
          $row.find('input[name="codigo_medicamento[]"]').val(ui.item.codigo).prop('disabled', false);
          $row.find('input[name="nombre_droga[]"]').val(ui.item.value.toUpperCase()).prop('disabled', false);
          

          // Agregar una nueva fila después de completar los datos
          addNewRowMedicamentos();
        }
      });

      // Autocompletar para el código del medicamento - solo autocompletar al salir del campo
      $(".cod").off('blur').on('blur', function() {
        var $input = $(this);
        var codigo = $input.val().toUpperCase(); // Convierte el código a mayúsculas

        if (codigo) {
          $.ajax({
            url: '/backend/medicacion.php',
            type: 'POST',
            dataType: 'json',
            data: {
              accion: 'MedicamentosList',
              term: codigo
            },
            success: function(data) {
              if (data.length) {
                var item = data[0];
                $input.closest('tr').find('input[name="nombre_droga[]"]').val(item.nombre.toUpperCase()).prop('disabled', false);
                $input.prop('disabled', false); // Desbloquear el campo de código

                // Agregar una nueva fila después de completar los datos
                addNewRowMedicamentos();
              } else {
                alert('Código de medicamento no encontrado.');
                $input.val('').focus();
              }
            }
          });
        }
      });
    }

    // Función para agregar una nueva fila
    function addNewRowMedicamentos() {
      var $tbody = $('#tbodyrecmed');
      var $firstRow = $tbody.find('tr:first');
      var newRow = $firstRow.clone();
      var newRowExtra = $firstRow.next('tr.accordion-next').clone();

      // Limpiar los valores y habilitar los campos en las nuevas filas
      newRow.find('input').val('').prop('disabled', false);
      newRowExtra.find('input').val('').prop('disabled', false);

      // Agregar las nuevas filas al tbody
      $tbody.append(newRow);
      $tbody.append(newRowExtra);

      // Re-inicializar autocompletado para nuevas filas
      initializeAutocompleteMedicamentos();
    }

    // Inicializar el autocompletado y eventos en el documento listo
    $(document).ready(function() {
      initializeAutocompleteMedicamentos();

      // Manejar la eliminación de filas
      $(document).on('click', '.rmLabRow4', function() {
        var $row = $(this).closest('tr');
        var $nextRow = $row.next('.accordion-next');

        $row.remove();
        if ($nextRow.length) {
          $nextRow.remove();
        }
      });
    });

// Función para mostrar el modal con pestañas Request y Response
function showJsonModal(response) {
    // Mostrar información general
    document.getElementById('responseMessage').textContent = response.message;
    document.getElementById('responseStatus').textContent = response.status;
    
    // Mostrar los JSON en las pestañas correspondientes
    const requestViewer = document.getElementById('requestViewer');
    const responseViewer = document.getElementById('responseViewer');
    
    // Request: mostrar el JSON que se envió al servidor FHIR
    requestViewer.innerHTML = syntaxHighlight(response.request);
    
    // Response: mostrar la respuesta del servidor FHIR
    responseViewer.innerHTML = syntaxHighlight(response.response);
    
    // Mostrar el modal
    $('#jsonModal').modal('show');
}

// Función para formatear y colorear JSON
function syntaxHighlight(json) {
    if (typeof json != 'string') {
        json = JSON.stringify(json, null, 2);
    }
    
    // Escapar caracteres HTML
    json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    
    // Aplicar colores
    return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
        let cls = 'json-number';
        if (/^"/.test(match)) {
            if (/:$/.test(match)) {
                cls = 'json-key';
            } else {
                cls = 'json-string';
            }
        } else if (/true|false/.test(match)) {
            cls = 'json-boolean';
        } else if (/null/.test(match)) {
            cls = 'json-null';
        }
        return '<span class="' + cls + '">' + match + '</span>';
    });
}

// Función para copiar Request al portapapeles
$('#copyRequest').click(function() {
    const requestViewer = document.getElementById('requestViewer');
    const requestData = requestViewer.textContent;
    navigator.clipboard.writeText(requestData).then(function() {
        toastr.success('Request FHIR copiado al portapapeles correctamente');
    }, function(err) {
        console.error('Error al copiar request: ', err);
        toastr.error('Error al copiar Request al portapapeles');
    });
});

// Función para copiar Response al portapapeles
$('#copyResponse').click(function() {
    const responseViewer = document.getElementById('responseViewer');
    const responseData = responseViewer.textContent;
    navigator.clipboard.writeText(responseData).then(function() {
        toastr.success('Response FHIR copiado al portapapeles correctamente');
    }, function(err) {
        console.error('Error al copiar response: ', err);
        toastr.error('Error al copiar Response al portapapeles');
    });
});

// Función para descargar Request como JSON
$('#downloadRequest').click(function() {
    const requestViewer = document.getElementById('requestViewer');
    const requestData = requestViewer.textContent;
    const blob = new Blob([requestData], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'fhir-request-' + new Date().toISOString().slice(0, 19).replace(/:/g, '-') + '.json';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    toastr.success('Request FHIR descargado correctamente');
});

// Función para descargar Response como JSON
$('#downloadResponse').click(function() {
    const responseViewer = document.getElementById('responseViewer');
    const responseData = responseViewer.textContent;
    const blob = new Blob([responseData], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'fhir-response-' + new Date().toISOString().slice(0, 19).replace(/:/g, '-') + '.json';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    toastr.success('Response FHIR descargado correctamente');
});



    function enviarFormulario() {
      // Habilitar el campo 'sexo' antes de enviar el formulario
      $('#sexo').attr('disabled', false);

      $('#guardar').attr("disabled", "disabled");

      const requestConfig = {
        url: '/backend/services/track1/paciente.php',
        method: 'POST',
        data: $('#form').serialize(),
        dataType: 'json' // Aseguramos que la respuesta sea manejada como JSON automáticamente
      };

      handleAjaxRequest(requestConfig)
        .done((response) => {
          // Ya no necesitas hacer JSON.parse, porque jQuery lo maneja automáticamente
          if (response.status === "success") {
            toastr.success(response.message);

            showJsonModal(response);
            //setTimeout(() => {
            //  location.reload();
            //}, 5000);
          } else {
            toastr.error(response.message);
          }
        })
        .fail(() => {
          toastr.error('Ocurrió un error intentando resolver la solicitud. Por favor contacte con el administrador de la red');
        })
        .always(() => {
          $('#guardar').removeAttr('disabled');
        });
    }


    $('#form').submit(function(e) {
      e.preventDefault();

      // Validación para campos
      //if (!isValid()) {
      //  return false;
      //}

      enviarFormulario();
    });
    /*
        // Función para validar los campos
        function isValid() {
          if ($('#documento').val() == "") {
            toastr.warning('Favor selecciona un tipo de documento.');
            return false;
          }
          if ($('#cedula').val() == "") {
            toastr.warning('Favor cargar un numero de documento.');
            return false;
          }
          if ($('#nombre').val() == "") {
            toastr.warning('Favor cargar nombre y apellido.');
            return false;
          }
          if ($('#email').val() == "") {
            toastr.warning('Favor cargar un Correo Electronico.');
            return false;
          }
          if ($('#contacto').val() == "") {
            toastr.warning('Favor cargar un contacto.');
            return false;
          }
          if ($('#cargo').val() == "") {
            toastr.warning('Favor cargar el cargo que ocupa.');
            return false;
          }
          if ($('#dependencia').val() == "") {
            toastr.warning('Favor cargar la dependencia a la cual corresponde.');
            return false;
          }
          if ($('#institucion').val() == "") {
            toastr.warning('Favor cargar la institucion a la cual corresponde.');
            return false;
          }
          if ($("#conformidad-si").is(":checked") == false &&
            $("#conformidad-no").is(":checked") == false) {
            toastr.warning('Favor seleccionar SI/NO la conformidad de Fotografia/Video.');
            return false;
          }
          if ($("#informacion-si").is(":checked") == false &&
            $("#informacion-no").is(":checked") == false) {
            toastr.warning('Favor seleccionar SI/NO la conformidad para uso de datos.');
            return false;
          }



          return true;
        }
    */

  })
</script>

<style>
.json-viewer {
    white-space: pre-wrap;
    word-break: break-word;
    line-height: 1.4;
    background-color: #1e1e1e !important;
    color: #d4d4d4 !important;
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
    border: 1px solid #444;
    border-radius: 0.375rem;
    padding: 1rem;
    max-height: 400px;
    overflow-y: auto;
}

.json-key { 
    color: #9cdcfe !important; 
    font-weight: bold; 
}

.json-string { 
    color: #ce9178 !important; 
}

.json-number { 
    color: #b5cea8 !important; 
}

.json-boolean { 
    color: #569cd6 !important; 
}

.json-null { 
    color: #569cd6 !important; 
}

/* Estilos para las pestañas */
.nav-tabs .nav-link {
    color: #6c757d;
    font-weight: 500;
}

.nav-tabs .nav-link.active {
    color: #dc3545;
    font-weight: 600;
    border-bottom: 2px solid #dc3545;
}
</style>