DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
bin=${DIR}/../bin
lib=${DIR}/../lib

  echo 'Updating the beatmap DB every 15 seconds (jdbcexec.sh). Press Ctrl+C to stop. Type bash ~/elasticsearch-jdbc-2.2.0.0/bin/jdbcexec.sh to restart again.'

echo '
{
  "type":"jdbc",
  "jdbc":{
    "fetchsize":1000,
    "max_retries":3,
    "url":"jdbc:mysql://localhost:3306/osu",
    "user":"osuweb",
    "password":"",
    "sql":"select s.beatmapset_id as _id, s.user_id, s.artist, s.artist, s.artist_unicode, s.title, s.title_unicode, s.creator, s.source, s.tags, s.video, s.storyboard, s.epilepsy, s.bpm, s.approved, s.approved_date, s.submit_date, s.last_update, s.rating, s.offset, s.genre_id, s.language_id, s.star_priority, s.filename, s.filesize, s.filesize_novideo, s.favourite_count, s.download_disabled, s.play_count, s.thread_id, s.difficulty_names, b.beatmap_id, b.version, b.total_length, b.hit_length, b.countTotal, b.countNormal, b.countSlider, b.countSpinner, b.diff_drain, b.diff_size, b.diff_overall, b.diff_approach, b.playmode, b.approved, b.difficultyrating, b.playcount, b.passcount FROM osu_beatmapsets s     JOIN osu_beatmaps b USING (beatmapset_id)     WHERE active = 1 AND orphaned = 0",
    "index":"osu",
    "type":"beatmaps"
  }
}
' | java \
    -cp "${lib}/*" \
    org.xbib.tools.Runner \
    org.xbib.tools.JDBCImporter
