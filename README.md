NwLaravel
=========

[![Build Status](https://travis-ci.org/naturalweb/NwLaravel.svg?branch=master)](https://travis-ci.org/naturalweb/NwLaravel)
[![Coverage Status](https://coveralls.io/repos/naturalweb/NwLaravel/badge.png)](https://coveralls.io/r/naturalweb/NwLaravel)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/naturalweb/NwLaravel/badges/quality-score.png?s=8fc61c67360b9bb0860b4ea33d2588dd35e8a1f1)](https://scrutinizer-ci.com/g/naturalweb/NwLaravel/)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/91c240e7-d736-45b8-afc9-a12576f3c9dc/mini.png)](https://insight.sensiolabs.com/projects/91c240e7-d736-45b8-afc9-a12576f3c9dc)

Pacote de classes básicas para projeto em Laravel


Sociallite
==========

``` composer require laravel/socialite ```
Documentação [https://github.com/laravel/socialite]


DRIVER OLX
----------

Para usar o driver para oauth na OLX é muito simples, você precisa somente definir
as configurações nos serviços em `config/services.php` adicione no array o dados enviado pela olx

```php
    ...
    'olx' => [
        'client_id' => '[CLIENT ID]',
        'client_secret' => '[CLIENT SECRET]',
        'redirect_uri' => '[URL DE REDIRECT]',
    ],
```