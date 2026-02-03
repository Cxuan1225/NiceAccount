<?php

namespace App\Http\Resources\Security;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\Models\Permission;

/**
 * @mixin Permission
 */
class PermissionResource extends JsonResource
{
    /**
     * @return array{id:int, name:string, label:string|null, category:string|null, description:string|null, sort_order:int, is_active:bool}
     */
    public function toArray(Request $request): array
    {
        $label = $this->getAttribute('label');
        $category = $this->getAttribute('category');
        $description = $this->getAttribute('description');
        $sortOrder = $this->getAttribute('sort_order');
        $isActive = $this->getAttribute('is_active');

        return [
            'id' => (int) $this->id,
            'name' => (string) $this->name,
            'label' => is_string($label) ? $label : null,
            'category' => is_string($category) ? $category : null,
            'description' => is_string($description) ? $description : null,
            'sort_order' => is_numeric($sortOrder) ? (int) $sortOrder : 0,
            'is_active' => (bool) $isActive,
        ];
    }
}
