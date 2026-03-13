<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('Gambar Produk')
                    ->disk('public')
                    ->circular(),
                TextColumn::make('name')
                    ->label('Nama Produk')
                    ->sortable()
                    ->limit(25)
                    ->searchable(),
                TextColumn::make('category')
                    ->label('Kategori')
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'makanan' => 'Makanan',
                        'minuman' => 'Minuman',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'makanan' => 'warning',
                        'minuman' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('price')
                    ->label('Harga Produk')
                    ->formatStateUsing(fn ($state) => formatRupiah($state))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()->button(),
                DeleteAction::make()->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Hapus yang dipilih'),
                ]),
            ]);
    }
}
