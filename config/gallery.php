<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Branch gallery — upload limits
    |--------------------------------------------------------------------------
    */

    // Gentle suggestion shown to the merchant; never enforced as a requirement.
    'suggested_min' => 2,

    // Files accepted in a single upload request.
    'max_files' => 15,

    // Maximum stored photos per kind (place / work) for one branch. Rejected
    // photos don't count. Enforced on both business and team uploads.
    'max_per_type' => 20,

    // Per-file ceiling (bytes). 20 MB.
    'max_bytes' => 20 * 1024 * 1024,

    // Comfortable minimum. Below this (but not tiny) the photo is still accepted
    // and simply held for review — never rejected just for being modest-res.
    'min_width'  => 500,
    'min_height' => 500,

    // Hard floor: only reject when the longest edge is below this (a genuine
    // thumbnail / icon, not a real photo).
    'reject_min_edge' => 400,

    'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
    'allowed_ext'   => ['jpg', 'jpeg', 'png', 'webp'],

    // Output quality when re-encoding to WebP.
    'webp_quality' => 82,

    /*
    |--------------------------------------------------------------------------
    | Technical quality analysis (GD only — no AI / computer vision)
    |--------------------------------------------------------------------------
    | These never reject a photo on their own. A photo that trips a threshold is
    | held as `pending` for a human at GlowRez to confirm, because a low score
    | does not always mean the photo is unusable.
    */

    // Laplacian-variance sharpness. Below this → likely blurry → pending review.
    // Kept low on purpose: only genuinely soft/blurry photos are flagged, so a
    // clear-but-not-razor-sharp photo is never held for no reason.
    'blur_threshold' => 40,

    // Mean luminance (0-255). Below this → too dark → pending review.
    'dark_threshold' => 32,

    // Longest edge (px) the image is downscaled to before analysis, for speed.
    'analysis_sample' => 320,

    /*
    |--------------------------------------------------------------------------
    | Rejection reasons (GlowRez review)
    |--------------------------------------------------------------------------
    | code => English label (translated via lang files). The code is what gets
    | stored in branch_images.rejection_reason; the label is shown to both the
    | reviewer and the merchant.
    */
    'rejection_reasons' => [
        'unclear'       => 'The photo is not clear',
        'poor_lighting' => 'Poor lighting',
        'not_place'     => "The photo doesn't show the place",
        'personal'      => 'The photo is personal',
        'not_branch'    => "The photo isn't of this branch",
        'inappropriate' => 'The photo is not suitable',
        'low_quality'   => 'Low photo quality',
        'other'         => 'Other',
    ],

];
