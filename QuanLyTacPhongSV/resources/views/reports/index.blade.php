@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-space-md py-space-md pb-space-xl">
  <div class="flex flex-col gap-space-sm md:flex-row md:items-end md:justify-between">
    <div>
      <h1 class="font-headline-xl text-headline-xl">Thống kê & báo cáo</h1>
      <p class="text-on-surface-variant">Báo cáo theo tuần, tháng, học kỳ hoặc khoảng tùy chọn; theo loại vi phạm, ngày, khung giờ và khu vực.</p>
    </div>
    <div class="flex gap-space-sm">
      <button class="flex items-center gap-1 rounded-lg bg-secondary px-space-md py-2 font-label-md text-white" id="export-xlsx" type="button">
        <span class="material-symbols-outlined text-[18px]">table_view</span>Xuất Excel
      </button>
      <button class="flex items-center gap-1 rounded-lg bg-error px-space-md py-2 font-label-md text-on-error" id="export-pdf" type="button">
        <span class="material-symbols-outlined text-[18px]">picture_as_pdf</span>Xuất PDF
      </button>
    </div>
  </div>

  @include('partials.report_filters', ['filterExtended' => true])

  <div class="hidden rounded-xl bg-error-container p-space-md text-on-error-container" id="report-error" role="alert"></div>

  <section class="rounded-xl bg-surface-container-lowest p-space-md shadow-sm">
    <h2 class="font-headline-md text-headline-md" id="report-title">Đang tải báo cáo…</h2>
    <ul class="mt-1 flex flex-wrap gap-x-space-md gap-y-1 text-on-surface-variant" id="report-filters-text"></ul>
    <div class="mt-space-md grid grid-cols-2 gap-space-sm md:grid-cols-4" id="report-kpis"></div>
  </section>

  <section class="grid grid-cols-1 gap-space-md xl:grid-cols-2">
    <article class="rounded-xl bg-surface-container-lowest p-space-md shadow-sm">
      <h2 class="font-headline-md text-headline-md">Theo ngày</h2>
      <div class="relative mt-space-sm h-64"><canvas id="chart-days"></canvas></div>
    </article>
    <article class="rounded-xl bg-surface-container-lowest p-space-md shadow-sm">
      <h2 class="font-headline-md text-headline-md">Theo loại vi phạm</h2>
      <div class="relative mt-space-sm h-64"><canvas id="chart-types"></canvas></div>
    </article>
    <article class="rounded-xl bg-surface-container-lowest p-space-md shadow-sm">
      <h2 class="font-headline-md text-headline-md">Theo khung giờ</h2>
      <div class="relative mt-space-sm h-64"><canvas id="chart-hours"></canvas></div>
    </article>
    <article class="rounded-xl bg-surface-container-lowest p-space-md shadow-sm">
      <h2 class="font-headline-md text-headline-md">Theo khu vực</h2>
      <div class="relative mt-space-sm h-64"><canvas id="chart-areas"></canvas></div>
    </article>
  </section>

  <section class="grid grid-cols-1 gap-space-md xl:grid-cols-3">
    @foreach ([
        ['hotspot-body', 'Thời điểm & khu vực nhiều vi phạm', ['Khu vực • khung giờ', 'Số vi phạm']],
        ['area-body', 'Theo khu vực', ['Khu vực', 'Số vi phạm']],
        ['weekday-body', 'Theo thứ trong tuần', ['Thứ', 'Số vi phạm']],
    ] as [$bodyId, $heading, $columns])
    <article class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-sm">
      <h2 class="px-space-md pt-space-md font-headline-md text-headline-md">{{ $heading }}</h2>
      <div class="overflow-x-auto"><table class="mt-space-sm w-full text-left">
        <thead class="bg-surface-container-low font-label-sm text-label-sm uppercase text-outline">
          <tr><th class="px-space-md py-2">{{ $columns[0] }}</th><th class="px-space-md py-2 text-right">{{ $columns[1] }}</th></tr>
        </thead>
        <tbody id="{{ $bodyId }}"></tbody>
      </table></div>
    </article>
    @endforeach
  </section>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js" defer></script>
<script src="{{ asset('assets/js/filters.js') }}" defer></script>
<script src="{{ asset('assets/js/charts.js') }}" defer></script>
<script src="{{ asset('assets/js/reports.js') }}" defer></script>
@endpush
