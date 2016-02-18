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
        $u = json_decode(file_get_contents($base_url. 'get_user?u=azer&event_days=3' . $api ))[0];
        if (!empty($u))
        {

          echo '<h3>Username: ' .$u->username . '</h3><h3>ID: ' . $u->user_id .'</h3><h3>PP Rank: ' . strval($u->pp_rank) . '</h3>';


        // Create the user if not exists
          if ($user = \App\Models\User::find($u->user_id)) {
            echo 'User already exists.<br>';
          }
          else try {
            $user = \App\Models\User::create([
              'username' => $u->username,
              'username_clean' => $u->username,
              'user_id' => $u->user_id,
              'user_password'=> password_hash(md5("password"), PASSWORD_BCRYPT)
            ]);

            echo 'User Saved';
            echo 'Stats: ' . $user->statistics('osu');
          } catch (\Illuminate\Database\QueryException $e) {
            echo 'Unable to save user<br>';
          }

        // Create the osu user stats  if not exists
          if ($stat = \App\Models\UserStatistics\Osu::find($u->user_id)) {
            echo 'Stats already exists.<br>';
          }
          else try {
            $stat = \App\Models\UserStatistics\Osu::create([
              'user_id'         => $u->user_id,
              'count300'        => $u->count300,
              'count100'        => $u->count100,
              'count50'         => $u->count50,
              'accuracy'        => $u->accuracy,
              'accuracy_new'    => $u->accuracy,
              'playcount'       => $u->playcount,
              'ranked_score'    => $u->ranked_score,
              'total_score'     => $u->total_score,
              'x_rank_count'    => $u->count_rank_ss,
              's_rank_count'    => $u->count_rank_s,
              'a_rank_count'    => $u->count_rank_a,
              'rank'            => $u->pp_rank,
              'country_acronym' => $u->country
            ]);
            echo 'Stats Saved<br>';
          } catch (\Illuminate\Database\QueryException $e) {
            echo ' Unable to save stats<br>';
          }

        // generate user rank history
        if ($hist = \App\Models\RankHistory::where('user_id',$u->user_id)->first()) {
          echo 'Stats already exists.<br>';
        }
        else try {
          echo '<h3>pp rank plus 1:'; echo (intval($u->pp_rank) + 1); echo '</h3>';
          $hist = new \App\Models\RankHistory;
          $hist->user_id = $u->user_id;
          $hist->mode = 0;
          for ($i=0; $i<90; $i++){
            $r = 'r'.$i;
            $hist->$r = intval($u->pp_rank) + (89 - $i); // 1 rank per day increase up to current rank lol
          }
          $hist->save();
          echo 'Rank Histroy Saved<br>';
        } catch (\Illuminate\Database\QueryException $e) {
          echo ' Unable to rank history<br>';
        }

          foreach ($u->events as $event) {
            // Save events
            $ev = \App\Models\Event::where('user_id',$u->user_id)->where('date',$event->date);
            if ( $ev->count() >0 ) {
              echo 'Event already exists.<br>';
            }
            else try {
              $event = new \App\Models\Event;
              $event->user_id       = $u->user_id;
              $event->text          = $event->display_html;
              $event->beatmap_id    = $event->beatmap_id;
              $event->beatmapset_id = $event->beatmapset_id;
              $event->epicfactor    = $event->epicfactor;
              $event->date          = $event->date;

              $event->save();
              echo 'Event saved';
            } catch (\Illuminate\Database\QueryException $e) {
              echo ' Unable to save event';
            }
          }





      // score stuff
          // Get the top 50 scores for the user
          $user_best = json_decode(file_get_contents($base_url. 'get_user_best?u='. $u->user_id . '&limit=2'. $api ));
          if (!empty($user_best)) {
            foreach ($user_best as $score) {

            // beatmap stuff
                // Get the first beatmap from the user's top scores
                $beatmap = json_decode(file_get_contents($base_url. 'get_beatmaps?b='. $score->beatmap_id . $api ))[0];
                $bmset_id = $beatmap->beatmapset_id; // save bmset id

                // Get the beatmapset from the beatmap
                echo '<h3>Beatmapset ID: ' . $beatmap->beatmapset_id . '</h3>';

                $beatmapset = json_decode(file_get_contents($base_url. 'get_beatmaps?s='. $beatmap->beatmapset_id . $api ));

                echo '<h3>Beatmap IDs in the set</h3>';



                $beatmap_diff_names = '';
                $set_play_count = 0;

                foreach ($beatmapset as $bm) {
                echo '<pre>'; var_dump($bm); echo '</pre>';
                  echo $bm->beatmap_id.' name '.$bm->version.'<br>';
                  $beatmap_diff_names =  $beatmap_diff_names . $bm->version.',';
                  $set_play_count += $bm->playcount;

              //Save each Beatmap in the set
                  if ( \App\Models\Beatmap::where('beatmap_id',$bm->beatmap_id)->count() > 0 ) {
                    echo 'Beatmap already exists.<br>';
                  } else try {
                    $new_bm = new \App\Models\Beatmap;
                      $new_bm->beatmap_id = $bm->beatmap_id;
                      $new_bm->beatmapset_id = $bm->beatmapset_id;
                      $new_bm->filename = $bm->beatmapset_id. ' '. $bm->artist . ' - '. $bm->title .'.osz';
                      $new_bm->checksum = $bm->file_md5;
                      $new_bm->version = $bm->version;
                      $new_bm->total_length = $bm->total_length;
                      $new_bm->hit_length = $bm->hit_length;
                      $new_bm->countTotal = $bm->max_combo;
                      $new_bm->countNormal = round(intval($bm->max_combo) - (0.2 * intval($bm->max_combo))); // sample
                      $new_bm->countSlider = round(intval($bm->max_combo) - (0.8 * intval($bm->max_combo))) - 1; // sample
                      $new_bm->countSpinner = 1; // sample
                      $new_bm->diff_drain = $bm->diff_drain;
                      $new_bm->diff_size = $bm->diff_size;
                      $new_bm->diff_overall = $bm->diff_overall;
                      $new_bm->diff_approach = $bm->diff_approach;
                      $new_bm->playmode = $bm->mode;
                      $new_bm->approved = $bm->approved;
                      $new_bm->difficultyrating = $bm->difficultyrating;
                      $new_bm->playcount = $bm->playcount;
                      $new_bm->passcount = $bm->passcount;

                      $new_bm->save();
                    echo 'Saved Beatmap<br>';

                  } catch (\Illuminate\Database\QueryException $e) {
                    echo ' Unable to save Beatmap';
                    echo $e->getMessage();
                  }
                } // end for each bm in beatmapset

                rtrim($beatmap_diff_names, ","); // take last comma off string

            // Save Beatmapset

                $bms = \App\Models\BeatmapSet::where('beatmapset_id',$bmset_id);
                if ( $bms->count() >0 ) {
                  echo 'Beatmapset already exists.<br>';
                }
                else try {
                  $set = new \App\Models\BeatmapSet;
                  $set->beatmapset_id = $bmset_id;
                  $set->creator = $beatmap->creator;
                  $set->artist = $beatmap->artist;
                  $set->title = $beatmap->title;
                  $set->displaytitle = $beatmap->title;
                  $set->source = $beatmap->source;
                  $set->tags = $beatmap->tags;
                  $set->bpm = $beatmap->bpm;
                  $set->approved = $beatmap->approved;
                  $set->approved_date = $beatmap->approved_date;
                  $set->genre_id = $beatmap->genre_id;
                  $set->language_id = $beatmap->language_id;
                  $set->versions_available = count($beatmapset);
                  $set->difficulty_names = $beatmap_diff_names;
                  $set->play_count = $set_play_count;
                  $set->favourite_count = $beatmap->favourite_count;

                  $set->save();
                  echo 'Beatmapset Saved<br>';

                } catch (\Illuminate\Database\QueryException $e) {
                  echo ' Unable to save BeatmapSet';
                }


                echo '</h3>';

          // Save score
          if  ( $existing_score = \App\Models\Score\Best\Osu::where('beatmapset_id', $bmset_id)->first() )
          $existing_score->forceDelete(); // overwrite existing score

          if  ( $existing_score2 = \App\Models\Score\Osu::where('beatmapset_id', $bmset_id)->first() )
          $existing_score2->forceDelete(); // overwrite existing score

              try {
              $sc = new \App\Models\Score\Osu;
              $sc2 = new \App\Models\Score\Best\Osu;
                $sc->user_id = $u->user_id;
                $sc->beatmap_id = $score->beatmap_id;
                $sc->beatmapset_id = $bmset_id;
                $sc->score = $score->score;
                $sc->maxcombo = $score->maxcombo;
                $sc->rank = $score->rank;
                $sc->count300 = $score->count300;
                $sc->count100 = $score->count100;
                $sc->count50 = $score->count50;
                $sc->countgeki = $score->countgeki;
                $sc->countmiss = $score->countmiss;
                $sc->countkatu = $score->countkatu;
                $sc->enabled_mods = $score->enabled_mods;
                $sc->date = $score->date;

              $sc2 = $sc;
              $sc->save();
              $sc2->save();
              echo 'Score saved<br>';}
              catch (\Illuminate\Database\QueryException $e) {
                echo ' Unable to save Score';
              }

            } // end foreach user best as score


          }
        }
        else return 'User Not Found';
    }
}
