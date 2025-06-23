<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col lg:flex-row gap-6">
            {{-- Chart Section --}}
            <div class="flex-1 min-w-0">
                <div class="relative" style="height: 300px;">
                    <canvas
                        x-data="{
                            chart: null,
                            init() {
                                this.chart = new Chart(this.$el, @js($this->getChartConfig()));
                            }
                        }"
                        @if (method_exists($this, 'updateChart'))
                            wire:ignore
                            x-on:update-chart.window="chart.data = $event.detail.data; chart.update()"
                        @endif
                    ></canvas>
                </div>
            </div>
            
            {{-- Data Table Section --}}
            <div class="lg:w-80 flex-shrink-0">
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Jabatan
                    </h3>
                    
                    @if(!empty($chartData['labels']) && $chartData['labels'][0] !== 'No Data Available')
                        <div class="space-y-3">
                            @foreach($chartData['labels'] as $index => $label)
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div 
                                            class="w-4 h-4 rounded-full flex-shrink-0"
                                            style="background-color: {{ $chartData['datasets'][0]['backgroundColor'][$index] ?? '#gray' }}"
                                        ></div>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {{ $label }}
                                        </span>
                                    </div>
                                    <span class="text-sm font-bold text-gray-900 dark:text-white">
                                        {{ $chartData['datasets'][0]['data'][$index] ?? 0 }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                        
                        {{-- Total --}}
                        <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                    Total
                                </span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ array_sum($chartData['datasets'][0]['data']) }}
                                </span>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <div class="text-gray-500 dark:text-gray-400">
                                <svg class="mx-auto h-12 w-12 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                <p class="text-sm">No data available</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>