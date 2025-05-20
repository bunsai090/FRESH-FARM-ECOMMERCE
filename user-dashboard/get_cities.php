<?php
require_once '../connect.php';

header('Content-Type: application/json');

$region = $_GET['region'] ?? '';
$cities = [];

// Add city data based on region
switch($region) {
    case 'NCR':
        $cities = [
            ['id' => 'NCR1', 'name' => 'Manila'],
            ['id' => 'NCR2', 'name' => 'Quezon City'], 
            ['id' => 'NCR3', 'name' => 'Makati'],
            ['id' => 'NCR4', 'name' => 'Pasig'],
            ['id' => 'NCR5', 'name' => 'Taguig'],
        ];
        break;
    case 'Region1':
        $cities = [
            ['id' => 'R1C1', 'name' => 'Laoag City'],
            ['id' => 'R1C2', 'name' => 'Vigan City'],
            ['id' => 'R1C3', 'name' => 'San Fernando City'],
            ['id' => 'R1C4', 'name' => 'Alaminos City'],
            ['id' => 'R1C5', 'name' => 'Urdaneta City'],
        ];
        break;
    case 'Region2':
        $cities = [
            ['id' => 'R2C1', 'name' => 'Tuguegarao City'],
            ['id' => 'R2C2', 'name' => 'Cauayan City'],
            ['id' => 'R2C3', 'name' => 'Ilagan City'],
            ['id' => 'R2C4', 'name' => 'Santiago City'],
            ['id' => 'R2C5', 'name' => 'Bayombong'],
        ];
        break;
    case 'Region3':
        $cities = [
            ['id' => 'R3C1', 'name' => 'San Fernando City'],
            ['id' => 'R3C2', 'name' => 'Angeles City'],
            ['id' => 'R3C3', 'name' => 'Olongapo City'],
            ['id' => 'R3C4', 'name' => 'Malolos'],
            ['id' => 'R3C5', 'name' => 'Tarlac City'],
        ];
        break;
    case 'Region4A':
        $cities = [
            ['id' => 'R4AC1', 'name' => 'Calamba'],
            ['id' => 'R4AC2', 'name' => 'Batangas City'],
            ['id' => 'R4AC3', 'name' => 'Lucena City'],
            ['id' => 'R4AC4', 'name' => 'Antipolo'],
            ['id' => 'R4AC5', 'name' => 'Tagaytay'],
        ];
        break;
    case 'Region4B':
        $cities = [
            ['id' => 'R4BC1', 'name' => 'Calapan'],
            ['id' => 'R4BC2', 'name' => 'Puerto Princesa'],
            ['id' => 'R4BC3', 'name' => 'Odiongan'],
            ['id' => 'R4BC4', 'name' => 'Romblon'],
            ['id' => 'R4BC5', 'name' => 'Boac'],
        ];
        break;
    case 'Region5':
        $cities = [
            ['id' => 'R5C1', 'name' => 'Legazpi City'],
            ['id' => 'R5C2', 'name' => 'Naga City'],
            ['id' => 'R5C3', 'name' => 'Sorsogon City'],
            ['id' => 'R5C4', 'name' => 'Masbate City'],
            ['id' => 'R5C5', 'name' => 'Iriga City'],
        ];
        break;
    case 'Region6':
        $cities = [
            ['id' => 'R6C1', 'name' => 'Iloilo City'],
            ['id' => 'R6C2', 'name' => 'Bacolod City'],
            ['id' => 'R6C3', 'name' => 'Roxas City'],
            ['id' => 'R6C4', 'name' => 'Kalibo'],
            ['id' => 'R6C5', 'name' => 'San Jose de Buenavista'],
        ];
        break;
    case 'Region7':
        $cities = [
            ['id' => 'R7C1', 'name' => 'Cebu City'],
            ['id' => 'R7C2', 'name' => 'Mandaue City'],
            ['id' => 'R7C3', 'name' => 'Lapu-Lapu City'],
            ['id' => 'R7C4', 'name' => 'Tagbilaran City'],
            ['id' => 'R7C5', 'name' => 'Toledo City'],
        ];
        break;
    case 'Region8':
        $cities = [
            ['id' => 'R8C1', 'name' => 'Tacloban City'],
            ['id' => 'R8C2', 'name' => 'Ormoc City'],
            ['id' => 'R8C3', 'name' => 'Borongan'],
            ['id' => 'R8C4', 'name' => 'Catbalogan'],
            ['id' => 'R8C5', 'name' => 'Maasin City'],
        ];
        break;
    case 'Region9':
        $cities = [
            ['id' => 'R9C1', 'name' => 'Zamboanga City'],
            ['id' => 'R9C2', 'name' => 'Dipolog City'],
            ['id' => 'R9C3', 'name' => 'Dapitan City'],
            ['id' => 'R9C4', 'name' => 'Pagadian City'],
            ['id' => 'R9C5', 'name' => 'Isabela City'],
        ];
        break;
    case 'Region10':
        $cities = [
            ['id' => 'R10C1', 'name' => 'Cagayan de Oro'],
            ['id' => 'R10C2', 'name' => 'Iligan City'],
            ['id' => 'R10C3', 'name' => 'Valencia City'],
            ['id' => 'R10C4', 'name' => 'Malaybalay City'],
            ['id' => 'R10C5', 'name' => 'Oroquieta City'],
        ];
        break;
    case 'Region11':
        $cities = [
            ['id' => 'R11C1', 'name' => 'Davao City'],
            ['id' => 'R11C2', 'name' => 'Digos City'],
            ['id' => 'R11C3', 'name' => 'Tagum City'],
            ['id' => 'R11C4', 'name' => 'Mati City'],
            ['id' => 'R11C5', 'name' => 'Panabo City'],
        ];
        break;
    case 'Region12':
        $cities = [
            ['id' => 'R12C1', 'name' => 'Koronadal City'],
            ['id' => 'R12C2', 'name' => 'General Santos City'],
            ['id' => 'R12C3', 'name' => 'Kidapawan City'],
            ['id' => 'R12C4', 'name' => 'Tacurong City'],
            ['id' => 'R12C5', 'name' => 'Cotabato City'],
        ];
        break;
    case 'Region13':
        $cities = [
            ['id' => 'R13C1', 'name' => 'Butuan City'],
            ['id' => 'R13C2', 'name' => 'Surigao City'],
            ['id' => 'R13C3', 'name' => 'Bislig City'],
            ['id' => 'R13C4', 'name' => 'Tandag City'],
            ['id' => 'R13C5', 'name' => 'Cabadbaran City'],
        ];
        break;
    case 'CAR':
        $cities = [
            ['id' => 'CARC1', 'name' => 'Baguio City'],
            ['id' => 'CARC2', 'name' => 'Tabuk City'],
            ['id' => 'CARC3', 'name' => 'La Trinidad'],
            ['id' => 'CARC4', 'name' => 'Bangued'],
            ['id' => 'CARC5', 'name' => 'Lagawe'],
        ];
        break;
    case 'BARMM':
        $cities = [
            ['id' => 'BARMMC1', 'name' => 'Marawi City'],
            ['id' => 'BARMMC2', 'name' => 'Lamitan City'],
            ['id' => 'BARMMC3', 'name' => 'Cotabato City'],
            ['id' => 'BARMMC4', 'name' => 'Jolo'],
            ['id' => 'BARMMC5', 'name' => 'Bongao'],
        ];
        break;
}

exit(json_encode([
    'status' => 'success',
    'cities' => $cities
]));