@php
    use Filament\Support\Enums\IconPosition;
    use Filament\Widgets\View\Components\StatsOverviewWidgetComponent\StatComponent\DescriptionComponent;
    use Filament\Widgets\View\Components\StatsOverviewWidgetComponent\StatComponent\StatsOverviewWidgetStatChartComponent;
    use Illuminate\View\ComponentAttributeBag;

    $chartColor = $getChartColor() ?? 'gray';
    $descriptionColor = $getDescriptionColor() ?? 'gray';
    $descriptionIcon = $getDescriptionIcon();
    $descriptionIconPosition = $getDescriptionIconPosition();
    $url = $getUrl();
    $tag = $url ? 'a' : 'div';
    $chartDataChecksum = $generateChartDataChecksum();
    $accountId = $getAccountId();
    $actionName = $getActionName();
    $color = $getColor(); // 'success' or 'danger' usually
@endphp

<{!! $tag !!}
    @if ($url)
        {{ \Filament\Support\generate_href_html($url, $shouldOpenUrlInNewTab()) }}
    @endif
    {{
        $getExtraAttributeBag()
            ->class([
                'fi-wi-stats-overview-stat',
            ])
    }}
>
    <div class="fi-wi-stats-overview-stat-content">
        <div class="fi-wi-stats-overview-stat-label-ctn">
            {{ \Filament\Support\generate_icon_html($getIcon()) }}

            <span class="fi-wi-stats-overview-stat-label">
                {{ $getLabel() }}
            </span>
        </div>

        <div class="fi-wi-stats-overview-stat-value">
            {{ $getValue() }}
        </div>

        @if ($description = $getDescription())
            <div
                {{ (new ComponentAttributeBag)->color(DescriptionComponent::class, $descriptionColor)->class(['fi-wi-stats-overview-stat-description']) }}
            >
                @if ($descriptionIcon && in_array($descriptionIconPosition, [IconPosition::Before, 'before']))
                    {{ \Filament\Support\generate_icon_html($descriptionIcon, attributes: (new \Illuminate\View\ComponentAttributeBag)) }}
                @endif

                <span>
                    {{ $description }}
                </span>

                @if ($descriptionIcon && in_array($descriptionIconPosition, [IconPosition::After, 'after']))
                    {{ \Filament\Support\generate_icon_html($descriptionIcon, attributes: (new \Illuminate\View\ComponentAttributeBag)) }}
                @endif
            </div>
        @endif

        @if($actionName && $accountId)
            <div class="mt-4">
                <x-filament::button
                    color="{{ $color ?? 'primary' }}"
                    size="xs"
                    wire:click="mountAction('{{ $actionName }}', { accountId: {{ $accountId }} })"
                >
                    {{ $actionName === 'payDebt' ? 'Pagar' : 'Cobrar' }}
                </x-filament::button>
            </div>
        @endif
    </div>
</{!! $tag !!}>

