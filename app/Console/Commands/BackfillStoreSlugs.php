<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class BackfillStoreSlugs extends Command
{
    protected $signature = 'merchants:backfill-store-slugs {--dry-run : Show proposed changes without writing them}';

    protected $description = 'Generate unique store slugs for merchants that do not yet have one.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $processed = 0;

        User::query()
            ->whereNull('store_slug')
            ->orWhere('store_slug', '')
            ->orderBy('id')
            ->chunkById(100, function ($merchants) use ($dryRun, &$processed): void {
                foreach ($merchants as $merchant) {
                    $slug = $this->uniqueSlug($merchant->name, $merchant->id);

                    if ($dryRun) {
                        $this->line("[dry-run] user {$merchant->id}: {$slug}");
                    } else {
                        $merchant->forceFill(['store_slug' => $slug])->saveQuietly();
                        $this->line("user {$merchant->id}: {$slug}");
                    }

                    $processed++;
                }
            });

        $this->info($dryRun
            ? "Dry run completed for {$processed} merchant(s)."
            : "Backfill completed for {$processed} merchant(s).");

        return self::SUCCESS;
    }

    private function uniqueSlug(string $name, int $ignoreUserId): string
    {
        $base = Str::slug($name) ?: 'store';
        $slug = $base;
        $suffix = 2;

        while (User::query()
            ->where('store_slug', $slug)
            ->where('id', '!=', $ignoreUserId)
            ->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
