<?php

/**
 *    Copyright 2015 ppy Pty. Ltd.
 *
 *    This file is part of osu!web. osu!web is distributed with the hope of
 *    attracting more community contributions to the core ecosystem of osu!.
 *
 *    osu!web is free software: you can redistribute it and/or modify
 *    it under the terms of the Affero GNU General Public License version 3
 *    as published by the Free Software Foundation.
 *
 *    osu!web is distributed WITHOUT ANY WARRANTY; without even the implied
 *    warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *    See the GNU Affero General Public License for more details.
 *
 *    You should have received a copy of the GNU Affero General Public License
 *    along with osu!web.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\LoginAttempt;
use App\Models\User;
use App\Transformers\AchievementTransformer;
use App\Transformers\UserTransformer;
use Auth;
use Request;

class UsersController extends Controller
{
    protected $section = 'user';

    public function __construct()
    {
        $this->middleware('guest', ['only' => [
            'login',
        ]]);

        $this->middleware('auth', ['only' => [
            'checkUsernameAvailability',
        ]]);

        return parent::__construct();
    }

    public function disabled()
    {
        return view('users.disabled');
    }

    public function checkUsernameAvailability()
    {
        $username = Request::input('username');

        $errors = Auth::user()->validateUsernameChangeTo($username);

        $available = count($errors) === 0;
        $message = $available ? "Username '".e($username)."' is available!" : implode(' ', $errors);
        $cost = $available ? Auth::user()->usernameChangeCost() : 0;

        return [
            'username' => Request::input('username'),
            'available' => $available,
            'message' => $message,
            'cost' => $cost,
            'costString' => currency($cost),
        ];
    }

    public function login()
    {
        $ip = Request::getClientIp();

        if (LoginAttempt::isLocked($ip)) {
            return error_popup('your IP address is locked. Please wait a few minutes.');
        } else {
            $username = Request::input('username');
            $password = Request::input('password');
            $remember = Request::input('remember') === 'yes';

            Auth::attempt(['username' => $username, 'password' => $password], $remember);

            if (Auth::check()) {
                return Auth::user()->defaultJson();
            } else {
                LoginAttempt::failedAttempt($ip, $username);

                return error_popup('wrong password or username');
            }
        }
    }

    public function logout()
    {
        if (Auth::check()) {
            Auth::logout();
        }

        return [];
    }

    public function show($id)
    {
        $user = User::lookup($id);

        if ($user === null || !$user->hasProfile()) {
            abort(404);
        }

        $achievements = fractal_collection_array(
            Achievement::achievable()->orderBy('grouping')->orderBy('ordering')->orderBy('progression')->get(),
            new AchievementTransformer()
        );

        $userArray = fractal_item_array(
            $user,
            new UserTransformer(), implode(',', [
                'allAchievements',
                'allRankHistories',
                'allScores',
                'allScoresBest',
                'allScoresFirst',
                'allStatistics',
                'beatmapPlaycounts',
                'page',
                'recentActivities',
                'recentlyReceivedKudosu',
                'rankedAndApprovedBeatmapSets.difficulties',
                'favouriteBeatmapSets.difficulties',
            ])
        );

        return view('users.show', compact('user', 'userArray', 'achievements'));
    }

    public function generateUsers(){
      $base_url = 'https://osu.ppy.sh/api/';
      $api_key = env('OSU_API', null);

      if (empty($api_key)) return 'no api key set';

      $api = '&k='.$api_key;

      // Get the user cookiezi.
        $u = json_decode(file_get_contents($base_url. 'get_user?u=cookiezi' . $api ))[0];
        if (!empty($u))
        {
          $user = new \App\Models\User;
          $user->username = $u->username;
          $user->username_clean = $u->username;
          $user->user_id = $u->user_id;
          $user->user_password = password_hash(md5("password"), PASSWORD_BCRYPT);

          if ($user = \App\Models\User::find($u->user_id)) {
            echo 'User already exists.';
          }
          else
          try {
            $user->save();
            echo 'User Saved';
            echo 'Stats: ' . $user->statistics('osu');
          } catch (\Illuminate\Database\QueryException $e) {
            echo 'Unable to save user';
          }

          if

          echo '<h3>Username: ' .$user->username . '</h3><h3>ID: ' . $u->user_id . '</h3>';
          echo '<pre>'; var_dump($u); echo '</pre>';

          // Get the top 50 scores for the user
          $user_best = json_decode(file_get_contents($base_url. 'get_user_best?u='. $u->user_id . '&limit=2'. $api ));
          if (!empty($user_best)) {
            echo '<h3>Beatmap ID: ' . $user_best[0]->beatmap_id . '</h3>';

            // Get the first beatmap from the user's top scores
            $beatmap = json_decode(file_get_contents($base_url. 'get_beatmaps?b='. $user_best[0]->beatmap_id . $api ))[0];

            // Get the beatmapset from the beatmap
            echo '<h3>Beatmapset ID: ' . $beatmap->beatmapset_id . '</h3>';

            $beatmapset = json_decode(file_get_contents($base_url. 'get_beatmaps?s='. $beatmap->beatmapset_id . $api ));

            echo '<h3>Beatmap IDs in the set: ';

            // Cycle through each beatmap in the set
            foreach ($beatmapset as $beatmap) {
              echo $beatmap->beatmap_id.', ';
            }
            echo '</h3>';
          }
        }
        else return 'User Not Found';
    }
}
