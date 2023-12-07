<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface ApiResultsQueryInterface
{
    public final const RUTA_API = '/api/v1/results';
    public function cgetAction(Request $request): Response;

}