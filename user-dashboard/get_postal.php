<?php
include '../connect.php';
header('Content-Type: application/json');

$region = $_GET['region'] ?? '';
$city = $_GET['city'] ?? '';
$barangay = $_GET['barangay'] ?? '';

$postal = '';

// NCR Cities
if ($region === 'NCR') {
    switch($city) {
        case 'NCR1': // Manila
            switch($barangay) {
                case 'B101': $postal = '1000'; break;
                case 'B102': $postal = '1001'; break;
                case 'B103': $postal = '1002'; break;
                case 'B104': $postal = '1003'; break;
                case 'B105': $postal = '1004'; break;
                case 'B106': $postal = '1005'; break;
                case 'B107': $postal = '1006'; break;
                case 'B108': $postal = '1007'; break;
                case 'B109': $postal = '1008'; break;
                case 'B110': $postal = '1009'; break;
                default: $postal = '1000';
            }
            break;
            
        case 'NCR2': // Quezon City
            switch($barangay) {
                case 'QC1': $postal = '1105'; break; // Alicia
                case 'QC2': $postal = '1119'; break; // Bagong Silangan
                case 'QC3': $postal = '1126'; break; // Batasan Hills
                case 'QC4': $postal = '1121'; break; // Commonwealth
                case 'QC5': $postal = '1127'; break; // Holy Spirit
                case 'QC6': $postal = '1104'; break; // Bagong Pag-asa
                case 'QC7': $postal = '1106'; break; // Balingasa
                case 'QC8': $postal = '1128'; break; // Culiat
                case 'QC9': $postal = '1101'; break; // Diliman
                case 'QC10': $postal = '1110'; break; // E. Rodriguez
                default: $postal = '1100';
            }
            break;
            
        case 'NCR3': // Makati
            switch($barangay) {
                case 'MK1': $postal = '1233'; break; // Bangkal
                case 'MK2': $postal = '1209'; break; // Bel-Air
                case 'MK3': $postal = '1207'; break; // Carmona
                case 'MK4': $postal = '1222'; break; // Dasmariñas
                case 'MK5': $postal = '1219'; break; // Forbes Park
                case 'MK6': $postal = '1212'; break; // Guadalupe Nuevo
                case 'MK7': $postal = '1232'; break; // Magallanes
                case 'MK8': $postal = '1210'; break; // Poblacion
                case 'MK9': $postal = '1223'; break; // San Lorenzo
                case 'MK10': $postal = '1225'; break; // Urdaneta
                default: $postal = '1200';
            }
            break;
            
        case 'NCR4': // Pasig
            switch($barangay) {
                case 'PSG1': $postal = '1600'; break; // Bagong Ilog
                case 'PSG2': $postal = '1601'; break; // Bagong Katipunan
                case 'PSG3': $postal = '1602'; break; // Caniogan
                case 'PSG4': $postal = '1603'; break; // Kapitolyo
                case 'PSG5': $postal = '1604'; break; // Malinao
                case 'PSG6': $postal = '1605'; break; // Oranbo
                case 'PSG7': $postal = '1606'; break; // Pineda
                case 'PSG8': $postal = '1607'; break; // San Antonio
                case 'PSG9': $postal = '1608'; break; // San Nicolas
                case 'PSG10': $postal = '1609'; break; // Ugong
                default: $postal = '1600';
            }
            break;
            
        case 'NCR5': // Taguig
            switch($barangay) {
                case 'TG1': $postal = '1632'; break; // Bagumbayan
                case 'TG2': $postal = '1633'; break; // Bambang
                case 'TG3': $postal = '1634'; break; // Calzada
                case 'TG4': $postal = '1630'; break; // Central Bicutan
                case 'TG5': $postal = '1631'; break; // Central Signal
                case 'TG6': $postal = '1635'; break; // Fort Bonifacio
                case 'TG7': $postal = '1636'; break; // Katuparan
                case 'TG8': $postal = '1637'; break; // Maharlika Village
                case 'TG9': $postal = '1638'; break; // Pinagsama
                case 'TG10': $postal = '1639'; break; // Western Bicutan
                default: $postal = '1630';
            }
            break;
    }
}

// Region 1 Cities
else if ($region === 'Region1') {
    switch($city) {
        case 'R1C1': // Laoag City
            switch($barangay) {
                case 'LG1': $postal = '2900'; break;
                case 'LG2': $postal = '2901'; break;
                case 'LG3': $postal = '2902'; break;
                case 'LG4': $postal = '2903'; break;
                case 'LG5': $postal = '2904'; break;
                case 'LG6': $postal = '2905'; break;
                case 'LG7': $postal = '2906'; break;
                case 'LG8': $postal = '2907'; break;
                case 'LG9': $postal = '2908'; break;
                case 'LG10': $postal = '2909'; break;
                default: $postal = '2900';
            }
            break;
        case 'R1C2': $postal = '2700'; break; // Vigan City
        case 'R1C3': $postal = '2500'; break; // San Fernando City
        case 'R1C4': $postal = '2404'; break; // Alaminos City
        case 'R1C5': $postal = '2428'; break; // Urdaneta City
    }
}

// Region 2 Cities
else if ($region === 'Region2') {
    switch($city) {
        case 'R2C1': // Tuguegarao City
            switch($barangay) {
                case 'TUG1': $postal = '3500'; break;
                case 'TUG2': $postal = '3501'; break;
                case 'TUG3': $postal = '3502'; break;
                case 'TUG4': $postal = '3503'; break;
                case 'TUG5': $postal = '3504'; break;
                default: $postal = '3500';
            }
            break;
        case 'R2C2': $postal = '3305'; break; // Cauayan City
        case 'R2C3': $postal = '3300'; break; // Ilagan City
        case 'R2C4': $postal = '3311'; break; // Santiago City
        case 'R2C5': $postal = '3700'; break; // Bayombong
    }
}

// Region 3 Cities
else if ($region === 'Region3') {
    switch($city) {
        case 'R3C1': $postal = '2000'; break; // San Fernando City
        case 'R3C2': $postal = '2009'; break; // Angeles City
        case 'R3C3': $postal = '2200'; break; // Olongapo City
        case 'R3C4': $postal = '3000'; break; // Malolos
        case 'R3C5': $postal = '2300'; break; // Tarlac City
    }
}

// Region 4A Cities
else if ($region === 'Region4A') {
    switch($city) {
        case 'R4AC1': $postal = '4027'; break; // Calamba
        case 'R4AC2': $postal = '4200'; break; // Batangas City
        case 'R4AC3': $postal = '4301'; break; // Lucena City
        case 'R4AC4': $postal = '1870'; break; // Antipolo
        case 'R4AC5': $postal = '4120'; break; // Tagaytay
    }
}

// Region 5 Cities
else if ($region === 'Region5') {
    switch($city) {
        case 'R5C1': $postal = '4500'; break; // Legazpi City
        case 'R5C2': $postal = '4600'; break; // Naga City
        case 'R5C3': $postal = '4701'; break; // Sorsogon City
        case 'R5C4': $postal = '4800'; break; // Masbate City
        case 'R5C5': $postal = '4428'; break; // Tabaco City
    }
}

// Region 6 Cities
else if ($region === 'Region6') {
    switch($city) {
        case 'R6C1': $postal = '5000'; break; // Iloilo City
        case 'R6C2': $postal = '6100'; break; // Bacolod City
        case 'R6C3': $postal = '5800'; break; // Roxas City
        case 'R6C4': $postal = '6200'; break; // Kalibo
        case 'R6C5': $postal = '5037'; break; // Passi City
    }
}

// Region 7 Cities
else if ($region === 'Region7') {
    switch($city) {
        case 'R7C1': $postal = '6000'; break; // Cebu City
        case 'R7C2': $postal = '6014'; break; // Mandaue City
        case 'R7C3': $postal = '6015'; break; // Lapu-Lapu City
        case 'R7C4': $postal = '6300'; break; // Tagbilaran City
        case 'R7C5': $postal = '6038'; break; // Toledo City
    }
}

// Region 8 Cities
else if ($region === 'Region8') {
    switch($city) {
        case 'R8C1': $postal = '6500'; break; // Tacloban City
        case 'R8C2': $postal = '6541'; break; // Ormoc City
        case 'R8C3': $postal = '6800'; break; // Borongan
        case 'R8C4': $postal = '6700'; break; // Catbalogan
        case 'R8C5': $postal = '6600'; break; // Maasin City
    }
}

// Region 9 Cities
else if ($region === 'Region9') {
    switch($city) {
        case 'R9C1': $postal = '7000'; break; // Zamboanga City
        case 'R9C2': $postal = '7100'; break; // Dipolog City
        case 'R9C3': $postal = '7101'; break; // Dapitan City
        case 'R9C4': $postal = '7016'; break; // Pagadian City
        case 'R9C5': $postal = '7300'; break; // Isabela City
    }
}

// Region 10 Cities
else if ($region === 'Region10') {
    switch($city) {
        case 'R10C1': $postal = '9000'; break; // Cagayan de Oro
        case 'R10C2': $postal = '9200'; break; // Iligan City
        case 'R10C3': $postal = '8709'; break; // Valencia City
        case 'R10C4': $postal = '8700'; break; // Malaybalay City
        case 'R10C5': $postal = '7207'; break; // Oroquieta City
    }
}

// Region 11 Cities
else if ($region === 'Region11') {
    switch($city) {
        case 'R11C1': // Davao City
            switch($barangay) {
                case 'DVO1': $postal = '8000'; break; // Agdao
                case 'DVO2': $postal = '8001'; break; // Buhangin
                case 'DVO3': $postal = '8002'; break; // Catalunan Grande
                case 'DVO4': $postal = '8003'; break; // Dumoy
                case 'DVO5': $postal = '8004'; break; // Langub
                case 'DVO6': $postal = '8005'; break; // Maa
                case 'DVO7': $postal = '8006'; break; // Matina
                case 'DVO8': $postal = '8007'; break; // Poblacion
                case 'DVO9': $postal = '8008'; break; // Talomo
                case 'DVO10': $postal = '8009'; break; // Toril
                default: $postal = '8000';
            }
            break;
        case 'R11C2': $postal = '8002'; break; // Digos City
        case 'R11C3': $postal = '8100'; break; // Tagum City
        case 'R11C4': $postal = '8200'; break; // Mati City
        case 'R11C5': $postal = '8105'; break; // Panabo City
    }
}

// Region 12 Cities
else if ($region === 'Region12') {
    switch($city) {
        case 'R12C1': // Koronadal City
            switch($barangay) {
                case 'KOR1': $postal = '9506'; break; // Assumption
                case 'KOR2': $postal = '9507'; break; // Caloocan
                case 'KOR3': $postal = '9508'; break; // Carpenter Hill
                case 'KOR4': $postal = '9509'; break; // Esperanza
                case 'KOR5': $postal = '9510'; break; // General Paulino Santos
                case 'KOR6': $postal = '9511'; break; // Mabini
                case 'KOR7': $postal = '9512'; break; // Morales
                case 'KOR8': $postal = '9513'; break; // San Isidro
                case 'KOR9': $postal = '9514'; break; // Santa Cruz
                case 'KOR10': $postal = '9515'; break; // Zone III
                default: $postal = '9506';
            }
            break;
        case 'R12C2': $postal = '9500'; break; // General Santos City
        case 'R12C3': $postal = '9400'; break; // Kidapawan City
        case 'R12C4': $postal = '9800'; break; // Tacurong City
        case 'R12C5': $postal = '9600'; break; // Cotabato City
    }
}

// Region 13 Cities
else if ($region === 'Region13') {
    switch($city) {
        case 'R13C1': // Butuan City
            switch($barangay) {
                case 'BUT1': $postal = '8600'; break; // Agusan Pequeño
                case 'BUT2': $postal = '8601'; break; // Ampayon
                case 'BUT3': $postal = '8602'; break; // Baan
                case 'BUT4': $postal = '8603'; break; // Bancasi
                case 'BUT5': $postal = '8604'; break; // Banza
                case 'BUT6': $postal = '8605'; break; // Buhangin
                case 'BUT7': $postal = '8606'; break; // Doongan
                case 'BUT8': $postal = '8607'; break; // Golden Ribbon
                case 'BUT9': $postal = '8608'; break; // Holy Redeemer
                case 'BUT10': $postal = '8609'; break; // Imadejas
                default: $postal = '8600';
            }
            break;
        case 'R13C2': $postal = '8400'; break; // Surigao City
        case 'R13C3': $postal = '8311'; break; // Bislig City
        case 'R13C4': $postal = '8300'; break; // Tandag City
        case 'R13C5': $postal = '8605'; break; // Cabadbaran City
    }
}

// CAR Cities
else if ($region === 'CAR') {
    switch($city) {
        case 'CARC1': $postal = '2600'; break; // Baguio City
        case 'CARC2': $postal = '3800'; break; // Tabuk City
        case 'CARC3': $postal = '2601'; break; // La Trinidad
        case 'CARC4': $postal = '2800'; break; // Bangued
        case 'CARC5': $postal = '3600'; break; // Lagawe
    }
}

// BARMM Cities
else if ($region === 'BARMM') {
    switch($city) {
        case 'BARMMC1': $postal = '9700'; break; // Marawi City
        case 'BARMMC2': $postal = '7300'; break; // Lamitan City
        case 'BARMMC3': $postal = '9600'; break; // Cotabato City
        case 'BARMMC4': $postal = '7400'; break; // Jolo
        case 'BARMMC5': $postal = '7500'; break; // Bongao
    }
}

echo json_encode(['postal_code' => $postal]);