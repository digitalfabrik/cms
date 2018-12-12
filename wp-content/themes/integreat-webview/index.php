<?php
$uri = str_replace("/wordpress","",$_SERVER['REQUEST_URI']);
header('Location: https://web.integreat-app.de'.$uri, true, 301);
get_header(); ?>
