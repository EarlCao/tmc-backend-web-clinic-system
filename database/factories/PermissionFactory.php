<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $module = strtolower(fake()->unique()->word());
        $action = fake()->randomElement(['view', 'create', 'update', 'delete']);

        return [
            'module' => $module,
            'name' => $module.'.'.$action,
            'label' => Str::headline($action).' '.Str::headline($module),
        ];
    }
}
