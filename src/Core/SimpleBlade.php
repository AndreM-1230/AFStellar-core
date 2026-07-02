<?php
namespace App\Core;
class SimpleBlade
{
    protected static $sections = [];
    protected static $currentSection = null;
    protected static $layout = null;
    protected static $stacks = [];

    private static function parseComponents($template)
    {
        $pattern = '/<x-([a-z0-9_-]+)\s*(.*?)\/?>/i';
        return preg_replace_callback($pattern, function($matches) {
            $componentName = $matches[1];
            $attributesString = $matches[2];
            $attributes = [];
            preg_match_all('/(\w+)="([^"]*)"/', $attributesString, $attrMatches);
            if (!empty($attrMatches[1])) {
                $attributes = array_combine($attrMatches[1], $attrMatches[2]);
            }
            $filePath = 'app/Views/components/' . $componentName . '.php';
            if (file_exists($filePath)) {
                extract($attributes);
                ob_start();
                include $filePath;
                return ob_get_clean();
            }
            return "<!-- Компонент {$componentName} не найден -->";
        }, $template);
    }

    public static function render($file, $data = [])
    {
        extract($data);
        self::$stacks = [];
        $cacheFile = 'app/cache/' . md5($file) . '.php';
        if (!file_exists($cacheFile) || filemtime($file) > filemtime($cacheFile)) {
            $compilied = self::compile($file);
            file_put_contents($cacheFile, $compilied);
        }
        include $cacheFile;
        if (self::$layout) {
            $layoutName = str_replace('.', '/', self::$layout);
            $layoutName = str_replace(['"', "'"], '', $layoutName);
            $layoutSource = __DIR__ . "/../Views/{$layoutName}.view.php";
            $layoutCache = 'app/cache/' . md5($layoutSource) . '.php';
            if (!file_exists($layoutCache) || filemtime($layoutSource) > filemtime($layoutCache)) {
                file_put_contents($layoutCache, self::compile($layoutSource));
            }
            include $layoutCache;
        }
    }

    public static function compile($file)
    {
        $content = file_get_contents($file);
        $content = self::parseComponents($content);
        $content = str_replace('@endsection', '<?php App\Core\SimpleBlade::endSection(); ?>', $content);
        $content = preg_replace_callback('/@if\s*\((.+)\)/', function($matches) {
            $expr = $matches[1];
            return '<?php if (' . $expr . '): ?>';
        }, $content);
        $content = preg_replace_callback('/@elseif\s*\((.+)\)/', function($matches) {
            $expr = $matches[1];
            return '<?php elseif (' . $expr . '): ?>';
        }, $content);
        $content = preg_replace_callback('/@foreach\s*\((.+)\)/', function($matches) {
            $expr = $matches[1];
            return '<?php foreach (' . $expr . '): ?>';
        }, $content);
        $content = preg_replace_callback('/@for\s*\((.+)\)/', function($matches) {
            $expr = $matches[1];
            return '<?php for (' . $expr . '): ?>';
        }, $content);
        $replacements = [
            '/@url/' => _URL,
            '/@php/' => '<?php',
            '/@endphp/' => '?>',
            '/@extends\s*\((.*?)\)/' => '<?php App\Core\SimpleBlade::setExtends("$1") ?>',
            '/@section\s*\((.*?)\)/' => '<?php App\Core\SimpleBlade::startSection("$1") ?>',
            '/@yield\s*\((.*?)\)/' => '<?php App\Core\SimpleBlade::yieldContent("$1") ?>',
            '/@include\s*\((.*?)\)/' => '<?php include App\Core\SimpleBlade::includeContent("$1") ?>',
            '/@endif/' => '<?php endif; ?>',
            '/@endforeach/' => '<?php endforeach; ?>',
            '/@endfor/' => '<?php endfor; ?>',
            '/@else/' => '<?php else: ?>',
            '/{{\s*(.*?)\s*}}/' => '<?php echo htmlspecialchars($1), ENT_QUOTES, "UTF-8"); ?>'
            
        ];
        $content = preg_replace('/\{\{\s*(.*?)\s*\}\}/', '<?php echo htmlspecialchars($1, ENT_QUOTES); ?>', $content);
        $content = preg_replace(array_keys($replacements), array_values($replacements), $content);
        $content = preg_replace_callback('/@push\(\'([a-z0-9_-]+)\'\)(.*?)@endpush/s', function($matches) {
            $name = $matches[1];
            $code = $matches[2];
            return "<?php ob_start(); ?>$code<?php App\Core\SimpleBlade::push('$name', ob_get_clean()); ?>";
        }, $content);
        $content = preg_replace_callback('/@stack\(\'([a-z0-9_-]+)\'\)/', function($matches) {
            $name = $matches[1];
            return "<?php echo App\Core\SimpleBlade::stack('$name'); ?>";
        }, $content);
        return $content;
    }

    public static function setExtends($layout)
    {
        self::$layout = $layout;
    }

    public static function startSection($name)
    {
        self::$currentSection = $name;
        ob_start();
    }

    public static function includeContent($name)
    {
        $path = str_replace('.', '/', $name);
        $path = __DIR__ . "/../Views/{$path}.view.php";
        $cacheFile = 'app/cache/' . md5($path) . '.php';
        if (!file_exists($cacheFile) || filemtime($path) > filemtime($cacheFile)) {
            file_put_contents($cacheFile, self::compile($path));
        }
        return $cacheFile;
    }

    public static function endSection()
    {
        self::$sections[self::$currentSection] = ob_get_clean();
        self::$currentSection = null;
    }

    public static function yieldContent($name)
    {
        echo self::$sections[$name] ?? '';
    }

    public static function push($name, $content)
    {
        if (!isset(self::$stacks[$name])) {
            self::$stacks[$name] = [];
        }
        self::$stacks[$name][] = $content;
    }

    public static function stack($name)
    {
        if (!isset(self::$stacks[$name])) {
            return '';
        }
        return implode("\n", self::$stacks[$name]);
    }
}
