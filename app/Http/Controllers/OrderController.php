<?php

namespace App\Http\Controllers;

use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * 📋 Obtener todas las órdenes
     */
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['status', 'supplier_id', 'product_id', 'date_from', 'date_to']);
            $orders = $this->orderService->getOrders($filters);

            return response()->json([
                'status' => 'success',
                'message' => 'Órdenes obtenidas correctamente.',
                'data' => $orders
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener órdenes: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener órdenes.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 📄 Obtener una orden específica
     */
    public function show($id)
    {
        try {
            $order = $this->orderService->getOrder($id);

            return response()->json([
                'status' => 'success',
                'message' => 'Orden obtenida correctamente.',
                'data' => $order
            ]);
        } catch (\Exception $e) {
            Log::error("Error al obtener orden {$id}: " . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Orden no encontrada.',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * 📦 Crear orden desde una alerta
     */
    public function createFromAlert(Request $request)
    {
        Log::info('📥 Recibiendo solicitud para crear orden desde alerta', $request->all());

        $validator = Validator::make($request->all(), [
            'alert_id' => 'required|integer|exists:alerts,id',
            'product_id' => 'required|integer|exists:products,id',
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'quantity' => 'required|numeric|min:1',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            Log::warning('❌ Validación fallida', $validator->errors()->toArray());

            return response()->json([
                'status' => 'error',
                'message' => 'Errores de validación.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order = $this->orderService->createFromAlert($request->all());

            Log::info("✅ Orden #{$order->id} creada exitosamente");

            return response()->json([
                'status' => 'success',
                'message' => '✅ Orden creada exitosamente. Se ha enviado un correo al proveedor.',
                'data' => $order
            ], 201);
        } catch (\Exception $e) {
            Log::error('❌ Error al crear orden desde alerta: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'status' => 'error',
                'message' => 'Error al crear la orden.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🔄 Actualizar estado de una orden
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:pendiente,enviado,recibido,cancelado'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Estado inválido.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order = $this->orderService->updateStatus($id, $request->status);

            return response()->json([
                'status' => 'success',
                'message' => 'Estado de orden actualizado correctamente.',
                'data' => $order
            ]);
        } catch (\Exception $e) {
            Log::error("Error al actualizar estado de orden {$id}: " . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Error al actualizar el estado.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ❌ Cancelar una orden
     */
    public function cancel(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Datos inválidos.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order = $this->orderService->cancelOrder($id, $request->reason);

            return response()->json([
                'status' => 'success',
                'message' => 'Orden cancelada correctamente.',
                'data' => $order
            ]);
        } catch (\Exception $e) {
            Log::error("Error al cancelar orden {$id}: " . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Error al cancelar la orden.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 📊 Obtener estadísticas de órdenes
     */
    public function stats()
    {
        try {
            $stats = $this->orderService->getStats();

            return response()->json([
                'status' => 'success',
                'message' => 'Estadísticas obtenidas correctamente.',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener estadísticas: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener estadísticas.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🔄 Reenviar email de orden
     */
    public function resendEmail($id)
    {
        try {
            $this->orderService->resendEmail($id);

            return response()->json([
                'status' => 'success',
                'message' => 'Email reenviado correctamente al proveedor.'
            ]);
        } catch (\Exception $e) {
            Log::error("Error al reenviar email de orden {$id}: " . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Error al reenviar el email.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
