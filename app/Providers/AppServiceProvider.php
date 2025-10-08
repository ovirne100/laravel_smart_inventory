<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Role; // 👈 Importamos el modelo Role

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Verifica si no existen roles y los crea automáticamente
        if (Role::count() === 0) {
          Role::insert([
    ['name' => 'admin', 'state' => true, 'permission' => json_encode(['all'])],
    ['name' => 'empleado', 'state' => true, 'permission' => json_encode(['limited'])],
    ['name' => 'invitado', 'state' => true, 'permission' => json_encode(['view-only'])],
]);
        }
    }
}
