<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    |
    */

    // The PHP DOM extension must be enabled in php.ini
    // The GD extension must be enabled in php.ini
    // For UTF-8 support you must use the mbstring extension
    // If you get "maximum execution time exceeded" errors, increase the value of
    // 'max_execution_time' in php.ini

    // If you get out of memory errors, increase the value of 'memory_limit' in php.ini

    // The temporary directory must be writable by the webserver
    // The font directory must be writable by the webserver

    // If you want to use a different temporary directory, you can set it here
    'temp_dir' => storage_path('fonts/'),

    // If you want to use a different font directory, you can set it here
    'font_dir' => storage_path('fonts/'),

    // If you want to use a different font cache directory, you can set it here
    'font_cache' => storage_path('fonts/'),

    // If you want to use a different chroot directory, you can set it here
    'chroot' => public_path(),

    // If you want to use a different log directory, you can set it here
    'log_output_file' => storage_path('logs/dompdf_log.html'),

    // If you want to use a different default paper size, you can set it here
    'default_paper_size' => 'a4',

    // If you want to use a different default paper orientation, you can set it here
    'default_paper_orientation' => 'portrait',

    // If you want to use a different default font, you can set it here
    'default_font' => 'serif',

    // If you want to use a different dpi, you can set it here
    'dpi' => 96,

    // If you want to use a different font height ratio, you can set it here
    'font_height_ratio' => 1.0,

    // If you want to use a different default media type, you can set it here
    'default_media_type' => 'screen',

    // If you want to enable/disable the use of the PHP DOM extension
    'is_php_enabled' => false,

    // If you want to enable/disable the use of remote fonts
    'is_remote_enabled' => false,

    // If you want to enable/disable the use of the HTML5 parser
    'is_html5_parser_enabled' => true,

    // If you want to enable/disable font subsetting
    'is_font_subsetting_enabled' => false,

    'debug_png' => false,
    'debug_keep' => false,
    'debug_css' => false,
    'debug_layout' => false,
    'debug_layout_lines' => true,
    'debug_layout_blocks' => true,
    'debug_nested_layout' => true,
    'debug_layout_padding_box' => true,

    'pdf_backend' => 'CPDF',
    'pdflib_license' => '',

    'admin_username' => '',
    'admin_password' => '',

];
