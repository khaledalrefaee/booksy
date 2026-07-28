<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Resource;
use App\Models\Service;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Finds a free room/device for a service in a given time window.
 * Services with no linked resources are unconstrained (legacy behaviour).
 */
class ResourceAllocator
{
    /**
     * Active resources the service requires one of — linked either directly
     * to the service, or to its whole category (same branch only).
     */
    public function candidatesFor(Service $service): Collection
    {
        return $this->candidateQuery($service)->get();
    }

    public function requiresResource(Service $service): bool
    {
        return $this->candidateQuery($service)->exists();
    }

    private function candidateQuery(Service $service): \Illuminate\Database\Eloquent\Builder
    {
        return Resource::query()
            ->where('is_active', true)
            ->where('branch_id', $service->branch_id)
            ->where(function ($q) use ($service) {
                $q->whereHas('services', fn ($s) => $s->where('services.id', $service->id));
                if ($service->service_category_id) {
                    $q->orWhereHas('serviceCategories',
                        fn ($c) => $c->where('service_categories.id', $service->service_category_id));
                }
            })
            ->orderBy('sort_order')->orderBy('name_en');
    }

    /**
     * First candidate resource free during [$start, $end), or null when all are busy.
     * Pass $lock = true inside a DB transaction to guard against double-booking.
     */
    public function findFree(
        Service $service,
        Carbon $start,
        Carbon $end,
        ?int $ignoreAppointmentId = null,
        bool $lock = false,
    ): ?Resource {
        $candidates = $this->candidatesFor($service);
        if ($candidates->isEmpty()) {
            return null;
        }

        $busyIds = $this->busyResourceIds($candidates->pluck('id'), $start, $end, $ignoreAppointmentId, $lock);

        return $candidates->first(fn (Resource $r) => ! $busyIds->contains($r->id));
    }

    /**
     * Earliest moment a candidate resource frees up for this window, or null
     * when nothing is holding one. Each resource frees when its last
     * overlapping appointment ends; the soonest of those wins.
     */
    public function nextFreeAt(Service $service, Carbon $start, Carbon $end): ?Carbon
    {
        $candidates = $this->candidatesFor($service);
        if ($candidates->isEmpty()) {
            return null;
        }

        $freeAt = Appointment::query()
            ->whereIn('resource_id', $candidates->pluck('id'))
            ->whereIn('status', AppointmentStatus::blockingValues())
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->get(['resource_id', 'end_time'])
            ->groupBy('resource_id')
            ->map(fn ($appts) => $appts->max('end_time'))
            ->min();

        return $freeAt ? Carbon::parse($freeAt) : null;
    }

    /**
     * Human message explaining a resource conflict: which room/device is busy
     * and the earliest time one frees up.
     */
    public function conflictMessage(Service $service, Carbon $start, Carbon $end): string
    {
        $candidates = $this->candidatesFor($service);

        if ($candidates->isEmpty()) {
            return __('The required room or equipment is not available at this time.');
        }

        $names  = $candidates->map(fn (Resource $r) => $r->localizedName())->join('، ');
        $freeAt = $this->nextFreeAt($service, $start, $end);

        $message = $candidates->count() === 1
            ? __('“:resource” is busy at this time.', ['resource' => $names])
            : __('All required resources (:resources) are busy at this time.', ['resources' => $names]);

        if ($freeAt) {
            $message .= ' ' . __('Nearest availability: :time', ['time' => $freeAt->format('H:i')]);
        }

        return $message;
    }

    /** Resource ids (among $resourceIds) held by an active appointment overlapping the window. */
    public function busyResourceIds(
        Collection $resourceIds,
        Carbon $start,
        Carbon $end,
        ?int $ignoreAppointmentId = null,
        bool $lock = false,
    ): Collection {
        return Appointment::query()
            ->whereIn('resource_id', $resourceIds)
            ->whereIn('status', AppointmentStatus::blockingValues())
            ->when($ignoreAppointmentId, fn ($q) => $q->where('id', '!=', $ignoreAppointmentId))
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->when($lock, fn ($q) => $q->lockForUpdate())
            ->pluck('resource_id');
    }
}
