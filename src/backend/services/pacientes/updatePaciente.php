<?php
header('Content-Type: application/json');

$id = $_GET['id'] ?? null;
if(!$id){
    echo json_encode(['status'=>'error','message'=>'ID requerido']);
    exit;
}

$input = file_get_contents("php://input");
if(!$input){
    echo json_encode(['status'=>'error','message'=>'JSON requerido']);
    exit;
}

$ch = curl_init("https://fhir-conectaton.mspbs.gov.py/fhir/Patient/$id");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/fhir+json',
    'Accept: application/fhir+json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo json_encode(['status'=>$status==200?'success':'error','response'=>$response]);
