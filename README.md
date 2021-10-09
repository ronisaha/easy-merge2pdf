# EasyMerge2pdf
EasyMerge2pdf is a PHP library for merging Images and PDFs. It uses the excellent [merge2pdf](https://github.com/ajaxray/merge2pdf) command available for OSX, linux, windows.

## Installation
```bash
composer require ronisaha/easy-merge2pdf
```

## Usages
```php
<?php
require_once 'vendor/autoload.php';

$m = new \EasyMerge2pdf\Merger(['auto' => true]);
$m->addInput('input.pdf', '1,3-8,2,1');
try {
    $m->merge('out.pdf');
} catch (Exception $exception) {
    echo $exception->getMessage();
}
```
