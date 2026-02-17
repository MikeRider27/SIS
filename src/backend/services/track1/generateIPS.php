<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Encabezado para indicar que la respuesta es JSON y codificado en UTF-8
header('Content-Type: application/json; charset=UTF-8');

// Cargar dependencias
require '/var/www/html/vendor/autoload.php';
use Ramsey\Uuid\Uuid;
require '/var/www/html/backend/services/translateCode2.php';

function generarFhirBundle($id_paciente, $id_consulta, $dbconnFHIR)
{
    try {
        // Consulta SQL para obtener los datos del paciente
        $sql = "SELECT id, tipo, codetipo, documento, pnombre, snombre, papellido, sapellido, fechanac, sexo, code 
              FROM paciente2026
              WHERE id = :id_paciente;";
        $stmt = $dbconnFHIR->prepare($sql);
        $stmt->bindParam(':id_paciente', $id_paciente, PDO::PARAM_INT);
        $stmt->execute();
        $paciente = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$paciente) {
            throw new Exception("Paciente no encontrado");
        }

        // Construir lista de given (nombres)
        $given = [];

        if (!empty($paciente['pnombre'])) {
            $given[] = $paciente['pnombre'];
        }

        if (!empty($paciente['snombre'])) {
            $given[] = $paciente['snombre'];
        }

        // Construir el apellido (family) solo con los que existan
        $familyParts = [];

        if (!empty($paciente['papellido'])) {
            $familyParts[] = $paciente['papellido'];
        }

        if (!empty($paciente['sapellido'])) {
            $familyParts[] = $paciente['sapellido'];
        }

        // TRAEMOS LA CONSULTA
        $sql2 = "SELECT id, id_paciente, id_medico, id_servicio, fecha_registro
               FROM consultas
               WHERE id = :id_consulta;";
        $stmt2 = $dbconnFHIR->prepare($sql2);
        $stmt2->bindParam(':id_consulta', $id_consulta, PDO::PARAM_INT);
        $stmt2->execute();
        $consultas = $stmt2->fetch(PDO::FETCH_ASSOC);

        if (!$consultas) {
            throw new Exception("Consulta no encontrada");
        }

        // TRAEMOS DATOS DEL MEDICO
        $sql3 = "SELECT id, documento, fechanac, sexo, pais, id_colegio, fecha, tipo, code, pnombre, snombre, papellido, sapellido
                FROM profesional2025
                WHERE id = :id_profesional";
        $stmt3 = $dbconnFHIR->prepare($sql3);
        $stmt3->bindParam(':id_profesional', $consultas['id_medico']);
        $stmt3->execute();
        $medico = $stmt3->fetch(PDO::FETCH_ASSOC);

         // Construir lista de given (nombres)
        $givenMedico = [];

        if (!empty($medico['pnombre'])) {
            $givenMedico[] = $medico['pnombre'];
        }

        if (!empty($medico['snombre'])) {
            $givenMedico[] = $medico['snombre'];
        }

        // Construir el apellido (family) solo con los que existan
        $familyPartsMedico = [];

        if (!empty($medico['papellido'])) {
            $familyPartsMedico[] = $medico['papellido'];
        }

        if (!empty($medico['sapellido'])) {
            $familyPartsMedico[] = $medico['sapellido'];
        }


        // Establecimiento
        $sql4 = "SELECT p.id, p.id_establecimiento, p.nombre, p.direccion, p.departamento, p.pais, p.code, c.alpha_2, c.alpha_3, p.type
               FROM establecimiento2025 p 
               INNER JOIN country c on p.pais = c.alpha_3 
               WHERE p.id = :id_servicio";
        $stmt4 = $dbconnFHIR->prepare($sql4);
        $stmt4->bindParam(':id_servicio', $consultas['id_servicio']);
        $stmt4->execute();
        $establecimiento = $stmt4->fetch(PDO::FETCH_ASSOC);


        // traemos los diagnosticos - CORREGIDA (había error en GROUP BY)
        $sql6 = "SELECT id, id_consulta, codigo_cie10, code, fecha, estado, note    
                 FROM consulta_diagnosticos
                 WHERE id_paciente = :id_paciente AND id_consulta = :id_consulta";
        $stmt6 = $dbconnFHIR->prepare($sql6);
        $stmt6->bindParam(':id_paciente', $id_paciente, PDO::PARAM_INT);
        $stmt6->bindParam(':id_consulta', $consultas['id'], PDO::PARAM_INT);
        $stmt6->execute();
        $diagnosticos = $stmt6->fetchAll(PDO::FETCH_ASSOC);
     
        // traemos los medicamentos
        $sql8 = "SELECT c.codigo_medicamento, c.code, m.forma, c.dosis, m.descripcion, c.fecha, c.via
               FROM consultas_recetas c 
               INNER JOIN medicacion m ON c.codigo_medicamento = m.local_code 
               WHERE c.id_paciente = :id_paciente AND c.id_consulta = :id_consulta  
               GROUP BY c.codigo_medicamento, c.code, m.forma, c.dosis, c.via, m.descripcion, c.fecha";
        $stmt8 = $dbconnFHIR->prepare($sql8);
        $stmt8->bindParam(':id_paciente', $id_paciente, PDO::PARAM_INT);
        $stmt8->bindParam(':id_consulta', $consultas['id'], PDO::PARAM_INT);
        $stmt8->execute();
        $medicamentos = $stmt8->fetchAll(PDO::FETCH_ASSOC);
      
        // traemos las alergias
        $sql9 = "SELECT c.codigo_alergia, c.code, c.type, a.alergias, a.category
                 FROM consulta_alergias c
                 INNER JOIN alergias a ON c.codigo_alergia = a.code 
               WHERE c.id_paciente = :id_paciente AND c.id_consulta = :id_consulta";
        $stmt9 = $dbconnFHIR->prepare($sql9);
        $stmt9->bindParam(':id_paciente', $id_paciente, PDO::PARAM_INT);
        $stmt9->bindParam(':id_consulta', $consultas['id'], PDO::PARAM_INT);
        $stmt9->execute();
        $alergias = $stmt9->fetchAll(PDO::FETCH_ASSOC);

        // ---------- UUIDs ----------
        $bundleId = Uuid::uuid4()->toString();
        $compositionId = Uuid::uuid4()->toString();
        $ListID = Uuid::uuid4()->toString();
        $DocumentReferenceID = Uuid::uuid4()->toString();
        $PacienteID = $paciente['code'];
        
        // Aquí deberías construir tus recursos FHIR basados en los datos obtenidos
        $entryArray = []; // Construye tus entries FHIR aquí
        $entryBundleArray = []; // Entries para el Bundle DocumentReference
        $sections = []; // Secciones para el Composition
        $conditionArray = []; // Entradas para la sección de Diagnósticos
        $medicationsArray = []; // Entradas para la sección de Medicamentos
        $allergiesArray = []; // Entradas para la sección de Alergias

        // Recorremos los diagnósticos para agregarlos a la sección correspondiente
        foreach ($diagnosticos as $diagnostico) {
            // Agregar la referencia a la sección
            $conditionArray[] = [
                "reference" => "urn:uuid:" . $diagnostico['code']
            ];
        }

        // Recorremos los medicamentos para agregarlos a la sección correspondiente
        foreach ($medicamentos as $medicamento) {
            // Agregar la referencia a la sección
            $medicationsArray[] = [
                "reference" => "urn:uuid:" . $medicamento['code']
            ];
        }

        // Recorremos las alergias para agregarlas a la sección correspondiente
        foreach ($alergias as $alergia) {
            // Agregar la referencia a la sección
            $allergiesArray[] = [
                "reference" => "urn:uuid:" . $alergia['code']
            ];
        }



        //Construimos el List
        $entryArray[] = 
        [
            "fullUrl"  => "urn:uuid:$ListID",
            "resource" => [
                "resourceType" => "List",
                "id"           => $ListID,
                "meta" => [
                    "profile"  => ["https://mspbs.gov.py/fhir/StructureDefinition/ListPy"]
                ],
                "text" => [
                    "status" => "extensions",
                    "div"    => "<div xmlns=\"http://www.w3.org/1999/xhtml\"><a name=\"List_ListEjemploPy2\"> </a><p class=\"res-header-id\"><b>Generated Narrative: List ListEjemploPy2</b></p><a name=\"ListEjemploPy2\"> </a><a name=\"hcListEjemploPy2\"> </a><div style=\"display: inline-block; background-color: #d9e0e7; padding: 6px; margin: 4px; border: 1px solid #8da1b4; border-radius: 5px; line-height: 60%\"><p style=\"margin-bottom: 0px\"/><p style=\"margin-bottom: 0px\">Profile: <a href=\"StructureDefinition-ListPy.html\">ListPy</a></p></div><table class=\"clstu\"><tr><td>Date: 2025-09-01 10:30:00+0000 </td><td>Mode: Working List </td><td>Status: Current </td></tr><tr><td>Subject: <a href=\"Bundle-BundleTrancEjemploPy.html#urn-uuid-05d3374b-0278-4d04-93f7-6adc181d5874\">Bundle: type = transaction; timestamp = 2022-03-03 10:30:00+0000</a></td></tr></table><table class=\"grid\"><tr style=\"backgound-color: #eeeeee\"><td><b>Items</b></td></tr><tr><td><a href=\"Bundle-BundleTrancEjemploPy.html#urn-uuid-487b6713-4647-4a9a-914e-7c552d7197e9\">Bundle: type = transaction; timestamp = 2022-03-03 10:30:00+0000</a></td></tr></table></div>"
                ],                
                "status" => "current",
                "mode"   => "working",
                "subject"=> ["reference" => "urn:uuid:".$PacienteID],
                "date"   => date('c'),
                "entry"  => [[ "item" => ["reference" => "urn:uuid:".$DocumentReferenceID] ]]
               ],
               "request" => [ "method" => "POST", "url" => "List" ]

        ];
        
        // Construimos el DocumentReference
        $entryArray[] = 
        [
            "fullUrl"  => "urn:uuid:".$DocumentReferenceID,
            "resource" => [
                "resourceType" => "DocumentReference",
                "id"           => $DocumentReferenceID,
                "meta" => [
                    "profile"  => ["https://mspbs.gov.py/fhir/StructureDefinition/DocumentReferencePy"]
                ],
                "text" => [
                    "status" => "generated",
                    "div"    => "<div xmlns=\"http://www.w3.org/1999/xhtml\"><a name=\"DocumentReference_DocumentReferenceEjemploPy2\"> </a><p class=\"res-header-id\"><b>Generated Narrative: DocumentReference DocumentReferenceEjemploPy2</b></p><a name=\"DocumentReferenceEjemploPy2\"> </a><a name=\"hcDocumentReferenceEjemploPy2\"> </a><div style=\"display: inline-block; background-color: #d9e0e7; padding: 6px; margin: 4px; border: 1px solid #8da1b4; border-radius: 5px; line-height: 60%\"><p style=\"margin-bottom: 0px\"/><p style=\"margin-bottom: 0px\">Profile: <a href=\"StructureDefinition-DocumentReferencePy.html\">Referencia de Documentos</a></p></div><p><b>status</b>: Current</p><p><b>type</b>: <span title=\"Codes:{http://loinc.org 34105-7}\">Nota de consulta</span></p><p><b>subject</b>: <a href=\"Bundle-BundleTrancEjemploPy.html#urn-uuid-05d3374b-0278-4d04-93f7-6adc181d5874\">Bundle: type = transaction; timestamp = 2022-03-03 10:30:00+0000</a></p><p><b>date</b>: 2025-09-01 10:30:00+0000</p><p><b>author</b>: <code>PractitionerPy/PractitionerEjemploPy</code></p><p><b>custodian</b>: <a href=\"Organization-OrganizacionEjemploPy.html\">Organization HOSPITAL GENERAL DE CORONEL OVIEDO</a></p><blockquote><p><b>content</b></p><h3>Attachments</h3><table class=\"grid\"><tr><td style=\"display: none\">-</td><td><b>ContentType</b></td><td><b>Url</b></td></tr><tr><td style=\"display: none\">*</td><td>application/fhir+json</td><td><a href=\"Bundle-BundleTrancEjemploPy.html#urn-uuid-d384326c-7c0f-4ac2-ba90-a1d83e5b548f\">Bundle: type = transaction; timestamp = 2022-03-03 10:30:00+0000</a></td></tr></table></blockquote></div>"
                ],
                "status"  => "current",
                "subject" => ["reference" => "urn:uuid:".$PacienteID],
                 "type" => [
                    "coding" => [
                        [
                            "system" => "http://loinc.org", 
                            "code" => "34105-7",
                            "display" => "Nota de consulta"
                        ]
                    ]
                ],
                "subject" => [
                    "reference" => "urn:uuid:".$PacienteID
                ],
                "date" => date('c'),
                "author" => [[
                    "reference" => "Practitioner/".$medico['code']
                ]],
                "custodian" => [
                    "reference" => "Organization/".$establecimiento['code']
                ], 
                "content" => [[
                    "attachment" => [
                         "contentType" => "application/fhir+json",
                         "url" => "urn:uuid:". $bundleId,
                         "title" => "Individual Patient Summary"
                    ]
                ]]
            ],
            "request" => [ "method" => "POST", "url" => "DocumentReference" ]
        ];

        // Añadimos las secciones al Composition
        // Sección Diagnósticos
        $sections[] =
        [
            "title" => "Active Problems",
            "code" => [
                "coding" => [[
                    "system" => "http://loinc.org",
                    "code" => "11450-4",
                    "display" => "Problem list Reported"
                ]]
            ],
            "text" => [
                "status" => "generated",
                "div" => "<div xmlns='http://www.w3.org/1999/xhtml'>Resumen de problemas activos actuales del paciente.</div>"
            ],
            "entry" => $conditionArray
        ];

        // Sección Alergias
        $sections[] =
        [
            "title" => "Allergies and Intolerances",
            "code" => [
                "coding" => [[
                    "system" => "http://loinc.org",
                    "code" => "48765-2",
                    "display" => "Allergies and adverse reactions Document"
                ]]
            ],
            "text" => [
                "status" => "generated",
                "div" => "<div xmlns='http://www.w3.org/1999/xhtml'>Resumen de alergias e intolerancias registradas.</div>"
            ],
            "entry" => $allergiesArray
        ];

        // Sección Medicamentos
        $sections[] =
        [
            "title" => "MedicationStatement",
            "code" => [
                "coding" => [[
                    "system" => "http://loinc.org",
                    "code" => "10160-0",
                    "display" => "History of Medication use Narrative"
                ]]
            ],
            "text" => [
                "status" => "generated",
                "div" => "<div xmlns='http://www.w3.org/1999/xhtml'>Historial de uso de medicación reportado.</div>"
            ],
            "entry" => $medicationsArray
        ];
        // Agregamos el Composition al entryBundleArray
        $entryBundleArray[] = 
        [
            "fullUrl" => "urn:uuid:" . $compositionId,
            "resource" => [
                "resourceType" => "Composition",
                "id" => $compositionId,
                "meta" => [
                    "profile" => ["https://mspbs.gov.py/fhir/StructureDefinition/CompositionPy"]
                ],
                "text" => [
                    "status" => "generated",
                    "div" => "<div xmlns=\"http://www.w3.org/1999/xhtml\"><h1>Documento Resumen Clínico de Paciente de Paraguay</h1></div>"
                ],
                "status" => "final",
                "type" => [
                    "coding" => [[
                        "system" => "http://loinc.org",
                        "code" => "60591-5",
                        "display" => "Patient Summary Document"
                    ]]
                ],
                "subject" => [
                    "reference" => "urn:uuid:" . $PacienteID
                ],
                "date" => date('c'),
                "author" => [[
                    "reference" => "urn:uuid:" . $medico['code']
                ]],
                "title" => "Documento Clinico Paraguay de " . date('d/m/Y'),
                "confidentiality" => "N",
                "custodian" => [
                    "reference" => "urn:uuid:" . $establecimiento['code']
                ],
                "section" => $sections
            ]
        ];

        //Agregamos el Patient al Bundle FHIR
        $entryBundleArray[] = 
        [
            "fullUrl" => "urn:uuid:".$PacienteID,
            "resource" => [
                "resourceType" => "Patient",
                "id"           => $PacienteID,
                "meta" => [
                    "profile"  => ["https://mspbs.gov.py/fhir/StructureDefinition/PacientePy"]
                ],
                "text" => [
                    "status" => "generated",
                    "div"    => "<div xmlns=\"http://www.w3.org/1999/xhtml\"><a name=\"Patient_PacienteEjemploPy\"> </a><p class=\"res-header-id\"><b>Generated Narrative: Patient PacienteEjemploPy</b></p><a name=\"PacienteEjemploPy\"> </a><a name=\"hcPacienteEjemploPy\"> </a><div style=\"display: inline-block; background-color: #d9e0e7; padding: 6px; margin: 4px; border: 1px solid #8da1b4; border-radius: 5px; line-height: 60%\"><p style=\"margin-bottom: 0px\"/><p style=\"margin-bottom: 0px\">Profile: <a href=\"StructureDefinition-PacientePy.html\">Paciente Paraguay</a></p></div><p style=\"border: 1px #661aff solid; background-color: #e6e6ff; padding: 10px;\">Luis Sanchez  Male, DoB: 1974-12-25 ( Cédula de Identidad: 98765)</p><hr/></div>"
                ],
                "identifier" => [
                    [
                        "type" => [
                            "coding" => [
                                [
                                    "system" => "https://mspbs.gov.py/fhir/CodeSystem/IdentificadoresPersonaCS",
                                    "code" => $paciente['tipo'],
                                    "display" => $paciente['codetipo']
                                ]
                            ]
                        ],
                        "value" => $paciente['documento']
                    ]
                ],
                "name" => [
                    [
                        "family" => implode(" ", $familyParts),
                        "given" => $given
                    ]
                ],
                "gender" => $paciente['sexo'],
                "birthDate" => $paciente['fechanac']
            ]
        ];

        // Agregamos los diagnósticos al Bundle FHIR
        foreach ($diagnosticos as $diagnostico) {
            //hacemos una consulta para obtener más detalles del diagnóstico
            $sql = "SELECT codcie10a, codcie10b, nomcie10 FROM cie10 WHERE codcie10a || '.' || codcie10b = :codigo";
            $stmt = $dbconnFHIR->prepare($sql);
            $stmt->bindParam(':codigo', $diagnostico['codigo_cie10']);
            $stmt->execute();
            $cie10 = $stmt->fetch(PDO::FETCH_ASSOC);


            $entryBundleArray[] = 
            [
                "fullUrl" => "urn:uuid:".$diagnostico['code'],
                "resource" => [
                    "resourceType" => "Condition",
                    "id"           => $diagnostico['code'],
                    "meta" => [
                        "profile"  => ["https://mspbs.gov.py/fhir/StructureDefinition/ConditionPy"]
                    ],
                    "text" => [
                        "status" => "generated",
                        "div"    => "<div xmlns=\"http://www.w3.org/1999/xhtml\"><h1>Condition Example</h1></div>"
                    ],
                    "verificationStatus" => [
                        "coding" => [
                            [
                                "system" => "http://terminology.hl7.org/CodeSystem/condition-ver-status",
                                "code" => $diagnostico['estado']
                            ]
                        ]
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://hl7.org/fhir/sid/icd-10",
                                "code" => $diagnostico['codigo_cie10'],
                                "display" => $cie10['nomcie10']
                            ]
                        ],
                        "text" => $cie10['nomcie10']
                    ],
                    "subject" => [
                        "reference" => "urn:uuid:".$PacienteID
                    ],
                    "onsetPeriod" => [
                        "start" => $diagnostico['fecha']
                    ],
                    "note" => [
                        [
                            "text" => $diagnostico['note']
                        ]
                    ]                                    
                ]

            ];
        }

        // Agregamos las alergias al Bundle FHIR
        foreach ($alergias as $alergia) {
            $entryBundleArray[] = 
            [
                "fullUrl" => "urn:uuid:".$alergia['code'],
                "resource" => [
                    "resourceType" => "AllergyIntolerance",
                    "id"           => $alergia['code'],
                    "meta" => [
                        "profile"  => ["https://mspbs.gov.py/fhir/StructureDefinition/AlergiaPy"]
                    ],
                    "text" => [
                        "status" => "generated",
                        "div"    => "<div xmlns=\"http://www.w3.org/1999/xhtml\"><h1>Alergia Example</h1></div>"
                    ],
                    "clinicalStatus" => [
                        "coding" => [
                            [
                                "system" => "http://terminology.hl7.org/CodeSystem/allergyintolerance-clinical",
                                "code" => "active"
                            ]
                        ]
                    ],
                    "verificationStatus" => [
                        "coding" => [
                            [
                                "system" => "http://terminology.hl7.org/CodeSystem/allergyintolerance-verification",
                                "code" => "confirmed"
                            ]
                        ]
                    ],
                    "category" => [
                        $alergia['category']
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://hl7.org/fhir/sid/icd-10",
                                "code" => $alergia['codigo_alergia'],
                                "display" => $alergia['alergias']
                            ]
                        ],
                        "text" => $alergia['alergias']
                    ],
                    "patient" => [
                        "reference" => "urn:uuid:".$PacienteID
                    ]
                ]
            ];
        }

        // Agregamos los medicamentos al Bundle FHIR
        foreach ($medicamentos as $medicamento) {
            $entryBundleArray[] = 
            [
                "fullUrl" => "urn:uuid:".$medicamento['code'],
                "resource" => [
                    "resourceType" => "MedicationStatement",
                    "id" => $medicamento['code'],
                    "meta" => [
                        "profile" => ["https://mspbs.gov.py/fhir/StructureDefinition/MedicationStatementPy"]
                    ],
                    "text" => [
                        "status" => "generated",
                        "div" => "<div xmlns=\"http://www.w3.org/1999/xhtml\"><h1>Medication Example</h1></div>"
                    ],
                    "status" => "active",
                    "medicationCodeableConcept" => [
                        "text" => $medicamento['descripcion']
                    ],
                    "subject" => [
                        "reference" => "urn:uuid:".$PacienteID
                    ],
                    "effectiveDateTime" => $medicamento['fecha'] ?? date('c'),
                    "dosage" => [
                        [
                            "text" => $medicamento['dosis'],
                            "route" => [
                                "text" => $medicamento['via']
                            ]
                        ]
                    ]
                ]
            ];
        }

        //Agregamos el Practitioner
        $entryBundleArray[] =
        [
            "fullUrl" => "urn:uuid:" . $medico['code'],
            "resource" => [
                "resourceType" => "Practitioner",
                "id" => $medico['code'],
                "meta" => [
                    "profile" => [
                        "https://mspbs.gov.py/fhir/StructureDefinition/PractitionerPy"
                    ]
                ],
                "text" => [
                    "status" => "generated",
                    "div" => "<div xmlns=\"http://www.w3.org/1999/xhtml\"><p class=\"res-header-id\"><b>Generated Narrative: Practitioner PractitionerEjemploPy</b></p><a name=\"PractitionerEjemploPy\"> </a><a name=\"hcPractitionerEjemploPy\"> </a><div style=\"display: inline-block; background-color: #d9e0e7; padding: 6px; margin: 4px; border: 1px solid #8da1b4; border-radius: 5px; line-height: 60%\"><p style=\"margin-bottom: 0px\"/><p style=\"margin-bottom: 0px\">Profile: <a href=\"StructureDefinition-PractitionerPy.html\">Profesional Paraguay</a></p></div><p><b>identifier</b>: Cédula de Identidad/5555555</p><p><b>name</b>: John Doe </p></div>"
                ],
                "identifier" => [
                    [
                        "type" => [
                            "coding" => [
                                [
                                    "system" => "https://mspbs.gov.py/fhir/CodeSystem/IdentificadoresProfesionalCS",
                                    "code" => "01",
                                    "display" => "Cédula de Identidad"
                                ]
                            ]
                        ],
                        "value" => trim($medico['documento'])
                    ]
                ],
                "name" => [
                    [
                        "family" => implode(" ", $familyPartsMedico),
                        "given" => $givenMedico
                    ]
                ]               
            ]
        ];

        //Agregamos el Organization
        $entryBundleArray[] =
        [
            "fullUrl"=> "urn:uuid:". $establecimiento['code'],
            "resource"=> [
                "resourceType"=> "Organization",
                "id"=> $establecimiento['code'],
                "meta" => [
                    "profile" => [ "https://mspbs.gov.py/fhir/StructureDefinition/OrganizacionPy" ]
                ],
                "text" => [
                    "status" => "generated",
                    "div" => "<div xmlns=\"http://www.w3.org/1999/xhtml\"><p class=\"res-header-id\"><b>Generated Narrative: Organization OrganizacionEjemploPy</b></p><a name=\"OrganizacionEjemploPy\"> </a><a name=\"hcOrganizacionEjemploPy\"> </a><div style=\"display: inline-block; background-color: #d9e0e7; padding: 6px; margin: 4px; border: 1px solid #8da1b4; border-radius: 5px; line-height: 60%\"><p style=\"margin-bottom: 0px\"/><p style=\"margin-bottom: 0px\">Profile: <a href=\"StructureDefinition-OrganizacionPy.html\">Organizacion Paraguay</a></p></div><p><b>identifier</b>: 0005000.00010102</p><p><b>type</b>: <span title=\"Codes:\">HG</span></p><p><b>name</b>: HOSPITAL GENERAL DE CORONEL OVIEDO</p></div>"
                ],
                "identifier" => [
                    [
                        "value" => $establecimiento['id_establecimiento']
                    ]
                ],
                "type" => [
                    [
                        "text" => $establecimiento['type']
                    ]
                ],
                "name" => $establecimiento['nombre']
            ]
        ];


        // Construimos el Bundle FHIR
        $entryArray[] = 
        [
            "fullUrl"=> "urn:uuid:".$bundleId,
            "resource"=> [
                "resourceType"=> "Bundle",
                "id"=> $bundleId,
                "meta" => [
                    "profile" => [
                         "https://mspbs.gov.py/fhir/StructureDefinition/BundleDocPy",
                         "https://mspbs.gov.py/fhir/StructureDefinition/BundlePy"
                    ]
                ],
                "identifier"=> [
                    "system"=> "urn:oid",
                    "value"=> $bundleId
                ],
                "type"=> "document",
                "timestamp"=> date('c'),
                "entry"=> $entryBundleArray
            ],
            "request"=> [
                "method"=> "POST",
                "url"=> "Bundle"
            ]
        ];

        //Agregamos el Patient
        $entryArray[] = 
        [
            "fullUrl" => "urn:uuid:".$PacienteID,
            "resource" => [
                "resourceType" => "Patient",
                "id"           => $PacienteID,
                "meta" => [
                    "profile"  => ["https://mspbs.gov.py/fhir/StructureDefinition/PacientePy"]
                ],
                "text" => [
                    "status" => "generated",
                    "div"    => "<div xmlns=\"http://www.w3.org/1999/xhtml\"><a name=\"Patient_PacienteEjemploPy\"> </a><p class=\"res-header-id\"><b>Generated Narrative: Patient PacienteEjemploPy</b></p><a name=\"PacienteEjemploPy\"> </a><a name=\"hcPacienteEjemploPy\"> </a><div style=\"display: inline-block; background-color: #d9e0e7; padding: 6px; margin: 4px; border: 1px solid #8da1b4; border-radius: 5px; line-height: 60%\"><p style=\"margin-bottom: 0px\"/><p style=\"margin-bottom: 0px\">Profile: <a href=\"StructureDefinition-PacientePy.html\">Paciente Paraguay</a></p></div><p style=\"border: 1px #661aff solid; background-color: #e6e6ff; padding: 10px;\">Luis Sanchez  Male, DoB: 1974-12-25 ( Cédula de Identidad: 98765)</p><hr/></div>"
                ],
                "identifier" => [
                    [
                        "type" => [
                            "coding" => [
                                [
                                    "system" => "https://mspbs.gov.py/fhir/CodeSystem/IdentificadoresPersonaCS",
                                    "code" => $paciente['tipo'],
                                    "display" => $paciente['codetipo']
                                ]
                            ]
                        ],
                        "value" => $paciente['documento']
                    ]
                ],
                "name" => [
                    [
                        "family" => implode(" ", $familyParts),
                        "given" => $given
                    ]
                ],
                "gender" => $paciente['sexo'],
                "birthDate" => $paciente['fechanac']
            ],
            "request" => [ "method" => "PUT", "url" => "Patient/".$PacienteID ]
        ];           

        // Estructura base del Bundle FHIR
        $bundle = [
            "resourceType" => "Bundle",               
            "meta" => [
                "profile" => [
                    "https://mspbs.gov.py/fhir/StructureDefinition/BundleTransaccPy"
                ]                  
            ],
            "id" => $bundleId,
            "type" => "transaction",
            "timestamp" => date('c'),
            "entry" => $entryArray
        ];

        return json_encode($bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        // En caso de error, retornar JSON de error
        $errorResponse = [
            "resourceType" => "OperationOutcome",
            "issue" => [
                [
                    "severity" => "error",
                    "code" => "exception",
                    "details" => [
                        "text" => "Error generando Bundle FHIR: " . $e->getMessage()
                    ]
                ]
            ]
        ];
        return json_encode($errorResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}

/*
// Ejecución principal
try {
    require_once('/var/www/html/core/connection.php');
    $dbconnFHIR = getConnectionFHIR();
    
    $JSON = generarFhirBundle(2, 75, $dbconnFHIR);
    
    // Validar que sea JSON válido
    if (json_decode($JSON) === null && json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Error generando JSON válido");
    }
    
    echo $JSON;
    
} catch (Exception $e) {
    $errorResponse = [
        "resourceType" => "OperationOutcome",
        "issue" => [
            [
                "severity" => "error",
                "code" => "exception", 
                "details" => [
                    "text" => "Error general: " . $e->getMessage()
                ]
            ]
        ]
    ];
    echo json_encode($errorResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}
 */