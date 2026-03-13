<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->randomFloat(2, 10000, 100000),
            'image' => $this->faker->imageUrl(640, 480, 'food'),
            'category' => $this->faker->randomElement(['makanan', 'minuman']),
        ];
    }

    /**
     * Indicate that the product is food (makanan).
     */
    public function makanan(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'makanan',
        ]);
    }

    /**
     * Indicate that the product is beverage (minuman).
     */
    public function minuman(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'minuman',
        ]);
    }
}
