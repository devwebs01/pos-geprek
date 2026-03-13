<?php

use function Livewire\Volt\{state, computed, usesPagination, mount};
use App\Models\{Product, Order, OrderItem};
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

usesPagination();

state(['order', 'record' => fn() => request()->route('record'), 'orderItems' => []]);

state(['search'])->url();

// mount: load order & orderItems (array) once
mount(function () {
    $recordId = request()->route('record');

    if (!$recordId) {
        return;
    }

    $order = Order::with(['OrderItems'])->find($recordId);

    if (!$order) {
        return;
    }

    $this->order = $order;

    $this->orderItems = $order->orderItems
        ->map(
            fn($item) => [
                'id' => $item->product_id,
                'order_item_id' => $item->id,
                'name' => $item->product?->name ?? ($item->name ?? 'Unknown'),
                'price' => (float) $item->price,
                'quantity' => (int) $item->quantity,
            ],
        )
        ->toArray();
});

// sinkronkan orderItems (array) ke DB: hapus semua item lama kemudian insert yang baru
$syncOrderItemsToDb = function (Order $order) {
    DB::transaction(function () use ($order) {
        OrderItem::where('order_id', $order->id)->delete();

        $rows = collect($this->orderItems)
            ->map(
                fn($it) => [
                    'order_id' => $order->id,
                    'product_id' => $it['id'],
                    'quantity' => $it['quantity'],
                    'price' => $it['price'],
                    'subtotal' => $it['price'] * $it['quantity'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            )
            ->toArray();

        if (!empty($rows)) {
            OrderItem::insert($rows);
        }

        $total = collect($this->orderItems)->sum(fn($it) => $it['price'] * $it['quantity']);

        $order->update(['total_price' => $total]);
    });
};

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

$isClosed = fn() => $this->order && in_array($this->order->status, ['completed', 'cancelled']);

$addItem = function ($productId) {
    if ($this->isClosed()) {
        Notification::make()->title('Order sudah selesai / dibatalkan')->warning()->send();
        return;
    }

    $product = Product::find($productId);
    if (!$product) {
        return;
    }

    $existing = collect($this->orderItems)->firstWhere('id', $product->id);

    if ($existing) {
        $this->incrementQuantity($product->id);
    } else {
        $items = $this->orderItems;
        $items[] = [
            'id' => $product->id,
            'name' => $product->name,
            'price' => (float) $product->price,
            'quantity' => 1,
        ];
        $this->orderItems = array_values($items);
    }
};

$incrementQuantity = function ($productId) use ($isClosed) {
    if ($this->isClosed()) {
        Notification::make()->title('Order sudah selesai / dibatalkan')->warning()->send();
        return;
    }

    $items = $this->orderItems;
    $key = collect($items)->search(fn($i) => $i['id'] === $productId);
    if ($key === false) {
        return;
    }
    $items[$key]['quantity'] = (int) $items[$key]['quantity'] + 1;
    $this->orderItems = array_values($items);
};

$decrementQuantity = function ($productId) use ($isClosed) {
    if ($this->isClosed()) {
        Notification::make()->title('Order sudah selesai / dibatalkan')->warning()->send();
        return;
    }

    $items = $this->orderItems;
    $key = collect($items)->search(fn($i) => $i['id'] === $productId);
    if ($key === false) {
        return;
    }
    $items[$key]['quantity'] = max(0, (int) $items[$key]['quantity'] - 1);

    if ($items[$key]['quantity'] <= 0) {
        array_splice($items, $key, 1);
    }

    $this->orderItems = array_values($items);
};

$totalAmount = computed(function () {
    return collect($this->orderItems)->sum(fn($item) => $item['price'] * $item['quantity']);
});

$updateOrder = function () use ($isClosed) {
    if ($this->isClosed()) {
        Notification::make()->title('Tidak dapat mengubah order')->warning()->send();
        return;
    }

    DB::transaction(function () {
        $this->order->update([
            'status' => 'confirm',
            'total_price' => $this->totalAmount,
        ]);

        $this->syncOrderItemsToDb($this->order);
    });

    $this->order->refresh();
    Notification::make()->title('Pesanan Diupdate')->success()->send();
};

$completedOrder = function () use ($isClosed) {
    if ($this->isClosed()) {
        Notification::make()->title('Order sudah selesai / dibatalkan')->warning()->send();
        return;
    }

    DB::transaction(function (): void {
        $this->order->update(['status' => 'completed']);
    });

    $this->order->refresh();
    Notification::make()->title('Pesanan Selesai')->success()->send();
};

$confirmOrder = function () {
    DB::transaction(function () {
        $this->order->update([
            'status' => 'confirm',
            'total_price' => $this->totalAmount,
        ]);

        $this->syncOrderItemsToDb($this->order);

        $this->order->refresh();
        Notification::make()->title('Pesanan Dikonfirmasi')->success()->send();
    });
};

$startProcessing = function () {
    if ($this->isClosed()) {
        Notification::make()->title('Tidak dapat memproses order')->warning()->send();
        return;
    }

    DB::transaction(function () {
        $this->order->update(['status' => 'processing']);
        $this->order->refresh();
        Notification::make()->title('Pesanan mulai diproses')->success()->send();
    });
};

$backToPending = function () {
    DB::transaction(function () {
        $this->order->update(['status' => 'pending']);
        $this->order->refresh();
        Notification::make()->title('Status dikembalikan ke Pending')->info()->send();
    });
};

$cancelOrder = function () {
    DB::transaction(function () {
        $this->order->update(['status' => 'cancelled']);
        $this->order->refresh();
        Notification::make()->title('Pesanan dibatalkan')->warning()->send();
    });
};

// Print Bill: contoh redirect ke route print (sesuaikan route)
$printBill = function () {
    if (!$this->order) {
        return;
    }
    return redirect()->route('filament.admin.resources.orders.view', $this->order);
};

?>

<x-filament-panels::page>
    <style>
        .text-shadow {
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.8);
        }

        .text-shadow-lg {
            text-shadow: 0 2px 6px rgba(0, 0, 0, 0.9);
        }
    </style>
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
                                wire:click="addItem({{ $item->id }})"
                                :disabled="in_array($order->status, ['completed', 'cancelled'])">

                                {{-- Container Gambar --}}
                                <div class="flex-shrink-0 sm:hidden">
                                    <x-filament::avatar src="{{ Storage::url($item->image) }}" alt="{{ $item->name }}"
                                        class="w-16 h-16 object-cover rounded-md" />
                                </div>

                                {{-- Container Detail Produk --}}
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-base font-semibold text-white text-shadow truncate">
                                        {{ Str::limit($item->name, 40) }}
                                    </h4>
                                    <p class="text-xs text-white text-shadow mt-1 line-clamp-2">
                                        {{ Str::limit($item->description, 50) }}
                                    </p>
                                    <span class="mt-2 text-sm font-bold text-white text-shadow-lg">
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
                            @forelse ($orderItems as $orderItem)
                                <div class="flex items-center justify-between py-3">
                                    <div class="flex items-center gap-2">
                                        <x-filament::icon-button icon="heroicon-m-minus-circle" size="sm"
                                            color="danger" wire:click="decrementQuantity({{ $orderItem['id'] }})" />
                                        <span class="text-sm font-bold w-6 text-center">{{ $orderItem['quantity'] }}</span>
                                        <x-filament::icon-button icon="heroicon-m-plus-circle" size="sm"
                                            color="success" wire:click="incrementQuantity({{ $orderItem['id'] }})" />
                                    </div>
                                    <span class="flex-1 mx-2 text-sm font-medium truncate">{{ $orderItem['name'] }}</span>
                                    <span class="text-sm font-bold text-gray-800 dark:text-gray-200">
                                        {{ formatRupiah($orderItem['price'] * $orderItem['quantity']) }}
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
                        {{-- Tombol berdasarkan status pesanan --}}
                        @switch($order->status)
                            @case('draft')
                                {{-- Pesanan baru dari admin, masih draft --}}
                                <x-filament::button icon="heroicon-o-paper-airplane" color="primary" class="w-full"
                                    :disabled="empty($orderItems)" wire:click="confirmOrder">
                                    Konfirmasi Pesanan
                                </x-filament::button>
                                <x-filament::button icon="heroicon-o-trash" color="danger" outlined class="w-full"
                                    wire:click="cancelOrder">
                                    Batalkan Pesanan
                                </x-filament::button>
                            @break

                            @case('pending')
                                {{-- Pesanan dari WhatsApp, siap diproses --}}
                                <x-filament::button icon="heroicon-o-play" color="primary" class="w-full"
                                    wire:click="startProcessing">
                                    Mulai Proses
                                </x-filament::button>
                                <x-filament::button icon="heroicon-o-pencil-square" color="gray" outlined class="w-full"
                                    wire:click="updateOrder">
                                    Edit Pesanan
                                </x-filament::button>
                                <x-filament::button icon="heroicon-o-x-circle" color="danger" outlined class="w-full"
                                    wire:click="cancelOrder">
                                    Batalkan
                                </x-filament::button>
                            @break

                            @case('processing')
                                {{-- Pesanan sedang dibuat / dimasak --}}
                                <x-filament::button icon="heroicon-o-check-circle" color="success" class="w-full"
                                    wire:click="completedOrder">
                                    Selesaikan Pesanan
                                </x-filament::button>
                                <x-filament::button icon="heroicon-o-arrow-path" color="warning" outlined class="w-full"
                                    wire:click="backToPending">
                                    Kembali ke Pending
                                </x-filament::button>
                            @break

                            @case('confirm')
                                {{-- Pesanan sudah dikonfirmasi, siap diproses --}}
                                <x-filament::button icon="heroicon-o-play" color="primary" class="w-full"
                                    wire:click="startProcessing">
                                    Mulai Proses
                                </x-filament::button>
                                <x-filament::button icon="heroicon-o-pencil-square" color="gray" outlined class="w-full"
                                    wire:click="updateOrder">
                                    Update Pesanan
                                </x-filament::button>
                                <x-filament::button icon="heroicon-o-archive-box-arrow-down" color="warning" outlined class="w-full"
                                    :disabled="empty($orderItems)" wire:click="completedOrder">
                                    Selesaikan Pesanan
                                </x-filament::button>
                                <x-filament::button icon="heroicon-o-printer" color="success" outlined class="w-full"
                                    :disabled="empty($orderItems)" wire:click="printBill">
                                    Cetak Tagihan
                                </x-filament::button>
                            @break

                            @case('completed')
                                {{-- Pesanan sudah selesai --}}
                                <x-filament::button icon="heroicon-o-check-circle" color="success" class="w-full" disabled>
                                    Pesanan Selesai
                                </x-filament::button>
                                <x-filament::button icon="heroicon-o-printer" color="success" outlined class="w-full"
                                    wire:click="printBill">
                                    Cetak Tagihan
                                </x-filament::button>
                            @break

                            @case('cancelled')
                                {{-- Pesanan dibatalkan --}}
                                <x-filament::button icon="heroicon-o-x-circle" color="danger" class="w-full" disabled>
                                    Pesanan Dibatalkan
                                </x-filament::button>
                            @break
                        @endswitch

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
