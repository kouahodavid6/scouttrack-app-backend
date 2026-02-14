<?php

namespace Database\Seeders;

use App\Models\Nation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $nation = new Nation();
        $nation->nom = 'Kouaho David';
        $nation->niveau = 'Nation';
        $nation->tel = '0171136261';
        $nation->photo = null;
        $nation->email = 'kouahodavid6@gmail.com';
        $nation->password = Hash::make('KouahoDavid10@**');
        $nation->role = 1;
        $nation->save();

        $this->command->info('Création du commissaire national');
    }
}