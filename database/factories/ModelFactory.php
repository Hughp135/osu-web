<?php
/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/
$factory->defineAs(App\Models\Forum\Forum::class, 'parent', function (Faker\Generator $faker) {
    return  [
        'forum_name' => $faker->catchPhrase,
        'forum_desc' => $faker->realtext(80)
    ];
});

$factory->defineAs(App\Models\Forum\Topic::class, 'parent', function (Faker\Generator $faker) {
    return  [
        'forum_name' => $faker->catchPhrase,
        'forum_desc' => $faker->realtext(80)
    ];
});
