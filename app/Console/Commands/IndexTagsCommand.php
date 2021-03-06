<?php

namespace Coyote\Console\Commands;

use Coyote\Services\Elasticsearch\Crawler;
use Coyote\Tag;
use Illuminate\Console\Command;

class IndexTagsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tags:index';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reindex recently updated tags.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $tags = Tag::withTrashed()->where('updated_at', '>', now()->subMinutes(6))->get();

        $crawler = new Crawler();

        foreach ($tags as $tag) {
            $this->info("Indexing $tag->name ...");

            $tag->deleted_at ? $crawler->delete($tag) : $crawler->index($tag);
        }

        $this->info('Done.');

        return 0;
    }
}
