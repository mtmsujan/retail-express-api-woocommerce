<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit8e31147f1302db4a6e594d13dbd658c4
{
    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'WooCommerce\\' => 12,
        ),
        'S' => 
        array (
            'Shortcodes\\' => 11,
        ),
        'D' => 
        array (
            'Database\\' => 9,
        ),
        'A' => 
        array (
            'AdminPage\\' => 10,
            'API\\' => 4,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'WooCommerce\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src/WooCommerce',
        ),
        'Shortcodes\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src/Shortcodes',
        ),
        'Database\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src/Database',
        ),
        'AdminPage\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src/AdminPage',
        ),
        'API\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src/API',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit8e31147f1302db4a6e594d13dbd658c4::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit8e31147f1302db4a6e594d13dbd658c4::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit8e31147f1302db4a6e594d13dbd658c4::$classMap;

        }, null, ClassLoader::class);
    }
}
