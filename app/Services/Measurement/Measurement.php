<?php

namespace App\Services\Measurement;

use App\Http\Resources\Measurement\MeasurementCollection;
use App\Http\Resources\Measurement\MeasurementResource;
use App\Models\Measurement as MeasurementModel;
use App\Models\Vitalab;

class Measurement
{
    public function index(array $data): MeasurementCollection
    {
        $perPage = (int) ($data['per_page'] ?? 10);
        $page    = (int) ($data['page']     ?? 1);

        $query = MeasurementModel::query()
            ->when($data['vitalab_id'] ?? null, fn ($q, $id) => $q->where('vitalab_id', $id))
            ->when(isset($data['failed_measurement']), fn ($q) => $q->where('failed_measurement', $data['failed_measurement']))
            ->when($data['measured_from'] ?? null, fn ($q, $from) => $q->where('measured_at', '>=', $from))
            ->when($data['measured_to'] ?? null, fn ($q, $to) => $q->where('measured_at', '<=', $to))
            ->orderByDesc('measured_at');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return new MeasurementCollection($paginator);
    }

    public function store(array $data): MeasurementResource
    {
        $vitalab = Vitalab::findOrFail($data['vitalab_id']);

        $measurementData = [
            'company_id'         => $vitalab->company_id,
            'vitalab_id'         => $vitalab->id,
            'humidity'           => $data['humidity'] ?? null,
            'measured_at'        => $data['measured_at'] ?? null,
            'failed_measurement' => !isset($data['temperature']) || !isset($data['humidity'])
        ];
        if(isset($data['temperature'])) {
            $measurementData['temperature'] = $data['temperature'];
        }
        if(isset($data['humidity'])) {
            $measurementData['humidity'] = $data['humidity'];
        }

        $measurement = MeasurementModel::create($measurementData);

        return new MeasurementResource($measurement);
    }
}