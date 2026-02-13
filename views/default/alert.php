<?php
$flash = $flash ?? getFlashMessage();
?>

<!-- Flash Alert Messages -->
<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show shadow-sm" role="alert">
    <?php
    $icon = [
        'success' => 'fa-check-circle',
        'danger' => 'fa-exclamation-circle',
        'warning' => 'fa-exclamation-triangle',
        'info' => 'fa-info-circle',
        'primary' => 'fa-bell'
    ];
    $alert_icon = $icon[$flash['type']] ?? 'fa-info-circle';
    ?>
    <i class="fas <?= $alert_icon ?> me-2"></i>
    <?= htmlspecialchars($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>
