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

use App\Models\User;
use App\Transformers\UserTransformer;
use Auth;
use Request;

class TestController extends Controller{
    protected $section = 'test';
  public function generateUsers(){
    $count_api_calls = 0;
    $base_url = 'https://osu.ppy.sh/api/';
    $api_key = env('OSU_API', null);

    if (empty($api_key)) return 'no api key set';

    $api = '&k='.$api_key;

    $users = ['cookiezi', 'azer', 'doomsday'];

    foreach ($users as $user)
      {
            // Get the user cookiezi.
              $u = file_get_contents($base_url. 'get_user?u='.$user.'&event_days=3' . $api );
              ++$count_api_calls;
              if (count($u)<1) return 'Error, users not found';
              $u = json_decode($u)[0];

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

        // Add user to users array
      $users_array[] = $user;

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

     $stats_array[] = $stat;

      // generate user rank history
      if ($hist = \App\Models\RankHistory::where('user_id',$u->user_id)->first()) {
        echo 'Stats already exists.<br>';
      }
      else try {
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

      $hist_array[] = $hist;

        foreach ($u->events as $event) {
          // Save events
          $ev = \App\Models\Event::where('user_id',$u->user_id)->where('date',$event->date)->first();
          if ( $ev ) {
            echo 'Event already exists.<br>';
          }
          else try {
            $ev = new \App\Models\Event;
            $ev->user_id       = $u->user_id;
            $ev->text          = $event->display_html;
            $ev->beatmap_id    = $event->beatmap_id;
            $ev->beatmapset_id = $event->beatmapset_id;
            $ev->epicfactor    = $event->epicfactor;
            $ev->date          = $event->date;

            $ev->save();
            echo 'Event saved';
          } catch (\Illuminate\Database\QueryException $e) {
            echo ' Unable to save event<br>';
            echo $e->getMessage().'<br>';
          }

          $events_array[] = $ev;
        }


    // score stuff
        // Get the top 50 scores for the user
        $user_best = json_decode(file_get_contents($base_url. 'get_user_best?u='. $u->user_id . '&limit=2'. $api ));
        ++$count_api_calls;
        if (!empty($user_best)) {
          foreach ($user_best as $score) {

          // beatmap stuff
              // Get the first beatmap from the user's top scores
              $beatmap = json_decode(file_get_contents($base_url. 'get_beatmaps?b='. $score->beatmap_id . $api ))[0];
              ++$count_api_calls;
              $bmset_id = $beatmap->beatmapset_id; // save bmset id

              // Get the beatmapset from the beatmap
              echo '<h3>Beatmapset ID: ' . $beatmap->beatmapset_id . '</h3>';

              $beatmapset = json_decode(file_get_contents($base_url. 'get_beatmaps?s='. $beatmap->beatmapset_id . $api ));
              ++$count_api_calls;

              echo '<h3>Beatmap IDs in the set</h3>';



              $beatmap_diff_names = '';
              $set_play_count = 0;

              foreach ($beatmapset as $bm) {

                echo $bm->beatmap_id.' name '.$bm->version.'<br>';
                $beatmap_diff_names =  $beatmap_diff_names . $bm->version.',';
                $set_play_count += $bm->playcount;

            //Save each Beatmap in the set
                if ( $new_bm = \App\Models\Beatmap::where('beatmap_id',$bm->beatmap_id)->first() ) {
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
                $new_bm->artist = $bm->artist;
                $new_bm->filename = $bm->beatmapset_id. ' '. $bm->artist . ' - '. $bm->title .'.osz';
                $beatmap_array[] = $new_bm;

              } // end for each bm in beatmapset

              rtrim($beatmap_diff_names, ","); // take last comma off string

          // Save Beatmapset

              $set = \App\Models\BeatmapSet::where('beatmapset_id',$bmset_id)->first();
              if ( $set ) {
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
                $set->difficulty_names = $beatmap_diff_names;

                $beatmapset_array[] = $set;
              echo '</h3>';

        // Save score

        if  ( $existing_score = \App\Models\Score\Osu::where('beatmapset_id', $bmset_id)->first() )
        $existing_score->forceDelete(); // overwrite existing score

            try {
            $sc = new \App\Models\Score\Osu;

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

            $sc->save();

            echo 'Score saved<br>';

          } catch (\Illuminate\Database\QueryException $e) {
              echo ' Unable to save Score';
            }
            $sc->enabled_mods_val = 16;
            $scores_array[] = $sc;
          } // end foreach user best as score

        }
      }
      else return 'User Not Found';

    } // end foreach user

    echo '<h3>API Calls Made: '.$count_api_calls.'</h3>';
        \Storage::put('users.json', json_encode($users_array));
        \Storage::put('stats.json', json_encode($stats_array));
        \Storage::put('hist.json', json_encode($hist_array));
        \Storage::put('events.json', json_encode($events_array));
        \Storage::put('beatmaps.json', json_encode($beatmap_array));
        \Storage::put('beatmapsets.json', json_encode($beatmapset_array));
        \Storage::put('scores.json', json_encode($scores_array));
  }


}
