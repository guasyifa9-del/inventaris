<?php
$empty_title = $empty_title ?? 'Tidak Ada Data';
$empty_message = $empty_message ?? 'Belum ada data untuk ditampilkan.';
$empty_icon = $empty_icon ?? 'fa-inbox';
$empty_action = $empty_action ?? null;
?>

<!-- Empty State -->
<div class="text-center py-5">
    <i class="fas <?= $empty_icon ?> text-muted" style="font-size: 64px; opacity: 0.3;"></i>
    <h4 class="mt-4 text-muted"><?= htmlspecialchars($empty_title) ?></h4>
    <p class="text-muted"><?= htmlspecialchars($empty_message) ?></p>
    <?php if ($empty_action): ?>
        <a href="<?= htmlspecialchars($empty_action['url']) ?>" class="btn btn-primary mt-3">
            <i class="fas <?= $empty_action['icon'] ?? 'fa-plus' ?> me-2"></i>
            <?= htmlspecialchars($empty_action['text']) ?>
        </a>
    <?php endif; ?>
</div>
