<?php

namespace Msl\FacturacionModular;

use Exception;
use Msl\FacturacionModular\fpdf\FpdfFactura;


class Comprobante 
{   

    private string  $qrPath;    
    public array    $items;
    public array    $netos;
    public array    $facturaData;
    public array    $clienteData;
    public array    $operacionData;
    public array    $afipData;
    public array    $cbtesAsoc=[];
    public array    $Iva=[];
    private array   $ids_alicuotas=[ 
        '0'     => '3',
        '10.5'  => '4',
        '21'    => '5',
        '27'    => '6',
        '5'     => '8',
        '2.5'   => '9',
    ];
    
    public function __construct(array $operacionData)
    {
        
        $this->operacionData    = $operacionData;
        $this->clienteData      = $operacionData['clienteData'];
        $this->items            = $operacionData['items'];
        $this->afipData         = $operacionData['afipData'];

        //si tiene comprobantes asociados (nota de credito)
        if(isset($operacionData['cbtesAsoc'])){
            $this->cbtesAsoc    = $operacionData['cbtesAsoc'];
        }

        //vuelco los items en un array agrupados por alicuota con ID segun tabla AFIOP de alicuotas
        $neto_grabado=0;
        $importe_iva=0;
        foreach ($this->items as $item) {            
            $this->netos[$item['alicuota_iva']]['Id']=$this->ids_alicuotas[ $item['alicuota_iva'] ];
            $neto_articulo=$item['neto_unitario']*$item['cantidad'];
            $iva_articulo=$neto_articulo*floatval($item['alicuota_iva'])/100;
            $iva_articulo=number_format($iva_articulo,2,'.','');
            if(isset($this->netos[$item['alicuota_iva']]['BaseImp'])){
                $this->netos[$item['alicuota_iva']]['BaseImp']+=$neto_articulo;
                $this->netos[$item['alicuota_iva']]['Importe']+=$iva_articulo;
            }else{
                $this->netos[$item['alicuota_iva']]['BaseImp']=$neto_articulo;
                $this->netos[$item['alicuota_iva']]['Importe']=$iva_articulo;
            } 
            $neto_grabado+=$neto_articulo;
            $importe_iva+=$iva_articulo;
        }

        //genero el array a enviar a AFIP ($Ivas) 
        foreach($this->netos as $neto){
            array_push($this->Iva,$neto);
        }

        //agrego importes totales al array de datos de la operacion
        $this->operacionData['neto_gravado']=$neto_grabado;
        $this->operacionData['importe_iva']=number_format($importe_iva,2,'.','');
        $this->operacionData['total']=number_format($neto_grabado+$importe_iva,2,'.','');
    } 

    public function solicitarAutorizacionAfip()
    {
        try{
            $afip = new \Afip($this->afipData);
        }catch(Exception $ex){
            die("Ha ocurrido el siguiente error al intentar generar el objeto Afip: ".$ex->getMessage());
        }        

        $punto_de_venta = EMPRESA_PTO_VTA;

        $tipo_de_factura = $this->operacionData['tipo']; // 1 = Factura A

        /**
         * Número de la ultima Factura A
         **/
        try {
            $last_voucher = $afip->ElectronicBilling->GetLastVoucher($punto_de_venta, $tipo_de_factura);
        }catch(Exception $ex){
            die("Ha ocurrido el siguiente error al intentar obtener el último comprobante: ".$ex->getMessage());
        } 

        /**
         * Concepto de la factura
         *
         * Opciones:
         *
         * 1 = Productos 
         * 2 = Servicios 
         * 3 = Productos y Servicios
         **/
        $concepto = $this->operacionData['concepto'];

        /**
         * Tipo de documento del comprador
         *
         * Opciones:
         *
         * 80 = CUIT 
         * 86 = CUIL 
         * 96 = DNI
         * 99 = Consumidor Final 
         **/
        $tipo_de_documento = $this->clienteData['tipo_documento'];

        /**
         * Numero de documento del comprador (0 para consumidor final)
         **/
        $numero_de_documento = $this->clienteData['numero_documento'];

        /**
         * Numero de factura
         **/
        $numero_de_factura = $last_voucher+1;

        /**
         * Fecha de la factura en formato aaaa-mm-dd (hasta 10 dias antes y 10 dias despues)
         **/
        $fecha = $this->operacionData['fecha'];

        /**
         * Importe sujeto al IVA (sin icluir IVA)
         **/
        $importe_gravado = $this->operacionData['neto_gravado'];

        /**
         * Importe exento al IVA
         **/
        $importe_exento_iva = 0;

        /**
         * Importe de IVA
         **/
        $importe_iva = $this->operacionData['importe_iva'];

        /**
         * Importe total
         **/
        $importe_total=$this->operacionData['total'];

        /**
         * Los siguientes campos solo son obligatorios para los conceptos 2 y 3
         **/
        if ($concepto === 2 || $concepto === 3) {
            /**
             * Fecha de inicio de servicio en formato aaaammdd
             **/
            $fecha_servicio_desde = intval(date('Ymd'));

            /**
             * Fecha de fin de servicio en formato aaaammdd
             **/
            $fecha_servicio_hasta = intval(date('Ymd'));

            /**
             * Fecha de vencimiento del pago en formato aaaammdd
             **/
            $fecha_vencimiento_pago = intval(date('Ymd'));
        }
        else {
            $fecha_servicio_desde = null;
            $fecha_servicio_hasta = null;
            $fecha_vencimiento_pago = null;
        }

        $data = array(
            'CantReg' 	=> 1, // Cantidad de facturas a registrar
            'PtoVta' 	=> $punto_de_venta,
            'CbteTipo' 	=> $tipo_de_factura, 
            'Concepto' 	=> $concepto,
            'DocTipo' 	=> $tipo_de_documento,
            'DocNro' 	=> $numero_de_documento,
            'CbteDesde' => $numero_de_factura,
            'CbteHasta' => $numero_de_factura,
            'CbteFch' 	=> intval(str_replace('-', '', $fecha)),
            'FchServDesde'  => $fecha_servicio_desde,
            'FchServHasta'  => $fecha_servicio_hasta,
            'FchVtoPago'    => $fecha_vencimiento_pago,
            'ImpTotal' 	=> $importe_total,
            'ImpTotConc'=> 0, // Importe neto no gravado
            'ImpNeto' 	=> $importe_gravado,
            'ImpOpEx' 	=> $importe_exento_iva,
            'ImpIVA' 	=> $importe_iva,
            'ImpTrib' 	=> 0, //Importe total de tributos
            'MonId' 	=> 'PES', //Tipo de moneda usada en la factura ('PES' = pesos argentinos) 
            'MonCotiz' 	=> 1, // Cotización de la moneda usada (1 para pesos argentinos)
            //'CbtesAsoc' => $this->cbtesAsoc,
            'Iva'       => $this->Iva
        );

        if(count($this->cbtesAsoc)){
            $data['CbtesAsoc']=$this->cbtesAsoc;
        }

        /** 
         * Creamos la Factura 
         **/
        try {
            $res = $afip->ElectronicBilling->CreateVoucher($data);
        } catch (Exception $ex) {
            die("Ha ocurrido el siguiente error al intentar obtener el CAE: ".$ex->getMessage());
        }        

        $this->facturaData=[
            'cae'           => $res['CAE'],
            'vto_cae'       => $res['CAEFchVto'],
            'numero'        => $numero_de_factura,
        ];
    }

    public function generarQR()
    {
        $qr=new CodigoQR();
        $datos_cmp_base_64 = json_encode([
            "ver" => 1,
            "fecha" => $this->operacionData['fecha'],
            "cuit" => (int) EMPRESA_CUIT,
            "ptoVta" => (int) EMPRESA_PTO_VTA,
            "tipoCmp" => (int) $this->operacionData['tipo'],
            "nroCmp" => (int) $this->facturaData['numero'],
            "importe" => (float) $this->operacionData['neto_gravado'],
            "moneda" => "PES",
            "ctz" => (float) 1,
            "tipoDocRec" => (int) $this->clienteData['tipo_documento'],
            "nroDocRec" => (int) $this->clienteData['numero_documento'],
            "tipoCodAut" => "E",
            "codAut" => (int) $this->facturaData['cae'],
        ]);
        $datos_cmp_base_64 = base64_encode($datos_cmp_base_64);
        
        $url = 'https://www.afip.gob.ar/fe/qr/';
        $to_qr = $url.'?p='.$datos_cmp_base_64;
        
        // ser properties
        $qrcodeObj = $qr->getBarcodeObj('QRCODE,H', $to_qr, - 4, - 4, 'black', array(- 2,- 2,- 2,- 2))->setBackgroundColor('#ffffff');
        
        // generate qrcode
        $imageData = $qrcodeObj->getPngData();
        $timestamp = time();
        $this->qrPath=$qr->dir . $timestamp . '.png';

        //store in the directory
        file_put_contents($this->qrPath, $imageData);
        
    }

    public function construirPDF() : string
    {        
        $pdf = new FpdfFactura();
        $pdf->setClienteData($this->clienteData);
        $pdf->setFacturaData($this->facturaData);
        $pdf->setOperacionData($this->operacionData);
        $pdf->setNetos($this->netos);
        $pdf->setQrPath($this->qrPath);
        $pdf->SetAutoPageBreak(true, 26);
        $pdf->AliasNbPages();
        $pdf->setTitulo("ORIGINAL");
        $pdf->AddPage();
        $pdf->printItems($this->items);
        $pdf->setTitulo("DUPLICADO");
        $pdf->AddPage();
        $pdf->printItems($this->items);
        $pdf->setTitulo("TRIPLICADO");
        $pdf->AddPage();
        $pdf->printItems($this->items);

        $pdf_name=EMPRESA_CUIT."_".
                    FpdfFactura::add_ceros($this->operacionData['tipo'],3)."_".
                    FpdfFactura::add_ceros(EMPRESA_PTO_VTA,5)."_".
                    FpdfFactura::add_ceros($this->facturaData['numero'],8).".pdf";

        $dest="comprobantes/".$pdf_name;
                
        $pdf->Output($dest, 'F');
        //copy('factura.pdf', 'facturas/factura.pdf');
        ?>
        <embed style="height: 100vh;" width='100%' name='plugin' src='<?=$dest?>' type='application/pdf' style="border:medium" />
        <?php
        return $pdf_name;
    }

}