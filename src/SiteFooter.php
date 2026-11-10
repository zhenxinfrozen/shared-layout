<?php
/**
 * SharedLayout\SiteFooter - 可挂载的站点底部组件 (logo + brand + description + nav + copyright)
 *
 * 与 SiteHeader 同一思路: 组件独立存放, 各项目自行 require 并传入配置,
 * 样式/结构由组件统一维护, 保证多项目底部一致。
 *
 * 用法:
 *   require_once <root>/shared-layout/src/SiteFooter.php;
 *   echo \SharedLayout\SiteFooter::render([
 *       'brand' => '颓废动画人',
 *       'description' => ['This site is a portfolio of Ray.', 'Hope you enjoy it!'],
 *       'nav' => ['首页 HOME' => '/', '作者 ABOUT' => '/about'],
 *       'copyright' => '© 2009-2026 RAY RUI. ALL RIGHTS RESERVED.',
 *   ]);
 *
 * 样式文件: shared-layout/assets/css/footer.css
 */

namespace SharedLayout;

class SiteFooter
{
    /**
     * 渲染底部 HTML
     *
     * @param array $config {
     *   brand        string   站点名称 (粗体大字), 选填, 默认 ''
     *   logo         string   站点图标 URL, 选填; 未传或文件缺失时回退套件内置 default-logo.svg
     *   logo_file    string   logo 磁盘路径(存在性检查), 选填
     *   description  array|string   站点描述 (小字, 数组按行渲染), 选填, 默认 []
     *   nav          array    底部导航: 'Label' => '/href', 选填, 默认 []
     *   copyright    string   版权行 (底部居中), 选填, 默认 ''
     *   powered_by   string   "Powered by ..." (版权行右侧), 选填, 默认 ''
     *   home_url     string   brand 点击跳转, 默认 '/'
     *   url_rewriter callable 链接改写函数, 默认 null
     *   assets_url   string   静态资源 URL 前缀, 默认 '/shared-layout/assets'
     *   assets_path  string   静态资源磁盘路径, 默认组件自带 assets 目录
     * }
     */
    public static function render(array $config = []): string
    {
        $brand       = $config['brand'] ?? '';
        $logo        = $config['logo'] ?? ($config['avatar'] ?? null);
        $logoFile    = $config['logo_file'] ?? ($config['avatar_file'] ?? null);
        $description = $config['description'] ?? [];
        if (is_string($description)) {
            $description = $description === '' ? [] : [$description];
        }
        $nav         = $config['nav'] ?? [];
        $copyright   = $config['copyright'] ?? '';
        $poweredBy   = $config['powered_by'] ?? '';
        $homeUrl     = $config['home_url'] ?? '/';
        $urlRewriter = $config['url_rewriter'] ?? null;

        $assetsUrl  = rtrim($config['assets_url'] ?? '/shared-layout/assets', '/');
        $assetsPath = rtrim($config['assets_path'] ?? (__DIR__ . '/../assets'), '/\\');

        // 内置默认 logo 退守: 未传入, 或传入的磁盘文件不存在时, 回退套件自带 default-logo.svg
        if (!$logo || (!empty($logoFile) && !is_file($logoFile))) {
            $defaultLogoFile = $assetsPath . '/images/default-logo.svg';
            $logo = is_file($defaultLogoFile) ? $assetsUrl . '/images/default-logo.svg' : null;
        }

        $buildUrl = function ($href) use ($urlRewriter) {
            return $urlRewriter ? call_user_func($urlRewriter, $href) : $href;
        };

        ob_start();
        ?>
<footer class="site-footer" role="contentinfo">
    <div class="site-footer__inner">
        <?php if ($logo || $brand !== '' || !empty($description)): ?>
        <div class="site-footer__brand">
            <?php if ($logo): ?>
                <img class="site-footer__logo" src="<?php echo self::e($logo); ?>" alt="<?php echo self::e($brand !== '' ? $brand : 'brand'); ?> logo">
            <?php endif; ?>
            <div class="site-footer__text">
                <?php if ($brand !== ''): ?>
                    <a class="site-footer__name" href="<?php echo self::e($buildUrl($homeUrl)); ?>"><?php echo self::e($brand); ?></a>
                <?php endif; ?>
                <?php if (!empty($description)): ?>
                    <p class="site-footer__desc">
                        <?php foreach ($description as $line): ?>
                            <?php echo self::e($line); ?><br>
                        <?php endforeach; ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($nav)): ?>
        <nav class="site-footer__nav" role="navigation" aria-label="Footer">
            <?php foreach ($nav as $label => $href): ?>
                <a class="site-footer__navlink" href="<?php echo self::e($buildUrl($href)); ?>"><?php echo self::e($label); ?></a>
            <?php endforeach; ?>
        </nav>
        <?php endif; ?>
    </div>

    <?php if ($copyright !== '' || $poweredBy !== ''): ?>
    <div class="site-footer__bottom">
        <?php if ($copyright !== ''): ?>
            <span class="site-footer__copy"><?php echo self::e($copyright); ?></span>
        <?php endif; ?>
        <?php if ($poweredBy !== ''): ?>
            <span class="site-footer__powered"><?php echo self::e($poweredBy); ?></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</footer>
<?php
        $html = ob_get_clean();

        // 样式 (filemtime 缓存破击)
        $cssFile = $assetsPath . '/css/footer.css';
        if (is_file($cssFile)) {
            $html .= '<link rel="stylesheet" href="' . self::e($assetsUrl . '/css/footer.css?v=' . filemtime($cssFile)) . '">' . PHP_EOL;
        }

        return $html;
    }

    private static function e($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
