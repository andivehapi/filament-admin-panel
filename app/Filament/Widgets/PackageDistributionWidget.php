<?php

namespace App\Filament\Widgets;

use App\Services\FahrschuleClient;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget as BaseWidget;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget\Stat;
use Filament\Actions\Action;
use Illuminate\Support\Carbon;

class PackageDistributionWidget extends BaseWidget
{
    public ?int $selectedPackage = null;
    public array $companiesForPackage = [];
    public ?string $modalTitle = null;
    public ?string $studentsModalTitle = null;
    public ?string $invoicesModalTitle = null;
    public array $studentsForCompany = [];
    public array $invoicesForCompany = [];
    public ?string $adminLoginUrl = null;
    public ?string $studentsLoading = null;
    public ?string $invoicesLoading = null;
    public ?string $studentsError = null;
    public ?string $invoicesError = null;

    private const ENDPOINT = '/services/sa/Admin/allFirmas';

    protected function getStats(): array
    {
        $client = app(FahrschuleClient::class);
        $companies = $client->post(self::ENDPOINT)->json();
        $realCompanies = collect($companies)->filter(fn($company) => $company['firma_is_real'] == '1');

        $package1 = $realCompanies->filter(fn($company) => $company['firma_package'] == '1')->count();
        $package2 = $realCompanies->filter(fn($company) => $company['firma_package'] == '2')->count();
        $package3 = $realCompanies->filter(fn($company) => $company['firma_package'] >= '3')->count();
        $totalCompanies = $realCompanies->count();

        $p1Percentage = $totalCompanies > 0 ? round(($package1 / $totalCompanies) * 100, 1) : 0;
        $p2Percentage = $totalCompanies > 0 ? round(($package2 / $totalCompanies) * 100, 1) : 0;
        $p3Percentage = $totalCompanies > 0 ? round(($package3 / $totalCompanies) * 100, 1) : 0;

        // Store for modal use
        $this->allCompanies = $realCompanies;

        return [
            Stat::make('Basic Package', $package1)
                ->icon('heroicon-o-tag')
                ->iconColor('gray')
                ->description("$p1Percentage% of total companies")
                ->descriptionIcon('heroicon-o-chart-pie', 'before')
                ->descriptionColor('gray')
                ->progress($p1Percentage)
                ->progressBarColor('gray'),

            Stat::make('Standard Package', $package2)
                ->icon('heroicon-o-star')
                ->iconColor('blue')
                ->description("$p2Percentage% of total companies")
                ->descriptionIcon('heroicon-o-chart-pie', 'before')
                ->descriptionColor('blue')
                ->progress($p2Percentage)
                ->progressBarColor('blue'),

            Stat::make('Premium Package', $package3)
                ->icon('heroicon-o-sparkles')
                ->iconColor('warning')
                ->description("$p3Percentage% of total companies")
                ->descriptionIcon('heroicon-o-chart-pie', 'before')
                ->descriptionColor('warning')
                ->progress($p3Percentage)
                ->progressBarColor('warning'),
        ];
    }
}