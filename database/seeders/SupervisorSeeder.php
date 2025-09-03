<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Supervisor;

class SupervisorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $supervisores = [
            [
                'name'  => 'Arturo Márquez valdez',
                'email' => 'arturo.marquezv@cfe.mx',
            ],
            [
                'name'  => 'Carlos Antonio de Jesús Pous',
                'email' => 'carlos.dejesus@cfe.mx',
            ],
            [
                'name'  => 'Roberto Lima Trava',
                'email' => 'roberto.lima@cfe.mx',
            ],
            [
                'name'  => 'Gustavo Esteban Serrano Sánchez',
                'email' => 'gustavo.serrano@cfe.mx',
            ],
            [
                'name'  => 'Roberto Lima Trava',
                'email' => 'roberto.lima@cfe.mx',
            ],
            [
                'name'  => 'Rubén Cabrera Vázquez',
                'email' => 'ruben.cabrera@cfe.mx',
            ],
        ];
        foreach ($supervisores as $s) {
            Supervisor::updateOrCreate(
                ['email' => $s['email']],
                $s
            );
        }
    }
}
