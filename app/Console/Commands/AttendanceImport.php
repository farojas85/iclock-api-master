<?php

namespace App\Console\Commands;

use App\Models\Marcacion;
use Illuminate\Console\Command;

class AttendanceImport extends Command
{
    // private $marcacion_model;

    // public function __construct() {
    //     $this->marcacion_model = new Marcacion();
    // }
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importar Marcaciones del reloj';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $marcacion_model = new Marcacion();
        $tempo = 1;
        while($tempo<=60){
            sleep(1);
            $estado_marcacion = $marcacion_model->saveAttendancesByAsc();
            if($estado_marcacion == 1) {
                $tempo += 1;
            }
        }
    }
}
