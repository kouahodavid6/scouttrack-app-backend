<?php

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
        // User::factory(10)->create();

        // $nation = new Nation();
        // $nation->nom = 'Kouaho David';
        // $nation->niveau = 'Nation';
        // $nation->tel = '0171136261';
        // $nation->photo = null;
        // $nation->email = 'kouahodavid6@gmail.com';
        // $nation->password = Hash::make('KouahoDavid10@**');
        // $nation->role = 1;
        // $nation->save();

        // $this->command->info('Création du commissaire national');

        //Ajout de branches
        // $branches = [
        //     ['nom' => 'La Colonnie', 'ordre' => 1],
        //     ['nom' => 'La Meute', 'ordre' => 2],
        //     ['nom' => 'La Troupe', 'ordre' => 3],
        //     ['nom' => 'La Génération', 'ordre' => 4],
        //     ['nom' => 'La Communauté', 'ordre' => 5],
        // ];

        // foreach ($branches as $brancheData) {
        //     $newBranche = new Branche();
        //     $newBranche->id = (string) Str::uuid();
        //     $newBranche->nomBranche = $brancheData['nom'];
        //     $newBranche->ordreBranche = $brancheData['ordre'];
        //     $newBranche->save();
        // }
        
        // $this->command->info("     - Toutes les branches créées avec leurs ordres respectifs");
    }
}