<?php

/**
 * PetBuddy Home Page - Premium Design
 */

// Start session and connect to database
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../include/db.php';
// 引入这个是为了使用 productImageUrl 函数
require_once '../include/product_utils.php';

// --- 1. 获取当前用户已收藏的商品ID (用于判断爱心状态) ---
$user_wishlist_ids = [];
if (isset($_SESSION['member_id'])) {
    try {
        $w_stmt = $pdo->prepare("SELECT product_id FROM wishlist WHERE member_id = ?");
        $w_stmt->execute([$_SESSION['member_id']]);
        $user_wishlist_ids = $w_stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        // 忽略错误
    }
}

// --- 2. 获取 Top 5 畅销商品 ---
$featured_products = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            pc.name as category_name,
            COALESCE(SUM(oi.quantity), 0) as total_sold,
            COUNT(DISTINCT oi.order_id) as order_count
        FROM products p 
        LEFT JOIN product_categories pc ON p.category_id = pc.category_id 
        LEFT JOIN order_items oi ON p.product_id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.order_id
        WHERE p.stock_qty > 0 
        GROUP BY p.product_id
        ORDER BY total_sold DESC, order_count DESC
        LIMIT 5
    ");
    $stmt->execute();
    $featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 如果不足5个，用最新商品补齐
    if (count($featured_products) < 5) {
        $stmt_backup = $pdo->prepare(
            "
            SELECT p.*, pc.name as category_name, 0 as total_sold, 0 as order_count
            FROM products p 
            LEFT JOIN product_categories pc ON p.category_id = pc.category_id 
            WHERE p.stock_qty > 0 
            AND p.product_id NOT IN (
                SELECT product_id FROM (" .
                ($featured_products ?
                    "SELECT " . implode(',', array_column($featured_products, 'product_id')) . " as product_id"
                    : "SELECT 0 as product_id WHERE 1=0") .
                ") as existing
            )
            ORDER BY p.product_id DESC 
            LIMIT " . (5 - count($featured_products))
        );
        $stmt_backup->execute();
        $backup_products = $stmt_backup->fetchAll(PDO::FETCH_ASSOC);
        $featured_products = array_merge($featured_products, $backup_products);
    }
} catch (PDOException $e) {
    // 错误处理...
}

// 获取分类 (用于分类展示区)
$categories = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM product_categories LIMIT 6");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching categories: " . $e->getMessage());
}

include '../include/header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetBuddy - Premium Pet Supplies</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        :root {
            --primary-color: #FFB774;
            --primary-dark: #E89C55;
            --text-dark: #2F2F2F;
            --text-light: #666;
            --border-color: #e8e8e8;
            --bg-light: #FFF9F4;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Inter", system-ui, sans-serif;
            color: var(--text-dark);
            background: #fff;
            overflow-x: hidden;
        }

        /* ================= Hero Section (保持原样) ================= */
        .hero-section {
            position: relative;
            height: 85vh;
            min-height: 600px;
            background: linear-gradient(135deg, #FFE8D1 0%, #FFF5EC 100%);
            overflow: hidden;
            display: flex;
            align-items: center;
        }

        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 40px;
            position: relative;
            z-index: 2;
        }

        .hero-slide-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
            opacity: 0;
            visibility: hidden;
            position: absolute;
            top: 0;
            left: 40px;
            right: 40px;
            transition: opacity 0.8s ease, visibility 0.8s ease;
        }

        .hero-slide-content.active {
            opacity: 1;
            visibility: visible;
            position: relative;
            animation: slideInContent 0.8s ease;
        }

        .hero-text {
            animation: fadeInUp 0.8s ease;
        }

        @keyframes slideInContent {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .hero-badge {
            display: inline-block;
            background: white;
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .hero-title {
            font-size: 64px;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 25px;
            color: var(--text-dark);
        }

        .hero-title span {
            color: var(--primary-dark);
            position: relative;
            display: inline-block;
        }

        .hero-description {
            font-size: 18px;
            line-height: 1.7;
            color: var(--text-light);
            margin-bottom: 35px;
            max-width: 500px;
        }

        .hero-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn-primary,
        .btn-secondary {
            padding: 16px 35px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 8px 25px rgba(255, 183, 116, 0.4);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(255, 183, 116, 0.5);
        }

        .btn-secondary {
            background: white;
            color: var(--text-dark);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .btn-secondary:hover {
            background: var(--text-dark);
            color: white;
            transform: translateY(-3px);
        }

        .hero-image {
            position: relative;
        }

        .hero-image-slider {
            position: relative;
            width: 100%;
            height: 500px;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .hero-slide {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 0.8s ease-in-out;
        }

        .hero-slide.active {
            opacity: 1;
        }

        .hero-slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .main-slider-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: white;
            border: none;
            width: 55px;
            height: 55px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--text-dark);
            transition: all 0.3s ease;
            z-index: 100;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .main-slider-nav:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-50%) scale(1.1);
            box-shadow: 0 6px 30px rgba(255, 183, 116, 0.4);
        }

        .main-slider-prev {
            left: 20px;
        }

        .main-slider-next {
            right: 20px;
        }

        .main-slider-dots {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 12px;
            z-index: 100;
        }

        .main-slider-dot {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.6);
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid white;
        }

        .main-slider-dot.active {
            background: var(--primary-color);
            transform: scale(1.3);
            box-shadow: 0 0 10px rgba(255, 183, 116, 0.6);
        }

        .float-element {
            position: absolute;
            animation: float 6s ease-in-out infinite;
        }

        /* 新增：确保浮动元素里的图片自适应并去除底部空隙 */
        .float-element img {
            width: 100%;
            height: auto;
            display: block;
        }

        /* 将 font-size 改为 width 来控制图片容器的大小 */
        .float-1 {
            top: 10%;
            left: 5%;
            width: 60px;
            /* 原本是 font-size: 60px */
            animation-delay: 0s;
        }

        .float-2 {
            top: 70%;
            left: 10%;
            width: 60px;
            /* 原本是 font-size: 40px */
            animation-delay: 1s;
        }

        .float-3 {
            top: 20%;
            right: 8%;
            width: 50px;
            /* 原本是 font-size: 50px */
            animation-delay: 2s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-20px);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ================= Features & Categories (保持原样) ================= */
        .features-section {
            padding: 80px 40px;
            background: white;
        }

        .features-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
        }

        .feature-card {
            text-align: center;
            padding: 40px 30px;
            border-radius: 20px;
            transition: all 0.3s ease;
            background: white;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
        }

        /* 确保图片在圆圈里居中且大小合适 */
        .feature-icon img {
            width: 40px;
            /* 👇 控制图片大小，你可以改成 35px 或 50px */
            height: 40px;
            object-fit: contain;
            /* 保持图片比例，不会被拉扁 */
            display: block;
        }

        /* 稍微调整一下父容器，确保 flex 居中对齐 */
        .feature-icon {
            /* 保持你原本的圆圈样式 */
            width: 80px;
            height: 80px;
            margin: 0 auto 25px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border-radius: 50%;

            /* 确保图片水平垂直居中 */
            display: flex;
            align-items: center;
            justify-content: center;

            box-shadow: 0 8px 20px rgba(255, 183, 116, 0.3);
        }

        .feature-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 12px;
            color: var(--text-dark);
        }

        .feature-desc {
            font-size: 15px;
            color: var(--text-light);
            line-height: 1.6;
        }

        .categories-section {
            padding: 100px 40px;
            background: white;
            position: relative;
            overflow: hidden;
        }

        .categories-section::before {
            content: '🐾';
            position: absolute;
            top: 50px;
            right: 50px;
            font-size: 200px;
            opacity: 0.03;
            z-index: 0;
        }

        .section-header {
            text-align: center;
            margin-bottom: 60px;
            position: relative;
            z-index: 1;
        }

        .section-subtitle {
            font-size: 14px;
            font-weight: 600;
            color: var(--primary-dark);
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .section-title {
            font-size: 42px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 15px;
        }

        .section-description {
            font-size: 17px;
            color: var(--text-light);
            max-width: 600px;
            margin: 0 auto;
        }

        .categories-wrapper {
            max-width: 1300px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
        }

        .category-card-new {
            position: relative;
            height: 260px;
            border-radius: 20px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
            border: 2px solid var(--border-color);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 30px;
        }

        .category-card-new:hover {
            transform: translateY(-12px);
            border-color: var(--primary-color);
            box-shadow: 0 20px 50px rgba(255, 183, 116, 0.25);
        }

        .category-icon-wrapper {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--bg-light) 0%, #FFF5EC 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            transition: all 0.4s ease;
            position: relative;
        }

        .category-card-new:hover .category-icon-wrapper {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            transform: scale(1.1) rotate(10deg);
            box-shadow: 0 15px 40px rgba(255, 183, 116, 0.4);
        }

        /* 2. 重置原本控制 Emoji 的样式 */
        .category-icon-new {
            /* 删除 font-size，或者保留也没关系，因为对 img 没影响 */
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .category-icon-new img {
            width: 50px;
            /* 普通卡片的图片大小 */
            height: 50px;
            object-fit: contain;
            /* 保持图片比例 */
            display: block;
        }


        /* 1. 让图片在容器里适应大小 */
        .category-icon-new img {
            width: 50px;
            /* 普通卡片的图片大小 */
            height: 50px;
            object-fit: contain;
            /* 保持图片比例 */
            display: block;
        }

        /* 2. 重置原本控制 Emoji 的样式 */
        .category-icon-new {
            /* 删除 font-size，或者保留也没关系，因为对 img 没影响 */
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* 3. 特殊处理：第一个大卡片的图片要大一点 */
        .category-card-new:first-child .category-icon-new img {
            width: 80px;
            /*大卡片的图片大小 */
            height: 80px;
        }

        .category-card-new:hover .category-icon-new {
            transform: scale(1.1);
            filter: grayscale(0);
        }

        .category-name-new {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 8px;
            transition: color 0.3s ease;
        }

        .category-card-new:hover .category-name-new {
            color: var(--primary-dark);
        }

        .category-desc-new {
            font-size: 14px;
            color: var(--text-light);
            line-height: 1.5;
            transition: color 0.3s ease;
        }

        .category-card-new:first-child {
            grid-column: span 2;
            grid-row: span 2;
            height: auto;
            background: linear-gradient(135deg, #FFE8D1 0%, #FFF5EC 100%);
            border: none;
        }

        .category-card-new:first-child .category-icon-wrapper {
            width: 140px;
            height: 140px;
        }

        .category-card-new:first-child .category-icon-new {
            font-size: 80px;
        }

        .category-card-new:first-child .category-name-new {
            font-size: 28px;
            margin-bottom: 15px;
        }

        .category-arrow {
            position: absolute;
            bottom: 20px;
            right: 20px;
            width: 35px;
            height: 35px;
            background: var(--bg-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: var(--primary-dark);
            opacity: 0;
            transform: translateX(-10px);
            transition: all 0.3s ease;
        }

        .category-card-new:hover .category-arrow {
            opacity: 1;
            transform: translateX(0);
            background: var(--primary-color);
            color: white;
        }

        .view-all-categories {
            text-align: center;
            margin-top: 50px;
        }

        .btn-view-all {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 16px 40px;
            background: white;
            color: var(--text-dark);
            border: 2px solid var(--primary-color);
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-view-all:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255, 183, 116, 0.3);
        }

        /* ================= Products Section (已更新为 Product Listing 的样式) ================= */
        .products-section {
            padding: 80px 40px;
            background: white;
        }

        .products-grid {
            max-width: 1300px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
        }

        /* === p-card 样式 (来自 product_listing.php) === */
        .p-card {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.06);
            position: relative;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid #f5f5f5;
            display: flex;
            flex-direction: column;
        }

        .p-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
            border-color: #FFB774;
        }

        .p-img-box {
            width: 100%;
            height: 200px;
            overflow: hidden;
            position: relative;
            background: linear-gradient(135deg, #f8f8f8, #fff);
            display: block;
        }

        .p-img-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, transparent 60%, rgba(0, 0, 0, 0.05));
            z-index: 1;
            pointer-events: none;
        }

        .p-img-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .p-card:hover .p-img-box img {
            transform: scale(1.08);
        }

        .p-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            /* 改为右上角，因为左上角有排名 */
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            color: #555;
            z-index: 2;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        /* 排名徽章样式 (特有) */
        .rank-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            z-index: 2;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .rank-badge.rank-1 {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: white;
        }

        .rank-badge.rank-2 {
            background: linear-gradient(135deg, #C0C0C0, #A8A8A8);
            color: white;
        }

        .rank-badge.rank-3 {
            background: linear-gradient(135deg, #CD7F32, #B87333);
            color: white;
        }

        .p-info {
            padding: 15px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .p-title {
            font-size: 15px;
            font-weight: 600;
            color: #222;
            margin-bottom: 8px;
            line-height: 1.4;
            text-decoration: none;

            /* ✨ 标准无黄线写法 (只显示 1 行) ✨ */
            white-space: nowrap;
            /* 强制在一行显示 */
            overflow: hidden;
            /* 隐藏超出的文字 */
            text-overflow: ellipsis;
            /* 超出的部分变成 ... */
            display: block;
            /* 确保它是块级元素 */

            /*以此保持卡片对齐，但我把高度稍微改小了一点，因为现在只有1行了 */
            min-height: 22px;
        }

        .p-title:hover {
            color: #FFB774;
        }

        .p-price-row {
            display: flex;
            align-items: flex-end;
            gap: 4px;
            margin-bottom: 15px;
            margin-top: auto;
        }

        .currency {
            font-size: 12px;
            color: #999;
            font-weight: 500;
            margin-bottom: 3px;
        }

        .amount {
            font-size: 20px;
            color: #FFB774;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .p-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .btn-add {
            background: linear-gradient(135deg, #2F2F2F, #1a1a1a);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            padding: 10px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-add:hover {
            background: linear-gradient(135deg, #000, #2F2F2F);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
        }

        .btn-add:active {
            transform: translateY(0);
        }

        .btn-heart-action {
            text-align: center;
            border: 2px solid #eee;
            padding: 10px;
            border-radius: 8px;
            background: #fff;
            color: #ccc;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-heart-action:hover {
            border-color: #FFB774;
            color: #FFB774;
            background: #fff9f4;
            transform: translateY(-2px);
        }

        .btn-heart-action.active {
            border-color: #ff4d4d;
            color: #ff4d4d;
            background: #fff0f0;
        }

        .btn-heart-action.animating {
            animation: heartPop 0.3s ease-in-out;
        }

        @keyframes heartPop {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.3);
            }
        }

        /* Responsive */
        @media (min-width: 1400px) {
            .products-grid {
                grid-template-columns: repeat(5, 1fr);
            }
        }

        @media (max-width: 1200px) {
            .products-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 900px) {
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .products-grid {
                grid-template-columns: 1fr;
            }

            .hero-title {
                font-size: 38px;
            }

            .hero-section {
                height: auto;
                min-height: 500px;
                padding: 60px 0;
            }

            .hero-image-slider {
                height: 350px;
            }

            .main-slider-nav {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }

            .category-card-new:first-child {
                grid-column: span 1;
                grid-row: span 1;
            }

            .category-card-new {
                height: 220px;
            }

            .section-title {
                font-size: 28px;
            }
        }

        /* CTA Section (保持原样) */
        .cta-section {
            padding: 100px 40px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta-content {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        .cta-title {
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 20px;
            line-height: 1.2;
        }

        .cta-description {
            font-size: 20px;
            margin-bottom: 35px;
            opacity: 0.95;
        }

        .cta-button {
            padding: 18px 45px;
            background: white;
            color: var(--primary-dark);
            border-radius: 50px;
            font-size: 18px;
            font-weight: 700;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .cta-button:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.25);
        }

        /* 新增：销量显示样式 */
        .p-sold {
            font-size: 12px;
            color: #888;
            margin-bottom: 5px;
            /* 与价格拉开一点距离 */
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .p-sold i {
            color: #FFB774;
            /* 图标用主题色（橙色） */
            font-size: 12px;
        }
    </style>
</head>

<body>

    <section class="hero-section">
        <div class="float-element float-1">
            <img src="../images/happy.png" alt="Floating pet icon 1">
        </div>
        <div class="float-element float-2">
            <img src="../images/cat.png" alt="Floating pet icon 2">
        </div>
        <div class="float-element float-3">
            <img src="../images/pawprints1.png" alt="Floating pet icon 3">
        </div>

        <div class="hero-content">
            <div class="hero-slide-content active">
                <div class="hero-text">
                    <span class="hero-badge">
                        <i class="fas fa-crown"></i> Premium Pet Care
                    </span>
                    <h1 class="hero-title">Everything Your <span>Pet Deserves</span></h1>
                    <p class="hero-description">Discover premium food, toys, and accessories curated for your beloved companions.</p>
                    <div class="hero-buttons">
                        <a href="product_listing.php" class="btn-primary">Shop Now <i class="fas fa-arrow-right"></i></a>
                        <a href="about.php" class="btn-secondary">Learn More</a>
                    </div>
                </div>
                <div class="hero-image">
                    <div class="hero-image-slider">
                        <div class="hero-slide active"><img src="../images/dog_and_cat1.jpg" alt="Happy Pets"></div>
                    </div>
                </div>
            </div>
            <div class="hero-slide-content">
                <div class="hero-text">
                    <span class="hero-badge">
                        <i class="fas fa-heart-pulse"></i> Happy & Healthy
                    </span>
                    <h1 class="hero-title">Quality Food for <span>Happy Tails</span></h1>
                    <p class="hero-description">Nutrition-packed meals specially formulated for your pet's wellbeing.</p>
                    <div class="hero-buttons">
                        <a href="product_listing.php?category=1" class="btn-primary">Shop Food <i class="fas fa-arrow-right"></i></a>
                        <a href="about.php" class="btn-secondary">Learn More</a>
                    </div>
                </div>
                <div class="hero-image">
                    <div class="hero-image-slider">
                        <div class="hero-slide active"><img src="../images/images1.jpg" alt="Cute Dog"></div>
                    </div>
                </div>
            </div>
            <div class="hero-slide-content">
                <div class="hero-text">
                    <span class="hero-badge">
                        <i class="fas fa-bone"></i> Playtime Fun
                    </span>
                    <h1 class="hero-title">Toys That Bring <span>Pure Joy</span></h1>
                    <p class="hero-description">Keep your furry friends entertained with our collection of safe, durable toys.</p>
                    <div class="hero-buttons">
                        <a href="product_listing.php?category=2" class="btn-primary">Shop Toys <i class="fas fa-arrow-right"></i></a>
                        <a href="about.php" class="btn-secondary">Learn More</a>
                    </div>
                </div>
                <div class="hero-image">
                    <div class="hero-image-slider">
                        <div class="hero-slide active"><img src="../images/images2.jpg" alt="Playful Cat"></div>
                    </div>
                </div>
            </div>
            <div class="hero-slide-content">
                <div class="hero-text">
                    <span class="hero-badge">
                        <i class="fas fa-tags"></i> Special Offer
                    </span>
                    <h1 class="hero-title">Save Up To <span>30% Off</span></h1>
                    <p class="hero-description">Limited time deals on premium pet supplies! Stock up on your pet's favorites.</p>
                    <div class="hero-buttons">
                        <a href="product_listing.php" class="btn-primary">Shop Deals <i class="fas fa-arrow-right"></i></a>
                        <a href="contact.php" class="btn-secondary">Contact Us</a>
                    </div>
                </div>
                <div class="hero-image">
                    <div class="hero-image-slider">
                        <div class="hero-slide active"><img src="../images/images3.jpg" alt="Happy Puppy"></div>
                    </div>
                </div>
            </div>
        </div>

        <button class="main-slider-nav main-slider-prev" onclick="changeMainSlide(-1)"><i class="fas fa-chevron-left"></i></button>
        <button class="main-slider-nav main-slider-next" onclick="changeMainSlide(1)"><i class="fas fa-chevron-right"></i></button>
        <div class="main-slider-dots">
            <span class="main-slider-dot active" onclick="goToMainSlide(0)"></span>
            <span class="main-slider-dot" onclick="goToMainSlide(1)"></span>
            <span class="main-slider-dot" onclick="goToMainSlide(2)"></span>
            <span class="main-slider-dot" onclick="goToMainSlide(3)"></span>
        </div>
    </section>

    <section class="features-section">
        <div class="features-container">
            <div class="feature-card">
                <div class="feature-icon">
                    <img src="../images/delivery-truck.png" alt="Shipping">
                </div>
                <h3 class="feature-title">Free Shipping</h3>
                <p class="feature-desc">Free delivery on orders over RM 50.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <img src="../images/diamond.png" alt="Quality">
                </div>
                <h3 class="feature-title">Premium Quality</h3>
                <p class="feature-desc">Only the finest products.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <img src="../images/padlock.png" alt="Secure">
                </div>
                <h3 class="feature-title">Secure Payment</h3>
                <p class="feature-desc">Safe and encrypted transactions.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <img src="../images/support.png" alt="Support">
                </div>
                <h3 class="feature-title">24/7 Support</h3>
                <p class="feature-desc">Expert pet care advice.</p>
            </div>
        </div>
    </section>

    <section class="categories-section">
        <div class="section-header">
            <p class="section-subtitle">Discover</p>
            <h2 class="section-title">Shop By Category</h2>
            <p class="section-description">Find everything your furry friend needs in one place</p>
        </div>
        <div class="categories-wrapper">
            <div class="categories-grid">
                <?php
                $category_icons = [
                    '../images/dog.png',      // 对应 category 1
                    '../images/munchkin.png',       // 对应 category 2
                    '../images/rabbit.png',    // 对应 category 3
                    '../images/bird.png',  // 对应 category 4
                    '../images/clown-fish.png',       // 对应 category 5
                    '../images/pet-spa.png',     // 对应 category 6
                    '../images/home-accessory.png',   // 对应 category 7
                    '../images/pet-food.png'    // 对应 category 8
                ];
                $category_descriptions = [
                    'Premium nutrition',
                    'Fun toys',
                    'Health & wellness',
                    'Grooming',
                    'Comfortable beds',
                    'Stylish clothing'
                ];
                foreach ($categories as $index => $cat):
                    $icon = $category_icons[$index] ?? '🐾';
                    $desc = $category_descriptions[$index] ?? 'Explore products';
                ?>
                    <a href="product_listing.php?category=<?= $cat['category_id'] ?>" style="text-decoration: none;">
                        <div class="category-card-new">
                            <div class="category-icon-wrapper">

                                <div class="category-icon-new">
                                    <?php if (strpos($icon, 'images/') !== false): ?>
                                        <img src="<?= $icon ?>" alt="<?= htmlspecialchars($cat['name']) ?>">
                                    <?php else: ?>
                                        <?= $icon ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <h3 class="category-name-new"><?= htmlspecialchars($cat['name']) ?></h3>
                            <p class="category-desc-new"><?= $desc ?></p>
                            <div class="category-arrow"><i class="fas fa-arrow-right"></i></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="view-all-categories">
                <a href="product_listing.php" class="btn-view-all">View All Products <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </section>

    <section class="products-section">
        <div class="section-header">
            <p class="section-subtitle">Best Sellers</p>
            <h2 class="section-title">Top 5 Best Selling Products</h2>
            <p class="section-description">Most popular products based on customer orders</p>
        </div>

        <div class="products-grid">
            <?php foreach ($featured_products as $index => $product): ?>
                <?php
                // 检查收藏状态
                $isWished = in_array($product['product_id'], $user_wishlist_ids);
                $btnClass = $isWished ? 'active' : '';
                $iconClass = $isWished ? 'fa-solid' : 'fa-regular';
                ?>

                <div class="p-card">
                    <a href="product_detail.php?id=<?= $product['product_id'] ?>" class="p-img-box">
                        <?php if ($index < 3): ?>
                            <div class="rank-badge rank-<?= $index + 1 ?>">
                                <?php if ($index == 0): ?> <i class="fas fa-crown"></i> <?php endif; ?>
                                #<?= $index + 1 ?>
                            </div>
                        <?php endif; ?>

                        <img src="<?= productImageUrl($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                        <div class="p-badge"><?= htmlspecialchars($product['category_name'] ?? 'Pet Item') ?></div>
                    </a>

                    <div class="p-info">
                        <a href="product_detail.php?id=<?= $product['product_id'] ?>" class="p-title">
                            <?= htmlspecialchars($product['name']) ?>
                        </a>

                        <div class="p-sold">
                            <?php if ($product['total_sold'] > 0): ?>
                                <i class="fas fa-fire"></i> <?= $product['total_sold'] ?> sold
                            <?php else: ?>
                                <span style="opacity:0;">-</span> <?php endif; ?>
                        </div>
                        <div class="p-price-row">
                            <span class="currency">RM</span>
                            <span class="amount"><?= number_format($product['price'], 2) ?></span>
                        </div>

                        <div class="p-actions">
                            <button class="btn-add" onclick="addToCart(<?= $product['product_id'] ?>)">
                                <i class="fas fa-shopping-cart"></i> Add
                            </button>

                            <button class="btn-heart-action <?= $btnClass ?>" onclick="toggleWishlist(this, <?= $product['product_id'] ?>)">
                                <i class="<?= $iconClass ?> fa-heart"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="text-align: center; margin-top: 50px;">
            <a href="product_listing.php" class="btn-primary" style="text-decoration: none;">
                View All Products <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </section>

    <section class="cta-section">
        <div class="cta-content">
            <h2 class="cta-title">Ready to Spoil Your Pet?</h2>
            <p class="cta-description">Join thousands of happy pet owners who trust PetBuddy for quality products</p>
            <a href="product_listing.php" class="cta-button">Start Shopping Now</a>
        </div>
    </section>

    <?php include '../include/footer.php'; ?>
    <?php include '../include/chat_widget.php'; ?>

    <script>
        // ============ 1. Hero Slider Logic (保持原样) ============
        let currentMainSlide = 0;
        const mainSlides = document.querySelectorAll('.hero-slide-content');
        const mainDots = document.querySelectorAll('.main-slider-dot');
        const totalMainSlides = mainSlides.length;
        let mainAutoSlideInterval = setInterval(() => {
            changeMainSlide(1);
        }, 6000);

        function changeMainSlide(direction) {
            if (mainSlides.length === 0) return;
            mainSlides[currentMainSlide].classList.remove('active');
            mainDots[currentMainSlide].classList.remove('active');
            currentMainSlide = (currentMainSlide + direction + totalMainSlides) % totalMainSlides;
            mainSlides[currentMainSlide].classList.add('active');
            mainDots[currentMainSlide].classList.add('active');
            resetMainAutoSlide();
        }

        function goToMainSlide(index) {
            if (mainSlides.length === 0) return;
            mainSlides[currentMainSlide].classList.remove('active');
            mainDots[currentMainSlide].classList.remove('active');
            currentMainSlide = index;
            mainSlides[currentMainSlide].classList.add('active');
            mainDots[currentMainSlide].classList.add('active');
            resetMainAutoSlide();
        }

        function resetMainAutoSlide() {
            clearInterval(mainAutoSlideInterval);
            mainAutoSlideInterval = setInterval(() => {
                changeMainSlide(1);
            }, 6000);
        }
        const heroSection = document.querySelector('.hero-section');
        if (heroSection) {
            heroSection.addEventListener('mouseenter', () => {
                clearInterval(mainAutoSlideInterval);
            });
            heroSection.addEventListener('mouseleave', () => {
                resetMainAutoSlide();
            });
        }

        // ============ 2. 加入购物车 (与 Product Listing 统一) ============
        function addToCart(pid) {
            let $btn = $("button[onclick='addToCart(" + pid + ")']");
            $btn.prop('disabled', true).css('opacity', '0.7');

            $.ajax({
                url: "add_to_cart.php",
                type: "POST",
                data: {
                    product_id: pid,
                    quantity: 1
                },
                success: function(response) {
                    let res = response.trim();
                    $btn.prop('disabled', false).css('opacity', '1');

                    if (res === "login_required") {
                        Swal.fire({
                            title: 'Login Required',
                            text: 'Please login first.',
                            icon: 'info',
                            showCancelButton: true,
                            confirmButtonText: 'Login',
                            confirmButtonColor: '#2F2F2F'
                        }).then((r) => {
                            if (r.isConfirmed) window.location.href = 'login.php';
                        });
                    } else if (res === "added" || res === "quantity increased") {
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true
                        });
                        Toast.fire({
                            icon: 'success',
                            title: 'Added to Cart!'
                        });

                        refreshCartSidebar();
                        setTimeout(() => {
                            if (typeof openCart === 'function') openCart();
                        }, 300);
                    } else {
                        Swal.fire('Error', 'Failed to add item.', 'error');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).css('opacity', '1');
                }
            });
        }

        // ============ 3. 刷新侧边栏 ============
        function refreshCartSidebar() {
            $.ajax({
                url: 'fetch_cart.php',
                type: 'GET',
                success: function(htmlResponse) {
                    $('#cartBody').html(htmlResponse);
                    let newTotal = $('#ajax-new-total').val();
                    if (newTotal) {
                        $('#cartSidebarTotal').text(newTotal);
                        if (typeof updateFreeShipping === 'function') updateFreeShipping();
                        if (parseFloat(newTotal) > 0) $('#cartFooter').show();
                    }
                    setTimeout(function() {
                        if (typeof updateCartBadge === 'function') updateCartBadge();
                    }, 200);
                }
            });
        }

        // ============ 4. 收藏功能 (与 Product Listing 统一) ============
        function toggleWishlist(btn, pid) {
            let $btn = $(btn);
            let $icon = $btn.find("i");

            $btn.addClass("animating");
            setTimeout(() => $btn.removeClass("animating"), 300);

            $.ajax({
                url: 'wishlist_action.php',
                type: 'POST',
                data: {
                    product_id: pid
                },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'login_required') {
                        Swal.fire({
                            title: 'Login Required',
                            text: 'Please login to save items.',
                            icon: 'warning',
                            confirmButtonText: 'Login',
                            confirmButtonColor: '#2F2F2F'
                        }).then((r) => {
                            if (r.isConfirmed) window.location.href = 'login.php';
                        });
                    } else if (res.status === 'added') {
                        $btn.addClass('active');
                        $icon.removeClass('fa-regular').addClass('fa-solid');
                        Swal.fire({
                            icon: 'success',
                            title: 'Saved!',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 1500
                        });
                    } else if (res.status === 'removed') {
                        $btn.removeClass('active');
                        $icon.removeClass('fa-solid').addClass('fa-regular');
                    }
                }
            });
        }
    </script>

</body>

</html>