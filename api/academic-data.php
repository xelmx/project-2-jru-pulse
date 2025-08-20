<?php

header('Content-Type: application/json');

$academicData = [
    "COLLEGE" => [
        "AB-ECO", "BSA", "AB-HIS", "AB-PSY", "BEED", "BSAM", "BSBA-ACC", 
        "BSBA-ECO", "BSBA-FIN", "BSBA-MGT", "BSBA-MKTG", "BSBA-SMGT", 
        "BSCPE", "BSCRIM", "BSECE", "BSED-ENG", "BSED-MAT", "BSED-SOC", 
        "BSEMC-DAT", "BSEMC-GAD", "BSHM", "BSHM-CMGT", "BSIT", "BSIT-AGD", 
        "BSIT-BA", "BSLGM", "BSN", "BSPSY", "BSTM", "MBA TGSB", "MPA", "TCP"
    ],
    "ES" => ["ES"],
    "GS" => [
        "DBA", "EDD", "EDD-SM", "MAED-ADMIN", "MAED-LANG", "MAED-SCI(OC)", 
        "MAED-SE", "MBA", "MBA TGSB", "MBA TGSB (T)", "MHM", "MPA"
    ],
    "JHS" => ["JHS"],
    "KG" => ["KG"],
    "LS" => ["JD"],
    "SHS" => [
        "SHS-ABM", "SHS-AD", "SHS-AN", "SHS-CHSS", "SHS-FB", 
        "SHS-HSSGA", "SHS-SP", "SHS-STEM", "SHS-TG"
    ]
];

echo json_encode(['success' => true, 'data' => $academicData]);
exit;
?>