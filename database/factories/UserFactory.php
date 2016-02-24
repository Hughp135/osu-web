<?php

$factory->define(App\Models\User::class, function (Faker\Generator $faker) {
    $existing_users = DB::table('phpbb_users')->get();

    $existing_names = [];
    $existing_ids = [];

    foreach ($existing_users as $existing_usr) {
      $existing_ids[] = $existing_usr->user_id;
      $existing_names[] = $existing_usr->username;
    }

    $username = null;
    $userid = null;

    // Check username doesn't already exist
    while ($username === null){
      if (!in_array($uname = $faker->userName, $existing_names)) {
        $username = str_replace('.', ' ', $uname); // remove fullstops from username
      };
    }

    // Generate a random unique ID
    $userid = null;
    while ($userid === null){
      if (!in_array($uid = rand(1,600000), $existing_ids)) {
        $userid = $uid;
      };
    }

    return ([
      'username' => $username,
      'username_clean' => $username,
      'user_id' => $userid,
      'user_password' => password_hash(md5('password'), PASSWORD_BCRYPT),
      'user_lastvisit' => 0,
  ]);
});
