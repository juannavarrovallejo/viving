<?php

namespace App\Imports\SuperAdmin;

use App\Models\Clinic;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;

class ClinicsImport implements ToCollection, WithValidation, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $clinic = Clinic::create([
                'name' => $row['name'],
                'description' => $row['description'],
                'is_active' => $row['is_active'],
                'user_name' => $row['user_name'],
                'image' => $row['image'] ?? null,
            ]);
        }
    }
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'description' => 'required|string',
            'image' => 'nullable|string',
            'user_name' => 'required|alpha_dash|unique:clinics,user_name',
            'is_active' => 'required|numeric|in:0,1',
        ];
    }
}
