<?php

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
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
      DB::table('osu_user_stats')->delete();
      DB::table('osu_user_performance_rank')->delete();

      // Store some modifiers
      $this->improvement_speeds = [
        (rand(100,110)/100) , // Fast Learner
        (rand(100,102)/100) , // Slow Learner
        (rand(100,115)/100) , // Genius / Multiaccounter :P
      ];


      // Create 10 users and their stats
      factory(App\Models\User::class, 10)->create()->each(function ($u){

      // USER STATS3
        $st = $u->statisticsOsu()->save(factory(App\Models\UserStatistics\Osu::class)->create());
        $st2 = $u->statisticsOsu()->save(factory(App\Models\UserStatistics\Fruits::class)->create());
        $st3 = $u->statisticsOsu()->save(factory(App\Models\UserStatistics\Mania::class)->create());
        $st1 = $u->statisticsOsu()->save(factory(App\Models\UserStatistics\Taiko::class)->create());

      // END USER STATS



      // RANK HISTORY
        $rank = $st->rank;
        $faker = Faker::create();
        $hist = new App\Models\RankHistory;

        $play_freq = rand(10,35); // How regulary the user plays (as a % chance per day)

        // Start with current rank, and move down (back in time) to r0
        $hist->r89 = $rank;
        for ($i=88; $i>=0; $i--) {
          $r = 'r'.$i;
          $prev_r = 'r'.($i+1);
          $prev_rank = $hist->$prev_r;

          // We wouldn't expect the user to improve every day
          $does_improve = $faker->boolean($play_freq); // 25% chance of improving rank today
          if ($does_improve === true) {
            $improvement_modifier = $this->improvement_speeds[array_rand($this->improvement_speeds)];
            // User rank will be modified by somewhere between 0.97 and 1.1 of current rank (realistic amount)
            $hist->$r = round($hist->$prev_r * $improvement_modifier );
          } else {
            $hist->$r = round($hist->$prev_r * (rand(998,999)/1000) ); // Slight decay of between 0.98 and 0.99
          }
        }
          $u->rankHistories()->save($hist);
      // END RANK HISTORY

      // EVENTS
        $beatmaps_count = App\Models\Beatmap::count();
        if ($beatmaps_count > 0) {
          // $ev = $u->events()->save(factory(App\Models\Event::class)->create([
          //   'user_id' => $u->user_id
          // ]));
        }
      // END EVENTS

    }); // end each user

    }
}
