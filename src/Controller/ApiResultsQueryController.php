<?php

namespace App\Controller;

use App\Controller\ApiResultsQueryInterface;
use App\Repository\ResultRepository;
use App\Utility\Utils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ApiResultsQueryController extends AbstractController implements ApiResultsQueryInterface
{
    private const HEADER_CACHE_CONTROL = 'Cache-Control';
    private const HEADER_ETAG = 'ETag';
    public function __construct(
        private readonly ResultRepository $resultRepository
    ){}
    #[Route(
        path: self::RUTA_API,
        name: 'api_results_get',
        methods: ['GET']
    )]
    public function cgetAction(Request $request): Response
    {
        $format = Utils::getFormat($request);
        $notAuthorized = $this->returnErrorIfNoAuthorized($request,$format);
        if($notAuthorized){
            return $notAuthorized;
        }
        $results = $this->resultRepository->findAll();

        return Utils::apiResponse(
            Response::HTTP_OK,
            ['results'=>array_map(fn($r)=>['result'=>$r],$results)],
            $format,
            [
                self::HEADER_CACHE_CONTROL => 'private'
            ]
        );
    }
    private function returnErrorIfNoAuthorized(Request $request,$format): Response|false{
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return Utils::errorMessage( // 401
                Response::HTTP_UNAUTHORIZED,
                '`Unauthorized`: Invalid credentials.',
                $format
            );
        }
        return false;
    }
}