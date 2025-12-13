<?php
// Definir la zona horaria predeterminada
date_default_timezone_set('America/Asuncion');

// Definimos las variables de conexión capacitacion
define("DB_HOST", "192.168.1.106");
define("DB_PORT", "5432");
define("DB_NAME", "mspbs_sis");
define("DB_USER", "postgres");
define("DB_PASSWORD", "dgtic123");

//COUNTRY
define("APP_COUNTRY_CODE", "PY");

//FHIR SERVER ENDPOINT
define("APP_FHIR_SERVER", "https://fhir-conectaton.mspbs.gov.py/fhir");
