<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\OrdersPerDayChart;
use App\Filament\Widgets\OrdersStatsOverview;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Enums\ActionSize;
use Filament\Widgets\Widget;

class Dashboard extends BaseDashboard
{
    public function getColumns(): int|array
    {
        return 12;
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('1h')
                    ->label('1 Hour')
                    ->action(fn () => $this->redirect(route('filament.admin.pages.dashboard'))),
                Action::make('24h')
                    ->label('24 Hours')
                    ->action(fn () => $this->redirect(route('filament.admin.pages.dashboard', ['period' => '24_hours']))),
                Action::make('7d')
                    ->label('7 Days')
                    ->action(fn () => $this->redirect(route('filament.admin.pages.dashboard', ['period' => '7_days']))),
            ])
                ->label('Filter')
                ->icon('heroicon-o-funnel')
                ->size(ActionSize::Small)
                ->color('gray')
                ->button(),
        ];
    }

    /**
     * @return array<class-string<Widget>|string>
     */
    public function getWidgets(): array
    {
        return [

            OrdersStatsOverview::class,
            OrdersPerDayChart::class,

        ];
    }

    public function getVisibleWidgets(): array
    {
        return $this->getWidgets();
    }
}
