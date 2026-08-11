<?php
namespace App\Controller;

use Core\Helpers\Validation;
use Core\Http\Request;
use Core\Http\Response as R;

class BaseController
{
    public $validador;
    public $request;
    public $response;

    public function __construct() {
        $this->validador = new Validation();
        $this->request = new Request();
        $this->response = new R();
    }
}