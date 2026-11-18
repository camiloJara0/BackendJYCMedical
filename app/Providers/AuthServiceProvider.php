<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::define('backoffice.access', function ($user) {
            if ($user->estado !== 'activo') {
                return false;
            }

            if ($user->rol === 'SuperAdmin') {
                return true;
            }

            if (!$user->rol_id) {
                return false;
            }

            return \DB::table('rol_has_permisos')
                ->join('secciones', 'secciones.id', '=', 'rol_has_permisos.seccion_id')
                ->where('rol_has_permisos.rol_id', $user->rol_id)
                ->where('secciones.nombre', 'backoffice_access')
                ->exists();
        });
    }
}
