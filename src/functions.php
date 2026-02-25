<?php
//namespace Sura;

function trans(string $key, array $params = []): string
{
    $translator = \Sura\Container::getInstance()->get('translator');
    return $translator->trans($key, $params);
}

