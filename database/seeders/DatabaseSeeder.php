<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Cliente
        $cliente = User::create([
            'name' => 'Julio Cliente',
            'email' => 'julio@test.com',
            'password' => Hash::make('12345678'),
            'role' => 'cliente',
            'avatar' => 'https://ui-avatars.com/api/?name=Julio+Cliente&background=0D8ABC&color=fff',
            'is_online' => true,
        ]);
        $cliente->wallet()->create(['balance' => 1000]); // 1000 monedas iniciales

        // 2. Modelo
        $modelo = User::create([
            'name' => 'Valentina Rose',
            'email' => 'valentina@test.com',
            'password' => Hash::make('12345678'),
            'role' => 'modelo',
            'avatar' => 'https://i.pravatar.cc/150?u=valentina',
            'bio' => 'Modelo exclusiva. Contenido diario 📸',
            'rate_message' => 50, // 50 monedas por mensaje
            'is_online' => true,
        ]);
        $modelo->wallet()->create(['balance' => 0]);

        // 3. Admin
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('12345678'),
            'role' => 'admin',
        ]);
        $admin->wallet()->create(['balance' => 999999]);

        echo "Usuarios creados:\n";
        echo "- Cliente: julio@test.com / 12345678\n";
        echo "- Modelo: valentina@test.com / 12345678\n";
    }
}
