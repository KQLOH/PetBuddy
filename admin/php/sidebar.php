<?php
$currentPage = basename($_SERVER['PHP_SELF']);

function isActive(string $file, string $currentPage): string
{
    return $file === $currentPage ? 'active' : '';
}
?>

<link rel="stylesheet" href="../css/admin_sidebar.css">
<link rel="stylesheet" href="../css/admin_btn.css">

<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-circle">🐾</div>
        <div>
            <div class="sidebar-title">PetBuddy</div>
            <div class="sidebar-subtitle">Admin Panel</div>
        </div>
    </div>

    <div class="menu-group-title">Overview</div>
    <ul class="menu">
        <li>
            <a href="dashboard.php" class="<?= isActive('dashboard.php', $currentPage); ?>">Dashboard</a>
        </li>
    </ul>

    <div class="menu-group-title">Management</div>
    <ul class="menu">
        <li><a href="member_list.php" class="<?= isActive('member_list.php', $currentPage); ?>">Members</a></li>
        <li><a href="orders_list.php" class="<?= isActive('orders_list.php', $currentPage); ?>">Orders</a></li>
        <li><a href="category_list.php" class="<?= isActive('category_list.php', $currentPage); ?>">Categories</a></li>
        <li><a href="product_list.php" class="<?= isActive('product_list.php', $currentPage); ?>">Products</a></li>
        <li><a href="chat.php" class="<?= isActive('chat.php', $currentPage); ?>">Chat</a></li>
    </ul>

    <div class="menu-group-title">Other</div>
    <ul class="menu">
        <li><a href="javascript:void(0)" onclick="handleBackToShop()">Back to Shop</a></li>
        <li><a href="javascript:void(0)" onclick="handleLogout()">Logout</a></li>
    </ul>
</aside>

<div id="sidebarAlert" class="custom-alert-overlay" style="display:none;">
    <div class="custom-alert-box">
        <div id="sidebarAlertIcon" class="custom-alert-icon"></div>
        <h3 id="sidebarAlertTitle" class="custom-alert-title"></h3>
        <p id="sidebarAlertText" class="custom-alert-text"></p>
        <div id="sidebarAlertButtons" class="custom-alert-buttons">
            <button id="sidebarAlertCancel" class="btn-alert btn-alert-cancel">Cancel</button>
            <button id="sidebarAlertConfirm" class="btn-alert btn-alert-confirm">Confirm</button>
        </div>
    </div>
</div>

<script>
    function showSidebarConfirm(type, title, text, callback = null) {
        const overlay = document.getElementById('sidebarAlert');
        const iconContainer = document.getElementById('sidebarAlertIcon');
        const btnCancel = document.getElementById('sidebarAlertCancel');
        const btnConfirm = document.getElementById('sidebarAlertConfirm');

        document.getElementById('sidebarAlertTitle').innerText = title;
        document.getElementById('sidebarAlertText').innerText = text;

        iconContainer.innerHTML = '';
        const img = document.createElement('img');
        img.src = `../images/${type === 'confirm' ? 'warning' : type}.png`;
        img.style.width = '40px';
        iconContainer.appendChild(img);

        btnConfirm.style.backgroundColor = '#F4A261';
        btnConfirm.innerText = 'Confirm';

        btnConfirm.onclick = function() {
            overlay.classList.remove('show');
            setTimeout(() => {
                overlay.style.display = 'none';
            }, 300);
            if (callback) callback();
        };

        btnCancel.onclick = function() {
            overlay.classList.remove('show');
            setTimeout(() => {
                overlay.style.display = 'none';
            }, 300);
        };

        overlay.style.display = 'flex';
        setTimeout(() => overlay.classList.add('show'), 10);
    }

    function handleBackToShop() {
        showSidebarConfirm('confirm', 'Return to Shop?', 'Are you sure you want to log out from Admin and return to the main shop home page?', function() {
            window.location.href = 'back_to_home.php';
        });
    }

    function handleLogout() {
        showSidebarConfirm('confirm', 'Confirm Logout?', 'Are you sure you want to sign out of the Admin Panel?', function() {
            window.location.href = 'admin_logout.php';
        });
    }
</script>