<?php

use function Livewire\Volt\{state, computed, usesPagination};
use App\Models\{Product, Order, OrderItem};
use Illuminate\Support\Facades\DB;

usesPagination();

state([
    'record' => fn() => request()->route('record'),
    'currentOrder' => collect([]),
]);

state(['search'])->url();

$menuItems = computed(function () {
    $query = Product::query();

    if ($this->search) {
        $query->where('name', 'like', '%' . $this->search . '%');
    }

    return $query->paginate(6);
});

$updatingSearch = function () {
    $this->resetPage();
};

$addItem = function ($productId) {
    $product = Product::find($productId);

    $existing = $this->currentOrder->firstWhere('id', $product->id);

    if ($existing) {
        $this->incrementQuantity($product->id);
    } else {
        $this->currentOrder->push(
            (object) [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => 1,
            ],
        );
    }
};

$incrementQuantity = function ($productId) {
    $this->currentOrder = $this->currentOrder->map(function ($item) use ($productId) {
        if ($item->id === $productId) {
            $item->quantity++;
        }
        return $item;
    });
};

$decrementQuantity = function ($productId) {
    $this->currentOrder = $this->currentOrder
        ->map(function ($item) use ($productId) {
            if ($item->id === $productId) {
                $item->quantity = max(0, ((int) $item->quantity) - 1);
            }
            return $item;
        })
        ->filter(fn($item) => $item->quantity > 0)
        ->values();
};

$totalAmount = computed(function () {
    return $this->currentOrder->sum(fn($item) => $item->price * $item->quantity);
});

$saveDraft = function () {
    DB::transaction(function () {
        $this->record->update([
            'status' => 'confirm',
            'total_price' => $this->totalAmount,
            'payment_method' => '',
        ]);

        foreach ($this->currentOrder as $item) {
            OrderItem::create([
                'order_id' => $this->record->id,
                'product_id' => $item->id,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'subtotal' => $item->price * $item->quantity,
            ]);
        }

        return redirect()->route('filament.admin.resources.orders.edit', ['record' => $this->record->id]);
    });
};

$printBill = function () {
    return redirect()->route('filament.admin.resources.orders.view', $this->record);
};

$confirmOrder = function () {
    DB::transaction(function () {
        $this->record->update([
            'status' => 'confirm',
            'total_price' => $this->totalAmount,
            'payment_method' => '',
        ]);

        foreach ($this->currentOrder as $item) {
            OrderItem::create([
                'order_id' => $this->record->id,
                'product_id' => $item->id,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'subtotal' => $item->price * $item->quantity,
            ]);
        }

        return redirect()->route('filament.admin.resources.orders.edit', ['record' => $this->record->id]);
    });
};

?>

<x-filament-panels::page>
    @volt
        <div class="flex flex-col lg:flex-row gap-6">
            {{-- Panel Kiri: Daftar Menu --}}
            <div class="lg:w-2/3 space-y-4">
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold">Daftar Menu Makanan & Minuman</h2>
                        </div>
                    </x-slot>

                    {{-- Search Bar --}}
                    <div class="sticky top-0 z-10  pb-4">
                        <x-filament::input.wrapper>
                            <x-filament::input wire:model.live.debounce.300ms="search" type="search"
                                placeholder="Cari menu..." />
                        </x-filament::input.wrapper>
                    </div>

                    {{-- Daftar Menu --}}
                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-4 max-h-[60vh] overflow-y-auto hide-scrollbar p-2">
                        @forelse ($this->menuItems as $item)
                            <div class="p-4 rounded-xl shadow-md flex items-center gap-4 hover:ring-2 hover:ring-primary-500 transition-all duration-200 ease-in-out cursor-pointer bg-cover bg-center relative overflow-hidden"
                                style="background-image: url('{{ Storage::url($item->image) }}');"
                                wire:click="addItem({{ $item->id }})">

                                {{-- Container Gambar --}}
                                <div class="flex-shrink-0 sm:hidden">
                                    <x-filament::avatar src="{{ Storage::url($item->image) }}" alt="{{ $item->name }}"
                                        class="w-16 h-16 object-cover rounded-md" />
                                </div>

                                {{-- Container Detail Produk --}}
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-base font-semibold text-primary-600 truncate">
                                        {{ Str::limit($item->name, 40) }}
                                    </h4>
                                    <p class="text-xs text-primary-600 mt-1 line-clamp-2">
                                        {{ Str::limit($item->description, 50) }}
                                    </p>
                                    <span class="mt-2 text-sm font-bold text-primary-600">
                                        {{ formatRupiah($item->price) }}
                                    </span>
                                </div>

                                {{-- Tombol Tambah --}}
                                <div class="flex-shrink-0">
                                    <x-filament::icon-button icon="heroicon-m-plus" size="md" color="primary"
                                        class="rounded-full flex-shrink-0 ml-2" />
                                </div>

                            </div>
                        @empty
                            <div class="col-span-full p-4 text-center text-gray-500 dark:text-gray-400">
                                😔 Tidak ada produk yang cocok dengan pencarian Anda.
                            </div>
                        @endforelse
                    </div>

                    {{-- Pagination --}}
                    <x-filament::pagination :paginator="$this->menuItems" :extreme-links="true" class="mt-6" />

                </x-filament::section>
            </div>

            {{-- Panel Kanan: Pesanan Saat Ini --}}
            <div class="lg:w-1/3 flex flex-col gap-4">
                <x-filament::section class="flex-1 flex flex-col justify-between">
                    <x-slot name="heading">
                        <x-filament::badge class="capitalize">
                            {{ $record->status }}
                        </x-filament::badge>

                        <h3 class="text-lg font-semibold">
                            Pesanan No. {{ $record->id }}
                            <p class="text-xs text-gray-500 mt-1">{{ now()->translatedFormat('l, d F Y') }}</p>

                        </h3>
                    </x-slot>

                    {{-- List Order --}}
                    <div class="flex-1 overflow-y-auto hide-scrollbar -mx-6 px-6">
                        <div class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($currentOrder as $orderItem)
                                <div class="flex items-center justify-between py-3">
                                    <div class="flex items-center gap-2">
                                        <x-filament::icon-button icon="heroicon-m-minus-circle" size="sm"
                                            color="danger" wire:click="decrementQuantity({{ $orderItem->id }})" />
                                        <span class="text-sm font-bold w-6 text-center">{{ $orderItem->quantity }}</span>
                                        <x-filament::icon-button icon="heroicon-m-plus-circle" size="sm"
                                            color="success" wire:click="incrementQuantity({{ $orderItem->id }})" />
                                    </div>
                                    <span class="flex-1 mx-2 text-sm font-medium truncate">{{ $orderItem->name }}</span>
                                    <span class="text-sm font-bold text-gray-800 dark:text-gray-200">
                                        {{ formatRupiah($orderItem->price * $orderItem->quantity) }}
                                    </span>
                                </div>
                            @empty
                                <div class="py-6 text-center text-gray-400 text-sm">
                                    Belum ada item ditambahkan. Silakan pilih menu di samping.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Total dan Tombol Aksi --}}
                    <div class="space-y-4 pt-4 border-t border-gray-200 dark:border-gray-700 mt-auto">
                        <div class="flex justify-between items-center text-lg font-bold">
                            <span>Total:</span>
                            <span class="text-primary-600">{{ formatRupiah($this->totalAmount) }}</span>
                        </div>
                        <x-filament::button icon="heroicon-o-paper-airplane" color="primary" class="w-full"
                            :disabled="$currentOrder->isEmpty()" wire:click="confirmOrder">
                            Konfirmasi Pesanan
                        </x-filament::button>
                        <div class="grid grid-cols-2 gap-3">
                            <x-filament::button icon="heroicon-o-archive-box-arrow-down" color="warning" outlined
                                class="w-full" :disabled="$currentOrder->isEmpty()" wire:click="saveDraft">
                                Simpan Draft
                            </x-filament::button>
                            <x-filament::button icon="heroicon-o-printer" color="success" outlined class="w-full"
                                :disabled="$currentOrder->isEmpty()" wire:click="printBill">
                                Cetak Tagihan
                            </x-filament::button>
                        </div>
                    </div>
                </x-filament::section>
            </div>
        </div>
    @endvolt

    <style>
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</x-filament-panels::page>
