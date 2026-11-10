# shared-layout

可挂载的站点公共布局组件（当前：顶部菜单栏 logo + 站点名 + 菜单；底部 footer logo + brand + nav + 版权），
与 `shared-admin` 同一思路：样式与结构由本组件统一维护，各项目自行 require 并传入配置即可接入。

**组件本身不假设任何品牌**：`brand` / `tagline` / `nav` 全部由接入方传入，默认值只有
`home_url='/'`、`fixed=true`、`nav=[]` 这类与品牌无关的通用约定。`logo` 未传或传入的磁盘文件
缺失时，回退套件内置的中性占位图 `assets/images/default-logo.svg`（不含任何品牌标识）。

## 结构

```
shared-layout/
├── src/SiteHeader.php        # 顶部菜单栏 \SharedLayout\SiteHeader
├── src/SiteFooter.php       # 底部组件 \SharedLayout\SiteFooter
├── assets/css/header.css     # 顶部样式
├── assets/css/footer.css     # 底部样式
└── assets/images/default-logo.svg  # 内置缺省 logo 退守图
```

## 接入方式（中性示例）

```php
require_once <root>/shared-layout/src/SiteHeader.php;

echo \SharedLayout\SiteHeader::render([
    // brand 区域三个变量 (自由组合, 都为空则不输出 brand 块):
    //   logo    = 站点图标 (显示在最左)
    //   brand   = 站点名称 (如 'RZX.ME')
    //   tagline = 副标题/slogan (如 '颓废动画人', 自动用竖线与 brand 分隔)
    'logo'    => '/assets/avatar.jpg',
    'logo_file' => __DIR__ . '/public/assets/avatar.jpg',
    'brand'   => 'MY-SITE',
    'tagline' => '',
    // 品牌 logo 点击后跳哪里。子项目接入时传自己的首页路径, 例如 '/lab/' 或 './index.html'
    'home_url' => '/',
    // 菜单: 每个接入项目自己决定 Home 指向哪里、有哪些项
    'nav' => [
        'Home'  => '/',
        'About' => '/about',
        // 下拉子菜单:
        'Projects' => [
            'href'     => '/projects/',
            'children' => ['alpha' => '/projects/alpha/'],
        ],
    ],
]);
```

常用配置项：`brand` / `tagline` / `badges` / `logo` / `logo_file` / `home_url` /
`nav` / `active_map` / `current_path` / `url_rewriter` / `assets_url` /
`assets_path` / `fixed`。详见 `SiteHeader::render()` 注释。

> 旧名 `avatar` / `avatar_file` 仍兼容, 但新接入建议用 `logo` / `logo_file`。
>
> `logo` 选填：不传、或 `logo_file` 指向的磁盘文件不存在时，组件自动回退内置
> `default-logo.svg`；接入方传了有效项目图则优先用项目图。

### Badges 徽章（git 分支 / 版本号等）

`badges` 是字符串数组，会在 brand 名称旁边渲染成灰色小胶囊。适合显示 git 分支、版本号等开发状态：

```php
echo \SharedLayout\SiteHeader::render([
    'brand'  => 'GeminiArchives',
    'badges' => ['v1.0.0', 'dev'],  // 两个灰色小徽章
    'nav'    => [...],
]);
```

接入项目自己负责获取 git 分支（如调用 `git rev-parse --abbrev-ref HEAD`），然后传给组件。
组件本身不内置 git 逻辑，保持通用性。

## SiteFooter 接入（中性示例）

```php
require_once <root>/shared-layout/src/SiteFooter.php;

echo \SharedLayout\SiteFooter::render([
    'logo'       => '/assets/avatar.jpg',
    'brand'      => 'MY-SITE',
    'description' => [
        'This site is a portfolio of Ray.',
        'Hope you enjoy it!',
    ],
    'nav' => [
        '首页 HOME'    => '/',
        '博客 BLOG'    => '/blog',
        '作者 ABOUT'   => '/about',
    ],
    'copyright'  => '© 2009-2026 RAY RUI. ALL RIGHTS RESERVED.',
    'powered_by' => 'Powered by something',
]);
```

`description` 支持数组（每行一个 `<br>`）或单字符串。`nav` / `copyright` / `powered_by` 均选填，
不传则对应块不渲染。

## 换肤（覆盖 CSS 变量）

组件在 `:root` 暴露了一组颜色变量，接入项目在自己的 CSS 里覆盖即可换色，**不要改
`shared-layout/assets/css/header.css` 本身**：

```css
:root{
    --site-bg:rgba(255,255,255,0.5); /* header 背景 (半透明, 配合 fixed 毛玻璃) */
    --site-bg-solid:#fff;             /* 下拉菜单/浮层背景 (不透明) */
    --accent:#333;                    /* 主文字色 */
    --muted:#666;                     /* 次要文字色 */
    --header-height:72px;
}
```

> 默认 `--site-bg` 是 50% 透明白, 配合 `.site-header.fixed` 的 `backdrop-filter:blur(10px)`
> 实现毛玻璃效果。如果接入项目不需要毛玻璃, 把 `--site-bg` 改成 `#fff` 即可。

## 静态资源

主仓库通过 junction 暴露样式文件：

- `public/shared-layout` → `<root>/shared-layout`
- `.htaccess` 放行规则: `RewriteRule ^shared-layout/ - [L]`

## 现有接入方

以下是真实接入示例，仅供参考，不代表组件的默认配置：

- **rzx.me 主站**: `app/Views/layouts/header.php`（`brand='RZX.ME'`、`tagline='颓废动画人'`、
  传 `url_rewriter => 'url'` 以支持非伪静态模式）
- **RayLab**: `lab/lab-plugin/raylab-header.php`（同主站菜单 + `LAB 项目` 下拉子菜单，
  兼容 junction 双路径）
- **ray-gemini-ai**: `src/layout/raylab-header.php`（`brand='GeminiArchives'`、
  `badges` 传入 git 分支 + 自定义版本号）
- **test-arterwer-static**（静态 HTML 测试页）: 通过 junction 挂载 `shared-layout/`，
  手写一份 SiteHeader 输出的镜像 HTML，用于验证组件在非 PHP 项目里的视觉效果

## 路线图

- [x] ~~footer 组件化（`\SharedLayout\SiteFooter`）~~ 已落地
- [ ] 更多布局组件（如侧边栏、面包屑）视需要补充
