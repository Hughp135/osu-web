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

use Illuminate\Database\Seeder;
use DB;

use App\Models\User;
use App\Models\Forum\Forum;
use App\Transformers\UserTransformer;
use Auth;
use Request;


class TestController3 extends Controller{
    protected $section = 'test3';


  public function test(){

    $beatmaps_array = [];
    $beatmapset_array = [];
    $overbeatmaps = [];
    $overbeatmapsets = [];

    $count_api_calls = 0;
    $base_url = 'https://osu.ppy.sh/api/';
    $api_key = env('OSU_API', null); // Set your osu API key in your .env file
    if (empty($api_key)) {
      $this->command->error('Error: No OSU_API key set in .env. Can\'t seed beatmap data');
      return;
    }
    $api = '&k='.$api_key;
    $beatmaplimit = 20;

    try {
      $beatmaps = json_decode(file_get_contents($base_url. 'get_beatmaps?since=2016-01-01%2000:00:00' . $api ));
      ++$count_api_calls;
      $last_beatmapset = null;
      $beatmap_diff_names = '';
      $beatmapset_versions = 0;
      $set_playcount = 0;

      $first_map = true;

      foreach ($beatmaps as $bm) {

        // Here we are going to check if the current beatmap belongs to a new set, and make the set if necessary
        if ( $last_beatmapset === $bm->beatmapset_id || $first_map === true) {
          ++$beatmapset_versions;
          $beatmap_diff_names =  $beatmap_diff_names . $bm->version.',';
          $set_playcount += $bm->playcount;
        } else {
            // Create new beatmapset based on the PREVIOUS beatmap (since current one is in the next set)
            rtrim($beatmap_diff_names, ","); // take last comma off string
            echo 'Set ID: '.$previous_beatmap->beatmapset_id.' Difficulties: '.$beatmap_diff_names.'<br>';
            $set = \App\Models\BeatmapSet::where('beatmapset_id',$previous_beatmap->beatmapset_id)->first();
             if ( $set ) {
               $set->delete();
               $overbeatmapsets[] = $previous_beatmap->beatmapset_id;
             }
               $set = new \App\Models\BeatmapSet;
               $set->beatmapset_id = $previous_beatmap->beatmapset_id;
               $set->creator = $previous_beatmap->creator;
               $set->artist = $previous_beatmap->artist;
               $set->title = $previous_beatmap->title;
               $set->displaytitle = $previous_beatmap->title;
               $set->source = $previous_beatmap->source;
               $set->tags = $previous_beatmap->tags;
               $set->bpm = $previous_beatmap->bpm;
               $set->approved = $previous_beatmap->approved;
               $set->approved_date = $previous_beatmap->approved_date;
               $set->genre_id = $previous_beatmap->genre_id;
               $set->language_id = $previous_beatmap->language_id;
               $set->versions_available = $beatmapset_versions;
               $set->difficulty_names = $beatmap_diff_names;
               $set->play_count = $set_playcount;
               $set->favourite_count = $previous_beatmap->favourite_count;
               $set->save();

               $set->difficulty_names = $beatmap_diff_names;
               $beatmapset_array[] = $set;

               // Reset variables to contain only current beatmap
               $set_playcount = $bm->playcount;
               $beatmapset_versions = 1;
               $beatmap_diff_names = $bm->version;


        }

        if ( $new_bm = \App\Models\Beatmap::where('beatmap_id',$bm->beatmap_id)->first() ) {
               $new_bm->delete();
               $overbeatmaps[] = $new_bm;
             }
               $new_bm = new \App\Models\Beatmap;
                 $new_bm->beatmap_id = $bm->beatmap_id;
                 $new_bm->beatmapset_id = $bm->beatmapset_id;
                 $new_bm->filename = $bm->beatmapset_id. ' '. $bm->artist . ' - '. $bm->title .'.osz';
                 $new_bm->checksum = $bm->file_md5;
                 $new_bm->version = $bm->version;
                 $new_bm->total_length = $bm->total_length;
                 $new_bm->hit_length = $bm->hit_length;
                 $new_bm->countTotal = $bm->max_combo !== null ? $bm->max_combo : 1500 ;
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

                 $beatmaps_array[] = $new_bm;

          if ($first_map === true) $first_map = false;
          $last_beatmapset = $bm->beatmapset_id;
          $previous_beatmap = $bm;
      } // end foreach beatmap

       echo("Saved ".strval(count($beatmaps_array))." Beatmaps.");
       echo("Saved ".strval(count($beatmapset_array))." Beatmap Sets.");
       echo("Overwritten ".strval(count($overbeatmaps))." Beatmaps.");
       echo("Overwritten ".strval(count($overbeatmapsets))." Beatmap Sets.");
    } catch (\Illuminate\Database\QueryException $e) {
         $this->command->error("DB Error: Unable to save User Profile Data\r\n".$e->getMessage());
    } catch (Exception $ex) {
        $this->command->error("Error: Unable to save User Profile Data\r\n".$ex->getMessage());
    }

    

  }
}
