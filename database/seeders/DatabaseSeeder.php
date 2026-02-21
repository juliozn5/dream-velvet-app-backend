<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Wallet;
use App\Models\SystemProfit;
use App\Models\ChatUnlock;
use App\Models\CoinTransaction;
use App\Models\Message;
use App\Models\FastContent;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create('es_ES');

        // Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('12345678'),
                'role' => 'admin',
            ]
        );
        Wallet::firstOrCreate(['user_id' => $admin->id], ['balance' => 999999]);

        echo "Generando 10 Modelos...\n";
        $modelos = collect();
        // Crear modelo fija para testing:
        $valentina = User::firstOrCreate(
            ['email' => 'valentina@test.com'],
            [
                'name' => 'Valentina Rose',
                'password' => Hash::make('12345678'),
                'role' => 'modelo',
                'avatar' => 'https://i.pravatar.cc/150?u=valentina',
                'chat_price' => 20,
            ]
        );
        Wallet::firstOrCreate(['user_id' => $valentina->id], ['balance' => 0]);
        // Solo crear si no tiene fastcontents
        if (FastContent::where('user_id', $valentina->id)->count() === 0) {
            FastContent::create([
                'user_id' => $valentina->id,
                'type' => 'video',
                'url' => 'https://via.placeholder.com/400x600.png?text=Video+Valentina+Exclusivo',
                'price' => 100,
                'description' => 'Un video exclusivo para ti',
            ]);
            FastContent::create([
                'user_id' => $valentina->id,
                'type' => 'image',
                'url' => 'https://via.placeholder.com/400x600.png?text=Foto+Valentina',
                'price' => 30,
                'description' => 'Foto VIP',
            ]);
        }
        $modelos->push($valentina);

        for ($i = 0; $i < 9; $i++) {
            $modelo = User::create([
                'name' => $faker->name('female'),
                'email' => $faker->unique()->safeEmail,
                'password' => Hash::make('12345678'),
                'role' => 'modelo',
                'avatar' => 'https://i.pravatar.cc/150?u=modelo' . $i . time(),
                'bio' => $faker->sentence(10),
                'chat_price' => $faker->randomElement([10, 20, 30, 50]),
                'rate_message' => 1,
                'is_online' => $faker->boolean(50),
            ]);
            Wallet::create(['user_id' => $modelo->id, 'balance' => 0]);

            for ($j = 0; $j < rand(1, 3); $j++) {
                FastContent::create([
                    'user_id' => $modelo->id,
                    'type' => $faker->randomElement(['image', 'video']),
                    'url' => 'https://via.placeholder.com/400x600.png?text=Premium+Content+' . $j,
                    'price' => $faker->randomElement([50, 100, 150, 200]),
                    'description' => $faker->sentence(),
                ]);
            }
            $modelos->push($modelo);
        }

        echo "Generando 10 Clientes...\n";
        $clientes = collect();
        // Crear cliente fijo
        $julio = User::firstOrCreate(
            ['email' => 'julio@test.com'],
            [
                'name' => 'Julio Cliente',
                'password' => Hash::make('12345678'),
                'role' => 'cliente',
                'avatar' => 'https://ui-avatars.com/api/?name=Julio+Cliente&background=0D8ABC&color=fff',
            ]
        );
        Wallet::firstOrCreate(['user_id' => $julio->id], ['balance' => 10000]);
        $clientes->push($julio);

        for ($i = 0; $i < 9; $i++) {
            $cliente = User::create([
                'name' => $faker->name('male'),
                'email' => $faker->unique()->safeEmail,
                'password' => Hash::make('12345678'),
                'role' => 'cliente',
                'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode('Cliente ' . $i) . '&background=random',
                'is_online' => $faker->boolean(50),
            ]);
            Wallet::create(['user_id' => $cliente->id, 'balance' => $faker->numberBetween(1000, 10000)]);
            $clientes->push($cliente);
        }

        echo "Simulando transacciones de monedas...\n";

        foreach ($clientes as $cliente) {
            $wallet = $cliente->wallet;

            // Seleccionar modelos aleatorias (3 a 6 modelos por cliente)
            $modelosParaChats = $modelos->random(rand(3, 6));

            foreach ($modelosParaChats as $modelo) {
                // Generar fecha en últimos 30 días
                $randDate = Carbon::now()->subDays(rand(1, 30))->subHours(rand(1, 24));

                $price = $modelo->chat_price;
                if ($price > 0 && $wallet->balance >= $price) {

                    // Asegurar que no esté desbloqueado ya
                    $existsUnlock = ChatUnlock::where('user_id', $cliente->id)->where('model_id', $modelo->id)->exists();

                    if (!$existsUnlock) {
                        try {
                            $transaction = $wallet->withdraw($price, 'chat_unlock', "Desbloqueo de chat con {$modelo->name}");
                            $transaction->update(['created_at' => $randDate, 'updated_at' => $randDate]);

                            SystemProfit::create([
                                'user_id' => $cliente->id,
                                'model_id' => $modelo->id,
                                'amount' => $price,
                                'source' => 'chat_unlock',
                                'created_at' => $randDate,
                                'updated_at' => $randDate
                            ]);

                            $chatUnlock = ChatUnlock::create([
                                'user_id' => $cliente->id,
                                'model_id' => $modelo->id,
                                'amount' => $price,
                                'created_at' => $randDate,
                                'updated_at' => $randDate
                            ]);

                            CoinTransaction::create([
                                'user_id' => $cliente->id,
                                'model_id' => $modelo->id,
                                'amount' => $price,
                                'type' => 'chat_unlock',
                                'reference_id' => $chatUnlock->id,
                                'description' => "Desbloqueo de chat con {$modelo->name}",
                                'created_at' => $randDate,
                                'updated_at' => $randDate
                            ]);

                        } catch (\Exception $e) {
                            continue;
                        }

                        // Probabilidad de pagar por contenido
                        if (rand(1, 100) > 30) {
                            $fastContent = FastContent::where('user_id', $modelo->id)->inRandomOrder()->first();

                            if ($fastContent && $wallet->balance >= $fastContent->price) {
                                $contentPrice = $fastContent->price;
                                $contentDate = (clone $randDate)->addHours(rand(1, 5));

                                $msg = Message::create([
                                    'sender_id' => $modelo->id,
                                    'receiver_id' => $cliente->id,
                                    'content' => $faker->sentence(),
                                    'fast_content_id' => $fastContent->id,
                                    'is_paid' => true,
                                    'read_at' => $contentDate,
                                    'created_at' => (clone $contentDate)->subMinutes(5),
                                    'updated_at' => $contentDate,
                                ]);

                                $trans = $wallet->withdraw($contentPrice, 'unlock_content', "Desbloqueo de contenido en mensaje #{$msg->id}");
                                $trans->update(['created_at' => $contentDate, 'updated_at' => $contentDate]);

                                SystemProfit::create([
                                    'user_id' => $cliente->id,
                                    'model_id' => $modelo->id,
                                    'amount' => $contentPrice,
                                    'source' => 'content_unlock',
                                    'created_at' => $contentDate,
                                    'updated_at' => $contentDate
                                ]);

                                CoinTransaction::create([
                                    'user_id' => $cliente->id,
                                    'model_id' => $modelo->id,
                                    'amount' => $contentPrice,
                                    'type' => 'content_unlock',
                                    'reference_id' => $msg->id,
                                    'description' => "Desbloqueo de contenido en mensaje #{$msg->id}",
                                    'created_at' => $contentDate,
                                    'updated_at' => $contentDate
                                ]);
                            }
                        }
                    }
                }
            }
        }

        echo "✅ Base de datos poblada exitosamente.\n";

        // Llamar a los seeders adicionales al final
        $this->call([
            SupportTicketSeeder::class,
        ]);
    }
}
