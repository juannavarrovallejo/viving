<?php

namespace App\Http\Resources\API;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class BroadcastsResource extends JsonResource
{
    public static $wrap = null;
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $tags = $this->relationLoaded('tags') ? $this->whenLoaded('tags'):null;
        $clinic = $this->relationLoaded('clinic') ? $this->whenLoaded('clinic'):null;
        $doctor = $this->relationLoaded('doctor') ? $this->whenLoaded('doctor'):null;
        return [
                "id" =>  $this->id,
                'doctor_id' => $this->doctor_id,
                'doctor_name' => $doctor ? $doctor->name :"",
                'clinic_id' => $this->clinic_id,
                'clinic_name' => $clinic ? $clinic->name :"",
                "tag_ids" => $tags ? TagsResource::collection($this->whenLoaded('tags',function(){
                    return $this->tags;
                }))->pluck('id')->toArray():[],
                "tags" => $tags ? TagsResource::collection($tags):[],
                "name" =>  $this->name,
                "description" =>  $this->description,
                "slug" =>  $this->slug,
                "is_active" =>  $this->is_active,
                "is_featured" =>  $this->is_featured,
                "icon" =>  $this->icon,
                "image" =>  $this->image,
                "audio" =>  $this->audio,
                "video" =>  $this->video,
                "link_type" =>  $this->link_type,
                "file_type" =>  $this->file_type,
                "file_url" =>  $this->file_url,
                "created_at" =>  Carbon::parse($this->created_at)->format('Y-m-d'),
                "updated_at" =>  $this->updated_at,
        ];
    }
}
