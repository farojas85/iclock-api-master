<?php

namespace App\Http\Traits;

use App\Models\Marcacion;
use Illuminate\Support\Facades\Http;
use Rats\Zkteco\Lib\ZKTeco;
use Exception;

trait MarcacionTrait
{
    private $zklib;

    private $tipo_marcacion;

    public function __construct()
    {
        // $this->zklib = new ZKLibrary(
        //     config('zkteco.ip'),
        //     config('zkteco.port'),
        //     config('zkteco.protocol')
        // );
        $this->zklib = new ZKTeco(config('zkteco.ip'));

        $this->tipo_marcacion = [
            0 => 'ENTRADA',
            1 => 'SALIDA',
            4 => 'ENTRADA TE',
            5 => 'SALIDA TE'
        ];
    }

    public function getUsers()
    {
        $res = $this->zklib->connect();
        $users = array();
        if($res)
        {
            $users = $this->zklib->getUser();
            $this->zklib->disconnect();
        }
        return $users;
    }

    public function getAttedances()
    {
        $res = $this->zklib->connect();
        $attendances = array();
        if($res)
        {
            $attendances = array_reverse($this->zklib->getAttendance());
            $this->zklib->disconnect();
        }
        return $attendances;
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
            $serialSub = substr($this->zklib->serialNumber(), 14);
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
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $res = $this->zklib->connect();
        if($res)
        {
            $attendances = array_reverse($this->zklib->getAttendance());
            $serialSub = substr($this->zklib->serialNumber(), 14);
            $serial = substr($serialSub, 0, -1);
            $this->zklib->disconnect();

            if(count($attendances) > 0)
            {
                //sleep(1);

                foreach ($attendances as $attItem) {

                    if($this->attendanceUserVerify($attItem[0],$attItem[4])===false)
                    {
                        // if($this->getVerificarDniPersonalApp($attItem[1]) != 0)
                        // {
                            $marcacion = new Marcacion();
                            $marcacion->uid = $attItem[0];
                            $marcacion->numero_documento = $attItem[1];
                            $marcacion->estado = $attItem[2];
                            $marcacion->fecha = $attItem[3];
                            $marcacion->tipo = $attItem[4];
                            $marcacion->serial = $serial;
                            $marcacion->ip = config('zkteco.ip');
                            $marcacion->save();

                            if($marcacion->numero_documento != null)
                            {
                                $estado = $this->saveAttendanceInApp($marcacion);
                                // if($estado['ok'] == 1)
                                // {

                                // }
                            }
                        //}
                    }
                }

            }



            return 1;
            //$this->zklib->clearAttendance(); // Remove attendance log only if not empty
        }
        return 404;
    }

    public function saveAttendancesCronJob()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $res = $this->zklib->connect();
        if($res)
        {
            $attendances = array_reverse($this->zklib->getAttendance());
            $serialSub = substr($this->zklib->serialNumber(), 14);
            $serial = substr($serialSub, 0, -1);
            $this->zklib->disconnect();

            if(count($attendances) > 0)
            {
                //sleep(1);
                foreach ($attendances as $attItem) {
                    if(($this->attendanceUserVerify($attItem['id'],$attItem['type'])===false ) && (
                        $attItem['timestamp'] >= date('Y-m-d')." 00:00:00" && $attItem[3] <= date('Y-m-d H:i:s')))
                    {
                        // if($this->getVerificarDniPersonalApp($attItem[1]) != 0)
                        // {
                            $marcacion = new Marcacion();
                            $marcacion->uid = $attItem['uid'];
                            $marcacion->numero_documento = $attItem['userid'];
                            $marcacion->estado = $attItem['state'];
                            $marcacion->fecha = $attItem['timestamp'];
                            $marcacion->tipo = $attItem['type'];
                            $marcacion->serial = $serial;
                            $marcacion->ip = config('zkteco.ip');
                            $marcacion->save();

                            if($marcacion->numero_documento != null)
                            {
                                $estado = $this->saveAttendanceInApp($marcacion);
                                // if($estado['ok'] == 1)
                                // {

                                // }
                            }
                        //}
                    }

                }

            }



            return 1;
            //$this->zklib->clearAttendance(); // Remove attendance log only if not empty
        }
        return 404;
    }

    public function attendanceUserVerify($user_id,$tipo)
    {
        $marcacion_count =  Marcacion::where('numero_documento',$user_id)
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
        //$client = new Client();

        try {
            $ruta = config('app.api_url').'/api/attendances';
            $response = Http::get($ruta);

            //return $ruta;
            // Verificar si la respuesta tiene un código 200 (éxito)
            if ($response->getStatusCode() == 200) {

                return $response->json();
                 //json_decode($response->getBody(), true);
                // Aquí puedes trabajar con los datos de respuesta
            } else {
                // Manejar el caso en que la respuesta no sea un código 200
            }
        } catch (\Exception $e) {
            // Manejar errores de excepción, como problemas de conexión
        }
    }

    public function getVerificarDniPersonalApp(string $dni)
    {
        //$client = new Client();

        try {
            $ruta = config('app.api_url').'/api/attendances/verificar-dni';
            $response = Http::get($ruta,[
                'dni' => $dni
            ]);

            //return $ruta;
            // Verificar si la respuesta tiene un código 200 (éxito)
            if ($response->getStatusCode() == 200) {

               return $response->json();
                 //json_decode($response->getBody(), true);
                // Aquí puedes trabajar con los datos de respuesta
            } else {
                // Manejar el caso en que la respuesta no sea un código 200
            }
        } catch (\Exception $e) {
            // Manejar errores de excepción, como problemas de conexión
        }
    }

    public function saveAttendanceInApp($marcacion) {
        set_time_limit(0);
        try {
            $ruta = config('app.api_url').'/api/attendances/store';
            $response = Http::timeout(0)->post($ruta,[
                'dni' => $marcacion->numero_documento,
                'uid' => $marcacion->uid,
                'estado' => $marcacion->estado,
                'fecha' => $marcacion->fecha,
                'tipo' => $this->tipo_marcacion[$marcacion->tipo],
                'serial' => $marcacion->serial,
                'ip' => $marcacion->ip
            ]);

            //return $ruta;
            // Verificar si la respuesta tiene un código 200 (éxito)
            if ($response->getStatusCode() == 200) {

               return $response->json();
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
