<?php
    use Symfony\Component\VarDumper\VarDumper;

    if (!function_exists('dd')) {
        function dd(...$args)
        {
            foreach ($args as $x) {
                Symfony\Component\VarDumper\VarDumper::dump($x);
            }
            die(1);
        }
    }
