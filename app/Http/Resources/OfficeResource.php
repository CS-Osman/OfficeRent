<?php

namespace App\Http\Resources;

use Illuminate\Support\Arr;
use App\Http\Resources\TagResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\ImageResource;
use Illuminate\Http\Resources\Json\JsonResource;

class OfficeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'user' => UserResource::make($this->user),
            'images' => ImageResource::collection($this->images),
            'tags' => TagResource::collection($this->tags),

            $this->merge(Arr::except(parent::toArray($request), [
                'user_id' , 'created_at', 'updated_at', 'deleted_at'
            ]))
        ];
    }
}
