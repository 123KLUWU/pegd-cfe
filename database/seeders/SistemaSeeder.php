<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Sistema;

class SistemaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sistemas = [
                // U01 / U02
                ['clave' => 'FBU', 'sistema' => 'CONTROL DE FLAMA DE QUEMADORES'],
                ['clave' => 'FFY', 'sistema' => 'BARRIDO DE CALDERA'],
                ['clave' => 'FGE', 'sistema' => 'SEGURIDADES DE CALDERA'],
                ['clave' => 'EPL', 'sistema' => 'ACONDICIONAMIENTO DE DIÉSEL'],
                ['clave' => 'ECA', 'sistema' => 'CIRCUITO DE AIRE DE COMBUSTIÓN'],
                ['clave' => 'FGA', 'sistema' => 'QUEMADORES DE ARRANQUE (DIÉSEL)'],
                ['clave' => 'FPO', 'sistema' => 'ACONDICIONAMIENTO DE COMBUSTÓLEO'],
                ['clave' => 'FSR', 'sistema' => 'VAPOR SOBRECALENTADO'],
                ['clave' => 'FTA', 'sistema' => 'CIRCUITO DE TIRO DE GASES'],
                ['clave' => 'FBA', 'sistema' => 'QUEMADORES PRINCIPALES NIVEL "A"'],
                ['clave' => 'FBB', 'sistema' => 'QUEMADORES PRINCIPALES NIVEL "B"'],
                ['clave' => 'FBC', 'sistema' => 'QUEMADORES PRINCIPALES NIVEL "C"'],
                ['clave' => 'FBD', 'sistema' => 'QUEMADORES PRINCIPALES NIVEL "D"'],
                ['clave' => 'FBR', 'sistema' => 'AUTOMATISMO DE QUEMADORES'],
                ['clave' => 'FDA', 'sistema' => 'DETECTORES DE FLAMA QUEMADORES NIVEL "A"'],
                ['clave' => 'FDB', 'sistema' => 'DETECTORES DE FLAMA QUEMADORES NIVEL "B"'],
                ['clave' => 'FDC', 'sistema' => 'DETECTORES DE FLAMA QUEMADORES NIVEL "C"'],
                ['clave' => 'FDD', 'sistema' => 'DETECTORES DE FLAMA QUEMADORES NIVEL "D"'],
                ['clave' => 'FTV', 'sistema' => 'TELEVISIÓN DE FLAMAS'],
                ['clave' => 'FPG', 'sistema' => 'ACONDICIONAMIENTO DE GAS'],
                ['clave' => 'FPL', 'sistema' => 'ACONDICIONAMIENTO DE DIÉSEL'],
            
                // U03
                ['clave' => 'FAE', 'sistema' => 'AIRE DE SELLOS DE QUEMADORES'],
                ['clave' => 'FAR', 'sistema' => 'AIRE DE ENFRIAMIENTO A DETECTORES DE FLAMA'],
                ['clave' => 'FCA', 'sistema' => 'CIRCUITO DE AIRE DE COMBUSTIÓN'],
                ['clave' => 'FJA', 'sistema' => 'CIRCUITO DE RECIRCULACIÓN DE GASES'],
                ['clave' => 'FRT', 'sistema' => 'RECUPERACIÓN DE COMBUSTÓLEO'],
                ['clave' => 'FPA', 'sistema' => 'PRECALENTAMIENTO DE AIRE'],
                ['clave' => 'FRA', 'sistema' => 'CALENTAMIENTO DE AIRE LADO IZQUIERDO'],
                ['clave' => 'FRB', 'sistema' => 'CALENTAMIENTO DE AIRE LADO DERECHO'],
                ['clave' => 'FRS', 'sistema' => 'VAPOR RECALENTADO'],
                ['clave' => 'FRM', 'sistema' => 'SOPLADORES DE HOLLÍN'],
            
                // U04 (Planta de Agua)
                ['clave' => 'ABP', 'sistema' => 'CALENTAMIENTO DE BAJA PRESIÓN'],
                ['clave' => 'ACO', 'sistema' => 'RECUPERACIÓN DE DRENAJES'],
                ['clave' => 'ADG', 'sistema' => 'DESGASIFICADOR'],
                ['clave' => 'AHP', 'sistema' => 'CALENTAMIENTO DE ALTA PRESIÓN'],
                ['clave' => 'APA', 'sistema' => 'AGUA DE ALIMENTACIÓN'],
                ['clave' => 'CAP', 'sistema' => 'REPOSICIÓN Y DESCARGA DE CONDENSADO'],
                ['clave' => 'CRF', 'sistema' => 'AGUA DE CIRCULACIÓN'],
                ['clave' => 'SIR', 'sistema' => 'ACONDICIONAMIENTO QUÍMICO DEL CICLO'],
                ['clave' => 'SIT', 'sistema' => 'CONTROL QUÍMICO Y TOMA DE MUESTRAS'],
                ['clave' => 'SVA', 'sistema' => 'DISTRIBUCIÓN DE VAPOR AUXILIAR'],
            
                // U05 (Turbogenerador)
                ['clave' => 'CET', 'sistema' => 'VAPOR DE SELLOS DE TURBINA'],
                ['clave' => 'CEX', 'sistema' => 'AGUA DE CONDENSADO'],
                ['clave' => 'CVI', 'sistema' => 'VACÍO DEL CONDENSADOR'],
                ['clave' => 'GRV', 'sistema' => 'GASES DEL GENERADOR ELÉCTRICO'],
                ['clave' => 'GFR', 'sistema' => 'FLUIDO DE CONTROL DE TURBINA'],
                ['clave' => 'GGR', 'sistema' => 'LUBRICACIÓN, LEVANTE Y TORNAFLECHA'],
                ['clave' => 'GHE', 'sistema' => 'ACEITE DE SELLOS DEL GENERADOR'],
                ['clave' => 'GMA', 'sistema' => 'SUPERVISORIO DE TURBINA'],
                ['clave' => 'GPV', 'sistema' => 'DRENAJES DE TURBINA'],
                ['clave' => 'GRE', 'sistema' => 'REGULACIÓN DE TURBINA'],
                ['clave' => 'GRH', 'sistema' => 'TEMPERATURAS DEL GENERADOR ELÉCTRICO'],
                ['clave' => 'GST', 'sistema' => 'AGUA DE ENFRIAMIENTO DEL ESTATOR'],
            
                // U06 (Consumos propios)
                ['clave' => 'GEV', 'sistema' => 'GENERACIÓN DE ENERGÍA'],
                ['clave' => 'GPA', 'sistema' => 'PROTECCIÓN DE GRUPO'],
                ['clave' => 'GSY', 'sistema' => 'SINCRONIZACIÓN Y ACOPLAMIENTO'],
                ['clave' => 'LBA', 'sistema' => 'CARGADORES DE BATERÍAS'],
                ['clave' => 'LC',  'sistema' => 'RECTIFICADORES DE 48 V CD'],
                ['clave' => 'LG',  'sistema' => 'BUSES DE 6.9 KV'],
                ['clave' => 'LK',  'sistema' => 'BUSES DE 480 VCA'],
                ['clave' => 'LLA', 'sistema' => 'BUS DE ESENCIALES'],
                ['clave' => 'LLP', 'sistema' => 'TABLERO GENERADOR DIÉSEL DE EMERGENCIA'],
                ['clave' => 'LNA', 'sistema' => 'ONDULADORES'],
                ['clave' => 'GEX', 'sistema' => 'EXCITACIÓN'],
            
                // U07 (Regulación/Medición GV)
                ['clave' => 'FCR', 'sistema' => 'LANZA DE PRUEBA'],
                ['clave' => 'FRG', 'sistema' => 'REGULACIÓN DEL GENERADOR DE VAPOR'],
                ['clave' => 'FPG', 'sistema' => 'ACONDICIONAMIENTO DE GAS'],
            
                // U08 (Regulación GV / Planta AG)
                ['clave' => 'STR', 'sistema' => 'VAPOR SECUNDARIO'],
                ['clave' => 'SRA', 'sistema' => 'SISTEMA DE ABIERTO DE AGUA DE MAR'],
                ['clave' => 'SRI', 'sistema' => 'SISTEMA CERRADO DE ENFRIAMIENTO'],          
        ];
        foreach ($sistemas as $sistema) {
            Sistema::create($sistema);
        }
    }
}