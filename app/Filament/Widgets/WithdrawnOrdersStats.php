<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class WithdrawnOrdersStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $withdrawn = DB::table('orders')->where('status', 'withdrawn')->count();

        $last7d = DB::table('orders')
            ->where('status', 'withdrawn')
            ->where('updated_at', '>=', now()->subDays(7))
            ->count();

        $latest = DB::table('orders')
            ->where('status', 'withdrawn')
            ->orderByDesc('updated_at')
            ->limit(1)
            ->value('updated_at');

        return [
            Stat::make('Taganetud tellimused (kokku)', $withdrawn)
                ->description('EL-i 14p taganemisõigus')
                ->color($withdrawn > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-arrow-uturn-left'),

            Stat::make('Viimase 7 päeva jooksul', $last7d)
                ->color($last7d > 0 ? 'warning' : 'gray'),

            Stat::make('Viimati taganetud', $latest ? \Carbon\Carbon::parse($latest)->diffForHumans() : '—')
                ->color('gray'),
        ];
    }
}
