<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Support\Content;
use Illuminate\Database\Seeder;

/** Website CMS pages. Safe to re-run: only adds fields that are missing, never overwrites edits. */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Content::slugs() as $slug) {
            $page = Page::firstOrNew(['slug' => $slug]);
            $page->name = Content::definition($slug)['name'];
            $page->content = array_merge(Content::defaults($slug), $page->content ?? []);
            $page->save();
        }
    }
}
