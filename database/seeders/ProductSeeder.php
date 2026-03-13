<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            // --- Ayam Geprek Original & Paket ---
            [
                'name' => 'Ayam Geprek Original',
                'price' => 17500,
                'category' => 'makanan',
                'image_url' => 'https://i.gojekapi.com/darkroom/gofood-indonesia/v2/images/uploads/647c153a-0aad-42a9-a18c-d0ab0f5d609b_ded6c5f8-e1cf-4550-8d51-c60ff7b9732c.jpg?auto=format',
            ],
            [
                'name' => 'Ayam Geprek + Nasi Putih',
                'price' => 21500,
                'category' => 'makanan',
                'image_url' => 'https://i.gojekapi.com/darkroom/gofood-indonesia/v2/images/uploads/64d98db6-f3d0-48f8-905c-b225f1ccc9bd_a4e4658a-84e8-4d43-bc6a-b1d222210c67_Go-Resto_20181107_153141.jpg?auto=format',
            ],
            [
                'name' => 'Ayam Geprek + Nasi Uduk',
                'price' => 24000,
                'category' => 'makanan',
                'image_url' => 'https://i.gojekapi.com/darkroom/gofood-indonesia/v2/images/uploads/ad2202ab-2610-48ae-8403-aa35546325d2_eb96fb00-ed59-4a17-9e6f-61050ad4f58e_Go-Resto_20181107_153208.jpg?auto=format',
            ],
            [
                'name' => 'Ayam Geprek + Nasi + Tahu Tempe',
                'price' => 25000,
                'category' => 'makanan',
                'image_url' => 'https://i.gojekapi.com/darkroom/gofood-indonesia/v2/images/uploads/ad3bcc1e-6cba-42aa-ae73-d8061feeac77_cffd9a30-5c3b-4795-8ec9-29d0ce7f4416_Go-Biz_20200117_133234.jpeg?auto=format',
            ],

            // --- Varian Geprek Modern ---
            [
                'name' => 'Ayam Geprek Mozzarella',
                'price' => 30000,
                'category' => 'makanan',
                'image_url' => 'https://i.gojekapi.com/darkroom/gofood-id/v2/images/uploads/c595bfb0-9cd4-4a99-ba72-0058e6a2f068_Image-+-Badge.png',
            ],
            [
                'name' => 'Ayam Geprek Sambal Matah',
                'price' => 28500,
                'category' => 'makanan',
                'image_url' => 'https://i.gojekapi.com/darkroom/gofood-indonesia/v2/images/uploads/9057d1d7-fbee-4d3e-8ad0-7b9fcf4a5ccf.jpg?auto=format',
            ],
            [
                'name' => 'Ayam Geprek Sambal Ijo',
                'price' => 28000,
                'category' => 'makanan',
                'image_url' => 'https://i.gojekapi.com/darkroom/gofood-indonesia/v2/images/uploads/3fa2ccbb-c94c-44f0-8ddf-81b9d7e19364.jpg?auto=format',
            ],
            [
                'name' => 'Ayam Geprek Level 5 Pedas',
                'price' => 27000,
                'category' => 'makanan',
                'image_url' => 'https://i.gojekapi.com/darkroom/gofood-indonesia/v2/images/uploads/e7c5f671-f75f-4b67-83f2-0f5dbad628c4.jpg?auto=format',
            ],

            // --- Tambahan Lauk ---
            [
                'name' => 'Tahu Goreng',
                'price' => 5000,
                'category' => 'makanan',
                'image_url' => 'https://i.gojekapi.com/darkroom/gofood-indonesia/v2/images/uploads/9d93f04a-3b3a-4a7a-a1bb-0eb28d7f0e01.jpg?auto=format',
            ],
            [
                'name' => 'Tempe Goreng',
                'price' => 5000,
                'category' => 'makanan',
                'image_url' => 'https://i.gojekapi.com/darkroom/gofood-indonesia/v2/images/uploads/14f708ab-8595-4f04-b7f0-6dfadac3c291.jpg?auto=format',
            ],
            [
                'name' => 'Terong Crispy',
                'price' => 7000,
                'category' => 'makanan',
                'image_url' => 'https://i.gojekapi.com/darkroom/gofood-indonesia/v2/images/uploads/d7d2e6d6-2211-4643-bf8f-cd8c4e587cd1.jpg?auto=format',
            ],
            [
                'name' => 'Mie Instan Goreng + Telur',
                'price' => 15000,
                'category' => 'makanan',
                'image_url' => 'https://i.gojekapi.com/darkroom/gofood-indonesia/v2/images/uploads/98c6f730-67e2-4640-8016-2c291a4b60ab.jpg?auto=format',
            ],

            // --- Minuman ---
            [
                'name' => 'Es Teh Manis',
                'price' => 5000,
                'category' => 'minuman',
                'image_url' => 'https://i.gojekapi.com/darkroom/gofood-indonesia/v2/images/uploads/6bbf0db5-994d-4d51-8de5-d5cfa7f2f39d.jpg?auto=format',
            ],
            [
                'name' => 'Teh Manis Panas',
                'price' => 5000,
                'category' => 'minuman',
                'image_url' => 'https://i.gojekapi.com/darkroom/gofood-indonesia/v2/images/uploads/f0e50172-6e36-4f6b-967c-24d23a12e89a.jpg?auto=format',
            ],
            [
                'name' => 'Es Jeruk',
                'price' => 7000,
                'category' => 'minuman',
                'image_url' => 'https://i.gojekapi.com/darkroom/gofood-indonesia/v2/images/uploads/bc5adf12-8ee1-448a-8c1f-5e3b942e72ab.jpg?auto=format',
            ],
            [
                'name' => 'Air Mineral',
                'price' => 4000,
                'category' => 'minuman',
                'image_url' => 'https://i.gojekapi.com/darkroom/gofood-indonesia/v2/images/uploads/9f87c2ef-7260-44eb-9329-98e0de7e6f2d.jpg?auto=format',
            ],
            [
                'name' => 'Kopi Hitam Panas',
                'price' => 10000,
                'category' => 'minuman',
                'image_url' => 'https://i.gojekapi.com/darkroom/gofood-indonesia/v2/images/uploads/b9d7b2b6-1c18-4f13-a79f-842a541f8b6b.jpg?auto=format',
            ],
            [
                'name' => 'Jus Alpukat',
                'price' => 15000,
                'category' => 'minuman',
                'image_url' => 'https://i.gojekapi.com/darkroom/gofood-indonesia/v2/images/uploads/c90fdd7b-1f3b-4c8e-9b98-951fc2c4b06d.jpg?auto=format',
            ],
        ];

        foreach ($products as $data) {
            try {
                $imageContents = @file_get_contents($data['image_url']);

                if ($imageContents === false) {
                    // fallback image default
                    $fallbackUrl = 'https://i.gojekapi.com/darkroom/gofood-id/v2/images/uploads/c595bfb0-9cd4-4a99-ba72-0058e6a2f068_Image-+-Badge.png';
                    $imageContents = file_get_contents($fallbackUrl);
                    Log::warning('Fallback image used for '.$data['name']);
                }

                $imageName = Str::random(20).'.jpg';
                $imagePath = 'products/'.$imageName;
                Storage::disk('public')->put($imagePath, $imageContents);
                Log::info('Image for '.$data['name'].' saved to '.$imagePath);

                Product::create([
                    'name' => $data['name'],
                    'price' => $data['price'],
                    'description' => $data['name'],
                    'image' => $imagePath,
                    'category' => $data['category'] ?? 'makanan',
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to download image for '.$data['name'].' from '.$data['image_url'].'. Error: '.$e->getMessage());
                Product::create([
                    'name' => $data['name'],
                    'price' => $data['price'],
                    'description' => $data['name'],
                    'image' => null,
                ]);
            }
        }
    }
}
