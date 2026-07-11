@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="container-fluid px-0">
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h4 class="mb-0">Notifications</h4>
            <p class="text-muted small mb-0">In-app alerts for sync, support tickets, and account activity.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <button type="button" class="btn btn-outline-primary btn-sm {{ $unreadCount > 0 ? '' : 'd-none' }}" id="markAllReadBtn">
                <i class="bi bi-check2-all"></i> Mark all as read
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0" id="notificationsList">
            @forelse($notifications as $notification)
            @php
                $url = app(\App\Services\NotificationService::class)->resolveUrl($notification);
            @endphp
            <div class="notification-row d-flex align-items-start gap-3 p-3 border-bottom {{ $notification->is_read ? '' : 'bg-light' }}"
                 data-id="{{ $notification->id }}"
                 data-read="{{ $notification->is_read ? '1' : '0' }}">
                <div class="flex-shrink-0">
                    <i class="bi {{ $notification->icon ?? 'bi-bell' }} {{ $notification->color ?? 'text-primary' }} fs-5"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <div class="fw-semibold notification-title">{{ $notification->title }}</div>
                            <div class="text-muted small">{{ $notification->message }}</div>
                            <div class="text-muted small mt-1">@istDateTime($notification->created_at)</div>
                        </div>
                        @if(!$notification->is_read)
                        <span class="badge bg-danger notification-new-badge">New</span>
                        @endif
                    </div>
                    <div class="d-flex gap-2 mt-2">
                        @if($url)
                        <a href="{{ $url }}" class="btn btn-link btn-sm px-0 notification-open-link">View details</a>
                        @endif
                        @if(!$notification->is_read)
                        <button type="button" class="btn btn-link btn-sm px-0 notification-mark-read">Mark as read</button>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center text-muted py-5" id="notificationsEmptyState">
                <i class="bi bi-bell-slash fs-1 d-block mb-2"></i>
                No notifications yet.
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    const readBaseUrl = '{{ url('admin/notifications') }}';

    function syncHeaderBadge(count) {
        if (typeof window.recoUpdateNotificationBadge === 'function') {
            window.recoUpdateNotificationBadge(count);
        }
    }

    function markRowAsRead($row) {
        $row.removeClass('bg-light').attr('data-read', '1');
        $row.find('.notification-new-badge').remove();
        $row.find('.notification-mark-read').remove();
        $row.find('.notification-title').removeClass('fw-semibold');
    }

    function markOne(id, $row) {
        return $.ajax({
            url: readBaseUrl + '/' + id + '/read',
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrfToken },
        }).done(function (data) {
            if ($row) {
                markRowAsRead($row);
            }
            syncHeaderBadge(data.unread_count);
            if ((data.unread_count || 0) === 0) {
                $('#markAllReadBtn').addClass('d-none');
            }
        });
    }

    $('#markAllReadBtn').on('click', function () {
        $.post('{{ route('admin.notifications.mark-all-read') }}', {
            _token: csrfToken
        }).done(function (data) {
            $('#notificationsList .notification-row').each(function () {
                markRowAsRead($(this));
            });
            $('#markAllReadBtn').addClass('d-none');
            syncHeaderBadge(data.unread_count);
        });
    });

    $(document).on('click', '.notification-mark-read', function () {
        const $row = $(this).closest('.notification-row');
        markOne($row.data('id'), $row);
    });

    $(document).on('click', '.notification-open-link', function (e) {
        const $row = $(this).closest('.notification-row');
        if (String($row.data('read')) === '1') {
            return;
        }
        e.preventDefault();
        const targetUrl = $(this).attr('href');
        markOne($row.data('id'), $row).always(function () {
            window.location.href = targetUrl;
        });
    });
});
</script>
@endpush
