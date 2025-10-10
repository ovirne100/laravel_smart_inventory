<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Services\AlertService;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    protected $alertService;

    public function __construct(AlertService $alertService)
    {
        $this->alertService = $alertService;
    }

    /**
     * 📋 Listar todas las alertas
     */
    public function index()
    {
        $alerts = $this->alertService->getAllAlerts();

        return response()->json([
            'status' => 'success',
            'data'   => $alerts
        ]);
    }

    /**
     * ⚠️ Listar solo las alertas activas
     */
    public function active()
    {
        $alerts = $this->alertService->getActiveAlerts();

        return response()->json([
            'status' => 'success',
            'data'   => $alerts
        ]);
    }

    /**
     * ✅ Resolver alerta manualmente
     */
    public function resolve($id)
    {
        $alert = $this->alertService->resolveAlert($id);

        return response()->json([
            'status'  => 'success',
            'message' => 'Alerta marcada como resuelta',
            'data'    => $alert
        ]);
    }

    /**
     * 🆕 Crear alerta manual y enviar notificación
     */
    public function store(Request $request)
    {
        $alert = $this->alertService->createManualAlert($request);

        return response()->json([
            'status'  => 'success',
            'message' => 'Alerta creada y notificación enviada correctamente.',
            'data'    => $alert
        ], 201);
    }

    /**
     * 🚨 Verifica el stock y crea, actualiza o resuelve alertas según sea necesario.
     */
    public static function checkStock(Inventory $inventory)
    {
        return app(AlertService::class)->checkStock($inventory);
    }
}
