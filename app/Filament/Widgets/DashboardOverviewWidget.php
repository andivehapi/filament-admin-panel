<?php

namespace App\Filament\Widgets;

use App\Services\FahrschuleClient;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget as BaseWidget;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class DashboardOverviewWidget extends BaseWidget
{
    private const FIRMA_ENDPOINT = '/services/sa/Admin/allFirmas';
    private const OVERVIEW_ENDPOINT = '/services/sa/Admin/firmaOverview';

    protected function getStats(): array
    {
        $client = app(FahrschuleClient::class);
        $now = Carbon::now();
        
        // Get companies data
        $companies = $client->post(self::FIRMA_ENDPOINT)->json();
        $realCompanies = collect($companies)->filter(fn($company) => $company['firma_is_real'] == '1');
        
        // Get current month financial data
        $currentData = $client->post(self::OVERVIEW_ENDPOINT, [
            'month' => $now->month,
            'year' => $now->year,
        ])->json();
        
        // Get previous month for comparison
        $previousMonth = $now->copy()->subMonth();
        $previousData = $client->post(self::OVERVIEW_ENDPOINT, [
            'month' => $previousMonth->month,
            'year' => $previousMonth->year,
        ])->json();

        // Calculate key metrics
        $totalCompanies = $realCompanies->count();
        $activeCompanies = $realCompanies->filter(fn($company) => $company['firma_is_visible'] == '1')->count();
        $retentionRate = $totalCompanies > 0 ? round(($activeCompanies / $totalCompanies) * 100, 1) : 0;
        
        $currentRevenue = collect($currentData)->sum('direktpay') + collect($currentData)->sum('paylink');
        $previousRevenue = collect($previousData)->sum('direktpay') + collect($previousData)->sum('paylink');
        $revenueChange = $this->calculatePercentageChange($currentRevenue, $previousRevenue);
        
        $totalStudents = collect($currentData)->sum('total_student');
        $activeStudents = collect($currentData)->sum('total_active_students');
        $activeStudentRate = $totalStudents > 0 ? round(($activeStudents / $totalStudents) * 100, 1) : 0;
        
        $newThisMonth = $realCompanies->filter(function($company) use ($now) {
            $startDate = Carbon::parse($company['started_using_from']);
            return $startDate->year === $now->year && $startDate->month === $now->month;
        })->count();

        return [
            Stat::make('Active Companies', $activeCompanies)
                ->icon('heroicon-o-building-office')
                ->iconColor('primary')
                ->description("$retentionRate% retention rate")
                ->descriptionIcon('heroicon-o-check-circle', 'before')
                ->descriptionColor('success'),

            Stat::make('Monthly Revenue', number_format($currentRevenue) . ' €')
                ->icon('heroicon-o-currency-euro')
                ->iconColor('success')
                ->description($this->formatChangeDescription($revenueChange))
                ->descriptionIcon($revenueChange > 0 ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down', 'before')
                ->descriptionColor($revenueChange > 0 ? 'success' : 'danger'),

            Stat::make('Active Students', number_format($activeStudents))
                ->icon('heroicon-o-users')
                ->iconColor('info')
                ->description("$activeStudentRate% of total students")
                ->descriptionIcon('heroicon-o-academic-cap', 'before')
                ->descriptionColor('info'),

            Stat::make('New Companies', $newThisMonth)
                ->icon('heroicon-o-plus-circle')
                ->iconColor('warning')
                ->description('Joined this month')
                ->descriptionIcon('heroicon-o-calendar-days', 'before')
                ->descriptionColor('warning'),
        ];
    }

    private function calculatePercentageChange(float $current, float $previous): float
    {
        if ($previous == 0) return 0;
        return (($current - $previous) / $previous) * 100;
    }

    private function formatChangeDescription(float $percentageChange): string
    {
        $sign = $percentageChange > 0 ? '+' : '-';
        $percentage = abs(round($percentageChange));
        return "$sign$percentage% from last month";
    }
} 