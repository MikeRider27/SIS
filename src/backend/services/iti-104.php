<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('../../core/connection.php');
$dbconn = getConnection();

// Verificar si el usuario está logueado
if (isset($_SESSION['idUsuario'])) {
    if (isset($_POST['accion']) && $_POST['accion'] === "agregar") {
        // Inicialización de variables
        $identifier = trim($_POST['identifier']);
        $family = mb_strtoupper(trim($_POST['family']), 'UTF-8');
        $given = mb_strtoupper(trim($_POST['given']), 'UTF-8');
        $gender = $_POST['gender'];
        $birthDate = $_POST['birthDate'];
        $addressLine = trim($_POST['addressLine']);
        $city = trim($_POST['city']);
        $country = trim($_POST['country']);

        // Preparar el array de datos para que coincida con el segundo JSON
        $arrayData = [
            "resourceType" => "Patient",
            "meta" => [
                "profile" => [
                    "http://profiles.ihe.net/ITI/PIXm/StructureDefinition/IHE.PIXm.Patient"
                ],
                "security" => [
                    [
                        "system" => "http://terminology.hl7.org/CodeSystem/v3-ActReason",
                        "code" => "HTEST"
                    ]
                ]
            ],
            "text" => [
                "status" => "generated",
                // Estructura XHTML correcta en el campo div
                "div" => "<div xmlns=\"http://www.w3.org/1999/xhtml\"><p style=\"border: 1px #661aff solid; background-color: #e6e6ff; padding: 10px;\"><b>Franz Muster </b> male, DoB: 1995-01-27 ( Medical record number: 8734)</p><hr/><table class=\"grid\"><tr><td style=\"background-color: #f3f5da\" title=\"Alternate names (see the one above)\">Alt. Name:</td><td colspan=\"3\">Muster </td></tr><tr><td style=\"background-color: #f3f5da\" title=\"Patient Links\">Links:</td><td colspan=\"3\"><ul><li>Managing Organization: <span/></li></ul></td></tr></table></div>"
            ],
            "identifier" => [
                [
                    "type" => [
                        "coding" => [
                            [
                                "system" => "http://terminology.hl7.org/CodeSystem/v2-0203",
                                "code" => "TAX"
                            ]
                        ]
                    ],
                    "system" => "urn:oid:2.16.756.888801.3.4",
                    "value" => $identifier
                ]
            ],
            "name" => [
                [
                    "family" => $family,
                    "given" => [$given]
                ]
            ],
            "address" => [
                [
                    "line" => [$addressLine],
                    "city" => $city,
                    "country" => $country
                ]
            ],
            "gender" => $gender,
            "birthDate" => $birthDate,
            "managingOrganization" => [
                "identifier" => [
                    "system" => "urn:oid:2.16.600",
                    "value" => "Ministerio de Salud Publica y Bienestar Social"
                ]
            ]
        ];

        // Convertir a JSON y mostrarlo para depuración
        $json = json_encode($arrayData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);


        // Verificar que la constante IUAToken esté definida
        if (!defined('IUAToken')) {
            die(json_encode(array("status" => "error", "message" => "Token de autenticación no encontrado")));
        }

        // Aquí iría el código cURL para la petición PUT
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://gazelle.racsel.org/pixm-connector/fhir/Patient?identifier=urn:oid:2.16.600|' . $identifier);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/fhir+json",
            "Accept: application/fhir+json",
            "Authorization: Bearer " . IUAToken  // Usamos la constante IUAToken
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

        // Ejecutar la solicitud cURL
        $response = curl_exec($ch);

        // Verificar si hubo un error en cURL
        if (curl_errno($ch)) {
            print json_encode(array("status" => "error", "message" => "Error de cURL: " . curl_error($ch)));
        } else {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            // Imprimir la respuesta completa y el código HTTP para depuración
            echo "<pre>Respuesta del servidor:</pre>";
            echo "<pre>$response</pre>";
            echo "<pre>Código HTTP:</pre>";
            echo "<pre>$httpCode</pre>";

            // Verificar la respuesta de la API
            if ($httpCode === 200) {
                $responseBody = json_decode($response, true);
                $patientId = $responseBody['id'];
                $patientFamily = $responseBody['name'][0]['family'];
                $patientGiven = $responseBody['name'][0]['given'][0];
                $patientGender = $responseBody['gender'];
                $patientBirthDate = $responseBody['birthDate'];
                $patientAddress = $responseBody['address'][0]['line'][0] . ", " . $responseBody['address'][0]['city'] . ", " . $responseBody['address'][0]['country'];

                // Formatear la respuesta
                $formattedResponse = array(
                    "status" => "success",
                    "data" => array(
                        "id" => $patientId,
                        "name" => $patientGiven . " " . $patientFamily,
                        "gender" => $patientGender,
                        "birthDate" => $patientBirthDate,
                        "address" => $patientAddress
                    )
                );

                print json_encode($formattedResponse);
            } else {
                // Mostrar el mensaje de error con más detalles
                print json_encode(array("status" => "error", "message" => "Error al agregar el paciente", "code" => $httpCode, "response" => $response));
            }
        }

        // Cerrar la sesión cURL
        curl_close($ch);
    } else {
        print json_encode(array("status" => "error", "message" => "Formulario no enviado"));
    }
} else {
    print json_encode(array("status" => "error", "message" => "No autorizado"));
}
