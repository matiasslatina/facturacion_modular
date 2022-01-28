<?php

namespace Msl\FacturacionModular;

// Include the library in your project
//require ('../vendor/autoload.php');

use \Com\Tecnick\Barcode\Barcode;

class CodigoQR extends Barcode{

    public $dir = "qr-code/";

    function __construct() {
        if (! is_dir($this->dir)) {
            mkdir($this->dir, 0777, true);
        }
    }

}