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
      DB::table('osu_user_stats_fruits')->delete();
      DB::table('osu_user_stats_mania')->delete();
      DB::table('osu_user_stats_taiko')->delete();
      DB::table('osu_user_performance_rank')->delete();


      $this->beatmapCount = App\Models\Beatmap::count();
      $this->faker = Faker::create();
      if ($this->beatmapCount === 0)
      $this->command->info('Can\'t seed events, scores, events or favourite maps due to having no beatmap data.');

      $this->stats_count = 0;
      $this->hist_count = 0;

      // Store some modifiers
      $this->improvement_speeds = [
        (rand(100,110)/100) , // Fast Learner
        (rand(100,102)/100) , // Slow Learner
        (rand(100,115)/100) , // Genius / Multiaccounter :P
      ];


      // Create 10 users and their stats
      factory(App\Models\User::class, 10)->create()->each(function ($u){

      // USER STATS
      $common_countries = ['US', 'JP', 'CN', 'DE', 'TW', 'RU', 'KR', 'PL', 'CA', 'FR', 'BR', 'GB', 'AU'];
      $country_code = $common_countries[array_rand($common_countries)];

        $st = $u->statisticsOsu()->save(factory(App\Models\UserStatistics\Osu::class)->create(['country_acronym'=>$country_code]));

        $st1 = $u->statisticsOsu()->save(factory(App\Models\UserStatistics\Taiko::class)->create(['country_acronym'=>$country_code]));
        if ($st1) ++$this->stats_count;
        $st2 = $u->statisticsOsu()->save(factory(App\Models\UserStatistics\Fruits::class)->create(['country_acronym'=>$country_code]));
        if ($st2) ++$this->stats_count;
        $st3 = $u->statisticsOsu()->save(factory(App\Models\UserStatistics\Mania::class)->create(['country_acronym'=>$country_code]));
        if ($st3) ++$this->stats_count;
      // END USER STATS

      // RANK HISTORY
        $rank = $st->rank;

        // Create rank histories for all 3 modes
        for ($c=0; $c<4; $c++) {
          $hist = new App\Models\RankHistory;

          $hist->mode = $c; // 0 = standard, 1 = taiko etc...

          $play_freq = rand(10,35); // How regulary the user plays (as a % chance per day)

          // Start with current rank, and move down (back in time) to r0
          $hist->r89 = $rank;
          for ($i=88; $i>=0; $i--) {
            $r = 'r'.$i;
            $prev_r = 'r'.($i+1);
            $prev_rank = $hist->$prev_r;
            // We wouldn't expect the user to improve every day
            $does_improve = $this->faker->boolean($play_freq); // 25% chance of improving rank today
            if ($does_improve === true) {
              $extreme_improvement = $this->faker->boolean(0.01); // 1% chance of extreme improvement today
              if ($extreme_improvement) {
                $improvement_modifier = 1.5;
              } else {
                $improvement_modifier = $this->improvement_speeds[array_rand($this->improvement_speeds)];
              }
              $new_rank =  round($hist->$prev_r * $improvement_modifier );
              if ($new_rank < 1) $new_rank = 1;
              // User rank will be modified by somewhere between 0.97 and 1.1 of current rank (realistic amount)
              $hist->$r = $new_rank;
            } else {
              $new_rank = round($hist->$prev_r * (rand(998,999)/1000) );
              if ($new_rank < 1) $new_rank = 1;
              $hist->$r = $new_rank; // Slight decay of between 0.98 and 0.99
            }
          }
            if ($u->rankHistories()->save($hist)) ++$this->hist_count;
        }

      // END RANK HISTORY



    }); // end each user

    }
}
