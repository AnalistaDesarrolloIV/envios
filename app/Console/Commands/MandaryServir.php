<?php

namespace App\Console\Commands;

use DateTime;
use DateTimeZone;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class MandaryServir extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:Mandaryservir';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Comando para autoguardado de datos de envio de Mandar y Servir';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $response = Http::retry(20 ,300)->post('https://10.170.20.95:50000/b1s/v1/Login',[
                'CompanyDB' => 'INVERSIONES',
                'UserName' => 'Transportadoras',
                'Password' => 'Asdf1234$',
            ])->json();
    
            $session = $response['SessionId'];
            
            $date =  new DateTime("now", new DateTimeZone('America/Bogota'));
            $time =  new DateTime("now", new DateTimeZone('America/Bogota'));
            $separador = "-----------======= facturas del dia ".$time->format('Y-m-d h:i:s A')." ========-------------";
            Storage::append('Guardados.txt', $separador.' Inicio');
            Storage::append('Errores.txt', $separador.' Inicio');
            $datos = Http::get('https://mandaryservir.co/mys/users/remesasivanagro/'.$date->format('Y-m-d'))['Guia'];
            
            
            // $separador = "-----------======= facturas del dia 2022/09/05 ========-------------";
            // Storage::append('Guardados.txt', $separador.' Inicio');
            // Storage::append('Errores.txt', $separador.' Inicio');
            // $datos = Http::get('https://mandaryservir.co/mys/users/remesasivanagro/2022-09-05')->json();
    
                if (!isset($datos['alert'])) {
                    $datos = $datos['Guia'];
        
                    foreach ($datos as $key => $value) {
                        // try {
                            foreach ($value['Venta']['facturasdocref1'] as $key => $val1) {
                                if ($val1 !== '') {
                                    $numeroD = $val1;
                                    $long =  strlen($numeroD);
                                    $inicio = substr($numeroD, 0, -($long-2));  
                                    if (!strrpos($numeroD, "N", 0)) {
                                        if ($inicio == 15) { 
                                            $envio_sap = Http::retry(20, 300)->withToken($session)->get('https://10.170.20.95:50000/b1s/v1/Invoices?$select = DocEntry,DocNum &$filter=DocNum eq '.$numeroD.' and U_R_GUIA eq null')->json();
                                            $envio_sap = $envio_sap['value'];
                                            if ($envio_sap !== '') {
                                                if (isset($envio_sap[0]['DocEntry'])) {
                                                    $id_doc = $envio_sap[0]['DocEntry'];
                                                    $save_sap = Http::retry(20,  300)->withToken($session)->patch("https://10.170.20.95:50000/b1s/v1/Invoices(".$id_doc.")", [
                                    
                                                        "U_R_GUIA"=>$value['Venta']['remesa'],
                                                        "U_F_GUIA"=>$value['Venta']['fecha'],
                                                        "U_H_GUIA"=>$value['Venta']['hora'],
                                                        "U_E_GUIA"=> "02"
                                    
                                                    ])->status();
                                                    
                                                    $texto = "factura ".$numeroD." status ".$save_sap;
                                                }else {
                                                    $texto = "La factura N° ".$numeroD." no existe o ya fue actualizada.";
                                                }
                                            }else {
                                                $texto = "La factura N° ".$numeroD." no existe o ya fue actualizada.";
                                            }
                                            
                                            Storage::append('Guardados.txt', $texto);
                                        }else if ($inicio == 10) {
                                            $envio_sap = Http::retry(20, 300)->withToken($session)->get('https://10.170.20.95:50000/b1s/v1/DeliveryNotes?$select = DocEntry,DocNum &$filter=DocNum eq '.$numeroD)->json();
                                            $envio_sap = $envio_sap['value'];
                                            if ($envio_sap !== '') {
                                                if (isset($envio_sap[0]['DocEntry'])) {
                                                    $id_doc = $envio_sap[0]['DocEntry'];
                                                    $save_sap = Http::retry(20, 300)->withToken($session)->patch("https://10.170.20.95:50000/b1s/v1/DeliveryNotes(".$id_doc.")", [
                                    
                                                        "U_R_GUIA"=>$value['Venta']['remesa'],
                                                        "U_F_GUIA"=>$value['Venta']['fecha'],
                                                        "U_H_GUIA"=>$value['Venta']['hora'],
                                                        "U_E_GUIA"=> "02"
                                    
                                                    ])->status();
                                                    
                                                    $texto = "facturas ".$numeroD." status ".$save_sap;
                                                }else {
                                                    $texto = "La factura N° ".$numeroD." no existe o ya fue actualizada.";
                                                }
                                            }else {
                                                $texto = "La facturas N° ".$numeroD." no existe o ya fue actualizada.";
                                            }
                                            
                                            Storage::append('Guardados.txt', $texto);
                            
                                        }
                                    }else {
                                        $texto = "La factura ".$numeroD." no copiada por existencia de caracteres.";
                                        Storage::append('Errores.txt', $texto);
                                    }
                                }
                            }
                            
                            foreach ($value['Venta']['facturasdocref2'] as $key => $val2) {
                                if ($val2 !== '') {
                                    $numeroD = $val2;
                                    $long =  strlen($numeroD);
                                    $inicio = substr($numeroD, 0, -($long-2));  
                                    if (!strrpos($numeroD, "N", 0)) {
                                        if ($inicio == 15) { 
                                            $envio_sap = Http::retry(20, 300)->withToken($session)->get('https://10.170.20.95:50000/b1s/v1/Invoices?$select = DocEntry,DocNum &$filter=DocNum eq '.$numeroD)->json();
                                            $envio_sap = $envio_sap['value'];
                                            if ($envio_sap !== '') {
                                                if (isset($envio_sap[0]['DocEntry'])) {
                                                    $id_doc = $envio_sap[0]['DocEntry'];
                                                    $save_sap = Http::retry(20, 300)->withToken($session)->patch("https://10.170.20.95:50000/b1s/v1/Invoices(".$id_doc.")", [
                                    
                                                        "U_R_GUIA"=>$value['Venta']['remesa'],
                                                        "U_F_GUIA"=>$value['Venta']['fecha'],
                                                        "U_H_GUIA"=>$value['Venta']['hora'],
                                                        "U_E_GUIA"=> "02"
                                    
                                                    ])->status();
                                                    
                                                    $texto = "factura ".$numeroD." status ".$save_sap;
                                                }else {
                                                    $texto = "La factura N° ".$numeroD." no existe o ya fue actualizada.";
                                                }
                                            }else {
                                                $texto = "La factura N° ".$numeroD." no existe o ya fue actualizada.";
                                            }
                                            
                                            Storage::append('Guardados.txt', $texto);
                                        }else if ($inicio == 10) {
                                            $envio_sap = Http::retry(20, 300)->withToken($session)->get('https://10.170.20.95:50000/b1s/v1/DeliveryNotes?$select = DocEntry,DocNum &$filter=DocNum eq '.$numeroD)->json();
                                            $envio_sap = $envio_sap['value'];
                                            if ($envio_sap !== '') {
                                                if (isset($envio_sap[0]['DocEntry'])) {
                                                    $id_doc = $envio_sap[0]['DocEntry'];
                                                    $save_sap = Http::retry(20, 300)->withToken($session)->patch("https://10.170.20.95:50000/b1s/v1/DeliveryNotes(".$id_doc.")", [
                                    
                                                        "U_R_GUIA"=>$value['Venta']['remesa'],
                                                        "U_F_GUIA"=>$value['Venta']['fecha'],
                                                        "U_H_GUIA"=>$value['Venta']['hora'],
                                                        "U_E_GUIA"=> "02"
                                    
                                                    ])->status();
                                                    
                                                    $texto = "facturas ".$numeroD." status ".$save_sap;
                                                }else {
                                                    $texto = "La factura N° ".$numeroD." no existe o ya fue actualizada.";
                                                }
                                            }else {
                                                $texto = "La factura N° ".$numeroD." no existe o ya fue actualizada.";
                                            }
                                            
                                            Storage::append('Guardados.txt', $texto);
                            
                                        }
                                    }else {
                                        $texto = "La factura ".$numeroD." no copiada por existencia de caracteres.";
                                        Storage::append('Errores.txt', $texto);
                                    }
                                }
                            }
                            
                            foreach ($value['Venta']['facturasdocref3'] as $key => $val3) {
                                if ($val3 !== '') {
                                    $numeroD = $val3;
                                    $long =  strlen($numeroD);
                                    $inicio = substr($numeroD, 0, -($long-2));  
                                    if (!strrpos($numeroD, "N", 0)) {
                                        if ($inicio == 15) { 
                                            $envio_sap = Http::retry(20, 300)->withToken($session)->get('https://10.170.20.95:50000/b1s/v1/Invoices?$select = DocEntry,DocNum &$filter=DocNum eq '.$numeroD)->json();
                                            $envio_sap = $envio_sap['value'];
                                            if ($envio_sap !== '') {
                                                if (isset($envio_sap[0]['DocEntry'])) {
                                                    $id_doc = $envio_sap[0]['DocEntry'];
                                                    $save_sap = Http::retry(20, 300)->withToken($session)->patch("https://10.170.20.95:50000/b1s/v1/Invoices(".$id_doc.")", [
                                    
                                                        "U_R_GUIA"=>$value['Venta']['remesa'],
                                                        "U_F_GUIA"=>$value['Venta']['fecha'],
                                                        "U_H_GUIA"=>$value['Venta']['hora'],
                                                        "U_E_GUIA"=> "02"
                                    
                                                    ])->status();
                                                    
                                                    $texto = "factura ".$numeroD." status ".$save_sap;
                                                }else {
                                                    $texto = "La factura N° ".$numeroD." no existe o ya fue actualizada.";
                                                }
                                            }else {
                                                $texto = "La factura N° ".$numeroD." no existe o ya fue actualizada.";
                                            }
                                            
                                            Storage::append('Guardados.txt', $texto);
                                        }else if ($inicio == 10) {
                                            $envio_sap = Http::retry(20, 300)->withToken($session)->get('https://10.170.20.95:50000/b1s/v1/DeliveryNotes?$select = DocEntry,DocNum &$filter=DocNum eq '.$numeroD)->json();
                                            $envio_sap = $envio_sap['value'];
                                            if ($envio_sap !== '') {
                                                if (isset($envio_sap[0]['DocEntry'])) {
                                                    $id_doc = $envio_sap[0]['DocEntry'];
                                                    $save_sap = Http::retry(20, 300)->withToken($session)->patch("https://10.170.20.95:50000/b1s/v1/DeliveryNotes(".$id_doc.")", [
                                    
                                                        "U_R_GUIA"=>$value['Venta']['remesa'],
                                                        "U_F_GUIA"=>$value['Venta']['fecha'],
                                                        "U_H_GUIA"=>$value['Venta']['hora'],
                                                        "U_E_GUIA"=> "02"
                                    
                                                    ])->status();
                                                    
                                                    $texto = "facturas ".$numeroD." status ".$save_sap;
                                                }else {
                                                    $texto = "La facturas N° ".$numeroD." no existe o ya fue actualizada.";
                                                }
                                            }else {
                                                $texto = "La facturas N° ".$numeroD." no existe o ya fue actualizada.";
                                            }
                                            
                                            Storage::append('Guardados.txt', $texto);
                            
                                        }
                                    }else {
                                        $texto = "La factura ".$numeroD." no copiada por existencia de caracteres.";
                                        Storage::append('Errores.txt', $texto);
                                    }
                                }
                            }
                        // } catch (\Throwable $th) {
    
                        //     $response = Http::retry(20 ,300)->post('https://10.170.20.95:50000/b1s/v1/Login',[
                        //         'CompanyDB' => 'INVERSIONES',
                        //         'UserName' => 'Transportadoras',
                        //         'Password' => 'Asdf1234$',
                        //     ])->json();
                    
                        //     $session = $response['SessionId']; 
    
                        //     Storage::append('Errores.txt', '------------------------- Error individual en Guia N° -----------'.$numeroD.' -------------------------------------------------------- \n'.$th);
                        // }
                       
                    }
                }else {
                    Storage::append('Errores.txt', 'Json caido mandar y servir "'.$datos['alert'].'" ');
                }
            // } catch (\Throwable $th) {
            //     $response = Http::retry(20 ,300)->post('https://10.170.20.95:50000/b1s/v1/Login',[
            //         'CompanyDB' => 'INVERSIONES',
            //         'UserName' => 'Transportadoras',
            //         'Password' => 'Asdf1234$',
            //     ])->json();
        
            //     $session = $response['SessionId'];
    
            //     Storage::append('Errores.txt', '---------------------------------------------------------- Error Guia N° '.$numeroD.' ---------------------------------------------------------------- \n'.$th);
            // }

            Storage::append('Guardados.txt', $separador .' Final');
            Storage::append('Errores.txt', $separador.' Final');

        } catch (\Throwable $th) {
            $texto = "Malo.". $th;
            Storage::append('Errores.txt', $texto);
        }
    }
}
