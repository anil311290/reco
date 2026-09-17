@php
    $sortAlign = $align ?? 'start';
    $sortWidth = $width ?? null;
    $sortExtraClass = $class ?? '';
    $sortPageParam = $pageParam ?? 'page';
    $currentSort = request('sort');
    $currentDir = request('dir') === 'desc' ? 'desc' : 'asc';
    $isActiveSort = $currentSort === $field;
    $nextDir = $isActiveSort && $currentDir === 'asc' ? 'desc' : 'asc';
    $sortHref = request()->fullUrlWithQuery(['sort' => $field, 'dir' => $nextDir, $sortPageParam => null]);
    $sortIcon = $isActiveSort
        ? ($currentDir === 'asc' ? 'bi-sort-up' : 'bi-sort-down')
        : 'bi-arrow-down-up';
@endphp
<th class="{{ $sortAlign === 'end' ? 'text-end' : '' }} report-sortable-th {{ $sortExtraClass }} {{ $isActiveSort ? 'is-active' : '' }}" @if($sortWidth) style="width:{{ $sortWidth }}" @endif>
    <a href="{{ $sortHref }}" class="report-sort-link">
        <span>{{ $label }}</span>
        <i class="bi {{ $sortIcon }}"></i>
    </a>
</th>
