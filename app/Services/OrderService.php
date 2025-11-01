<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Alert;
use App\Models\Product;
use App\Models\Supplier;
use App\Mail\OrderToSupplierMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;

class OrderService
{
    /**
     * 📦 Crear orden desde una alerta y enviar email al proveedor
     */
    public function createFromAlert(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            // Validar que la alerta existe y está pendiente
            $alert = Alert::with(['product', 'inventory'])->findOrFail($data['alert_id']);

            if ($alert->status === Alert::STATUS_RESOLVED) {
                throw new \Exception('La alerta ya ha sido resuelta.');
            }

            // Validar que el producto existe
            $product = Product::findOrFail($data['product_id']);

            // Validar que el proveedor existe
            $supplier = Supplier::findOrFail($data['supplier_id']);

            if (!$supplier->email) {
                throw new \Exception('El proveedor no tiene email configurado.');
            }

            // Crear la orden
            $order = Order::create([
                'alert_id' => $alert->id,
                'product_id' => $product->id,
                'supplier_id' => $supplier->id,
                'user_id' => Auth::id(),
                'quantity' => $data['quantity'],
                'status' => Order::STATUS_PENDING,
                'notes' => $data['notes'] ?? null,
            ]);

            Log::info("📦 Orden #{$order->id} creada para producto '{$product->name}'");

            // Enviar email al proveedor
            try {
                $this->sendOrderToSupplier($order);

                // Marcar orden como enviada
                $order->markAsSent();

                Log::info("✅ Email de orden enviado al proveedor {$supplier->name} ({$supplier->email})");
            } catch (\Exception $e) {
                Log::error("❌ Error al enviar email al proveedor: " . $e->getMessage());
                throw new \Exception('Orden creada pero error al enviar email: ' . $e->getMessage());
            }

            // Resolver automáticamente la alerta
            $this->resolveAlertFromOrder($alert);

            return $order->fresh(['product', 'supplier', 'alert', 'user']);
        });
    }

    /**
     * 📧 Enviar email al proveedor con los detalles de la orden
     */
    private function sendOrderToSupplier(Order $order): void
    {
        $supplier = $order->supplier;

        Mail::to($supplier->email)->send(new OrderToSupplierMail($order));
    }

    /**
     * ✅ Resolver automáticamente la alerta después de crear la orden
     */
    private function resolveAlertFromOrder(Alert $alert): void
    {
        $alert->update([
            'status' => Alert::STATUS_RESOLVED,
            'message' => $alert->message . ' (Pedido realizado al proveedor)',
            'resolved_at' => now(),
        ]);

        Log::info("✅ Alerta #{$alert->id} resuelta automáticamente tras crear orden");
    }

    /**
     * 📋 Obtener todas las órdenes con filtros opcionales
     */
    public function getOrders(array $filters = [])
    {
        $query = Order::with(['product', 'supplier', 'alert', 'user']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * 📄 Obtener una orden específica
     */
    public function getOrder(int $id): Order
    {
        return Order::with(['product', 'supplier', 'alert', 'user'])->findOrFail($id);
    }

    /**
     * 🔄 Actualizar estado de una orden
     */
    public function updateStatus(int $id, string $status): Order
    {
        $order = Order::findOrFail($id);

        if (!in_array($status, [
            Order::STATUS_PENDING,
            Order::STATUS_SENT,
            Order::STATUS_RECEIVED,
            Order::STATUS_CANCELLED
        ])) {
            throw new \Exception('Estado de orden inválido.');
        }

        $order->update(['status' => $status]);

        // Si se marca como recibida, actualizar timestamp
        if ($status === Order::STATUS_RECEIVED) {
            $order->update(['received_at' => now()]);
            Log::info("📦 Orden #{$order->id} marcada como recibida");
        }

        // Si se marca como enviada, actualizar timestamp
        if ($status === Order::STATUS_SENT) {
            $order->update(['sent_at' => now()]);
            Log::info("📧 Orden #{$order->id} marcada como enviada");
        }

        Log::info("🔄 Estado de orden #{$order->id} actualizado a '{$status}'");

        return $order->fresh(['product', 'supplier', 'alert', 'user']);
    }

    /**
     * ❌ Cancelar una orden
     */
    public function cancelOrder(int $id, string $reason = null): Order
    {
        $order = Order::findOrFail($id);

        if ($order->status === Order::STATUS_RECEIVED) {
            throw new \Exception('No se puede cancelar una orden ya recibida.');
        }

        $order->cancel();

        if ($reason) {
            $order->update(['notes' => ($order->notes ? $order->notes . ' | ' : '') . "Cancelada: {$reason}"]);
        }

        Log::info("❌ Orden #{$order->id} cancelada. Razón: {$reason}");

        return $order->fresh(['product', 'supplier', 'alert', 'user']);
    }

    /**
     * 📊 Obtener estadísticas de órdenes
     */
    public function getStats(): array
    {
        return [
            'total' => Order::count(),
            'pending' => Order::pending()->count(),
            'sent' => Order::sent()->count(),
            'received' => Order::received()->count(),
            'cancelled' => Order::cancelled()->count(),
            'today' => Order::whereDate('created_at', today())->count(),
            'this_month' => Order::whereMonth('created_at', now()->month)
                                 ->whereYear('created_at', now()->year)
                                 ->count(),
        ];
    }

    /**
     * 🔄 Reenviar email de orden al proveedor
     */
    public function resendEmail(int $id): void
    {
        $order = Order::with(['product', 'supplier'])->findOrFail($id);

        if (!$order->supplier->email) {
            throw new \Exception('El proveedor no tiene email configurado.');
        }

        $this->sendOrderToSupplier($order);

        Log::info("🔄 Email de orden #{$order->id} reenviado al proveedor {$order->supplier->name}");
    }
}
