<?php

$factory->define(App\Models\UserStatistics\Osu::class, function (Faker\Generator $faker) {

  // Calculatable stats
  $acc = (float)(rand(750000,1000000)) / 10000; // 75.0000 - 100.0000
  $score = (float)rand(500000,2000000000) * 2; // 500k - 4bil
  $playcount = rand(1000,250000); // 1k - 250k
  $common_countries = ['US', 'JP', 'CN', 'DE', 'TW', 'RU', 'KR', 'PL', 'CA', 'FR', 'BR', 'GB', 'AU'];
  $rank = rand(1,500000);

  return ([
    'count300' => rand(10000,5000000),
    'count100' => rand(10000,2000000),
    'count50' => rand(10000,1000000),
    'countMiss' => rand(10000,1000000),
    'accuracy_total' => rand(1000,250000), // not sure what this field is meant to be
    'accuracy_count' => rand(1000,250000), // not sure what this field is meant to be
    'accuracy' => $acc,
    'accuracy_new' => $acc,
    'playcount' => $playcount,
    'fail_count' => rand($playcount * 0.1, $playcount * 0.2),
    'exit_count' => rand($playcount * 0.2, $playcount * 0.3),
    'ranked_score' => $score,
    'total_score' => $score * 1.4,
    'x_rank_count' => round($playcount * 0.001),
    's_rank_count' => round($playcount * 0.05),
    'a_rank_count' => round($playcount * 0.2),
    'rank' => $rank,
    'rank_score' => $score,
    'rank_score_index' => rand(1,500000),
    'country_acronym' => $common_countries[array_rand($common_countries)],
    'max_combo' => rand(500,4000),
  ]);
});
