<?php

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Faker\Provider\Biased as Biased;

class ScoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      DB::table('osu_scores')->delete();
      DB::table('osu_scores_high')->delete();
      DB::table('osu_scores_taiko')->delete();
      DB::table('osu_scores_taiko_high')->delete();
      DB::table('osu_scores_fruits')->delete();
      DB::table('osu_scores_fruits_high')->delete();
      $beatmaps = App\Models\Beatmap::orderByRaw('RAND()')->get();
      $beatmapCount = count($beatmaps);
      $faker = Faker::create();

      if ($beatmapCount < 1) {
        $this->command->error("Unable to seed scores due to not having enough beatmap data.");
        return;
      }

      $users = App\Models\User::all();
      App\Models\Score\Model::unguard();



      $allBeatmapSets = App\Models\BeatmapSet::all();

      foreach ($users as $k=>$u) {
        $osuBeatmaps = $beatmaps->where('playmode',0)->take(20);
        $taikoBeatmaps = $beatmaps->where('playmode',1)->take(20);
        $fruitsBeatmaps = $beatmaps->where('playmode',2)->take(20);
        $maniaBeatmaps = $beatmaps->where('playmode',3)->take(20);

        //add 20 osu! Standard scores
        foreach ($osuBeatmaps as $bm) {
          $bms = $allBeatmapSets->find($bm->beatmapset_id);
          $maxcombo = rand(1,5000);
          $possible_mods = [16,24,64,72]; // hr, hd/hr, dt, hd/dt
          $sc = App\Models\Score\Osu::create([
            'user_id' => $u->user_id,
            'beatmap_id' => $bm->beatmap_id,
            'beatmapset_id' => $bm->beatmapset_id,
            'score' => rand(50000,100000000),
            'maxcombo' => $maxcombo,
            'rank' => rand(1,1000),
            'count300' => round($maxcombo*0.8),
            'count100' => rand(0, round($maxcombo*0.15)),
            'count50' => rand(0, round($maxcombo*0.05)),
            'countgeki' => round($maxcombo*0.3),
            'countmiss' => round($maxcombo*0.05),
            'countkatu' => round($maxcombo*0.05),
            'enabled_mods' => $possible_mods[array_rand($possible_mods)],
            'date' => rand(1451606400, time()), // random timestamp between 01/01/2016 and now,
          ]);

          $sc2 = App\Models\Score\Best\Osu::create([
            'user_id' => $u->user_id,
            'beatmap_id' => $bm->beatmap_id,
            'beatmapset_id' => $bm->beatmapset_id,
            'score' => rand(50000,100000000),
            'maxcombo' => $maxcombo,
            'rank' => rand(1,1000),
            'count300' => round($maxcombo*0.8),
            'count100' => rand(0, round($maxcombo*0.15)),
            'count50' => rand(0, round($maxcombo*0.05)),
            'countgeki' => round($maxcombo*0.3),
            'countmiss' => round($maxcombo*0.05),
            'countkatu' => round($maxcombo*0.05),
            'enabled_mods' => $possible_mods[array_rand($possible_mods)],
            'date' => rand(1451606400, time()), // random timestamp between 01/01/2016 and now,
            'pp' => 727-$faker->biasedNumberBetween(10,727),
          ]);
        }

        //Taiko scores
        foreach ($osuBeatmaps as $bm) {
          $bms = $allBeatmapSets->find($bm->beatmapset_id);
          $maxcombo = rand(1,5000);
          $possible_mods = [16,24,64,72]; // hr, hd/hr, dt, hd/dt
          $sc3 = App\Models\Score\Taiko::create([
            'user_id' => $u->user_id,
            'beatmap_id' => $bm->beatmap_id,
            'beatmapset_id' => $bm->beatmapset_id,
            'score' => rand(50000,100000000),
            'maxcombo' => $maxcombo,
            'rank' => rand(1,1000),
            'count300' => round($maxcombo*0.8),
            'count100' => rand(0, round($maxcombo*0.15)),
            'count50' => rand(0, round($maxcombo*0.05)),
            'countgeki' => round($maxcombo*0.3),
            'countmiss' => round($maxcombo*0.05),
            'countkatu' => round($maxcombo*0.05),
            'enabled_mods' => $possible_mods[array_rand($possible_mods)],
            'date' => rand(1451606400, time()), // random timestamp between 01/01/2016 and now,
            'pass' => $faker->boolean(85), //85% chance of pass
          ]);

          $sc4 = App\Models\Score\Best\Taiko::create([
            'user_id' => $u->user_id,
            'beatmap_id' => $bm->beatmap_id,
            'beatmapset_id' => $bm->beatmapset_id,
            'score' => rand(50000,100000000),
            'maxcombo' => $maxcombo,
            'rank' => rand(1,1000),
            'count300' => round($maxcombo*0.8),
            'count100' => rand(0, round($maxcombo*0.15)),
            'count50' => rand(0, round($maxcombo*0.05)),
            'countgeki' => round($maxcombo*0.3),
            'countmiss' => round($maxcombo*0.05),
            'countkatu' => round($maxcombo*0.05),
            'enabled_mods' => $possible_mods[array_rand($possible_mods)],
            'date' => rand(1451606400, time()), // random timestamp between 01/01/2016 and now,
            'pp' => 680-$faker->biasedNumberBetween(10,727),
          ]);
        }
      }
      App\Models\Score\Model::reguard();
    }
}
