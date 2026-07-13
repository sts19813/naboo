<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorageItemRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'storage_warehouse_id' => 'required|integer|exists:storage_warehouses,id',
            'storage_zone_id' => 'required|integer|exists:storage_zones,id',
            'product_type' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'brand' => 'nullable|string|max:255',
            'condition' => 'required|in:bueno,regular,malo',
            'quantity' => 'required|integer|min:1',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'photo_camera' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ];
    }
}

