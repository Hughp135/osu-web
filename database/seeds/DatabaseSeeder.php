<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      $this->command->info('Creating sample users...');
      if (!empty(env('OSU_API'))){
        factory(App\Models\User::class)->create();
      } else {
        $this->command->info('You havent set an osu! API key in the .env file. Therefore cannot make more than a couple of users.');
      }
    }
