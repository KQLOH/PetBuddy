<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../include/db.php';
require_once '../include/product_utils.php';


$user_wishlist_ids = [];
if (isset($_SESSION['member_id'])) {
    try {
        $w_stmt = $pdo->prepare("SELECT product_id FROM wishlist WHERE member_id = ?");
        $w_stmt->execute([$_SESSION['member_id']]);
        $user_wishlist_ids = $w_stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
    }
}


$featured_products = [];
try {
    $stmt = $pdo->prepare("
        SELECT p.*, pc.name as category_name, COALESCE(SUM(oi.quantity), 0) as total_sold
        FROM products p 
        LEFT JOIN product_categories pc ON p.category_id = pc.category_id 
        LEFT JOIN order_items oi ON p.product_id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.order_id
        WHERE p.stock_qty > 0 
        GROUP BY p.product_id
        ORDER BY total_sold DESC
        LIMIT 5
    ");
    $stmt->execute();
    $featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);


    if (count($featured_products) < 5) {
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
}


$categories = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM product_categories LIMIT 8");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
}

include '../include/header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetBuddy - Premium Pet Supplies</title>

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

        .hero-text {
            animation: fadeInUp 0.8s ease;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
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

        .float-element img {
            width: 100%;
            height: auto;
            display: block;
        }

        .float-1 {
            top: 10%;
            left: 5%;
            width: 60px;
            animation-delay: 0s;
        }

        .float-2 {
            top: 70%;
            left: 10%;
            width: 40px;
            animation-delay: 1s;
        }

        .float-3 {
            top: 20%;
            right: 8%;
            width: 50px;
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

        .feature-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 25px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 20px rgba(255, 183, 116, 0.3);
        }

        .feature-icon img {
            width: 40px;
            height: 40px;
            object-fit: contain;
            display: block;
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
            grid-template-columns: repeat(4, 1fr);
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

        .category-icon-new img {
            width: 50px;
            height: 50px;
            object-fit: contain;
            display: block;
            transition: all 0.3s ease;
        }

        .category-card-new:hover .category-icon-new img {
            transform: scale(1.1);
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
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
            min-height: 22px;
        }

        .p-title:hover {
            color: #FFB774;
        }

        .p-sold {
            font-size: 12px;
            color: #888;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .p-sold i {
            color: #FFB774;
            font-size: 12px;
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

        .btn-add img {
            width: 18px;
            height: 18px;
            filter: brightness(0) invert(1);
            margin-right: 5px;
            object-fit: contain;
        }

        .btn-heart-action {
            text-align: center;
            border: 2px solid #eee;
            padding: 10px;
            border-radius: 8px;
            background: #fff;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-heart-action:hover {
            border-color: #FFB774;
            background: #fff9f4;
            transform: translateY(-2px);
        }

        .btn-heart-action img {
            width: 20px;
            height: 20px;
            object-fit: contain;
            transition: all 0.3s ease;
            filter: grayscale(100%) opacity(0.5);
        }

        .btn-heart-action.active {
            border-color: #ff4d4d;
            background: #fff0f0;
        }

        .btn-heart-action.active img {
            filter: grayscale(0%) opacity(1);
            transform: scale(1.1);
        }

        .btn-heart-action:hover img {
            filter: grayscale(0%) opacity(0.8);
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


        @media (min-width: 1400px) {
            .products-grid {
                grid-template-columns: repeat(5, 1fr);
            }

            .categories-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 1200px) {
            .products-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .categories-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 900px) {
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .categories-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .products-grid {
                grid-template-columns: 1fr;
            }

            .categories-grid {
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

            .category-card-new {
                height: 220px;
            }

            .section-title {
                font-size: 28px;
            }
        }

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


        /* Toast Styles (for success messages) */
        #custom-toast {
            visibility: hidden;
            min-width: 200px;
            background-color: rgba(40, 40, 40, 0.95);
            color: #fff;
            text-align: center;
            border-radius: 50px;
            padding: 12px 24px;
            position: fixed;
            z-index: 9999;
            left: 50%;
            bottom: 30px;
            transform: translateX(-50%);
            font-size: 15px;
            box-shadow: 0px 8px 20px rgba(0, 0, 0, 0.2);
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        #custom-toast.show {
            visibility: visible;
            opacity: 1;
            bottom: 50px;
        }

        .toast-icon {
            width: 20px;
            height: 20px;
        }

        /* Modal Prompt Styles (for login required) */
        #custom-modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }

        #custom-modal-overlay.show {
            display: flex;
        }

        #custom-modal {
            background: white;
            border-radius: 16px;
            padding: 40px 30px 30px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            animation: slideUp 0.3s ease;
            position: relative;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #E8F5E9;
        }

        .modal-icon img {
            width: 40px;
            height: 40px;
        }

        .modal-icon.error {
            background: #FFEBEE;
        }

        .modal-icon.warning {
            background: #FFF3E0;
        }

        .modal-title {
            font-size: 24px;
            font-weight: 700;
            color: #2F2F2F;
            margin: 0 0 10px 0;
        }

        .modal-message {
            font-size: 15px;
            color: #666;
            margin: 0 0 25px 0;
            line-height: 1.5;
        }

        .modal-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
        }

        .modal-btn {
            padding: 12px 32px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            min-width: 100px;
        }

        .modal-btn-primary {
            background: #FFB774;
            color: white;
        }

        .modal-btn-primary:hover {
            background: #E89C55;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 183, 116, 0.4);
        }

        .modal-btn-secondary {
            background: #f5f5f5;
            color: #666;
        }

        .modal-btn-secondary:hover {
            background: #e8e8e8;
        }
    </style>
</head>

<body>

    <section class="hero-section">
        <div class="float-element float-1"><img src="../images/happy.png" alt="Happy Pet"></div>
        <div class="float-element float-2"><img src="../images/cat.png" alt="Happy Pet"></div>
        <div class="float-element float-3"><img src="../images/pawprints1.png" alt="Happy Pet"></div>
        <div class="hero-content">
            <div class="hero-slide-content active">
                <div class="hero-text">
                    <span class="hero-badge"> Premium Pet Care</span>
                    <h1 class="hero-title">Everything Your <span>Pet Deserves</span></h1>
                    <p class="hero-description">Discover premium food, toys, and accessories curated for your beloved companions.</p>
                    <div class="hero-buttons">
                        <a href="product_listing.php" class="btn-primary">Shop Now </a>
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
                    <span class="hero-badge"> Happy & Healthy</span>
                    <h1 class="hero-title">Quality Food for <span>Happy Tails</span></h1>
                    <p class="hero-description">Nutrition-packed meals specially formulated for your pet's wellbeing.</p>
                    <div class="hero-buttons">
                        <a href="product_listing.php?search=food" class="btn-primary">Shop Food </a>
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
                    <span class="hero-badge"> Playtime Fun</span>
                    <h1 class="hero-title">Toys That Bring <span>Pure Joy</span></h1>
                    <p class="hero-description">Keep your furry friends entertained with our collection of safe, durable toys.</p>
                    <div class="hero-buttons">
                        <a href="product_listing.php?search=toy" class="btn-primary">Shop Toys </a>
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
                    <span class="hero-badge"> Special Offer</span>
                    <h1 class="hero-title">Save Up To <span>30% Off</span></h1>
                    <p class="hero-description">Limited time deals on premium pet supplies! Stock up on your pet's favorites.</p>
                    <div class="hero-buttons">
                        <a href="product_listing.php" class="btn-primary">Shop Deals </a>
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

        <div class="main-slider-dots">
            <span class="main-slider-dot active" onclick="goToMainSlide(0)"></span><span class="main-slider-dot" onclick="goToMainSlide(1)"></span><span class="main-slider-dot" onclick="goToMainSlide(2)"></span><span class="main-slider-dot" onclick="goToMainSlide(3)"></span>
        </div>
    </section>

    <section class="features-section">
        <div class="features-container">
            <div class="feature-card">
                <div class="feature-icon"><img src="../images/fast-delivery.png" alt="Shipping"></div>
                <h3 class="feature-title">Free Shipping</h3>
                <p class="feature-desc">Free delivery on orders over RM 50.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><img src="../images/diamond.png" alt="Quality"></div>
                <h3 class="feature-title">Premium Quality</h3>
                <p class="feature-desc">Only the finest products.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><img src="../images/padlock.png" alt="Secure"></div>
                <h3 class="feature-title">Secure Payment</h3>
                <p class="feature-desc">Safe and encrypted transactions.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><img src="../images/support.png" alt="Support"></div>
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
                <?php foreach ($categories as $cat): ?>
                    <?php
                    $icon = !empty($cat['image']) ? $cat['image'] : '🐾';
                    $desc = !empty($cat['description']) ? $cat['description'] : 'Explore products';
                    ?>
                    <a href="product_listing.php?category=<?= $cat['category_id'] ?>" style="text-decoration: none;">
                        <div class="category-card-new">
                            <div class="category-icon-wrapper">
                                <div class="category-icon-new">
                                    <?php if (strpos($icon, 'images/') !== false): ?>
                                        <img src="<?= htmlspecialchars($icon) ?>" alt="<?= htmlspecialchars($cat['name']) ?>">
                                    <?php else: ?>
                                        <?= $icon ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <h3 class="category-name-new"><?= htmlspecialchars($cat['name']) ?></h3>
                            <p class="category-desc-new"><?= htmlspecialchars($desc) ?></p>
                            <div class="category-arrow">
                                <img src="../images/arrow-right.png" alt="Arrow" style="width: 25; height: 25;">
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="view-all-categories"><a href="product_listing.php" class="btn-view-all">View All Products </a></div>
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
                $isWished = in_array($product['product_id'], $user_wishlist_ids);
                $btnClass = $isWished ? 'active' : '';
                ?>

                <div class="p-card">
                    <a href="product_detail.php?id=<?= $product['product_id'] ?>" class="p-img-box">
                        <?php if ($index < 3): ?>
                            <div class="rank-badge rank-<?= $index + 1 ?>">
                                <?php if ($index == 0): ?> <?php endif; ?>
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
                                <?= $product['total_sold'] ?> sold
                            <?php else: ?>
                                <span style="opacity:0;">-</span>
                            <?php endif; ?>
                        </div>

                        <div class="p-price-row">
                            <span class="currency">RM</span>
                            <span class="amount"><?= number_format($product['price'], 2) ?></span>
                        </div>

                        <div class="p-actions">
                            <button class="btn-add" onclick="addToCart(<?= $product['product_id'] ?>)">
                                <img src="../images/cart.png" alt="Add"> Add
                            </button>

                            <button class="btn-heart-action <?= $btnClass ?>" onclick="toggleWishlist(this, <?= $product['product_id'] ?>)">
                                <img src="../images/heart.png" alt="Wishlist">
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="text-align: center; margin-top: 50px;">
            <a href="product_listing.php" class="btn-primary" style="text-decoration: none;">
                View All Products
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

    <!-- Toast Notification -->
    <div id="custom-toast">
        <img src="../images/success.png" alt="" class="toast-icon">
        <span id="custom-toast-msg">Added to cart!</span>
    </div>

    <!-- Modal Prompt (for login required) -->
    <div id="custom-modal-overlay">
        <div id="custom-modal">
            <div class="modal-icon" id="modal-icon">
                <img src="../images/success.png" alt="">
            </div>
            <h3 class="modal-title" id="modal-title">Login Required</h3>
            <p class="modal-message" id="modal-message">Please login first to add items to cart.</p>
            <div class="modal-buttons" id="modal-buttons">
                <button class="modal-btn modal-btn-primary" id="modal-ok-btn">Go to Login</button>
                <button class="modal-btn modal-btn-secondary" id="modal-cancel-btn">Cancel</button>
            </div>
        </div>
    </div>

    <?php include '../include/footer.php'; ?>
    <?php include '../include/chat_widget.php'; ?>

    <script>
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


        document.addEventListener('DOMContentLoaded', function() {
            const overlay = document.getElementById('custom-modal-overlay');
            if (overlay) {
                overlay.addEventListener('click', function(e) {
                    if (e.target === this) {
                        this.classList.remove('show');
                    }
                });
            }
        });

        function safeToast(message, showLoginBtn = false) {
            if (showLoginBtn) {
                const overlay = document.getElementById('custom-modal-overlay');
                const modal = document.getElementById('custom-modal');
                const iconDiv = document.getElementById('modal-icon');
                const iconImg = iconDiv.querySelector('img');
                const titleEl = document.getElementById('modal-title');
                const messageEl = document.getElementById('modal-message');
                const buttonsDiv = document.getElementById('modal-buttons');

                titleEl.textContent = 'Login Required';
                messageEl.textContent = message;
                iconDiv.className = 'modal-icon';
                iconImg.src = '../images/success.png';

                buttonsDiv.innerHTML = '';
                const loginBtn = document.createElement('button');
                loginBtn.className = 'modal-btn modal-btn-primary';
                loginBtn.textContent = 'Go to Login';
                loginBtn.onclick = function() {
                    overlay.classList.remove('show');
                    window.location.href = 'login.php';
                };
                buttonsDiv.appendChild(loginBtn);

                const cancelBtn = document.createElement('button');
                cancelBtn.className = 'modal-btn modal-btn-secondary';
                cancelBtn.textContent = 'Cancel';
                cancelBtn.onclick = function() {
                    overlay.classList.remove('show');
                };
                buttonsDiv.appendChild(cancelBtn);

                overlay.classList.add('show');
                return;
            }

            const toast = document.getElementById('custom-toast');
            const msgSpan = document.getElementById('custom-toast-msg');
            const img = toast.querySelector('img');

            msgSpan.innerText = message;

            if (message.toLowerCase().includes("remove") || message.toLowerCase().includes("delete")) {
                img.src = '../images/dusbin.png';
            } else {
                img.src = '../images/success.png';
            }

            img.onerror = function() {
                this.src = '../images/success.png';
            };

            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, 2500);
        }


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
                        safeToast("Please login first to add items to cart.", true);
                    } else if (res === "added" || res === "quantity increased") {
                        safeToast("Added to Cart!");
                        refreshCartSidebar();
                        setTimeout(() => {
                            if (typeof openCart === 'function') openCart();
                        }, 500);
                    } else {
                        safeToast("Failed to add item. Out of stock?");
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).css('opacity', '1');
                }
            });
        }


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


        function toggleWishlist(btn, pid) {
            let $btn = $(btn);

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
                        safeToast("Please login to save items to wishlist.", true);
                    } else if (res.status === 'added') {
                        $btn.addClass('active');
                        safeToast("Saved to Wishlist!");
                    } else if (res.status === 'removed') {
                        $btn.removeClass('active');
                        safeToast("Removed from Wishlist!");
                    }
                }
            });
        }


        $(document).ready(function() {
            $(document).on('click', '.btn-remove-item', function(e) {
                e.preventDefault();
                let btn = $(this);
                let pid = btn.data('id');
                if (btn.prop('disabled')) return;
                btn.prop('disabled', true);

                $.ajax({
                    url: 'remove_from_cart.php',
                    type: 'POST',
                    data: {
                        product_id: pid
                    },
                    success: function() {
                        refreshCartSidebar();
                        safeToast("Item removed from cart");
                    },
                    error: function() {
                        btn.prop('disabled', false);
                    }
                });
            });
        });
    </script>

</body>

</html>