<?php

$factory->define(App\Models\UserStatistics\Osu::class, function (Faker\Generator $faker) {
  return ([
    'count300' => 0,
    'count100' => 0,
    'count50' => 0,
    'accuracy' => 100,
    'accuracy_new' => 100,
    'playcount' => 1000,
    'ranked_score' => 123123,
    'total_score' => 234234,
    'x_rank_count' => 15,
    's_rank_count' => 20,
    'a_rank_count' => 30,
    'rank' => 5,
    'country_acronym' => 'UK',
  ]);
});
