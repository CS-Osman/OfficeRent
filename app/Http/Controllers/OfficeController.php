<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Models\Reservation;
use Illuminate\Http\Request;
use App\Http\Resources\OfficeResource;
use Illuminate\Database\Eloquent\Builder;

class OfficeController extends Controller
{
    public function index()
    {
        $offices = Office::query()
                    ->where('approval_status', Office::APPROVAL_APPROVE)
                    ->where('hidden', false)
                    ->when(request('host_id'), fn($builder) => $builder->whereUserId(request('host_id')))
                    ->when(
                        request('user_id'),
                        fn (Builder $builder)
                            => $builder->whereRelation('reservations', 'user_id', '=', request('user_id'))
                    )
                    ->when(
                        request('lat') && request('lng'),
                        fn($builder) => $builder->nearestTo(request('lat'), request('lng')),
                        fn($builder) => $builder->orderBy('id', 'ASC')
                    )
                    ->with(['images', 'tags', 'user'])
                    ->withCount(['reservations' => fn($builder) => $builder->where('status', Reservation::STATUS_ACTIVE)])
                    ->latest('id')
                    ->paginate(20);

        return OfficeResource::collection($offices);
    }

    public function show(Office $office)
    {
        $office->loadCount(['reservations' => fn($builder) => $builder->where('status', Reservation::STATUS_ACTIVE)])
            ->load(['images', 'tags', 'user']);

        return OfficeResource::make($office);
    }
}
