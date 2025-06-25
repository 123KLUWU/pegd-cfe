<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * https://laravel.com/docs/12.x/seeding
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. **Crear Permisos**
        // Permisos de gestión de usuarios
        Permission::findOrCreate('view users'); // Ver listado de usuarios
        Permission::findOrCreate('manage users'); // Crear, editar, eliminar usuarios
        Permission::findOrCreate('approve registrations'); // Aprobar solicitudes de registro

        // Permisos de documentos
        Permission::findOrCreate('generate documents'); // Generar nuevas hojas/procedimientos
        Permission::findOrCreate('view all documents'); // Ver todos los documentos (si no son propios)
        Permission::findOrCreate('manage templates'); // Gestionar plantillas

        // Permisos de manuales/diagramas
        Permission::findOrCreate('view diagrams'); // Ver diagramas y manuales
        Permission::findOrCreate('upload diagrams'); // Subir nuevos diagramas y manuales

        // Permisos de IA
        Permission::findOrCreate('use ai assistant'); // Usar el asistente de IA

        // 2. **Crear Roles**
        $adminRole = Role::findOrCreate('admin'); //Administrador
        $employeeRole = Role::findOrCreate('employee'); //Empleado

        // 3. **Asignar Permisos a Roles**
        // Rol ADMIN (Administrador), Tiene todos los permisos
        $adminRole->givePermissionTo(Permission::all()); // Asigna TODOS los permisos existentes al admin

        // Rol EMPLOYEE (Empleado), contiene permisos básicos de operación
        $employeeRole->givePermissionTo([
            'generate documents',
            'view diagrams',
            'use ai assistant',
        ]);

        // 4. **Asignar Roles a Usuarios (Ejemplo: Asignar al primer usuario registrado como admin)**
        $adminUser = User::where('rpe', 'admin')->first(); // O User::first()
        if ($adminUser) {
            $adminUser->assignRole('admin');
            $this->command->info('Rol de administrador asignado al usuario ' . $adminUser->rpe);
        } else {
            $this->command->warn('No se encontró el usuario admin para asignar rol.');
        }

        // Opcional: Si creas usuarios de prueba, asignales el rol 'employee'
        // $employeeUser = User::where('rpe', 'TU_RPE_DE_EMPLEADO_PRUEBA')->first();
        // if ($employeeUser) {
        //     $employeeUser->assignRole('employee');
        // }
    }
}