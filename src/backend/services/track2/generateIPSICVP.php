<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=UTF-8');

require '/var/www/html/vendor/autoload.php';
use Ramsey\Uuid\Uuid;
require '/var/www/html/backend/services/translateCode2.php';


function generarFhirBundle($id_paciente, $id_consulta, $dbconnFHIR)
{
    try {
        // ---------- Paciente ----------
        $sql = "SELECT p.id, p.documento, p.nombre, p.apellido, p.fechanac, p.sexo, p.pais, p.code , c.alpha_2, c.alpha_3 
                FROM paciente2025 p
                INNER JOIN country c on p.pais = c.name 
                WHERE p.id = :id_paciente;";
        $stmt = $dbconnFHIR->prepare($sql);
        $stmt->bindParam(':id_paciente', $id_paciente, PDO::PARAM_INT);
        $stmt->execute();
        $paciente = $stmt->fetch(PDO::FETCH_ASSOC);

        // ---------- Consulta ----------
        $sql2 = "SELECT id, id_paciente, id_medico, id_servicio, fecha_registro
                 FROM consultas
                 WHERE id = :id_consulta;";
        $stmt2 = $dbconnFHIR->prepare($sql2);
        $stmt2->bindParam(':id_consulta', $id_consulta, PDO::PARAM_INT);
        $stmt2->execute();
        $consultas = $stmt2->fetch(PDO::FETCH_ASSOC);

        // ---------- Profesional ----------
        $sql3 = "SELECT p.id, p.documento, p.nombre, p.apellido, p.fechanac, p.sexo, p.pais, p.id_colegio, p.tipo, p.code, c.alpha_2, c.alpha_3 
                 FROM profesional2025 p
                 INNER JOIN country c on p.pais = c.name 
                 WHERE id = :id_profesional";
        $stmt3 = $dbconnFHIR->prepare($sql3);
        $stmt3->bindParam(':id_profesional', $consultas['id_medico']);
        $stmt3->execute();
        $medico = $stmt3->fetch(PDO::FETCH_ASSOC);

        // ---------- Establecimiento ----------
        $sql4 = "SELECT p.id, p.id_establecimiento, p.nombre, p.direccion, p.departamento, p.pais, p.code, c.alpha_2, c.alpha_3
                 FROM establecimiento2025 p 
                 INNER JOIN country c on p.pais = c.alpha_3 
                 WHERE p.id = :id_servicio";
        $stmt4 = $dbconnFHIR->prepare($sql4);
        $stmt4->bindParam(':id_servicio', $consultas['id_servicio']);
        $stmt4->execute();
        $establecimiento = $stmt4->fetch(PDO::FETCH_ASSOC);

        // ---------- Vacunas ----------
        $sql5 = "SELECT v.id, v.id_paciente, v.id_consulta, v.codigo_vacuna, v.lote, v.fecha_aplicacion, v.code,
                        c.num_dosis , c.local_term, c.estado_vac, c.autoridad_vac , c.tipo_organizacion_vac,
                        c.cod_organizacion_vac , c.fabricante_vac
                 FROM consulta_vacunacion v
                 INNER JOIN vacunas c ON v.codigo_vacuna = c.local_code 
                 WHERE v.id_paciente = :id_paciente AND v.id_consulta = :id_consulta";
        $stmt5 = $dbconnFHIR->prepare($sql5);
        $stmt5->bindParam(':id_paciente', $id_paciente);
        $stmt5->bindParam(':id_consulta', $consultas['id']);
        $stmt5->execute();
        $vacunaciones = $stmt5->fetchAll(PDO::FETCH_ASSOC);

        // ---------- UUIDs ----------
        $bundleId            = Uuid::uuid4()->toString();
        $compositionId       = Uuid::uuid4()->toString();
        $ListID              = Uuid::uuid4()->toString();
        $DocumentReferenceID = Uuid::uuid4()->toString();
        $PacienteID          = $paciente['code'];
        $EncounterID         = Uuid::uuid4()->toString();
        $MedicationID        = Uuid::uuid4()->toString();
        $AlergiaID           = Uuid::uuid4()->toString();
        $ConditionID         = Uuid::uuid4()->toString();
        $inmunizationID      = Uuid::uuid4()->toString();

        $entryArray        = [];
        $entryBundleArray  = [];
        $sectionsArray     = [];

        // Sección de inmunizaciones: referencias a cada Immunization
        foreach ($vacunaciones as $vacuna) {
            $sectionsArray[] = ["reference" => "urn:uuid:" . $vacuna['code']];
        }

        // ---------- List (SubmissionSet) ----------
        $entryArray[] = [
            "fullUrl"  => "urn:uuid:$ListID",
            "resource" => [
                "resourceType" => "List",
                "id"           => $ListID,
                "meta" => [
                    "profile"  => ["https://profiles.ihe.net/ITI/MHD/StructureDefinition/IHE.MHD.Minimal.SubmissionSet"],
                    "security" => [[ "system" => "http://terminology.hl7.org/CodeSystem/v3-ActReason", "code" => "HTEST" ]]
                ],
                "text" => [
                    "status" => "extensions",
                    "div"    => "<div xmlns=\"http://www.w3.org/1999/xhtml\">SubmissionSet with Patient</div>"
                ],
                "extension" => [[
                    "url" => "https://profiles.ihe.net/ITI/MHD/StructureDefinition/ihe-sourceId",
                    "valueIdentifier" => ["value" => "urn:oid:1.2.3.4"]
                ]],
                "identifier" => [[
                    "use"    => "usual",
                    "system" => "urn:ietf:rfc:3986",
                    "value"  => "urn:oid:1.2.840.113556.1.8000.2554.58783.21864.3474.19410.44358.58254.41281.46343"
                ]],
                "status" => "current",
                "mode"   => "working",
                "code"   => ["coding" => [[ "system" => "https://profiles.ihe.net/ITI/MHD/CodeSystem/MHDlistTypes", "code" => "submissionset" ]]],
                "subject"=> ["reference" => "urn:uuid:$PacienteID"],
                "date"   => date('c'),
                "entry"  => [[ "item" => ["reference" => "urn:uuid:".$DocumentReferenceID] ]]
            ],
            "request" => [ "method" => "POST", "url" => "List" ]
        ];

        // ---------- DocumentReference ----------
        $entryArray[] = [
            "fullUrl"  => "urn:uuid:".$DocumentReferenceID,
            "resource" => [
                "resourceType" => "DocumentReference",
                "id"           => $DocumentReferenceID,
                "meta" => [
                    "profile"  => ["https://profiles.ihe.net/ITI/MHD/StructureDefinition/IHE.MHD.Minimal.DocumentReference"],
                    "security" => [[ "system" => "http://terminology.hl7.org/CodeSystem/v3-ActReason", "code" => "HTEST" ]]
                ],
                "text" => [
                    "status" => "generated",
                    "div"    => "<div xmlns=\"http://www.w3.org/1999/xhtml\"><p><b>Generated Narrative: DocumentReference</b></p></div>"
                ],
                "masterIdentifier" => [
                    "system" => "urn:ietf:rfc:3986",
                    "value"  => "urn:oid:1.2.840.113556.1.8000.2554.53432.348.12973.17740.34205.4355.50220.62012"
                ],
                "status"  => "current",
                "subject" => ["reference" => "urn:uuid:$PacienteID"],
                "content" => [[
                    "attachment" => [
                        // CAMBIO: documento FHIR JSON
                        "contentType" => "application/fhir+json",
                        "url"         => "urn:uuid:".$bundleId,
                        "size"        => 11,
                        "hash"        => "MGE0ZDU1YThkNzc4ZTUwMjJmYWI3MDE5NzdjNWQ4NDBiYmM0ODZkMA=="
                    ],
                    "format" => [
                        "system" => "http://ihe.net/fhir/ihe.formatcode.fhir/CodeSystem/formatcode",
                        "code"   => "urn:ihe:iti:xds-sd:text:2008"
                    ]
                ]]
            ],
            "request" => [ "method" => "POST", "url" => "DocumentReference" ]
        ];

        // ---------- Composition (documento principal) ----------
        $entryBundleArray[] = [
            "fullUrl"=> "urn:uuid:".$compositionId,
            "resource"=> [
                "resourceType"=> "Composition",
                "meta" => [
                    // CAMBIO: perfil LAC Composition
                    "profile" => ["http://smart.who.int/icvp/StructureDefinition/Composition-uv-ips-ICVP"]
                ],                    
                "status"=> "final",
                "type"=> [
                    "coding"=> [[ "system"=> "http://loinc.org", "code"=> "60591-5", "display"=> "Patient Summary Document" ]]
                ],
                "subject"=> [ "reference"=> "Patient/$PacienteID" ],
                "date"=> date('c'),
                "author"=> [[ "reference"=> "Organization/" . $establecimiento['code'] ]],
                "title" => "International Vaccine Patient Summary",
                "event" => [[
                    "code" => [[
                        "coding" => [[
                            "system" => "http://terminology.hl7.org/CodeSystem/v3-ActClass",
                            "code"   => "PCPR",
                            "display"=> "care provision"
                        ]]
                    ]],
                    "period" => [ "start" => "2017-12-11", "end" => "2017-12-11" ]
                ]],
                "section"=> [
                    [
                        "id" => $MedicationID,
                        "title" => "Medication Summary Section",
                        "code" => [ "coding" => [[ "system" => "http://loinc.org", "code" => "10160-0" ]] ],
                        "text" => [ "status" => "empty", "div" => "<div xmlns=\"http://www.w3.org/1999/xhtml\">narrative not available</div>" ],
                        "emptyReason" => [ "coding" => [[ "system" => "http://terminology.hl7.org/CodeSystem/list-empty-reason", "code" => "unavailable", "display" => "Information not available" ]] ]
                    ],
                    [
                        "id" => $AlergiaID,
                        "title" => "Allergies Section",
                        "code" => [ "coding" => [[ "system" => "http://loinc.org", "code" => "48765-2" ]] ],
                        "text" => [ "status" => "empty", "div" => "<div xmlns=\"http://www.w3.org/1999/xhtml\">narrative not available</div>" ],
                        "emptyReason" => [ "coding" => [[ "system" => "http://terminology.hl7.org/CodeSystem/list-empty-reason", "code" => "unavailable" , "display" => "Information not available" ]] ]
                    ],
                    [
                        "id" => $ConditionID,
                        "title" => "Problems Section",
                        "code" => [ "coding" => [[ "system" => "http://loinc.org", "code" => "11450-4" ]] ],
                        "text" => [ "status" => "empty", "div" => "<div xmlns=\"http://www.w3.org/1999/xhtml\">narrative not available</div>" ],
                        "emptyReason" => [ "coding" => [[ "system" => "http://terminology.hl7.org/CodeSystem/list-empty-reason", "code" => "unavailable" , "display" => "Information not available" ]] ]
                    ],
                    [
                        "id" => $inmunizationID,
                        "title" => "History of Immunizations",
                        "code" => [ "coding" => [[ "system" => "http://loinc.org", "code" => "11369-6" ]] ],
                        "text" => [ "status" => "empty", "div" => "<div xmlns=\"http://www.w3.org/1999/xhtml\">narrative not available</div>" ],
                        "entry"=> $sectionsArray
                    ]
                ]
            ]
        ];

        // ---------- Patient dentro del documento (active = true) ----------
        $entryBundleArray[] =  [
            "fullUrl"=> "urn:uuid:$PacienteID",
            "resource"=> [
                "resourceType"=> "Patient",
                "id"=> "$PacienteID",
                "meta" => [ "profile" => [ "http://lacpass.racsel.org/StructureDefinition/lac-patient" ] ],
                "extension" => [[
                    "url" => "http://hl7.org/fhir/StructureDefinition/patient-citizenship",
                    "extension" => [[
                        "url" => "code",
                        "valueCodeableConcept" => [
                            "coding" => [[
                                "system" => "urn:iso:std:iso:3166",
                                "code"   => trim($paciente['alpha_2']),
                                "display"=> trim($paciente['pais'])
                            ]]
                        ]
                    ]]
                ]],
                "identifier" => [[
                    "use" => "official",
                    "type" => [ "coding" => [[ "system" => "http://terminology.hl7.org/CodeSystem/v2-0203", "code" => "PPN" ]] ],
                    "system" => "urn:oid.2.16.858.2.10000675.68912",
                    "value"  => trim($paciente['documento'])
                ]],
                // CAMBIO: ICVP/IPS esperan active=true salvo causa específica
                "active" => true,
                "name" => [[
                    "text"   => trim($paciente['nombre']). " ". trim($paciente['apellido']),
                    "family" => trim($paciente['apellido']),
                    "given"  => [ trim($paciente['nombre']) ]
                ]],
                "gender"    => trim($paciente['sexo']),
                "birthDate" => $paciente['fechanac']
            ]
        ];

        // ---------- Vacunas (Immunization-uv-ips-ICVP) ----------
        foreach ($vacunaciones as $vacuna) {
        
            $code = $vacuna['codigo_vacuna'];

               
        
            // 1️⃣ LOCAL → PreQual
            $url    = 'http://racsel.org/fhir/ConceptMap/vs-vacunas-local-to-prequal';
            $system = 'http://node-acme.org/terminology';
            $source = 'http://racsel.org/fhir/ValueSet/vacunas-local-vs';
            $target = 'http://racsel.org/fhir/ValueSet/prequal-vs';
            $Prequal = translateCode($dbconnFHIR, $url, $system, $source, $target, $code);
        
            // Valores por defecto para evitar errores
            $system2 = 'http://smart.who.int/pcmt-vaxprequal/CodeSystem/PreQualProductIDs';
        
            // 2️⃣ Solo traducir a SNOMED si Prequal fue exitoso
            $url2 = 'http://racsel.org/fhir/ConceptMap/vs-vacunas-prequal-to-snomed';
            $source2 = 'http://racsel.org/fhir/ValueSet/prequal-vs';
            $target2 = 'http://racsel.org/fhir/ValueSet/snomed-vs';
            $Snomed = translateCode($dbconnFHIR, $url2, $system2, $source2, $target2, $Prequal['code']);
                   
            $snomedSystem = 'http://snomed.info/sct';
        
            // 3️⃣ Construcción del recurso Immunization
            $entryBundleArray[] = [
                "fullUrl"  => "urn:uuid:" . $vacuna['code'],
                "resource" => [
                    "resourceType" => "Immunization",
                    "id" => $vacuna['code'],
                    "meta" => [
                        "profile" => [
                            "http://smart.who.int/trust-phw/StructureDefinition/Immunization-uv-ips-PreQual"
                        ]
                    ],
                    "text" => [
                        "status" => "generated",
                        "div" => "<div xmlns=\"http://www.w3.org/1999/xhtml\">Registro de vacunación</div>"
                    ],
                    // 🔹 Extensión ProductID (PreQual)
                    "extension" => [[
                        "url" => "http://smart.who.int/pcmt/StructureDefinition/ProductID",
                        "valueCoding" => [
                            "system"  => $system2,
                            "code"    => $Prequal['code'],
                            
                        ]
                    ]],
                    "status" => "completed",
                    // 🔹 vaccineCode (preferir SNOMED, fallback a PreQual/local)
                    "vaccineCode" => [
                        "coding" => [[
                            "system"  => $snomedSystem,
                            "code"    => $Snomed['code'],
                            "display" => $Snomed['display']
                        ]]
                    ],
                    "patient" => [ "reference" => "urn:uuid:$PacienteID" ],
                    "occurrenceDateTime" => !empty($vacuna['fecha_aplicacion'])
                        ? $vacuna['fecha_aplicacion']
                        : "2017-12-11T14:30:00+01:00",
                    "location" => [
                        "display" => $establecimiento['nombre']
                    ],
                    "lotNumber" => $vacuna['lote'],
                    "performer" => [[
                        "actor" => [ "reference" => "urn:uuid:" . $medico['code'] ]
                    ]]
                ]
            ];
        }

        // ---------- Organization ----------
        $entryBundleArray[] = [
            "fullUrl"=> "urn:uuid:". $establecimiento['code'],
            "resource"=> [
                "resourceType"=> "Organization",
                "id"=> $establecimiento['code'],
                "name"=> $establecimiento['nombre'],
                "address"=> [[
                    "text"=> $establecimiento['direccion'],
                    "country"=> trim($establecimiento['alpha_2'])
                ]]
            ]
        ];




        // ---------- Practitioner ----------
        $entryBundleArray[] = [
            "fullUrl" => "urn:uuid:" . $medico['code'],
            "resource" => [
                "resourceType" => "Practitioner",
                "id" => $medico['code'],
                "meta" => [ "profile" => ["http://hl7.org/fhir/uv/ips/StructureDefinition/Practitioner-uv-ips"] ],
                "identifier" => [[
                    "use" => "official",
                    "type" => [ "coding" => [[ "system" => "http://terminology.hl7.org/CodeSystem/v2-0203", "code" => "PPN" ]] ],
                    "system" => "urn:oid.2.16.858.2.10000675.68912",
                    "value"  => trim($medico['documento'])
                ]],
                "name" => [[
                    "use" => "official",
                    "text"  => trim($medico['nombre']) . " " . trim($medico['apellido']),
                    "family" => trim($medico['apellido']),
                    "given" => [trim($medico['nombre'])]                    
                ]]
                
            ]
        ];

        // ---------- Bundle interno (documento) ----------
        $entryArray[] = [
            "fullUrl"=> "urn:uuid:".$bundleId,
            "resource"=> [
                "resourceType"=> "Bundle",
                "id"=> $bundleId,
                "meta" => [
                    // CAMBIO: perfil LAC Document Bundle
                    "profile" => ["http://smart.who.int/icvp/StructureDefinition/Bundle-uv-ips-ICVP"]
                ],
                "identifier"=> [ "system"=> "urn:ietf:rfc:4122", "value"=> $bundleId ],
                "type"=> "document",
                "timestamp"=> date('c'),
                "entry"=> $entryBundleArray
            ],
            "request"=> [ "method"=> "POST", "url"=> "Bundle" ]
        ];

        // ---------- Patient (entry separado del Provide - ITI-65) ----------
        $entryArray[] = [
            "fullUrl"=> "urn:uuid:$PacienteID",
            "resource"=> [
                "resourceType"=> "Patient",
                "id"=> "$PacienteID",
                "meta" => [ "profile" => [ "http://lacpass.racsel.org/StructureDefinition/lac-patient" ] ],
                "extension" => [[
                    "url" => "http://hl7.org/fhir/StructureDefinition/patient-citizenship",
                    "extension" => [[
                        "url" => "code",
                        "valueCodeableConcept" => [
                            "coding" => [[
                                "system" => "urn:iso:std:iso:3166",
                                "code"   => trim($paciente['alpha_2']),
                                "display"=> trim($paciente['pais'])
                            ]]
                        ]
                    ]]
                ]],
                "identifier" => [[
                    "use" => "official",
                    "type" => [ "coding" => [[ "system" => "http://terminology.hl7.org/CodeSystem/v2-0203", "code" => "PPN" ]] ],
                    "system" => "urn:oid.2.16.858.2.10000675.68912",
                    "value"  => trim($paciente['documento'])
                ]],
                // CAMBIO: active=true
                "active" => true,
                "name" => [[
                    "text"   => trim($paciente['nombre']). " ". trim($paciente['apellido']),
                    "family" => trim($paciente['apellido']),
                    "given"  => [ trim($paciente['nombre']) ]
                ]],
                "gender"    => trim($paciente['sexo']),
                "birthDate" => $paciente['fechanac']
            ],
            "request"=> [ "method"=> "PUT", "url"=> "Patient/$PacienteID" ]
        ];

        // ---------- Provide Bundle ITI-65 ----------
        $bundle = [
            "resourceType" => "Bundle",
            "id" => "ex-minimalProvideDocumentBundleSimple",
            "meta" => [
                "profile" => ["https://profiles.ihe.net/ITI/MHD/StructureDefinition/IHE.MHD.Minimal.ProvideBundle"],
                "security" => [[ "system" => "http://terminology.hl7.org/CodeSystem/v3-ActReason", "code" => "HTEST" ]]
            ],
            "type" => "transaction",
            "timestamp" => date('c'),
            "entry" => $entryArray
        ];

        return json_encode($bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        return [ "status" => "error", "message" => $e->getMessage() ];
    }
}