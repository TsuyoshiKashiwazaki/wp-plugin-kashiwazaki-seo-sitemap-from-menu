<?php
/**
 * Plugin Name: Kashiwazaki SEO Sitemap from Menu
 * Plugin URI: https://www.tsuyoshikashiwazaki.jp
 * Description: ナビゲーションメニューからHTML形式のサイトマップを生成するプラグイン。ショートコード [menu_sitemap] でメニュー構造を階層的に表示します。
 * Version: 1.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Author: 柏崎剛 (Tsuyoshi Kashiwazaki)
 * Author URI: https://www.tsuyoshikashiwazaki.jp/profile/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: kashiwazaki-seo-sitemap-from-menu
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Kashiwazaki_SEO_Sitemap_From_Menu {

    /**
     * プラグインのバージョン
     */
    const VERSION = '1.0.0';

    /**
     * オプション名
     */
    const OPTION_NAME = 'kashiwazaki_seo_sitemap_from_menu_options';

    /**
     * シングルトンインスタンス
     */
    private static $instance = null;

    /**
     * シングルトンインスタンスを取得
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * コンストラクタ
     */
    private function __construct() {
        add_action( 'init', array( $this, 'load_textdomain' ) );
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        add_shortcode( 'menu_sitemap', array( $this, 'render_sitemap_shortcode' ) );

        // プラグイン一覧に設定リンクを追加
        add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_plugin_action_links' ) );
    }

    /**
     * プラグイン一覧に設定リンクを追加
     */
    public function add_plugin_action_links( $links ) {
        $settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=kashiwazaki-seo-sitemap-from-menu' ) ) . '">' . esc_html__( '設定', 'kashiwazaki-seo-sitemap-from-menu' ) . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

    /**
     * テキストドメインを読み込み
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'kashiwazaki-seo-sitemap-from-menu',
            false,
            dirname( plugin_basename( __FILE__ ) ) . '/languages'
        );
    }

    /**
     * 管理メニューを追加
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'Kashiwazaki SEO Sitemap from Menu', 'kashiwazaki-seo-sitemap-from-menu' ),
            __( 'Kashiwazaki SEO Sitemap from Menu', 'kashiwazaki-seo-sitemap-from-menu' ),
            'manage_options',
            'kashiwazaki-seo-sitemap-from-menu',
            array( $this, 'render_admin_page' ),
            'dashicons-list-view',
            81
        );
    }

    /**
     * 設定を登録
     */
    public function register_settings() {
        register_setting(
            'kashiwazaki_seo_sitemap_from_menu_group',
            self::OPTION_NAME,
            array( $this, 'sanitize_options' )
        );

        add_settings_section(
            'kashiwazaki_seo_sitemap_from_menu_section',
            __( '表示設定', 'kashiwazaki-seo-sitemap-from-menu' ),
            array( $this, 'render_section_description' ),
            'kashiwazaki-seo-sitemap-from-menu'
        );

        add_settings_field(
            'default_menu',
            __( 'デフォルトメニュー', 'kashiwazaki-seo-sitemap-from-menu' ),
            array( $this, 'render_default_menu_field' ),
            'kashiwazaki-seo-sitemap-from-menu',
            'kashiwazaki_seo_sitemap_from_menu_section'
        );

        add_settings_field(
            'list_style',
            __( 'リストスタイル', 'kashiwazaki-seo-sitemap-from-menu' ),
            array( $this, 'render_list_style_field' ),
            'kashiwazaki-seo-sitemap-from-menu',
            'kashiwazaki_seo_sitemap_from_menu_section'
        );

        add_settings_field(
            'indent_size',
            __( 'インデントサイズ', 'kashiwazaki-seo-sitemap-from-menu' ),
            array( $this, 'render_indent_size_field' ),
            'kashiwazaki-seo-sitemap-from-menu',
            'kashiwazaki_seo_sitemap_from_menu_section'
        );

        add_settings_field(
            'show_tooltip',
            __( 'ツールチップ', 'kashiwazaki-seo-sitemap-from-menu' ),
            array( $this, 'render_show_tooltip_field' ),
            'kashiwazaki-seo-sitemap-from-menu',
            'kashiwazaki_seo_sitemap_from_menu_section'
        );

        add_settings_field(
            'custom_css',
            __( 'カスタムCSS', 'kashiwazaki-seo-sitemap-from-menu' ),
            array( $this, 'render_custom_css_field' ),
            'kashiwazaki-seo-sitemap-from-menu',
            'kashiwazaki_seo_sitemap_from_menu_section'
        );
    }

    /**
     * オプションをサニタイズ
     */
    public function sanitize_options( $input ) {
        $sanitized = array();

        $sanitized['default_menu'] = isset( $input['default_menu'] ) ? sanitize_text_field( $input['default_menu'] ) : '';
        $sanitized['list_style'] = isset( $input['list_style'] ) ? sanitize_text_field( $input['list_style'] ) : 'disc';
        $sanitized['indent_size'] = isset( $input['indent_size'] ) ? absint( $input['indent_size'] ) : 20;
        $sanitized['show_tooltip'] = isset( $input['show_tooltip'] ) ? 1 : 0;
        $sanitized['custom_css'] = isset( $input['custom_css'] ) ? wp_strip_all_tags( $input['custom_css'] ) : '';

        return $sanitized;
    }

    /**
     * デフォルトオプションを取得
     */
    public function get_default_options() {
        return array(
            'default_menu'     => '',
            'list_style'       => 'disc',
            'indent_size'      => 20,
            'show_tooltip'     => 1,
            'custom_css'       => '',
        );
    }

    /**
     * オプションを取得
     */
    public function get_options() {
        $options = get_option( self::OPTION_NAME, array() );
        return wp_parse_args( $options, $this->get_default_options() );
    }

    /**
     * セクション説明を表示
     */
    public function render_section_description() {
        echo '<p>' . esc_html__( 'サイトマップの表示設定をカスタマイズできます。', 'kashiwazaki-seo-sitemap-from-menu' ) . '</p>';
    }

    /**
     * デフォルトメニューフィールドを表示
     */
    public function render_default_menu_field() {
        $options = $this->get_options();
        $menus = wp_get_nav_menus();
        ?>
        <select name="<?php echo esc_attr( self::OPTION_NAME ); ?>[default_menu]" id="default_menu">
            <option value=""><?php esc_html_e( '-- 選択してください --', 'kashiwazaki-seo-sitemap-from-menu' ); ?></option>
            <?php foreach ( $menus as $menu ) : ?>
                <option value="<?php echo esc_attr( $menu->slug ); ?>" <?php selected( $options['default_menu'], $menu->slug ); ?>>
                    <?php echo esc_html( $menu->name ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php esc_html_e( 'ショートコードでメニューを指定しない場合に使用されるデフォルトのメニューです。', 'kashiwazaki-seo-sitemap-from-menu' ); ?></p>
        <?php
    }

    /**
     * リストスタイルフィールドを表示
     */
    public function render_list_style_field() {
        $options = $this->get_options();
        $styles = array(
            'disc'   => __( '● 黒丸', 'kashiwazaki-seo-sitemap-from-menu' ),
            'circle' => __( '○ 白丸', 'kashiwazaki-seo-sitemap-from-menu' ),
            'square' => __( '■ 四角', 'kashiwazaki-seo-sitemap-from-menu' ),
            'none'   => __( 'なし', 'kashiwazaki-seo-sitemap-from-menu' ),
            'tree'   => __( '📁 ディレクトリツリー', 'kashiwazaki-seo-sitemap-from-menu' ),
        );
        ?>
        <select name="<?php echo esc_attr( self::OPTION_NAME ); ?>[list_style]" id="list_style">
            <?php foreach ( $styles as $value => $label ) : ?>
                <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $options['list_style'], $value ); ?>>
                    <?php echo esc_html( $label ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * インデントサイズフィールドを表示
     */
    public function render_indent_size_field() {
        $options = $this->get_options();
        ?>
        <input type="number" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[indent_size]" id="indent_size" value="<?php echo esc_attr( $options['indent_size'] ); ?>" min="0" max="100" step="5" />
        <span>px</span>
        <p class="description"><?php esc_html_e( '子メニューのインデント幅を指定します。', 'kashiwazaki-seo-sitemap-from-menu' ); ?></p>
        <?php
    }

    /**
     * ツールチップ表示フィールドを表示
     */
    public function render_show_tooltip_field() {
        $options = $this->get_options();
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[show_tooltip]" id="show_tooltip" value="1" <?php checked( $options['show_tooltip'], 1 ); ?> />
            <?php esc_html_e( 'ページのDescriptionをツールチップで表示する', 'kashiwazaki-seo-sitemap-from-menu' ); ?>
        </label>
        <?php
    }

    /**
     * カスタムCSSフィールドを表示
     */
    public function render_custom_css_field() {
        $options = $this->get_options();
        ?>
        <textarea name="<?php echo esc_attr( self::OPTION_NAME ); ?>[custom_css]" id="custom_css" rows="8" cols="60" class="large-text code"><?php echo esc_textarea( $options['custom_css'] ); ?></textarea>
        <p class="description"><?php esc_html_e( 'サイトマップに適用するカスタムCSSを入力してください。', 'kashiwazaki-seo-sitemap-from-menu' ); ?></p>
        <?php
    }

    /**
     * 管理ページを表示
     */
    public function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $options = $this->get_options();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'kashiwazaki_seo_sitemap_from_menu_group' );
                do_settings_sections( 'kashiwazaki-seo-sitemap-from-menu' );
                submit_button();
                ?>
            </form>

            <hr />

            <h2><?php esc_html_e( '使い方', 'kashiwazaki-seo-sitemap-from-menu' ); ?></h2>
            <p><?php esc_html_e( '以下のショートコードを固定ページや投稿に挿入してサイトマップを表示できます。', 'kashiwazaki-seo-sitemap-from-menu' ); ?></p>

            <h3><?php esc_html_e( '基本的な使い方', 'kashiwazaki-seo-sitemap-from-menu' ); ?></h3>
            <code>[menu_sitemap]</code>
            <p class="description"><?php esc_html_e( 'デフォルトメニューを表示します。', 'kashiwazaki-seo-sitemap-from-menu' ); ?></p>

            <h3><?php esc_html_e( 'メニュー名を指定', 'kashiwazaki-seo-sitemap-from-menu' ); ?></h3>
            <code>[menu_sitemap menu="main-menu"]</code>
            <p class="description"><?php esc_html_e( '指定したメニュー名（スラッグ）のサイトマップを表示します。', 'kashiwazaki-seo-sitemap-from-menu' ); ?></p>

            <h3><?php esc_html_e( 'メニューロケーションを指定', 'kashiwazaki-seo-sitemap-from-menu' ); ?></h3>
            <code>[menu_sitemap location="primary"]</code>
            <p class="description"><?php esc_html_e( '指定したテーマロケーションに割り当てられているメニューを表示します。', 'kashiwazaki-seo-sitemap-from-menu' ); ?></p>

            <h3><?php esc_html_e( '利用可能なメニュー', 'kashiwazaki-seo-sitemap-from-menu' ); ?></h3>
            <table class="widefat" style="max-width: 700px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'メニュー名', 'kashiwazaki-seo-sitemap-from-menu' ); ?></th>
                        <th><?php esc_html_e( 'ショートコード', 'kashiwazaki-seo-sitemap-from-menu' ); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $menus = wp_get_nav_menus();
                    if ( $menus ) :
                        foreach ( $menus as $menu ) :
                            $shortcode = '[menu_sitemap menu="' . esc_attr( $menu->slug ) . '"]';
                            ?>
                            <tr>
                                <td><?php echo esc_html( $menu->name ); ?></td>
                                <td><code id="menu-shortcode-<?php echo esc_attr( $menu->slug ); ?>"><?php echo esc_html( $shortcode ); ?></code></td>
                                <td><button type="button" class="button button-small kashiwazaki-copy-btn" data-target="menu-shortcode-<?php echo esc_attr( $menu->slug ); ?>"><?php esc_html_e( 'コピー', 'kashiwazaki-seo-sitemap-from-menu' ); ?></button></td>
                            </tr>
                            <?php
                        endforeach;
                    else :
                        ?>
                        <tr>
                            <td colspan="3"><?php esc_html_e( 'メニューが登録されていません。', 'kashiwazaki-seo-sitemap-from-menu' ); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <h3><?php esc_html_e( '利用可能なメニューロケーション', 'kashiwazaki-seo-sitemap-from-menu' ); ?></h3>
            <table class="widefat" style="max-width: 700px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'ロケーション名', 'kashiwazaki-seo-sitemap-from-menu' ); ?></th>
                        <th><?php esc_html_e( 'ショートコード', 'kashiwazaki-seo-sitemap-from-menu' ); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $locations = get_registered_nav_menus();
                    if ( $locations ) :
                        foreach ( $locations as $location_id => $location_name ) :
                            $shortcode = '[menu_sitemap location="' . esc_attr( $location_id ) . '"]';
                            ?>
                            <tr>
                                <td><?php echo esc_html( $location_name ); ?></td>
                                <td><code id="location-shortcode-<?php echo esc_attr( $location_id ); ?>"><?php echo esc_html( $shortcode ); ?></code></td>
                                <td><button type="button" class="button button-small kashiwazaki-copy-btn" data-target="location-shortcode-<?php echo esc_attr( $location_id ); ?>"><?php esc_html_e( 'コピー', 'kashiwazaki-seo-sitemap-from-menu' ); ?></button></td>
                            </tr>
                            <?php
                        endforeach;
                    else :
                        ?>
                        <tr>
                            <td colspan="3"><?php esc_html_e( 'メニューロケーションが登録されていません。', 'kashiwazaki-seo-sitemap-from-menu' ); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.kashiwazaki-copy-btn').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var targetId = this.getAttribute('data-target');
                        var target = document.getElementById(targetId);
                        if (target) {
                            navigator.clipboard.writeText(target.textContent).then(function() {
                                btn.textContent = '<?php esc_html_e( 'コピー済', 'kashiwazaki-seo-sitemap-from-menu' ); ?>';
                                setTimeout(function() {
                                    btn.textContent = '<?php esc_html_e( 'コピー', 'kashiwazaki-seo-sitemap-from-menu' ); ?>';
                                }, 2000);
                            });
                        }
                    });
                });
            });
            </script>
        </div>
        <?php
    }

    /**
     * フロントエンドスタイルを読み込み
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            'kashiwazaki-seo-sitemap-from-menu',
            plugin_dir_url( __FILE__ ) . 'assets/css/sitemap.css',
            array(),
            self::VERSION
        );

        $options = $this->get_options();
        if ( ! empty( $options['custom_css'] ) ) {
            wp_add_inline_style( 'kashiwazaki-seo-sitemap-from-menu', $options['custom_css'] );
        }
    }

    /**
     * ショートコードを処理
     */
    public function render_sitemap_shortcode( $atts ) {
        $options = $this->get_options();

        $atts = shortcode_atts(
            array(
                'menu'     => '',
                'location' => '',
            ),
            $atts,
            'menu_sitemap'
        );

        $menu_items = null;

        // ロケーションが指定されている場合
        if ( ! empty( $atts['location'] ) ) {
            $locations = get_nav_menu_locations();
            if ( isset( $locations[ $atts['location'] ] ) ) {
                $menu_items = wp_get_nav_menu_items( $locations[ $atts['location'] ] );
            }
        }
        // メニュー名が指定されている場合
        elseif ( ! empty( $atts['menu'] ) ) {
            $menu_items = wp_get_nav_menu_items( $atts['menu'] );
        }
        // デフォルトメニューを使用
        elseif ( ! empty( $options['default_menu'] ) ) {
            $menu_items = wp_get_nav_menu_items( $options['default_menu'] );
        }

        if ( empty( $menu_items ) ) {
            return '<p class="kashiwazaki-seo-sitemap-from-menu-error">' . esc_html__( 'メニューが見つかりません。', 'kashiwazaki-seo-sitemap-from-menu' ) . '</p>';
        }

        $output = '<nav class="kashiwazaki-seo-sitemap-from-menu" aria-label="' . esc_attr__( 'サイトマップ', 'kashiwazaki-seo-sitemap-from-menu' ) . '">';

        // ディレクトリツリーモードで、ホーム項目をルートとして扱う処理
        if ( 'tree' === $options['list_style'] ) {
            $home_url = home_url();
            $front_page_id = (int) get_option( 'page_on_front' );
            $home_item = null;
            $home_key = null;

            // ホーム項目を探す
            foreach ( $menu_items as $key => $item ) {
                $is_home = false;

                // 1. フロントページIDとの比較（固定ページの場合）
                if ( 'post_type' === $item->type && (int) $item->object_id === $front_page_id && $front_page_id > 0 ) {
                    $is_home = true;
                }
                // 2. URLでの比較（カスタムリンクの場合）
                else {
                    $item_url = untrailingslashit( $item->url );
                    $home_url_norm = untrailingslashit( $home_url );

                    if ( $item_url === $home_url_norm ) {
                        $is_home = true;
                    }
                    // 相対パス '/' の場合（サイトがルートにあると仮定）
                    elseif ( '/' === $item->url && ( empty( parse_url( $home_url, PHP_URL_PATH ) ) || '/' === parse_url( $home_url, PHP_URL_PATH ) ) ) {
                        $is_home = true;
                    }
                }

                if ( $is_home ) {
                    $home_item = $item;
                    $home_key = $key;
                    break;
                }
            }

            // ホーム項目が見つかった場合
            if ( $home_item ) {
                // リストから削除
                unset( $menu_items[ $home_key ] );

                // ルートノードとして出力
                $output .= '<div class="kashiwazaki-seo-sitemap-from-menu-tree-item kashiwazaki-seo-sitemap-from-menu-tree-root">';
                // ルートアイコン（フォルダまたは家）
                $output .= '<span class="kashiwazaki-seo-sitemap-from-menu-prefix"></span>'; 
                $output .= '<a href="' . esc_url( $home_item->url ) . '"><strong>' . esc_html( $home_item->title ) . '</strong></a>';
                
                if ( $options['show_tooltip'] ) {
                    $description = $this->get_page_description( $home_item );
                    if ( ! empty( $description ) ) {
                        $output .= '<span class="kashiwazaki-seo-sitemap-from-menu-tooltip"><span class="kashiwazaki-seo-sitemap-from-menu-tooltip-icon">ℹ</span><span class="kashiwazaki-seo-sitemap-from-menu-tooltip-text">' . esc_html( $description ) . '</span></span>';
                    }
                }
                $output .= '</div>';
            }
        }

        // 階層構造を構築
        $menu_tree = $this->build_menu_tree( $menu_items );

        // HTMLを生成
        $output .= $this->render_menu_tree( $menu_tree, 0, $options );
        $output .= '</nav>';

        return $output;
    }

    /**
     * ページのDescriptionを取得
     */
    private function get_page_description( $item ) {
        // メニューアイテムにDescriptionがあればそれを使用
        if ( ! empty( $item->description ) ) {
            return $item->description;
        }

        // 投稿・固定ページの場合、meta descriptionまたは抜粋を取得
        if ( 'post_type' === $item->type && ! empty( $item->object_id ) ) {
            $post_id = $item->object_id;

            // Yoast SEO
            $yoast_desc = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );
            if ( ! empty( $yoast_desc ) ) {
                return $yoast_desc;
            }

            // All in One SEO
            $aioseo_desc = get_post_meta( $post_id, '_aioseo_description', true );
            if ( ! empty( $aioseo_desc ) ) {
                return $aioseo_desc;
            }

            // Rank Math
            $rankmath_desc = get_post_meta( $post_id, 'rank_math_description', true );
            if ( ! empty( $rankmath_desc ) ) {
                return $rankmath_desc;
            }

            // SEOPress
            $seopress_desc = get_post_meta( $post_id, '_seopress_titles_desc', true );
            if ( ! empty( $seopress_desc ) ) {
                return $seopress_desc;
            }

            // 抜粋
            $post = get_post( $post_id );
            if ( $post && ! empty( $post->post_excerpt ) ) {
                return $post->post_excerpt;
            }
        }

        return '';
    }

    /**
     * メニューアイテムから階層構造を構築
     */
    private function build_menu_tree( $menu_items, $parent_id = 0 ) {
        $tree = array();

        foreach ( $menu_items as $item ) {
            if ( (int) $item->menu_item_parent === $parent_id ) {
                $children = $this->build_menu_tree( $menu_items, (int) $item->ID );
                $tree[] = array(
                    'item'     => $item,
                    'children' => $children,
                );
            }
        }

        return $tree;
    }

    /**
     * メニューツリーをHTMLで出力
     */
    private function render_menu_tree( $tree, $depth, $options, $prefix = '' ) {
        if ( empty( $tree ) ) {
            return '';
        }

        // ディレクトリツリーモード
        if ( 'tree' === $options['list_style'] ) {
            return $this->render_menu_tree_directory( $tree, $depth, $options, $prefix );
        }

        // 通常のリストモード
        return $this->render_menu_tree_list( $tree, $depth, $options );
    }

    /**
     * 通常のリスト形式で出力
     */
    private function render_menu_tree_list( $tree, $depth, $options ) {
        if ( empty( $tree ) ) {
            return '';
        }

        $indent = $depth > 0 ? ' style="margin-left: ' . esc_attr( $options['indent_size'] ) . 'px;"' : '';
        $list_style = ' style="list-style-type: ' . esc_attr( $options['list_style'] ) . ';"';

        $output = '<ul class="kashiwazaki-seo-sitemap-from-menu-list kashiwazaki-seo-sitemap-from-menu-level-' . esc_attr( $depth ) . '"' . $indent . $list_style . '>';

        foreach ( $tree as $node ) {
            $item = $node['item'];
            $has_children = ! empty( $node['children'] );

            $output .= '<li class="kashiwazaki-seo-sitemap-from-menu-item' . ( $has_children ? ' kashiwazaki-seo-sitemap-from-menu-has-children' : '' ) . '">';

            $output .= '<a href="' . esc_url( $item->url ) . '">' . esc_html( $item->title ) . '</a>';

            if ( $options['show_tooltip'] ) {
                $description = $this->get_page_description( $item );
                if ( ! empty( $description ) ) {
                    $output .= '<span class="kashiwazaki-seo-sitemap-from-menu-tooltip"><span class="kashiwazaki-seo-sitemap-from-menu-tooltip-icon">ℹ</span><span class="kashiwazaki-seo-sitemap-from-menu-tooltip-text">' . esc_html( $description ) . '</span></span>';
                }
            }

            if ( $has_children ) {
                $output .= $this->render_menu_tree_list( $node['children'], $depth + 1, $options );
            }

            $output .= '</li>';
        }

        $output .= '</ul>';

        return $output;
    }

    /**
     * ディレクトリツリー形式で出力
     */
    private function render_menu_tree_directory( $tree, $depth, $options, $prefix = '' ) {
        if ( empty( $tree ) ) {
            return '';
        }

        $output = '';
        $count = count( $tree );
        $index = 0;

        foreach ( $tree as $node ) {
            $index++;
            $item = $node['item'];
            $has_children = ! empty( $node['children'] );
            $is_last = ( $index === $count );

            // ツリー記号
            $branch = $is_last ? '└── ' : '├── ';

            $output .= '<div class="kashiwazaki-seo-sitemap-from-menu-tree-item">';
            $output .= '<span class="kashiwazaki-seo-sitemap-from-menu-prefix">' . esc_html( $prefix . $branch ) . '</span>';

            $output .= '<a href="' . esc_url( $item->url ) . '">' . esc_html( $item->title ) . '</a>';

            if ( $options['show_tooltip'] ) {
                $description = $this->get_page_description( $item );
                if ( ! empty( $description ) ) {
                    $output .= '<span class="kashiwazaki-seo-sitemap-from-menu-tooltip"><span class="kashiwazaki-seo-sitemap-from-menu-tooltip-icon">ℹ</span><span class="kashiwazaki-seo-sitemap-from-menu-tooltip-text">' . esc_html( $description ) . '</span></span>';
                }
            }

            $output .= '</div>';

            if ( $has_children ) {
                // 子要素用のプレフィックス
                $child_prefix = $prefix . ( $is_last ? '    ' : '│   ' );
                $output .= $this->render_menu_tree_directory( $node['children'], $depth + 1, $options, $child_prefix );
            }
        }

        return $output;
    }
}

// プラグインを初期化
Kashiwazaki_SEO_Sitemap_From_Menu::get_instance();
