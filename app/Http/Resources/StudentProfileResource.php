<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'avatar_url' => $this->avatar_url,
            'role' => $this->role,

            'student_profile' => $this->whenLoaded(
                'studentProfile',
                function () {
                    if (!$this->studentProfile) {
                        return null;
                    }

                    return [
                        'nis' =>
                            $this->studentProfile->nis,

                        'class' =>
                            $this->studentProfile->class,

                        'major' =>
                            $this->studentProfile->major,
                    ];
                }
            ),
        ];
    }
}
