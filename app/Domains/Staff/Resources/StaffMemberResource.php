<?php

namespace App\Domains\Staff\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffMemberResource extends JsonResource
{
    public function toArray($request): array
    {
        $internshipDuration = null;

        if ($this->internship_start_date && $this->internship_end_date) {
            $start = Carbon::parse($this->internship_start_date);
            $end = Carbon::parse($this->internship_end_date);
            $internshipDuration = [
                'days' => $start->diffInDays($end) + 1,
                'label' => $start->diffForHumans($end, [
                    'parts' => 3,
                    'short' => false,
                    'syntax' => Carbon::DIFF_ABSOLUTE,
                ]),
            ];
        }

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'employee_number' => $this->employee_number,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'status' => $this->status,
            'is_ceo' => (bool) $this->is_ceo,
            'is_board_member' => (bool) $this->is_board_member,
            'is_manager' => (bool) $this->is_manager,
            'is_intern' => (bool) $this->is_intern,
            'intern_sponsor_name' => $this->intern_sponsor_name,
            'internship_start_date' => $this->internship_start_date?->format('Y-m-d'),
            'internship_end_date' => $this->internship_end_date?->format('Y-m-d'),
            'internship_duration' => $internshipDuration,
            'department_id' => $this->department_id,
            'department_name' => $this->department?->name,
            'manager_id' => $this->manager_id,
            'manager_name' => $this->manager
                ? trim($this->manager->first_name.' '.$this->manager->last_name)
                : null,
            'department' => $this->department ? [
                'id' => $this->department->id,
                'name' => $this->department->name,
                'description' => $this->department->description,
            ] : null,
            'next_of_kin' => $this->nextOfKin ? [
                'id' => $this->nextOfKin->id,
                'full_name' => $this->nextOfKin->full_name,
                'relationship' => $this->nextOfKin->relationship,
                'phone' => $this->nextOfKin->phone,
                'email' => $this->nextOfKin->email,
            ] : null,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
