<?php

use Illuminate\Database\Seeder;
use Faker\Factory;

class ForumSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      $faker = Faker\Factory::create();

      try {
        DB::table('phpbb_forums')->delete();
        DB::table('phpbb_topics')->delete();
        DB::table('phpbb_posts')->delete();
        $forums = [];

        // Create 3 forums
        factory(App\Models\Forum\Forum::class, 'parent', 3)->create()->each(function($f) {
          for ($i=0; $i<4; $i++) {
            // Make 4 Sub forums for each forum.
              $f2 = $f->subforums()->save(factory(App\Models\Forum\Forum::class, 'child')->make());
              $f2->refreshCache();
              $t = $f2->topics()->save(factory(App\Models\Forum\Topic::class)->make());
              $t->refreshCache();
              $t->posts()->save(factory(App\Models\Forum\Post::class)->make());

            }
         });


        // for ($i=0;$i<5;$i++) {
        //   App\Models\Forum\Forum::create([
        //     'forum_name' => $faker->catchPhrase,
        //     'forum_desc' => $faker->realtext(80),
        //     'parent_id'  => $forum_id
        //   ]);
        // }
        // Add forum cover

      } catch (\Illuminate\Database\QueryException $e) {
        echo $e->getMessage().'\r\n';
      }

    }
}
