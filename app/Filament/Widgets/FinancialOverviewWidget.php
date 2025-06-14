<?php

namespace App\Filament\Widgets;

use App\Enums\ExcludedFirma;
use App\Enums\StatField;
use App\Services\FahrschuleClient;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget as BaseWidget;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class FinancialOverviewWidget extends BaseWidget
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

        // Get same month last year data
        $lastYearData = $client->post(self::ENDPOINT, [
            'month' => $now->month,
            'year' => $now->year - 1,
        ])->json();

        // Remove excluded companies
        $excludedIds = collect(ExcludedFirma::cases())->pluck('value')->toArray();
        $currentData = collect($currentData)->filter(fn($company) => !in_array($company['firma_id'], $excludedIds))->toArray();
        $lastYearData = collect($lastYearData)->filter(fn($company) => !in_array($company['firma_id'], $excludedIds))->toArray();

        // Create stats for each field
        $stats = [];
        
        foreach (StatField::cases() as $field) {
            // Calculate sums
            $currentSum = $this->sumField($currentData, $field);
            $lastYearSum = $this->sumField($lastYearData, $field);
            
            // Calculate percentage change
            $percentageChange = $this->calculatePercentageChange($currentSum, $lastYearSum);
            
            // Format value
            $formattedValue = $this->formatValue($currentSum, $field->isCurrency());
            
            // Create stat using Filament's enum methods
            $stats[] = Stat::make($field->getLabel(), $formattedValue)
                ->icon($field->getIcon())
                ->iconColor($field->getColor())
                ->description($this->formatChangeDescription($percentageChange))
                ->descriptionIcon($percentageChange > 0 ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down', 'before')
                ->descriptionColor($percentageChange > 0 ? 'success' : 'danger');
        }

        return $stats;
    }

    private function sumField(array $data, StatField $field): float
    {
        if ($field === StatField::FINSUIT) {
            // FinSuit is sum of DirektPay + PayLink
            $direktpaySum = collect($data)->sum(StatField::DIREKTPAY->value);
            $paylinkSum = collect($data)->sum(StatField::PAYLINK->value);
            return $direktpaySum + $paylinkSum;
        }
        
        return collect($data)->sum($field->value);
    }

    private function calculatePercentageChange(float $current, float $previous): float
    {
        if ($previous == 0) return 0;
        return (($current - $previous) / $previous) * 100;
    }

    private function formatValue(float $value, bool $isCurrency): string
    {
        $formatted = number_format($value, 0, ',', '.');
        return $isCurrency ? "$formatted €" : $formatted;
    }

    private function formatChangeDescription(float $percentageChange): string
    {
        $sign = $percentageChange > 0 ? '+' : '-';
        $percentage = abs(round($percentageChange));
        return "$sign$percentage%";
    }
} 