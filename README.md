# EasyMerge2pdf
![Build Status](https://github.com/ronisaha/easy-merge2pdf/actions/workflows/ci.yml/badge.svg?branch=main)
[![Coverage Status](https://coveralls.io/repos/github/ronisaha/easy-merge2pdf/badge.svg?branch=main)](https://coveralls.io/github/ronisaha/easy-merge2pdf?branch=main)
[![Latest Stable Version](https://poser.pugx.org/ronisaha/easy-merge2pdf/v/stable.png)](https://packagist.org/packages/ronisaha/easy-bangla-date)
[![Total Downloads](https://poser.pugx.org/ronisaha/easy-merge2pdf/downloads.png)](https://packagist.org/packages/ronisaha/easy-bangla-date) 

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
