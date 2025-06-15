<?php

namespace App\Filament\Widgets;

use App\Services\FahrschuleClient;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget as BaseWidget;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class FinancialDetailsWidget extends BaseWidget
{
    private const ENDPOINT = '/services/sa/Admin/firmaOverview';

    protected function getStats(): array
    {
        $client = app(FahrschuleClient::class);
        $now = Carbon::now();
        
        // Get current month data
        $currentData = $client->post(self::ENDPOINT, [
            'month' => $now->month,
            'year' => $now->year,
        ])->json();

        // Get previous month data for comparison
        $previousMonth = $now->copy()->subMonth();
        $previousData = $client->post(self::ENDPOINT, [
            'month' => $previousMonth->month,
            'year' => $previousMonth->year,
        ])->json();

        // Calculate revenue metrics
        $currentDirektpay = collect($currentData)->sum('direktpay');
        $currentPaylink = collect($currentData)->sum('paylink');
        $currentTotalRevenue = $currentDirektpay + $currentPaylink;
        
        $previousDirektpay = collect($previousData)->sum('direktpay');
        $previousPaylink = collect($previousData)->sum('paylink');
        $previousTotalRevenue = $previousDirektpay + $previousPaylink;

        // Calculate percentage changes
        $direktpayChange = $this->calculatePercentageChange($currentDirektpay, $previousDirektpay);
        $paylinkChange = $this->calculatePercentageChange($currentPaylink, $previousPaylink);
        $totalRevenueChange = $this->calculatePercentageChange($currentTotalRevenue, $previousTotalRevenue);

        // Calculate average revenue per company
        $activeCompanies = collect($currentData)->filter(fn($company) => $company['total_student'] > 0)->count();
        $avgRevenuePerCompany = $activeCompanies > 0 ? $currentTotalRevenue / $activeCompanies : 0;

        // Calculate revenue distribution
        $direktpayPercentage = $currentTotalRevenue > 0 ? round(($currentDirektpay / $currentTotalRevenue) * 100, 1) : 0;
        $paylinkPercentage = $currentTotalRevenue > 0 ? round(($currentPaylink / $currentTotalRevenue) * 100, 1) : 0;

        return [
            Stat::make('DirektPay Revenue', number_format($currentDirektpay) . ' €')
                ->icon('heroicon-o-credit-card')
                ->iconColor('primary')
                ->description("$direktpayPercentage% of total revenue")
                ->descriptionIcon($direktpayChange > 0 ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down', 'before')
                ->descriptionColor($direktpayChange > 0 ? 'success' : 'danger'),

            Stat::make('PayLink Revenue', number_format($currentPaylink) . ' €')
                ->icon('heroicon-o-link')
                ->iconColor('info')
                ->description("$paylinkPercentage% of total revenue")
                ->descriptionIcon($paylinkChange > 0 ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down', 'before')
                ->descriptionColor($paylinkChange > 0 ? 'success' : 'danger'),

            Stat::make('Avg per Company', number_format($avgRevenuePerCompany, 0) . ' €')
                ->icon('heroicon-o-building-office')
                ->iconColor('warning')
                ->description('Average revenue per active company')
                ->descriptionIcon('heroicon-o-chart-bar-square', 'before')
                ->descriptionColor('info'),

            Stat::make('Revenue Growth', $this->getGrowthTrend($totalRevenueChange))
                ->icon('heroicon-o-arrow-trending-up')
                ->iconColor($totalRevenueChange > 0 ? 'success' : 'danger')
                ->description($this->formatChangeDescription($totalRevenueChange))
                ->descriptionIcon('heroicon-o-chart-bar-square', 'before')
                ->descriptionColor($totalRevenueChange > 0 ? 'success' : 'danger'),
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

    private function getGrowthTrend(float $percentageChange): string
    {
        if ($percentageChange > 5) return 'Strong Growth';
        if ($percentageChange > 0) return 'Growing';
        if ($percentageChange > -5) return 'Stable';
        return 'Declining';
    }
} 