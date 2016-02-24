<?php

use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      //test
      DB::table('phpbb_users')->delete();

      // Create 10 users
      factory(App\Models\User::class, 10)->create()->each(function ($u){
        factory(App\Models\UserStatistics\Osu::class, 1)->create(['user_id'=>$u->user_id]);
      });

    }
}
