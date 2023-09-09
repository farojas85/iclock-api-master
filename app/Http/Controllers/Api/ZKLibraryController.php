<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Marcacion;
use Illuminate\Http\Request;
use App\ZKService\ZKLib;
use App\ZKService\ZKLibrary;
use Rats\Zkteco\Lib\ZKTeco;

class ZKLibraryController extends Controller
{
    private $zklib_main;

    private $marcacion_model;

    public function __construct() {
        $this->marcacion_model = new Marcacion();
    }    

    public function getUsers() {
        $users = $this->marcacion_model->getUsers();

        if($users == 404)
        {
            abort(404);
        }
        
        return response()->json($users,200);

    }

    public function getAttendances() {
        $attendances = $this->marcacion_model->getAttedances();
        
        if($attendances == 404)
        {
            abort(404);
        }
        
        return response()->json($attendances,200);
    }

    public function saveAttendandes() {
        $estado_save = $this->marcacion_model->saveAttendances();

        if($estado_save == 404){
            abort(404);
        }

        if($estado_save == 1) {
            return "Marcaciones guardadas satisfactoriamente";
        }
    }
}
