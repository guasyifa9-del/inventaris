<?php
$breadcrumbs = $breadcrumbs ?? [];
?>

<!-- Breadcrumb Navigation -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb bg-light p-3 rounded shadow-sm">
        <li class="breadcrumb-item">
            <a href="<?= isset($_SESSION['role']) && $_SESSION['role'] == 'admin' ? '/inventaris/admin/dashboard.php' : '/inventaris/user/dashboard.php' ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
        </li>
        <?php foreach ($breadcrumbs as $index => $crumb): ?>
            <?php if ($index == count($breadcrumbs) - 1): ?>
                <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($crumb['title']) ?></li>
            <?php else: ?>
                <li class="breadcrumb-item">
                    <a href="<?= htmlspecialchars($crumb['url']) ?>"><?= htmlspecialchars($crumb['title']) ?></a>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ol>
</nav>
