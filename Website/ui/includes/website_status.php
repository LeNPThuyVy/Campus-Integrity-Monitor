<?php
declare(strict_types=1);

function website_render_unavailable(string $title, string $message): void
{
    echo '<section class="my-space-lg max-w-4xl rounded-xl border border-error/20 bg-surface-container-lowest p-space-lg shadow-sm" role="alert">';
    echo '<div class="flex items-start gap-space-md">';
    echo '<span class="material-symbols-outlined mt-0.5 text-error" aria-hidden="true">cloud_off</span>';
    echo '<div class="min-w-0">';
    echo '<h1 class="font-headline-lg text-headline-lg font-bold text-on-surface">'
        . htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</h1>';
    echo '<p class="mt-2 font-body-md text-body-md text-on-surface-variant">'
        . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>';
    echo '<p class="mt-3 font-label-sm text-label-sm text-error">Trạng thái: Chưa kết nối hoặc chưa được cấu hình; không có dữ liệu thật để hiển thị.</p>';
    echo '</div></div></section>';
}
