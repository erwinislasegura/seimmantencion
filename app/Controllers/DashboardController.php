<?php
namespace App\Controllers;
use App\Core\Controller; use App\Models\DashboardModel;

class DashboardController extends Controller
{
    private function filters(): array {
        $f=[
            'fecha_desde'=>$_GET['fecha_desde']??'', 'fecha_hasta'=>$_GET['fecha_hasta']??'',
            'supervisor_id'=>$_GET['supervisor_id']??'', 'estado_cable'=>$_GET['estado_cable']??'', 'origen_cable'=>$_GET['origen_cable']??''
        ];
        if(($_SESSION['user']['rol']??'')==='Técnico') $f['usuario_id']=$_SESSION['user']['id']??0;
        return $f;
    }
    public function index():void{ $this->requirePermission('Dashboard'); $m=new DashboardModel; $filters=$this->filters(); $stats=$m->getDashboardData($filters); $catalogos=$m->getFiltros(); $this->view('dashboard/index',compact('stats','filters','catalogos')); }
    public function filtrar():void{ $this->index(); }
    public function obtenerDatosGraficosAjax():void{ $this->requirePermission('Dashboard'); header('Content-Type: application/json'); echo json_encode((new DashboardModel)->getDashboardData($this->filters())); }
    public function alertas():void{ $this->requirePermission('Dashboard'); header('Content-Type: application/json'); echo json_encode((new DashboardModel)->getAlertasOperativas()); }
    public function kpis():void{ $this->requirePermission('Dashboard'); header('Content-Type: application/json'); echo json_encode((new DashboardModel)->getDashboardData($this->filters())['kpis']); }
}
