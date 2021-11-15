<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Resources\OfficeResource;
use App\Models\Validators\OfficeValidator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class OfficeController extends Controller
{
    public function index()
    {
        $offices = Office::query()
                    ->where('approval_status', Office::APPROVAL_APPROVE)
                    ->where('hidden', false)
                    ->when(request('user_id'), fn ($builder) => $builder->whereUserId(request('user_id')))
                    ->when(
                        request('visitor_id'),
                        fn (Builder $builder)
                            => $builder->whereRelation('reservations', 'user_id', '=', request('visitor_id'))
                    )
                    ->when(
                        request('lat') && request('lng'),
                        fn ($builder) => $builder->nearestTo(request('lat'), request('lng')),
                        fn ($builder) => $builder->orderBy('id', 'ASC')
                    )
                    ->with(['images', 'tags', 'user'])
                    ->withCount(['reservations' => fn ($builder) => $builder->where('status', Reservation::STATUS_ACTIVE)])
                    ->latest('id')
                    ->paginate(20);

        return OfficeResource::collection($offices);
    }

    public function show(Office $office)
    {
        $office->loadCount(['reservations' => fn ($builder) => $builder->where('status', Reservation::STATUS_ACTIVE)])
            ->load(['images', 'tags', 'user']);

        return OfficeResource::make($office);
    }

    public function create()
    {
        abort_unless(
            !auth()->user()->tokenCan('office.create'),
            Response::HTTP_FORBIDDEN
        );

        $attibutes = (new OfficeValidator())->validate(
            $office = new Office(),
            request()->all()
        );
        
        $attibutes['approval_status'] = Office::APPROVAL_PENDING;
        $attibutes['user_id'] = auth()->id();

        $office = DB::transaction(function ($office, $attibutes) {
            $office->fill(
                Arr::except($attibutes, ['tags'])
            )->save();
    
            if (isset($attibutes['tags'])) {
                $office->tags()->sync($attibutes['tags']);
            }

            return $office;
        });

        return OfficeResource::make(
            $office->load(['images', 'tags', 'user'])
        );
    }

    public function update(Office $office)
    {
        abort_unless(
            !auth()->user()->tokenCan('office.create'),
            Response::HTTP_FORBIDDEN
        );
        
        $this->authorize('update', $office);

        $attibutes = (new OfficeValidator())->validate($office, request()->all());
        
        $office = DB::transaction(function ($office, $attibutes) {
            $office->update(
                Arr::except($attibutes, ['tags'])
            );
            if (isset($attibutes['tags'])) {
                $office->tags()->sync($attibutes['tags']);
            }
            return $office;
        });

        return OfficeResource::make(
            $office->load(['images', 'tags', 'user'])
        );
    }
}
