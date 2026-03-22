<?php
// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use App\Models\Branche;

use App\Models\Nation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // // Création du commissaire national
        // $nation = new Nation();
        // $nation->nom = 'Kouaho David';
        // $nation->niveau = 'Nation';
        // $nation->tel = '0171136261';
        // $nation->photo = null;
        // $nation->email = 'kouahodavid6@gmail.com';
        // $nation->password = Hash::make('KouahoDavid10@**');
        // $nation->role = 1;
        // $nation->save();

        // $this->command->info('✅ Création du commissaire national');

        // // Ajout des branches avec leurs tranches d'âge
        // $branches = [
        //     [
        //         'nom' => 'La Colonnie', 
        //         'ordre' => 1,
        //         'age_min' => 4,
        //         'age_max' => 8
        //     ],
        //     [
        //         'nom' => 'La Meute', 
        //         'ordre' => 2,
        //         'age_min' => 8,
        //         'age_max' => 12
        //     ],
        //     [
        //         'nom' => 'La Troupe', 
        //         'ordre' => 3,
        //         'age_min' => 12,
        //         'age_max' => 15
        //     ],
        //     [
        //         'nom' => 'La Génération', 
        //         'ordre' => 4,
        //         'age_min' => 15,
        //         'age_max' => 18
        //     ],
        //     [
        //         'nom' => 'La Communauté', 
        //         'ordre' => 5,
        //         'age_min' => 18,
        //         'age_max' => 21
        //     ],
        // ];

        // foreach ($branches as $brancheData) {
        //     $newBranche = new Branche();
        //     $newBranche->id = (string) Str::uuid();
        //     $newBranche->nomBranche = $brancheData['nom'];
        //     $newBranche->ordreBranche = $brancheData['ordre'];
        //     $newBranche->age_min = $brancheData['age_min'];
        //     $newBranche->age_max = $brancheData['age_max'];
        //     $newBranche->save();
            
        //     $this->command->info("   ➕ Branche créée : {$brancheData['nom']} ({$brancheData['age_min']}-{$brancheData['age_max']} ans)");
        // }
        
        // $this->command->info('✅ Toutes les branches créées avec leurs tranches d\'âge');
    }
}