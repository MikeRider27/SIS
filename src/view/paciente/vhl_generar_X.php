{
  "resourceType": "Composition",
  "meta": {
    "profile": [
      "http://lacpass.racsel.org/StructureDefinition/lac-composition"
    ]
  },
  "text": {
    "status": "generated",
    "div": "
Documento Resumen Clínico de Paciente de Paraguay
"
  },
  "status": "final",
  "type": {
    "coding": [
      {
        "system": "http://loinc.org",
        "code": "60591-5",
        "display": "Patient Summary Document"
      }
    ]
  },
  "subject": {
    "reference": "urn:uuid:e4a48f79-fbdc-4217-93b9-647b55ca2a54"
  },
  "date": "2025-10-17T10:40:31-03:00",
  "author": [
    {
      "reference": "urn:uuid:bc57b7fa-3449-4c4e-b8d3-3f4c029a1c27"
    }
  ],
  "title": "Patient Summary as of 17/10/2025",
  "confidentiality": "N",
  "custodian": {
    "reference": "urn:uuid:bc57b7fa-3449-4c4e-b8d3-3f4c029a1c27"
  },
  "section": [
    {
      "title": "Allergies and Intolerances",
      "code": {
        "coding": [
          {
            "system": "http://loinc.org",
            "code": "48765-2",
            "display": "Allergies and adverse reactions Document"
          }
        ]
      },
      "text": {
        "status": "generated",
        "div": "
Resumen de alergias e intolerancias registradas.
"
      },
      "entry": [
        {
          "reference": "urn:uuid:f0ef68da-a545-449f-bf5b-34d7c4b35242"
        }
      ]
    },
    {
      "title": "Procedures",
      "code": {
        "coding": [
          {
            "system": "http://loinc.org",
            "code": "47519-4",
            "display": "History of Procedures Document"
          }
        ]
      },
      "text": {
        "status": "generated",
        "div": "
Procedimientos médicos realizados.
"
      },
      "entry": [
        {
          "reference": "urn:uuid:32e6dd04-8aa2-4a64-983b-4a124adb406c"
        }
      ]
    }
  ]
}