<?php
/**
 * SharedLayout\SiteHeader - 可挂载的站点顶部菜单栏组件 (logo + 站点名 + 菜单)
 *
 * 与 shared-admin 同一思路: 组件独立存放, 各项目自行 require 并传入配置,
 * 样式/结构由组件统一维护, 保证多项目顶部菜单一致。
 *
 * 用法:
 *   require_once <root>/shared-layout/src/SiteHeader.php;
 *   echo \SharedLayout\SiteHeader::render([
 *       'brand' => 'RZX.ME',
 *       'nav'   => ['Home' => '/', 'About' => '/about'],
 *   ]);
 *
 * 样式文件: shared-layout/assets/css/header.css
 *   通过 <root>/public/shared-layout 联接(junction)暴露为 /shared-layout/assets/...
 */

namespace SharedLayout;

class SiteHeader
{
    /**
     * 渲染顶部菜单栏 HTML
     *
     * @param array $config {
     *   brand        string   站点名称, 默认 'RZX.ME'
     *   tagline      string   品牌右侧标语, 默认 ''
     *   home_url     string   品牌链接, 默认 '/'
     *   avatar       string   头像 URL, 默认 null (不显示)
     *   avatar_file  string   头像对应的磁盘路径(用于存在性检查), 默认 null (不检查)
     *   nav          array    菜单: 'Label' => '/href'
     *                         或 'Label' => ['href' => '/href', 'children' => ['子项' => '/href', ...]]
     *   active_map   array    'Label' => [额外视为激活的路径前缀...]
     *   current_path string   当前路径, 默认自动检测 (兼容 ?route= 模式)
     *   url_rewriter callable 链接改写函数(如主站的 url()), 默认 null (原样输出)
     *   assets_url   string   静态资源 URL 前缀, 默认 '/shared-layout/assets'
     *   assets_path  string   静态资源磁盘路径, 默认组件自带 assets 目录
     *   fixed        bool     是否置顶(pin)并启用滚动样式, 默认 true
     * }
     */
    public static function render(array $config = []): string
    {
        $brand    = $config['brand'] ?? 'RZX.ME';
        $tagline  = $config['tagline'] ?? '';
        $homeUrl  = $config['home_url'] ?? '/';
        $avatar   = $config['avatar'] ?? null;
        $nav      = $config['nav'] ?? [];
        $fixed    = $config['fixed'] ?? true;
        $urlRewriter = $config['url_rewriter'] ?? null;
        $activeMap   = $config['active_map'] ?? [];
        $currentPath = $config['current_path'] ?? self::detectCurrentPath();

        if ($avatar && !empty($config['avatar_file']) && !is_file($config['avatar_file'])) {
            $avatar = null;
        }

        $assetsUrl  = rtrim($config['assets_url'] ?? '/shared-layout/assets', '/');
        $assetsPath = rtrim($config['assets_path'] ?? (__DIR__ . '/../assets'), '/\\');

        $buildUrl = function ($href) use ($urlRewriter) {
            return $urlRewriter ? call_user_func($urlRewriter, $href) : $href;
        };

        ob_start();
        ?>
<header class="site-header" role="banner">
    <div class="site-header__inner">
        <a class="site-brand" href="<?php echo self::e($buildUrl($homeUrl)); ?>">
            <?php if ($avatar): ?>
                <img src="<?php echo self::e($avatar); ?>" alt="<?php echo self::e($brand); ?> avatar">
            <?php endif; ?>
            <?php echo self::e($brand); ?><?php if ($tagline !== ''): ?> <span class="site-tagline"><?php echo self::e($tagline); ?></span><?php endif; ?>
        </a>

        <input id="nav-toggle" class="nav-toggle" type="checkbox" aria-hidden="true">
        <label for="nav-toggle" class="nav-toggle-label" aria-hidden="true">
            <span aria-hidden="true"></span>
        </label>

        <nav class="site-nav" role="navigation" aria-label="Primary">
            <ul class="site-nav__list">
                <?php foreach ($nav as $label => $item):
                    list($href, $children) = self::normalizeItem($item);
                    $isActive = self::isActivePath($currentPath, $href, $activeMap[$label] ?? []);
                    if (!$isActive && $children) {
                        foreach ($children as $childItem) {
                            list($childHref) = self::normalizeItem($childItem);
                            if (self::isActivePath($currentPath, $childHref)) {
                                $isActive = true;
                                break;
                            }
                        }
                    }
                    $linkClass = 'site-nav__link' . ($isActive ? ' site-nav__link--active' : '');
                ?>
                <?php if ($children): ?>
                <li class="site-nav__item site-nav__item--has-children">
                    <a class="<?php echo $linkClass; ?>" href="<?php echo self::e($buildUrl($href)); ?>"><?php echo self::e($label); ?></a>
                    <ul class="site-nav__sub">
                        <?php foreach ($children as $childLabel => $childItem):
                            list($childHref) = self::normalizeItem($childItem);
                            $childClass = 'site-nav__sublink' . (self::isActivePath($currentPath, $childHref) ? ' site-nav__sublink--active' : '');
                        ?>
                        <li><a class="<?php echo $childClass; ?>" href="<?php echo self::e($buildUrl($childHref)); ?>"><?php echo self::e($childLabel); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <?php else: ?>
                <li class="site-nav__item"><a class="<?php echo $linkClass; ?>" href="<?php echo self::e($buildUrl($href)); ?>"><?php echo self::e($label); ?></a></li>
                <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </nav>
    </div>
</header>
<?php
        $html = ob_get_clean();

        // 样式 (filemtime 缓存破击)
        $cssFile = $assetsPath . '/css/header.css';
        if (is_file($cssFile)) {
            $html .= '<link rel="stylesheet" href="' . self::e($assetsUrl . '/css/header.css?v=' . filemtime($cssFile)) . '">' . PHP_EOL;
        }

        if ($fixed) {
            $html .= self::fixedScript();
        }

        return $html;
    }

    /** 菜单项归一化: 字符串 => [href, []], 数组 => [href, children] */
    private static function normalizeItem($item): array
    {
        if (is_array($item)) {
            return [$item['href'] ?? '#', $item['children'] ?? []];
        }
        return [(string)$item, []];
    }

    /** 当前路径是否命中菜单项 (含额外前缀) */
    private static function isActivePath(string $currentPath, string $href, array $extraPrefixes = []): bool
    {
        foreach (array_merge([$href], $extraPrefixes) as $candidate) {
            if ($candidate === '') {
                continue;
            }
            if ($candidate === '/') {
                if ($currentPath === '/') {
                    return true;
                }
                continue;
            }
            $candidate = rtrim($candidate, '/');
            if ($currentPath === $candidate || strpos($currentPath, $candidate . '/') === 0) {
                return true;
            }
        }
        return false;
    }

    /** 自动检测当前逻辑路径 (兼容主站 ?route= 模式) */
    private static function detectCurrentPath(): string
    {
        $currentPath = $_SERVER['REQUEST_URI'] ?? '/';
        if (isset($_GET['route'])) {
            $currentPath = $_GET['route'];
        } else {
            $parsed = parse_url($currentPath, PHP_URL_PATH);
            $currentPath = ($parsed && $parsed !== '/') ? rtrim($parsed, '/') : ($parsed ?: '/');
        }
        if (strpos($currentPath, '/') !== 0) {
            $currentPath = '/' . $currentPath;
        }
        return $currentPath;
    }

    /** 置顶 + 滚动样式脚本 */
    private static function fixedScript(): string
    {
        return <<<'HTML'
<script>
// header scroll script
(function(){
    var header = document.querySelector('.site-header');
    if (!header) return;
    header.classList.add('fixed');
    document.body.classList.add('has-fixed-header');
    var ticking = false;
    function onScroll(){
        if (!ticking){
            window.requestAnimationFrame(function(){
                var y = window.scrollY || window.pageYOffset;
                if (y > 10){
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }
                ticking = false;
            });
            ticking = true;
        }
    }
    window.addEventListener('scroll', onScroll, {passive:true});
    onScroll();
})();
</script>
HTML;
    }

    private static function e($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
