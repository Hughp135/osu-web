<?php

use Illuminate\Database\Seeder;

class ForumSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      try {
        DB::table('phpbb_forums')->delete();
        DB::table('phpbb_topics')->delete();
        $forums = [];

        // Create 5 forums
        factory(App\Models\Forum\Forum::class, 'parent', 5)->create()->each(function($f) {
              $forums[] = $f->forum_id;
        });

        // Loop through forums, add 5 sub forums per forum
        foreach($forums as $forum_id) {
          for ($i=0;$i<5;$i++) {
            App\Models\Forum\Topic::create([
             'forum_id' => $forum_id,
            ]);
          }
        }

      } catch (\Illuminate\Database\QueryException $e) {
        echo $e->getMessage().'\r\n';
      }

    }
}
