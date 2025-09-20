<?php
//========================================
// 概要   : データベース接続設定
// 機能   : MySQL接続、セッション開始
// 作成日 : 2025年
//========================================

// データベース接続情報
const DB_HOST = 'localhost';
const DB_NAME = 'hotel_booking';
const DB_USER = 'root';
const DB_PASS = '';

// データベース接続処理
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("データベース接続エラー: " . $e->getMessage());
}

// セッション開始
session_start();
?>