<?php

include __DIR__ . "/../vendor/autoload.php";
include __DIR__ . "/config.php";

//cliente para factura A
$clienteData_FactA=[
    'nombre'            => "Juan Perez",
    'direccion'         => "Av. Libertador 450",
    'localidad'         => "Buenos Aires",
    'condicion'         => "Responsable Inscripto",
    'tipo_documento'    => "80", // 80 = CUIT 
    'numero_documento'  => "1111111111",
];

//cliente para factura B
$clienteData_FactB=[
    'nombre'            => "Juan Perez",
    'direccion'         => "Av. Libertador 450",
    'localidad'         => "Buenos Aires",
    'condicion'         => "Consumidor final",
    'tipo_documento'    => "96", // 96 = DNI - 99 = Consumidor Final (Sin identificar)
    'numero_documento'  => "11111111",
];

$items=[
    [
        'codigo'        => '1',
        'descripcion'   => 'Lámpara',
        'cantidad'      => 3,
        'neto_unitario' => 20.00,
        'alicuota_iva'  => '21',
        'unidad_medida' => 'unidades',
    ],
    [
        'codigo'        => '123123',
        'descripcion'   => 'art. 3',
        'cantidad'      => 3,
        'neto_unitario' => 20.00,
        'alicuota_iva'  => '21',
        'unidad_medida' => 'unidades',
    ],
    // [
    //     'codigo'        => '123123',
    //     'descripcion'   => 'art. 2',
    //     'cantidad'      => 1,
    //     'neto_unitario' => 40.00,
    //     'alicuota_iva'  => '10.5',
    //     'unidad_medida' => 'unidades',
    // ]
];

$afipData_Homologacion=[
    'CUIT' => EMPRESA_CUIT,
    'res_folder' => __DIR__ . '/certificados//',
    'key'  => 'homologacion/key_homologacion',
    'cert' => 'homologacion/cert_homologacion' 
];

$afipData_Produccion=[
    'CUIT' => EMPRESA_CUIT,
    'res_folder' => __DIR__ . '/certificados//',
    'key'  => 'produccion/key_produccion',
    'cert' => 'produccion/cert_produccion.crt',
    'production' => TRUE 
];

$operacionData_FactA=[
    'tipo'                  => '1', // 1 = Factura A
    'concepto'              => '1', // 1 = Productos
    'fecha'                 => date('Y-m-d'),
    'condicion_de_venta'    => 'CONTADO',
    'items'                 => $items,
    'clienteData'           => $clienteData_FactA,
    'afipData'              => $afipData_Homologacion,    
];

$operacionData_CreditoA=[
    'tipo'                  => '3', // 3 = Nota de cedito A
    'concepto'              => '1', // 1 = Productos
    'fecha'                 => date('Y-m-d'),
    'condicion_de_venta'    => 'CONTADO',
    'items'                 => $items,
    'clienteData'           => $clienteData_FactA,
    'afipData'              => $afipData_Homologacion,  
    'cbtesAsoc'             => [
        [
            'Tipo' 		=> '1',
            'PtoVta' 	=> EMPRESA_PTO_VTA,
            'Nro' 		=> '10',
        ],
    ]    
];

$operacionData_FactB=[
    'tipo'                  => '6', // 6 = Factura B
    'concepto'              => '1', // 1 = Productos
    'fecha'                 => date('Y-m-d'),
    'condicion_de_venta'    => 'CONTADO',
    'items'                 => $items,
    'clienteData'           => $clienteData_FactB,
    'afipData'              => $afipData_Homologacion,  
];

$operacionData_CreditoB=[
    'tipo'                  => '8', // 8 = Nota de credito B
    'concepto'              => '1', // 1 = Productos
    'fecha'                 => date('Y-m-d'),
    'condicion_de_venta'    => 'CONTADO',
    'items'                 => $items,
    'clienteData'           => $clienteData_FactB,
    'afipData'              => $afipData_Homologacion,
    'cbtesAsoc'             => [
        [
            'Tipo' 		=> '6',
            'PtoVta' 	=> EMPRESA_PTO_VTA,
            'Nro' 		=> '11',
        ],
    ]  
];

use Msl\FacturacionModular\Comprobante;

$comprobante=new Comprobante($operacionData_CreditoB);
try {
    $datos_afip=$comprobante->solicitarAutorizacionAfip();
} catch (Exception $ex) {
    $ex->getMessage();
}
$comprobante->generarQR();
$pdf_name=$comprobante->construirPDF();
?>
<embed style="height: 100vh;" width='100%' name='plugin' src='comprobantes/<?=$pdf_name?>' type='application/pdf' style="border:medium" />