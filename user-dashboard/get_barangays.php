<?php
require_once '../connect.php';

header('Content-Type: application/json');

$city = $_GET['city'] ?? '';
$barangays = [];

// Add barangay data based on city
switch($city) {
    case 'NCR1': // Manila
        $barangays = [
            ['id' => 'B101', 'name' => 'Barangay 101'],
            ['id' => 'B102', 'name' => 'Barangay 102'],
            ['id' => 'B103', 'name' => 'Barangay 103'],
            ['id' => 'B104', 'name' => 'Barangay 104'],
            ['id' => 'B105', 'name' => 'Barangay 105'],
            ['id' => 'B106', 'name' => 'Barangay 106'],
            ['id' => 'B107', 'name' => 'Barangay 107'],
            ['id' => 'B108', 'name' => 'Barangay 108'],
            ['id' => 'B109', 'name' => 'Barangay 109'],
            ['id' => 'B110', 'name' => 'Barangay 110']
        ];
        break;
        
    case 'NCR2': // Quezon City
        $barangays = [
            ['id' => 'QC1', 'name' => 'Alicia'],
            ['id' => 'QC2', 'name' => 'Bagong Silangan'],
            ['id' => 'QC3', 'name' => 'Batasan Hills'],
            ['id' => 'QC4', 'name' => 'Commonwealth'],
            ['id' => 'QC5', 'name' => 'Holy Spirit'],
            ['id' => 'QC6', 'name' => 'Bagong Pag-asa'],
            ['id' => 'QC7', 'name' => 'Balingasa'],
            ['id' => 'QC8', 'name' => 'Culiat'],
            ['id' => 'QC9', 'name' => 'Diliman'],
            ['id' => 'QC10', 'name' => 'E. Rodriguez']
        ];
        break;
        
    case 'NCR3': // Makati
        $barangays = [
            ['id' => 'MK1', 'name' => 'Bangkal'],
            ['id' => 'MK2', 'name' => 'Bel-Air'],
            ['id' => 'MK3', 'name' => 'Carmona'],
            ['id' => 'MK4', 'name' => 'Dasmariñas'],
            ['id' => 'MK5', 'name' => 'Forbes Park'],
            ['id' => 'MK6', 'name' => 'Guadalupe Nuevo'],
            ['id' => 'MK7', 'name' => 'Magallanes'],
            ['id' => 'MK8', 'name' => 'Poblacion'],
            ['id' => 'MK9', 'name' => 'San Lorenzo'],
            ['id' => 'MK10', 'name' => 'Urdaneta']
        ];
        break;

    case 'NCR4': // Pasig
        $barangays = [
            ['id' => 'PSG1', 'name' => 'Bagong Ilog'],
            ['id' => 'PSG2', 'name' => 'Bagong Katipunan'],
            ['id' => 'PSG3', 'name' => 'Caniogan'],
            ['id' => 'PSG4', 'name' => 'Kapitolyo'],
            ['id' => 'PSG5', 'name' => 'Malinao'],
            ['id' => 'PSG6', 'name' => 'Oranbo'],
            ['id' => 'PSG7', 'name' => 'Pineda'],
            ['id' => 'PSG8', 'name' => 'San Antonio'],
            ['id' => 'PSG9', 'name' => 'San Nicolas'],
            ['id' => 'PSG10', 'name' => 'Ugong']
        ];
        break;

    case 'NCR5': // Taguig
        $barangays = [
            ['id' => 'TG1', 'name' => 'Bagumbayan'],
            ['id' => 'TG2', 'name' => 'Bambang'],
            ['id' => 'TG3', 'name' => 'Calzada'],
            ['id' => 'TG4', 'name' => 'Central Bicutan'],
            ['id' => 'TG5', 'name' => 'Central Signal'],
            ['id' => 'TG6', 'name' => 'Fort Bonifacio'],
            ['id' => 'TG7', 'name' => 'Katuparan'],
            ['id' => 'TG8', 'name' => 'Maharlika Village'],
            ['id' => 'TG9', 'name' => 'Pinagsama'],
            ['id' => 'TG10', 'name' => 'Western Bicutan']
        ];
        break;

    case 'R1C1': // Laoag City
        $barangays = [
            ['id' => 'LG1', 'name' => 'Barangay 1 - Poblacion'],
            ['id' => 'LG2', 'name' => 'Barangay 2 - Poblacion'],
            ['id' => 'LG3', 'name' => 'Barangay 3 - Poblacion'],
            ['id' => 'LG4', 'name' => 'Barangay 4 - Poblacion'],
            ['id' => 'LG5', 'name' => 'Barangay 5 - Poblacion'],
            ['id' => 'LG6', 'name' => 'Barangay 6 - Poblacion'],
            ['id' => 'LG7', 'name' => 'Barangay 7 - Poblacion'],
            ['id' => 'LG8', 'name' => 'Barangay 8 - Poblacion'],
            ['id' => 'LG9', 'name' => 'Barangay 9 - Poblacion'],
            ['id' => 'LG10', 'name' => 'Barangay 10 - Poblacion']
        ];
        break;

    case 'R2C1': // Tuguegarao City
        $barangays = [
            ['id' => 'TUG1', 'name' => 'Annafunan East'],
            ['id' => 'TUG2', 'name' => 'Bagay'],
            ['id' => 'TUG3', 'name' => 'Caggay'],
            ['id' => 'TUG4', 'name' => 'Caritan Centro'],
            ['id' => 'TUG5', 'name' => 'Centro 1'],
            ['id' => 'TUG6', 'name' => 'Centro 2'],
            ['id' => 'TUG7', 'name' => 'Centro 3'],
            ['id' => 'TUG8', 'name' => 'Centro 4'],
            ['id' => 'TUG9', 'name' => 'Centro 5'],
            ['id' => 'TUG10', 'name' => 'Centro 6']
        ];
        break;

    case 'R3C1': // San Fernando City
        $barangays = [
            ['id' => 'SF1', 'name' => 'Alasas'],
            ['id' => 'SF2', 'name' => 'Baliti'],
            ['id' => 'SF3', 'name' => 'Bulaon'],
            ['id' => 'SF4', 'name' => 'Calulut'],
            ['id' => 'SF5', 'name' => 'Del Carmen'],
            ['id' => 'SF6', 'name' => 'Del Pilar'],
            ['id' => 'SF7', 'name' => 'Del Rosario'],
            ['id' => 'SF8', 'name' => 'Dolores'],
            ['id' => 'SF9', 'name' => 'Juliana'],
            ['id' => 'SF10', 'name' => 'Lara']
        ];
        break;

    case 'R4AC1': // Calamba
        $barangays = [
            ['id' => 'CAL1', 'name' => 'Banadero'],
            ['id' => 'CAL2', 'name' => 'Banlic'],
            ['id' => 'CAL3', 'name' => 'Batino'],
            ['id' => 'CAL4', 'name' => 'Bucal'],
            ['id' => 'CAL5', 'name' => 'Canlubang'],
            ['id' => 'CAL6', 'name' => 'Halang'],
            ['id' => 'CAL7', 'name' => 'La Mesa'],
            ['id' => 'CAL8', 'name' => 'Lingga'],
            ['id' => 'CAL9', 'name' => 'Majada'],
            ['id' => 'CAL10', 'name' => 'Mapagong']
        ];
        break;

    case 'R5C1': // Legazpi City
        $barangays = [
            ['id' => 'LEG1', 'name' => 'Bagong Abre'],
            ['id' => 'LEG2', 'name' => 'Bigaa'],
            ['id' => 'LEG3', 'name' => 'Bogtong'],
            ['id' => 'LEG4', 'name' => 'Bonot'],
            ['id' => 'LEG5', 'name' => 'Buyuan'],
            ['id' => 'LEG6', 'name' => 'Cabangan'],
            ['id' => 'LEG7', 'name' => 'Cruzada'],
            ['id' => 'LEG8', 'name' => 'Em\'s Barrio'],
            ['id' => 'LEG9', 'name' => 'Homapon'],
            ['id' => 'LEG10', 'name' => 'Imperial Court']
        ];
        break;

    case 'R6C1': // Iloilo City
        $barangays = [
            ['id' => 'ILO1', 'name' => 'Arevalo'],
            ['id' => 'ILO2', 'name' => 'Baldoza'],
            ['id' => 'ILO3', 'name' => 'Calaparan'],
            ['id' => 'ILO4', 'name' => 'Dungon'],
            ['id' => 'ILO5', 'name' => 'Flores'],
            ['id' => 'ILO6', 'name' => 'Gustilo'],
            ['id' => 'ILO7', 'name' => 'Hibao-an'],
            ['id' => 'ILO8', 'name' => 'Ingore'],
            ['id' => 'ILO9', 'name' => 'La Paz'],
            ['id' => 'ILO10', 'name' => 'Molo']
        ];
        break;

    case 'R7C1': // Cebu City
        $barangays = [
            ['id' => 'CC1', 'name' => 'Apas'],
            ['id' => 'CC2', 'name' => 'Banilad'],
            ['id' => 'CC3', 'name' => 'Capitol Site'],
            ['id' => 'CC4', 'name' => 'Cogon Ramos'],
            ['id' => 'CC5', 'name' => 'Guadalupe'],
            ['id' => 'CC6', 'name' => 'Lahug'],
            ['id' => 'CC7', 'name' => 'Luz'],
            ['id' => 'CC8', 'name' => 'Mabolo'],
            ['id' => 'CC9', 'name' => 'Punta Princesa'],
            ['id' => 'CC10', 'name' => 'Talamban']
        ];
        break;

    case 'R8C1': // Tacloban City
        $barangays = [
            ['id' => 'TAC1', 'name' => 'Abucay'],
            ['id' => 'TAC2', 'name' => 'Anibong'],
            ['id' => 'TAC3', 'name' => 'Apitong'],
            ['id' => 'TAC4', 'name' => 'Bagacay'],
            ['id' => 'TAC5', 'name' => 'Caibaan'],
            ['id' => 'TAC6', 'name' => 'Calanipawan'],
            ['id' => 'TAC7', 'name' => 'Campetic'],
            ['id' => 'TAC8', 'name' => 'Diit'],
            ['id' => 'TAC9', 'name' => 'Fatima'],
            ['id' => 'TAC10', 'name' => 'Libertad']
        ];
        break;

    case 'R9C1': // Zamboanga City
        $barangays = [
            ['id' => 'ZAM1', 'name' => 'Ayala'],
            ['id' => 'ZAM2', 'name' => 'Baliwasan'],
            ['id' => 'ZAM3', 'name' => 'Cabatangan'],
            ['id' => 'ZAM4', 'name' => 'Divisoria'],
            ['id' => 'ZAM5', 'name' => 'Guiwan'],
            ['id' => 'ZAM6', 'name' => 'La Paz'],
            ['id' => 'ZAM7', 'name' => 'Pasonanca'],
            ['id' => 'ZAM8', 'name' => 'Putik'],
            ['id' => 'ZAM9', 'name' => 'Santa Maria'],
            ['id' => 'ZAM10', 'name' => 'Tetuan']
        ];
        break;

    case 'R10C1': // Cagayan de Oro
        $barangays = [
            ['id' => 'CDO1', 'name' => 'Agusan'],
            ['id' => 'CDO2', 'name' => 'Balulang'],
            ['id' => 'CDO3', 'name' => 'Carmen'],
            ['id' => 'CDO4', 'name' => 'Gusa'],
            ['id' => 'CDO5', 'name' => 'Kauswagan'],
            ['id' => 'CDO6', 'name' => 'Lapasan'],
            ['id' => 'CDO7', 'name' => 'Macasandig'],
            ['id' => 'CDO8', 'name' => 'Nazareth'],
            ['id' => 'CDO9', 'name' => 'Patag'],
            ['id' => 'CDO10', 'name' => 'Puerto']
        ];
        break;

    case 'R11C1': // Davao City
        $barangays = [
            ['id' => 'DVO1', 'name' => 'Agdao'],
            ['id' => 'DVO2', 'name' => 'Buhangin'],
            ['id' => 'DVO3', 'name' => 'Catalunan Grande'],
            ['id' => 'DVO4', 'name' => 'Dumoy'],
            ['id' => 'DVO5', 'name' => 'Langub'],
            ['id' => 'DVO6', 'name' => 'Maa'],
            ['id' => 'DVO7', 'name' => 'Matina'],
            ['id' => 'DVO8', 'name' => 'Poblacion'],
            ['id' => 'DVO9', 'name' => 'Talomo'],
            ['id' => 'DVO10', 'name' => 'Toril']
        ];
        break;

    case 'R12C1': // Koronadal City
        $barangays = [
            ['id' => 'KOR1', 'name' => 'Assumption'],
            ['id' => 'KOR2', 'name' => 'Caloocan'],
            ['id' => 'KOR3', 'name' => 'Carpenter Hill'],
            ['id' => 'KOR4', 'name' => 'Esperanza'],
            ['id' => 'KOR5', 'name' => 'General Paulino Santos'],
            ['id' => 'KOR6', 'name' => 'Mabini'],
            ['id' => 'KOR7', 'name' => 'Morales'],
            ['id' => 'KOR8', 'name' => 'San Isidro'],
            ['id' => 'KOR9', 'name' => 'Santa Cruz'],
            ['id' => 'KOR10', 'name' => 'Zone III']
        ];
        break;

    case 'R13C1': // Butuan City
        $barangays = [
            ['id' => 'BUT1', 'name' => 'Agusan Pequeño'],
            ['id' => 'BUT2', 'name' => 'Ampayon'],
            ['id' => 'BUT3', 'name' => 'Baan'],
            ['id' => 'BUT4', 'name' => 'Bancasi'],
            ['id' => 'BUT5', 'name' => 'Banza'],
            ['id' => 'BUT6', 'name' => 'Buhangin'],
            ['id' => 'BUT7', 'name' => 'Doongan'],
            ['id' => 'BUT8', 'name' => 'Golden Ribbon'],
            ['id' => 'BUT9', 'name' => 'Holy Redeemer'],
            ['id' => 'BUT10', 'name' => 'Imadejas']
        ];
        break;
}

// Return the barangays as JSON
echo json_encode([
    'status' => 'success',
    'barangays' => $barangays
]);
exit;