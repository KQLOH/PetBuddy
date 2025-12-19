<?php
// admin/members_list.php
session_start();

// Ensure the path to db.php is correct
require __DIR__ . '/../include/db.php'; 

// --- SECURITY CHECK: Only allow admin access ---
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../admin.php');
    exit;
}
$adminName = $_SESSION['full_name'] ?? 'Admin';
$adminId = $_SESSION['member_id'] ?? 0;

$adminImage = null; 

// Fetch the current admin's profile picture path
try {
    if ($adminId > 0) {
        $stmt = $pdo->prepare("SELECT image FROM members WHERE member_id = ?");
        $stmt->execute([$adminId]);
        $result = $stmt->fetch();
        if ($result) {
            $adminImage = $result['image'];
        }
    }
} catch (PDOException $e) {
    // Fail silently on database error and use default image/text
}

// --- FILTERS, SORTING, AND PAGINATION SETUP ---
$search     = trim($_GET['search'] ?? '');
$roleFilter = $_GET['role'] ?? 'all';

// Pagination setup
$limit = 15; // Items per page
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * $limit;

// Allowed sortable fields
$allowedSort = [
    'id'     => 'member_id',
    'name'   => 'full_name',
    'email'  => 'email',
    'phone'  => 'phone',
    'gender' => 'gender',
    'dob'    => 'dob',
    'role'   => 'role',
];

$sort = $_GET['sort'] ?? 'id';
$dir  = $_GET['dir'] ?? 'asc';

if (!isset($allowedSort[$sort])) {
    $sort = 'id';
}
$dir = (strtolower($dir) === 'desc') ? 'desc' : 'asc';
$sortColumn = $allowedSort[$sort];


// --- UTILITY FUNCTIONS ---

function buildSortUrl(string $field, string $currentSort, string $currentDir, string $roleFilter, string $search, int $currentPage): string
{
    $script = basename($_SERVER['PHP_SELF']);
    $dir = 'asc';
    if ($currentSort === $field && $currentDir === 'asc') {
        $dir = 'desc';
    }

    return $script . '?' . http_build_query([
        'role'   => $roleFilter,
        'search' => $search,
        'sort'   => $field,
        'dir'    => $dir,
        'p'      => $currentPage 
    ]);
}

function sortArrow(string $field, string $currentSort, string $currentDir): string
{
    if ($currentSort !== $field) {
        return '<span class="sort-arrow sort-none">↕</span>';
    }
    if ($currentDir === 'asc') {
        return '<span class="sort-arrow sort-active">▲</span>';
    }
    return '<span class="sort-arrow sort-active">▼</span>';
}


// --- DATABASE QUERY LOGIC ---
$members = [];
$totalMembers = 0; 

try {
    $conditions = [];
    $params     = [];
    
    if ($roleFilter !== 'all' && in_array($roleFilter, ['admin', 'member'], true)) {
        $conditions[] = "role = ?";
        $params[]     = $roleFilter;
    }

    if ($search !== '') {
        $conditions[] = "(full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
        $like = '%' . $search . '%';
        $params[] = $like; 
        $params[] = $like; 
        $params[] = $like; 
    }

    $whereClause = $conditions ? " WHERE " . implode(" AND ", $conditions) : "";

    $countParams = $params;
    $countSql = "SELECT COUNT(member_id) FROM members" . $whereClause;
    
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($countParams); 
    $totalMembers = $countStmt->fetchColumn();
    $totalPages = ceil($totalMembers / $limit);
    
    if ($page > $totalPages && $totalPages > 0) {
        $page = $totalPages;
        $offset = ($page - 1) * $limit;
    }

    $sql = "SELECT member_id, full_name, email, phone, gender, dob, role, image
            FROM members" . $whereClause;

    $sql .= " ORDER BY {$sortColumn} {$dir}";

    $sql .= " LIMIT ?, ?";
    
    $params[] = $offset;
    $params[] = $limit; 

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $members = $stmt->fetchAll();

} catch (PDOException $e) {
    $errorMessage = "An error occurred while fetching the member list.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PetBuddy Admin - Members</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> 

    <style>
        /* --- Base Layout Styles (Unchanged) --- */
        :root {
            --primary-color: #F4A261;
            --primary-dark: #E68E3F;
            --bg-light: #f5f5f7;
            --bg-sidebar: #fff7ec;
            --text-dark: #333333;
            --border-color: #e0e0e0;
            --danger-color: #d32f2f;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        body { min-height: 100vh; background-color: #ffffff; color: var(--text-dark); transition: padding-left 0.25s ease; padding-left: 220px; }
        .main { min-height: 100vh; display: flex; flex-direction: column; }
        .topbar { height: 56px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; padding: 0 18px; background-color: #ffffff; position: sticky; top: 0; z-index: 10; }
        .topbar-left { display: flex; align-items: center; gap: 10px; }
        .topbar-title { font-size: 18px; font-weight: 600; }
        .sidebar-toggle { width: 32px; height: 32px; border-radius: 50%; border: 1px solid var(--border-color); background-color: #ffffff; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 18px; }
        .sidebar-toggle:hover { background: rgba(244,162,97,0.12); border-color: var(--primary-color); }
        .topbar-right { display: flex; align-items: center; gap: 10px; font-size: 13px; }
        .tag-pill { font-size: 11px; padding: 3px 8px; border-radius: 999px; background-color: rgba(244, 162, 97, 0.12); color: var(--primary-dark); }
        .admin-avatar { width: 24px; height: 24px; border-radius: 50%; object-fit: cover; vertical-align: middle; margin-right: 5px; border: 1px solid var(--primary-color); }
        .content { padding: 18px; background-color: var(--bg-light); min-height: calc(100vh - 56px); }
        .page-header { margin-bottom: 18px; }
        .page-title { font-size: 20px; font-weight: 600; margin-bottom: 4px; }
        .page-subtitle { font-size: 12px; color: #777; }
        .filter-bar { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-bottom: 14px; }
        .filter-bar label { font-size: 12px; color: #555; }
        .filter-bar select, .filter-bar input[type="text"] { padding: 6px 10px; font-size: 13px; border-radius: 999px; border: 1px solid var(--border-color); background: #fff; }
        .filter-bar input[type="text"] { min-width: 220px; }
        .btn-secondary { border-radius: 999px; padding: 6px 14px; font-size: 12px; cursor: pointer; border: 1px solid var(--border-color); background-color: #fff; color: #555; }
        .btn-secondary:hover { border-color: var(--primary-color); color: var(--primary-dark); background-color: #fffaf4; }
        .panel { background-color: #ffffff; border-radius: 14px; border: 1px solid #e5e5e5; padding: 12px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        thead tr { background: #fafafa; }
        th, td { padding: 10px 8px; border-bottom: 1px solid #f0f0f0; text-align: left; white-space: nowrap; }
        th { font-weight: 600; color: #555; }
        tbody tr:hover { background-color: #fffaf4; }
        th a { color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .sort-arrow { font-size: 10px; opacity: 0.4; }
        .sort-active { opacity: 1; color: var(--primary-dark); }
        .role-admin { background: #ffe0b2; color: #e65100; padding: 3px 8px; border-radius: 999px; font-size: 11px; }
        .role-member { background: #e3f2fd; color: #1565c0; padding: 3px 8px; border-radius: 999px; font-size: 11px; }
        .gender-pill { padding: 2px 8px; border-radius: 999px; font-size: 11px; background: #f3e5f5; color: #6a1b9a; }
        .actions a { font-size: 12px; margin-right: 6px; text-decoration: none; }
        .actions a.link-edit { color: #1976d2; }
        .actions a.link-delete { color: var(--danger-color); cursor: pointer; } /* Added cursor: pointer */
        .no-data { padding: 10px; font-size: 13px; color: #777; text-align: center; }
        .member-avatar { width: 30px; height: 30px; border-radius: 50%; object-fit: cover; border: 1px solid var(--border-color); }
        .pagination a, .pagination span { padding: 6px 10px; text-decoration: none; border: 1px solid var(--border-color); border-radius: 6px; transition: background-color 0.2s; color: #555; }
        .pagination a:hover { background-color: rgba(244, 162, 97, 0.1); border-color: var(--primary-color); }
        .pagination .current-page { background-color: var(--primary-color); color: white; border-color: var(--primary-color); font-weight: 600; }
        .pagination span.disabled { opacity: 0.5; cursor: default; }
        @media (max-width: 900px) { body { padding-left: 0; } }

        /* --- NEW: Modal Styles --- */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000; /* Ensure it's above everything */
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .delete-modal {
            background: #ffffff;
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            transform: scale(0.95);
            transition: transform 0.3s ease;
        }

        .modal-overlay.active .delete-modal {
            transform: scale(1);
        }

        .modal-header {
            font-size: 18px;
            font-weight: 700;
            color: var(--danger-color);
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .modal-body p {
            font-size: 14px;
            line-height: 1.5;
            color: #555;
            margin-bottom: 20px;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
            padding: 8px 15px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .btn-danger:hover {
            background-color: #b02727;
        }
        
        .btn-cancel-modal {
            background: #f0f0f0;
            color: #555;
            padding: 8px 15px;
            border-radius: 8px;
            border: 1px solid #ccc;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .btn-cancel-modal:hover {
            background-color: #e0e0e0;
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main">
    <header class="topbar">
        <div class="topbar-left">
            <button id="sidebarToggle" class="sidebar-toggle">☰</button>
            <div class="topbar-title">Members</div>
        </div>
        <div class="topbar-right">
            <?php if (!empty($adminImage)): ?>
                <img src="../<?php echo htmlspecialchars($adminImage); ?>" alt="Admin Profile" class="admin-avatar">
            <?php else: ?>
                <img src="https://via.placeholder.com/24/cccccc/ffffff?text=A" alt="Admin Default" class="admin-avatar">
            <?php endif; ?>
            <span class="tag-pill">Admin: <?php echo htmlspecialchars($adminName); ?></span>
        </div>
    </header>

    <main class="content">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div style="padding: 12px; background: #e8f5e9; border: 1px solid #4caf50; border-radius: 8px; margin-bottom: 15px; color: #1b5e20;">
                ✅ <strong>Success:</strong> <?php echo htmlspecialchars($_SESSION['success_message']); ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div style="padding: 12px; background: #fef2f2; border: 1px solid #fca5a5; border-radius: 8px; margin-bottom: 15px; color: #b91c1c;">
                ❌ <strong>Failed:</strong> <?php echo htmlspecialchars($_SESSION['error_message']); ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <div class="page-header">
            <div class="page-title">Member List (<?php echo $totalMembers; ?> Total)</div>
            <div class="page-subtitle">View and manage registered PetBuddy members.</div>
        </div>

        <form method="get" class="filter-bar">
            <label>Role:</label>
            <select name="role">
                <option value="all"   <?php if ($roleFilter === 'all')   echo 'selected'; ?>>All</option>
                <option value="admin" <?php if ($roleFilter === 'admin') echo 'selected'; ?>>Admin</option>
                <option value="member"<?php if ($roleFilter === 'member')echo 'selected'; ?>>Member</option>
            </select>

            <label>Search:</label>
            <input type="text" name="search"
                   placeholder="Name / Email / Phone"
                   value="<?php echo htmlspecialchars($search); ?>">

            <button type="submit" class="btn-secondary">Filter</button>
            <a href="members_list.php" class="btn-secondary">Reset</a>
        </form>

        <div class="panel">
            <?php if (empty($members)): ?>
                <div class="no-data">No members found.</div>
            <?php else: ?>
                <table>
                    <thead>
                    <tr>
                        <th>
                            <a href="<?php echo buildSortUrl('id', $sort, $dir, $roleFilter, $search, $page); ?>">
                                <span>ID</span>
                                <?php echo sortArrow('id', $sort, $dir); ?>
                            </a>
                        </th>
                        <th>Photo</th> 
                        <th>
                            <a href="<?php echo buildSortUrl('name', $sort, $dir, $roleFilter, $search, $page); ?>">
                                <span>Full Name</span>
                                <?php echo sortArrow('name', $sort, $dir); ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?php echo buildSortUrl('email', $sort, $dir, $roleFilter, $search, $page); ?>">
                                <span>Email</span>
                                <?php echo sortArrow('email', $sort, $dir); ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?php echo buildSortUrl('phone', $sort, $dir, $roleFilter, $search, $page); ?>">
                                <span>Phone</span>
                                <?php echo sortArrow('phone', $sort, $dir); ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?php echo buildSortUrl('gender', $sort, $dir, $roleFilter, $search, $page); ?>">
                                <span>Gender</span>
                                <?php echo sortArrow('gender', $sort, $dir); ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?php echo buildSortUrl('dob', $sort, $dir, $roleFilter, $search, $page); ?>">
                                <span>Date of Birth</span>
                                <?php echo sortArrow('dob', $sort, $dir); ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?php echo buildSortUrl('role', $sort, $dir, $roleFilter, $search, $page); ?>">
                                <span>Role</span>
                                <?php echo sortArrow('role', $sort, $dir); ?>
                            </a>
                        </th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($members as $m): ?>
                        <tr>
                            <td>
                                <?php echo sprintf("M%04d", (int)$m['member_id']); ?>
                            </td>
                            
                            <td>
                                <?php if (!empty($m['image'])): ?>
                                    <img src="../<?php echo htmlspecialchars($m['image']); ?>" alt="Profile" class="member-avatar">
                                <?php else: ?>
                                    <img src="https://via.placeholder.com/30/cccccc/ffffff?text=U" alt="No Image" class="member-avatar">
                                <?php endif; ?>
                            </td>
                            
                            <td><?php echo htmlspecialchars($m['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($m['email']); ?></td>
                            <td><?php echo htmlspecialchars($m['phone'] ?? '-'); ?></td>
                            <td>
                                <?php if ($m['gender']): ?>
                                    <span class="gender-pill"><?php echo htmlspecialchars(ucfirst($m['gender'])); ?></span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?php echo $m['dob'] ? htmlspecialchars($m['dob']) : '-'; ?></td>
                            <td>
                                <?php if ($m['role'] === 'admin'): ?>
                                    <span class="role-admin">Admin</span>
                                <?php else: ?>
                                    <span class="role-member">Member</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <a class="link-edit"
                                   href="member_edit.php?id=<?php echo (int)$m['member_id']; ?>">Edit</a>
                                <a class="link-delete delete-trigger"
                                   href="#" 
                                   data-id="<?php echo (int)$m['member_id']; ?>"
                                   data-name="<?php echo htmlspecialchars($m['full_name']); ?>"
                                   data-formatted-id="<?php echo sprintf("M%04d", (int)$m['member_id']); ?>">
                                    Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <?php // ... (Pagination code omitted for brevity but should remain) ... ?>
                
            <?php endif; ?>
        </div>
    </main>
</div>


<div id="deleteModal" class="modal-overlay">
    <div class="delete-modal">
        <div class="modal-header">
            Confirm Deletion
        </div>
        <div class="modal-body">
            <p>Are you sure you want to permanently delete member <strong id="modalMemberFormattedId"></strong> (<strong id="modalMemberName"></strong>)?</p>
            <p style="font-weight: 600; color: var(--danger-color);">This action cannot be undone and will delete all associated data (orders, reviews, cart items).</p>
        </div>
        <div class="modal-actions">
            <button class="btn-cancel-modal" id="cancelDeleteBtn">Cancel</button>
            <button class="btn-danger" id="confirmDeleteBtn" data-delete-id="">Confirm Delete</button>
        </div>
    </div>
</div>
<script>
    // Sidebar Toggle
    document.getElementById('sidebarToggle').addEventListener('click', function () {
        document.body.classList.toggle('sidebar-collapsed');
    });

    // --- jQuery for Custom Delete Modal ---
    $(document).ready(function() {
        const $modal = $('#deleteModal');
        const $confirmBtn = $('#confirmDeleteBtn');
        const $cancelBtn = $('#cancelDeleteBtn');
        
        // 1. Show the Modal on 'Delete' link click
        $('.delete-trigger').on('click', function(e) {
            e.preventDefault(); // Stop the link from navigating

            const memberId = $(this).data('id');
            const memberName = $(this).data('name');
            const formattedId = $(this).data('formatted-id');
            
            // Populate modal content
            $('#modalMemberFormattedId').text(formattedId);
            $('#modalMemberName').text(memberName);
            
            // Store the ID on the confirm button
            $confirmBtn.data('delete-id', memberId);

            // Show modal with animation classes
            $modal.addClass('active');
        });

        // 2. Hide the Modal on Cancel
        $cancelBtn.on('click', function() {
            $modal.removeClass('active');
        });

        // Hide modal when clicking overlay (optional but good UX)
        $modal.on('click', function(e) {
            if (e.target.id === 'deleteModal') {
                $modal.removeClass('active');
            }
        });

        // 3. Handle Confirmation
        $confirmBtn.on('click', function() {
            const idToDelete = $confirmBtn.data('delete-id');
            
            if (idToDelete) {
                 // Redirect to the backend deletion script using the stored ID
                window.location.href = 'member_delete.php?id=' + idToDelete;
            }
        });
    });
</script>

</body>
</html>