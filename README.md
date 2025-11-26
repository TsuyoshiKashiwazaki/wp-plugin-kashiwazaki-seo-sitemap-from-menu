# Kashiwazaki SEO Sitemap from Menu

![Requires PHP](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)
![Requires WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)
![License](https://img.shields.io/badge/License-GPLv2-blue.svg)
![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)

WordPressのナビゲーションメニューからHTML形式のサイトマップを生成するプラグインです。

## 機能

- ショートコード `[menu_sitemap]` でメニュー構造を階層的に表示
- 複数のリストスタイル（黒丸、白丸、四角、なし、ディレクトリツリー）
- ページのDescriptionをツールチップで表示（Yoast SEO、All in One SEO、Rank Math、SEOPress対応）
- カスタムCSS対応
- 多言語対応（日本語翻訳付属）

## インストール

1. プラグインフォルダを `/wp-content/plugins/` にアップロード
2. WordPress管理画面の「プラグイン」メニューで有効化

## 使い方

### 基本

```
[menu_sitemap]
```

デフォルトメニューを表示します。

### メニュー名を指定

```
[menu_sitemap menu="main-menu"]
```

### メニューロケーションを指定

```
[menu_sitemap location="primary"]
```

## 設定

管理画面の「Kashiwazaki SEO Sitemap from Menu」メニューから設定できます。

- **デフォルトメニュー**: ショートコードでメニューを指定しない場合に使用されるメニュー
- **リストスタイル**: リストのマーカースタイル
- **インデントサイズ**: 子メニューのインデント幅（px）
- **ツールチップ**: ページのDescriptionをツールチップで表示するかどうか
- **カスタムCSS**: 追加のスタイルシート

## ライセンス

GPL v2 or later

## 作者

柏崎剛 (Tsuyoshi Kashiwazaki)
- https://www.tsuyoshikashiwazaki.jp

## Changelog

### [1.0.0] - 2025-11-26
- Initial release.

