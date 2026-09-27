<?php

namespace App\Console\Commands;

use App\Models\BranchImage;
use App\Models\Employee;
use App\Models\Service;
use App\Support\ImageThumb;
use Illuminate\Console\Command;

/**
 * Pre-generate the downscaled copies the public branch page serves, so the
 * first visitor after a deploy doesn't pay for resizing multi-megapixel
 * uploads. Safe to re-run: existing, up-to-date thumbnails are skipped.
 * New uploads are warmed automatically; this is for existing images.
 */
class WarmImageThumbs extends Command
{
    protected $signature = 'images:thumbs {--branch= : Only this branch id}';

    protected $description = 'Generate public-page thumbnails for branch photos, staff photos and service images';

    public function handle(): int
    {
        $branch = $this->option('branch');

        $photos = BranchImage::where('status', BranchImage::STATUS_APPROVED)
            ->when($branch, fn ($q) => $q->where('branch_id', $branch))
            ->pluck('path');
        $staff = Employee::whereNotNull('image')
            ->when($branch, fn ($q) => $q->where('branch_id', $branch))
            ->pluck('image');
        $services = Service::whereNotNull('image_path')->where('image_path', '!=', '')
            ->when($branch, fn ($q) => $q->where('branch_id', $branch))
            ->pluck('image_path');

        $jobs = $photos->map(fn ($p) => [$p, BranchImage::THUMB_WIDTHS])
            ->concat($staff->map(fn ($p) => [$p, [192]]))
            ->concat($services->map(fn ($p) => [$p, [160]]));

        $bar = $this->output->createProgressBar($jobs->count());
        foreach ($jobs as [$path, $widths]) {
            ImageThumb::warm($path, $widths);
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
        $this->info("Thumbnails ready for {$jobs->count()} image(s).");

        return self::SUCCESS;
    }
}
