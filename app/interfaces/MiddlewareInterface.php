<?php

namespace app\interfaces;


use app\Request;

interface MiddlewareInterface
{
    public function handle(Request $request, \Closure $next);
}
