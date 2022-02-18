<?php

namespace Msl\FacturacionModular\fpdf;


class FpdfFactura extends  \FPDF
{

    private array $clienteData;    
    private array $facturaData;
    public array $operacionData;
    private string $titulo;
    private string $qrPath;
    private array $netos;
    private array $comprobantes=[
        '1' => ['nombre'=>'FACTURA','letra'=>'A'], // A
        '3' => ['nombre'=>'NOTA DE CRÉDITO','letra'=>'A'], //A
        '6' => ['nombre'=>'FACTURA','letra'=>'B'], //B
        '8' => ['nombre'=>'NOTA DE CRÉDITO','letra'=>'B'], //B 
    ];

    public function setClienteData(array $clienteData)
    {
        $this->clienteData=$clienteData;
    }

    public function setFacturaData(array $data)
    {
        $this->facturaData=$data;
    }

    public function setOperacionData(array $data)
    {
        $this->operacionData=$data;
    }

    public function setTitulo(String $titulo)
    {
        $this->titulo=$titulo;
    }

    public function setNetos(array $netos)
    {
        $this->netos=$netos;
    }

    public function setQrPath(string $path)
    {
        $this->qrPath=$path;
    }

    function Header()
    {

        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0,10,$this->titulo,1,1,'C');

        $this->SetFont('Arial', 'B', 15);
        $this->Cell(85,10,EMPRESA,'LR',0,'C');

        $comprob_letra=$this->comprobantes[$this->operacionData['tipo']]['letra'];
        $this->Cell(20,10,$comprob_letra,"R",0,'C');
        $comprob_nombre=utf8_decode($this->comprobantes[$this->operacionData['tipo']]['nombre']);
        $this->Cell(0,10,$comprob_nombre,"R",1,'C');

        $this->SetFont('Arial', 'B', 8);

        $this->Cell(85,5,'','LR',0,'C');
        $this->Cell(20,5,'('.$this->add_ceros($this->operacionData['tipo'],4).')',"RB",0,'C');
        $this->Cell(0,5,'',"R",1,'C');
        
        $this->Cell(95,6,"",'LR',0,'L');
        $this->Cell(0,6,"      PTO. DE VENTA: ".$this->add_ceros(EMPRESA_PTO_VTA,5)."  Comp. Nro: ".$this->add_ceros($this->facturaData['numero'],8),"R",1,'L');

        $this->Cell(95,6,"     RAZON SOCIAL: ".RAZON_SOCIAL,'LR',0,'L');        
        $this->Cell(0,6,"      FECHA EMICION: ".date("d/m/Y"),"R",1,'L');

        $this->Cell(95,6,"     DOMICILIO COMERCIAL: ".utf8_decode(EMPRESA_DOMICILIO),'LR',0,'L');        
        $this->Cell(0,6,"      CUIT: ".EMPRESA_CUIT,"R",1,'L');
       
        $this->Cell(95,6,"     CONDICION FRENTE AL IVA: ".EMPRESA_COND_IVA,'LR',0,'L');
        $this->Cell(0,6,"      INGRESOS BRUTOS: ".EMPRESA_CUIT,"R",1,'L');

        $this->Cell(95,6,"",'LRB',0,'L');
        $this->Cell(0,6,"      FECHA INICIO ACTIVIDADES: ".EMPRESA_INICIO_ACTIVIDADES,"RB",1,'L');

        $this->Ln('1');
        $this->SetFont('Arial', 'B', 7);

        $tipoDoc=($this->clienteData['tipo_documento']=='80')?'CUIT':'DNI';
        $numDoc=($this->clienteData['numero_documento']!='0')?$this->clienteData['numero_documento']:'';
        $this->Cell(70,6,"     $tipoDoc: $numDoc",'LT',0,'L');        
        $this->Cell(0,6,"      APELLIDO Y NOMBRE / RAZON SOCIAL: ".$this->clienteData['nombre'],"RT",1,'L');

        $this->Cell(75,6,"     CONDICION FRENTE AL IVA: ".$this->clienteData['condicion'],'L',0,'L');        
        $this->Cell(0,6,"      DOMICILIO COMERCIAL: ".utf8_decode($this->clienteData['direccion']." ".$this->clienteData['localidad']),"R",1,'L');

        if($this->operacionData['tipo']=='1'){
            $this->Cell(0,6,"     CONDICION DE VENTA: ".$this->operacionData['condicion_de_venta'],'LBR',1,'L');
        }
        if($this->operacionData['tipo']=='3'){
            $this->Cell(75,6,"     CONDICION DE VENTA: ".$this->operacionData['condicion_de_venta'],'LB',0,'L');
            $this->Cell(0,6,"     FACTURA: ".$this->add_ceros(EMPRESA_PTO_VTA,5)."-".$this->add_ceros($this->operacionData['cbtesAsoc'][0]['Nro'],8),'BR',1,'L');
        }

        $this->Ln('1');

        if($this->operacionData['tipo']=='1' OR $this->operacionData['tipo']=='3'){
            $this->HeaderTipoA();
        }

        if($this->operacionData['tipo']=='6' OR $this->operacionData['tipo']=='8'){
            $this->HeaderTipoB();
        } 

    }

    private function HeaderTipoA(){
        $this->SetFont('Arial', 'B', 6);        
        $this->setFillColor(204,204,204); 
        $this->Cell(15, 5, utf8_decode('Código'), 'LTB', 0, 'L', 1);
        $this->Cell(75, 5, utf8_decode('Producto / Servicio'), 'LBT', 0, 'L', 1);
        $this->Cell(12, 5, 'Cantidad', 'LBT', 0, 'L', 1);
        $this->Cell(15, 5, 'U. medida', 'LBT', 0, 'L', 1);
        $this->Cell(15, 5, 'Precio Unit.', 'LBT', 0, 'L', 1);
        $this->Cell(10, 5, utf8_decode('% Bonif'), 'LBT', 0, 'L', 1);
        $this->Cell(15, 5, 'Subtotal', 'LBT', 0, 'L', 1);
        $this->Cell(15, 5, 'Alicuota IVA', 'LBT', 0, 'L', 1);
        $this->Cell(18, 5, 'Subtotal c/IVA', 'LBTR', 1, 'L', 1);
    }

    private function HeaderTipoB(){
        $this->SetFont('Arial', 'B', 6);        
        $this->setFillColor(204,204,204); 
        $this->Cell(15, 5, utf8_decode('Código'), 'LTB', 0, 'L', 1);
        $this->Cell(90, 5, utf8_decode('Producto / Servicio'), 'LBT', 0, 'L', 1);
        $this->Cell(12, 5, 'Cantidad', 'LBT', 0, 'L', 1);
        $this->Cell(15, 5, 'U. medida', 'LBT', 0, 'L', 1);
        $this->Cell(15, 5, 'Precio Unit.', 'LBT', 0, 'L', 1);
        $this->Cell(10, 5, utf8_decode('% Bonif'), 'LBT', 0, 'L', 1);
        $this->Cell(15, 5, 'Imp.Bonif.', 'LBT', 0, 'L', 1);
        $this->Cell(18, 5, 'Subtotal', 'LBTR', 1, 'L', 1);
    }

    function Footer()
    {
        if($this->operacionData['tipo']=='1' OR $this->operacionData['tipo']=='3'){
            $this->footerTipoA();
        }

        if($this->operacionData['tipo']=='6' OR $this->operacionData['tipo']=='8'){
            $this->footerTipoB();
        }        

        $this->Image($this->qrPath,12,255,27,27,'PNG');
        $this->Image(__DIR__ . '\..\img\afip.png',45,255,50,0,'PNG');

        $this->ln(2);
        
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(85, 5, '', 0, 0, 'C');
        $this->Cell(20, 5, 'Pag. ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
        $this->SetFont('Arial', 'B', 8); 
        $this->Cell(45, 5, utf8_decode('CAE Nº: '), 0, 0, 'R');
        $this->SetFont('Arial', '', 8); 
        $this->Cell(0, 5, $this->facturaData['cae'] , 0, 1, 'L');

        $this->Cell(105, 5, '', 0, 0, 'C');
        $this->SetFont('Arial', 'B', 8); 
        $this->Cell(45, 5, 'Fecha de Vto. CAE: ', 0, 0, 'R');
        $this->SetFont('Arial', '', 8); 
        $this->Cell(0, 5, $this->facturaData['vto_cae'] , 0, 1, 'L');

        $this->ln(9);
        $this->Cell(40);
        $this->Cell(0, 5, 'COMPROBANTE AUTORIZADO ', 0, 1, 'L');
        $this->Cell(40);
        $this->Cell(0, 5, utf8_decode('Esta Administración Federal no se responsabiliza por los datos ingresados en el detalle de la operación'), 0, 0, 'L');
    }

    private function footerTipoA()
    {
        $this->SetFont('Arial', 'B', 8);
        //Posicion: a 2,5 cm del final
        $this->SetY(-100);

        $this->Cell(0,6,'Otros Tributos','LTR', 1,'L');

        $this->SetFont('Arial', '', 7);        
        $this->setFillColor(204,204,204); 
        $this->Cell(50, 5, utf8_decode('Descripción'), 'LTB', 0, 'L', 1);
        $this->Cell(25, 5, utf8_decode('Detalle'), 'LBT', 0, 'L', 1);
        $this->Cell(12, 5, 'Alic.%', 'LBT', 0, 'L', 1);
        $this->Cell(15, 5, 'Importe', 'LBTR', 0, 'R', 1);
        $this->Cell(0, 5, '', 'R', 1, 'L');

        $this->printItemOtrosImpuestos('Per./Ret. de Impuesto a las Ganancias');
        $this->Cell(0, 4, '', 'R', 1, 'L');

        $this->printItemOtrosImpuestos('Per./Ret. de IVA');
        $this->Cell(0, 4, '', 'R', 1, 'L');

        $this->printItemOtrosImpuestos('Per./Ret. Ingresos Brutos');
        $this->SetFont('Arial', 'B', 8); 
        $this->Cell(60, 4, 'Importe neto grabado: $', 0, 0,'R');
        $this->Cell(0, 4, number_format($this->operacionData['neto_gravado'],2,',',''), 'R', 1, 'R');

        $this->printItemOtrosImpuestos('Impuestos Internos');
        $this->SetFont('Arial', 'B', 8); 
        $this->Cell(60, 4, 'IVA 27%: $', 0, 0,'R');
        $this->Cell(0, 4, '0,00', 'R', 1, 'R');

        $this->printItemOtrosImpuestos('Impuestos Municipales');
        $this->SetFont('Arial', 'B', 8); 
        $this->Cell(60, 4, 'IVA 21%: $', 0, 0,'R');
        $iva_21=(isset($this->netos['21']['Importe']))?$this->netos['21']['Importe']:0;
        $this->Cell(0, 4, number_format($iva_21,2,',',''), 'R', 1, 'R');

        $this->SetFont('Arial', '', 6); 
        $this->Cell(50, 4, '', 'L', 0, 'L');
        $this->Cell(37, 4, 'Importe Otros Tributos: $', 0, 0, 'L');
        $this->Cell(15, 4, '0.00', 0, 0, 'R');
        $this->SetFont('Arial', 'B', 8); 
        $this->Cell(60, 4, 'IVA 10.5%: $', 0, 0,'R');
        $iva_105=(isset($this->netos['10.5']['Importe']))?$this->netos['10.5']['Importe']:0;
        $this->Cell(0, 4, number_format($iva_105,2,',',''), 'R', 1, 'R');

        $this->Cell(102, 4, '', 'L', 0, 'R');
        $this->Cell(60, 4, 'IVA 5%: $', 0, 0,'R');
        $this->Cell(0, 4, '0,00', 'R', 1, 'R');

        $this->Cell(102, 4, '', 'L', 0, 'R');
        $this->Cell(60, 4, 'IVA 2.5%: $', 0, 0,'R');
        $this->Cell(0, 4, '0,00', 'R', 1, 'R');

        $this->Cell(102, 4, '', 'L', 0, 'R');
        $this->Cell(60, 4, 'IVA 0%: $', 0, 0,'R');
        $this->Cell(0, 4, '0,00', 'R', 1, 'R');

        $this->Cell(102, 4, '', 'L', 0, 'R');
        $this->Cell(60, 4, 'Importe Otros Tributos: $', 0, 0,'R');
        $this->Cell(0, 4, '0,00', 'R', 1, 'R');

        $this->Cell(102, 4, '', 'LB', 0, 'R');
        $this->Cell(60, 4, 'Importe Total: $', 'B', 0,'R');
        $this->Cell(0, 4, number_format($this->operacionData['total'],2,',',''), 'RB', 1, 'R');
    }

    private function footerTipoB()
    {
        $this->SetFont('Arial', 'B', 8);
        //Posicion: a 2,5 cm del final
        $this->SetY(-70);

        $this->Cell(0,12,'','LTR', 1,'L');

        $this->Cell(102, 4, '', 'L', 0, 'R');
        $this->Cell(60, 4, 'Subtotal: $', 0, 0,'R');
        $this->Cell(0, 4, number_format($this->operacionData['total'],2,',',''), 'R', 1, 'R');

        $this->Cell(102, 4, '', 'L', 0, 'R');
        $this->Cell(60, 4, 'Importe Otros Tributos: $', 0, 0,'R');
        $this->Cell(0, 4, '0,00', 'R', 1, 'R');

        $this->Cell(102, 4, '', 'LB', 0, 'R');
        $this->Cell(60, 4, 'Total: $', 'B', 0,'R');
        $this->Cell(0, 4, number_format($this->operacionData['total'],2,',',''), 'RB', 1, 'R'); 
    }

    private function printItemOtrosImpuestos(string $item)
    {

        $this->SetFont('Arial', '', 6); 
        $this->Cell(50, 4, $item, 'L', 0, 'L');
        $this->Cell(25, 4, '', 0, 0, 'L');
        $this->Cell(12, 4, '', 0, 0, 'L');
        $this->Cell(15, 4, '0.00', 0, 0, 'R');

    }

    public function printItems(array $items)
    {
        if($this->operacionData['tipo']=='1' OR $this->operacionData['tipo']=='3'){
            $this->printItemsFacturaA($items);
        }

        if($this->operacionData['tipo']=='6' OR $this->operacionData['tipo']=='8'){
            $this->printItemsFacturaB($items);
        } 
    }

    public function printItemsFacturaA($items)
    {
        $this->SetFont('Arial', '', 8);
        foreach ($items as $item) {
            $this->Cell(15, 5, utf8_decode($item['codigo']), 0, 0, 'R');
            $puntos=(strlen($item['descripcion'])>40)?'...':'';
            $this->Cell(75, 5, substr(utf8_decode($item['descripcion']),0,40).$puntos , 0, 0, 'L');
            $this->Cell(12, 5, $item['cantidad'], 0, 0, 'C');
            $this->Cell(15, 5, $item['unidad_medida'], 0, 0, 'L');
            $this->Cell(15, 5, number_format($item['neto_unitario'],2,',',''), 0, 0, 'R');
            $this->Cell(10, 5, '', 0, 0, 'L');
            $subtotal=$item['cantidad']*$item['neto_unitario'];
            $this->Cell(15, 5, number_format($subtotal,2,',',''), 0, 0, 'R');
            $this->Cell(15, 5, $item['alicuota_iva'].'%', 0, 0, 'R');
            $subtotal_con_iva=$subtotal + $subtotal*$item['alicuota_iva']/100;
            $this->Cell(18, 5, number_format($subtotal_con_iva,2,',',''), 0, 1, 'R');
        }
    }

    public function printItemsFacturaB($items)
    {
        $this->SetFont('Arial', '', 8);
        foreach ($items as $item) {
            $this->Cell(15, 5, utf8_decode($item['codigo']), 0, 0, 'R');
            $puntos=(strlen($item['descripcion'])>40)?'...':'';
            $this->Cell(90, 5, substr(utf8_decode($item['descripcion']),0,45).$puntos, 0, 0, 'L');
            $this->Cell(12, 5, $item['cantidad'], 0, 0, 'C');
            $this->Cell(15, 5, $item['unidad_medida'], 0, 0, 'L');
            $precioUnitario=$item['neto_unitario']+$item['neto_unitario']*$item['alicuota_iva']/100;
            $this->Cell(15, 5, number_format($precioUnitario,2,',',''), 0, 0, 'R');
            $this->Cell(10, 5, '0,00', 0, 0, 'C');
            $this->Cell(15, 5, '0,00', 0, 0, 'R');
            $subtotal=$item['cantidad']*$item['neto_unitario'];
            $subtotal_con_iva=$subtotal + $subtotal*$item['alicuota_iva']/100;
            $this->Cell(18, 5, number_format($subtotal_con_iva,2,',',''), 0, 1, 'R');
        }
    }

    /**
     * en este bloque completo los valores con ceros a la izquierda
     * @param <type> $numero
     * @param <type> $ceros
     * @return <type>
     * @assert (25, 3) == 00025 
     */
    public static function add_ceros($numero, $ceros)
    {
        $insertar_ceros='';
        $order_diez = explode(".", $numero);
        $dif_diez = $ceros - strlen($order_diez[0]);
        for ($m = 0; $m < $dif_diez; $m++) {
            @$insertar_ceros .= 0;
        }
        return $insertar_ceros .= $numero;
    }

}
