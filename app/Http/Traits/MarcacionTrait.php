<?php

namespace App\Http\Traits;

use App\Models\Marcacion;
use App\ZKService\ZKLibrary;
use GuzzleHttp\Client;

trait MarcacionTrait
{
    private $zklib;
    
    public function __construct()
    {
        $this->zklib = new ZKLibrary(
            config('zkteco.ip'),
            config('zkteco.port'),
            config('zkteco.protocol')
        );
    }
    
    public function getUsers()
    {
        $res = $this->zklib->connect();
        if($res)
        {
            $users = $this->zklib->getUser();
            $this->zklib->disconnect();

            return $users;
        }

        return 404;        
    }

    public function getAttedances()
    {
        $res = $this->zklib->connect();
        if($res)
        {
            $attendances = array_reverse($this->zklib->getAttendance());
            
            $this->zklib->disconnect();
            
            return $attendances;
        }

        return 404;
    }

    public function getAttedancesByAsc()
    {
        $res = $this->zklib->connect();
        if($res)
        {
            $attendances = $this->zklib->getAttendance();
            
            $this->zklib->disconnect();
            
            return $attendances;
        }

        return 404;
    }

    public function saveAttendances()
    {
        $res = $this->zklib->connect();
        if($res)
        {

            $attendances = array_reverse($this->zklib->getAttendance());
            $serialSub = substr($this->zklib->getSerialNumber(), 14);
            $serial = substr($serialSub, 0, -1);

            if(count($attendances) > 0) 
            {
                foreach ($attendances as $attItem) {    

                    if($this->attendanceUserVerify($attItem[0],$attItem[4])===false)
                    {
                        $marcacion = new Marcacion();
                        $marcacion->uid = $attItem[0];
                        $marcacion->numero_documento = $attItem[1];
                        $marcacion->estado = $attItem[2];
                        $marcacion->fecha = $attItem[3];
                        $marcacion->tipo = $attItem[4];
                        $marcacion->serial = $serial;
                        $marcacion->ip = config('zkteco.ip');
                        $marcacion->save();
                    }
                }

            }

            $this->zklib->disconnect();

            return 1;
            //$this->zklib->clearAttendance(); // Remove attendance log only if not empty            
        }
        return 404;
    }

    public function saveAttendancesByAsc()
    {
        $res = $this->zklib->connect();
        if($res)
        {
            $attendances = $this->zklib->getAttendance();
            $serialSub = substr($this->zklib->getSerialNumber(), 14);
            $serial = substr($serialSub, 0, -1);

            if(count($attendances) > 0) 
            {
                sleep(1);
                
                foreach ($attendances as $attItem) {    

                    if($this->attendanceUserVerify($attItem[0],$attItem[4])===false)
                    {
                        $marcacion = new Marcacion();
                        $marcacion->uid = $attItem[0];
                        $marcacion->numero_documento = $attItem[1];
                        $marcacion->estado = $attItem[2];
                        $marcacion->fecha = $attItem[3];
                        $marcacion->tipo = $attItem[4];
                        $marcacion->serial = $serial;
                        $marcacion->ip = config('zkteco.ip');
                        $marcacion->save();
                    }
                }

            }

            $this->zklib->disconnect();

            return 1;
            //$this->zklib->clearAttendance(); // Remove attendance log only if not empty            
        }
        return 404;
    }

    public function attendanceUserVerify($user_id,$tipo)
    {
        $marcacion_count =  Marcacion::where('uid',$user_id)
                                ->whereDate('fecha',date('Y-m-d'))
                                ->where('tipo',$tipo)
                                ->count()
        ;

        if($marcacion_count > 0)
        {
            return true;
        }

        return false;

    }

    public function getAllAttendacesApi()
    {
        $client = new Client();

        try {
            $response = $client->get(config('app.api_url'));

            // Verificar si la respuesta tiene un código 200 (éxito)
            if ($response->getStatusCode() == 200) {
                return $response->getBody();
                 //json_decode($response->getBody(), true);
                // Aquí puedes trabajar con los datos de respuesta
            } else {
                // Manejar el caso en que la respuesta no sea un código 200
            }
        } catch (\Exception $e) {
            // Manejar errores de excepción, como problemas de conexión
        }
    }
}