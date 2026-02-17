<?php
header('Content-Type: application/json');

$id = $_GET['id'] ?? null;
if(!$id){
    echo json_encode(['status'=>'error','message'=>'ID requerido']);
    exit;
}

$ch = curl_init("https://fhir-conectaton.mspbs.gov.py/fhir/Patient/$id");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/fhir+json']);
$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo json_encode(['status'=>$status==200?'success':'error','response'=>$response]);
