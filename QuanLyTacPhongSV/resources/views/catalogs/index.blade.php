@extends('layouts.app')

@section('content')
<div class="flex flex-col w-full gap-space-md pb-space-xl" id="catalog-module">
  <div class="flex flex-col gap-1 py-space-md">
    <h1 class="font-headline-xl text-headline-xl text-on-surface">Quản lý danh mục</h1>
    <p class="font-body-md text-body-md text-on-surface-variant">
      Quản lý Khoa, Ngành, Lớp, Loại vi phạm và Học kỳ theo các bảng hiện có trong SQL Server.
    </p>
  </div>

  <div class="rounded-xl border border-error/20 bg-error-container/40 p-space-md text-on-error-container" id="catalog-status" role="status" aria-live="polite">
    Đang tải dữ liệu danh mục từ cơ sở dữ liệu…
  </div>

  <section class="rounded-xl bg-surface-container-lowest p-space-md shadow-sm">
    <div class="flex flex-col gap-space-sm md:flex-row md:items-end md:justify-between">
      <label class="flex w-full flex-col gap-1 md:max-w-sm">
        <span class="font-label-md text-label-md">Loại danh mục</span>
        <select class="h-10 rounded-lg bg-surface-container-low px-3" id="catalog-entity">
          <option value="KHOA">Khoa</option>
          <option value="NGANH">Ngành</option>
          <option value="LOP">Lớp</option>
          <option value="LOI_VIPHAM">Loại vi phạm</option>
          <option value="HOCKY">Học kỳ</option>
        </select>
      </label>
      <button class="h-10 rounded-lg bg-surface-container px-space-md font-label-md" id="catalog-refresh" type="button">
        Làm mới từ cơ sở dữ liệu
      </button>
    </div>
  </section>

  <section class="grid grid-cols-1 gap-space-md xl:grid-cols-[minmax(280px,360px)_1fr]">
    <form class="flex flex-col gap-space-sm rounded-xl bg-surface-container-lowest p-space-md shadow-sm" id="catalog-form">
      <h2 class="font-headline-md text-headline-md" id="catalog-form-title">Thêm danh mục</h2>
      <div class="grid gap-space-sm" id="catalog-fields"></div>
      <div class="flex gap-space-sm pt-1">
        <button class="rounded-lg bg-primary-container px-space-md py-2 font-label-md text-on-primary" id="catalog-save" type="submit">
          Lưu danh mục
        </button>
        <button class="hidden rounded-lg bg-surface-container px-space-md py-2 font-label-md" id="catalog-cancel" type="button">
          Hủy sửa
        </button>
      </div>
    </form>

    <section class="min-w-0 overflow-hidden rounded-xl bg-surface-container-lowest shadow-sm">
      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead class="bg-surface-container-low">
            <tr class="font-label-sm uppercase tracking-wide text-outline" id="catalog-table-head"></tr>
          </thead>
          <tbody class="font-body-md text-on-surface" id="catalog-table-body"></tbody>
        </table>
      </div>
    </section>
  </section>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/catalogs.js') }}" defer></script>
@endpush
