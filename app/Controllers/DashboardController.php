<?php
namespace App\Controllers;
use App\Core\Controller; use App\Models\DashboardModel;
class DashboardController extends Controller { public function index():void{ $this->requirePermission('Dashboard'); $stats=(new DashboardModel)->stats(); $this->view('dashboard/index',compact('stats')); }}
