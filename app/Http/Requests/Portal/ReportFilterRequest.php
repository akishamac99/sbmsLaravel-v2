<?php

namespace App\Http\Requests\Portal;

use App\Services\Reports\ReportSummary;
use Illuminate\Foundation\Http\FormRequest;

/** The filters shared by the Reports page, Download Report and Print Report. */
class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('staff') !== null;
    }

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', 'in:'.implode(',', ReportSummary::STATUSES)],
            'type' => ['nullable', 'in:online,walkin'],
            'staff_id' => ['nullable', 'integer'],
            'service_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:100'],
        ];
    }

    /** @return array<string, mixed> only the filters that were actually filled in */
    public function filters(): array
    {
        return array_filter($this->validated(), fn ($value) => $value !== null && $value !== '');
    }
}
