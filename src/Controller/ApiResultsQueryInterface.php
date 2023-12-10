<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface ApiResultsQueryInterface
{
    public final const RUTA_API = '/api/v1/results';
    public function cgetAction(Request $request): Response;
    public function postAction(Request $request): Response;
    public function optionsAction(int|null $userId):Response;
    public function getAction(Request $request,int $resultId): Response;
    public function deleteAction(Request $request, int $resultId): Response;
    public function putAction(Request $request, int $resultId): Response;
}