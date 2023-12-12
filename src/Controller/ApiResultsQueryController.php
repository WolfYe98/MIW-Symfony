<?php

namespace App\Controller;

use App\Controller\ApiResultsQueryInterface;
use App\Entity\Result;
use App\Entity\User;
use App\Repository\ResultRepository;
use App\Utility\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Config\Doctrine\Dbal\ConnectionConfig\ReplicaConfig;

#[Route(
    path: self::RUTA_API,
    name: 'api_results_',
    requirements: ['_format'=>'json|xml'],
    defaults: ['_format'=>'json']
)]
class ApiResultsQueryController extends AbstractController implements ApiResultsQueryInterface
{
    private const HEADER_CACHE_CONTROL = 'Cache-Control';
    private const HEADER_ALLOW = 'Allow';
    private const HEADER_ETAG = 'ETag';
    private const ROLE_ADMIN = "ROLE_ADMIN";
    public function __construct(
        private readonly ResultRepository $resultRepository,
        private readonly EntityManagerInterface $entityManager
    ){}

    /**
     * @param Request $request
     * @return Response
     * @throws \JsonException
     */
    #[Route(
        path: '.{_format}',
        name: 'api_results_cget',
        methods: ['GET']
    )]
    public function cgetAction(Request $request): Response
    {
        $format = Utils::getFormat($request);
        $notAuthorized = $this->returnErrorIfNoAuthorized($format);
        if($notAuthorized){
            return $notAuthorized;
        }
        $userIdentifier = $this->getUser()->getUserIdentifier();
        $user = $this->entityManager->getRepository(User::class)->findBy([User::EMAIL_ATTR=>$userIdentifier]);
        $results = $this->resultRepository->findBy([Result::USER_ATTR=>$user]);
        if($this->isGranted(self::ROLE_ADMIN)){
            $results = $this->resultRepository->findAll();
        }
        if(empty($results)){
            return $this->createNotFoundResponse($format);
        }
        $etag = md5((string) json_encode($results, JSON_THROW_ON_ERROR));
        if (($etags = $request->getETags()) && (in_array($etag, $etags) || in_array('*', $etags))) {
            return new Response(null, Response::HTTP_NOT_MODIFIED); // 304
        }
        return Utils::apiResponse(
            Response::HTTP_OK,
            ['results'=>array_map(fn($r)=>['result'=>$r],$results)],
            $format,
            [
                self::HEADER_CACHE_CONTROL => 'private',
                self::HEADER_ETAG=>$etag
            ]
        );
    }

    /**
     * @param Request $request
     * @return Response
     * @throws \JsonException
     */
    #[Route(
        path: '.{_format}',
        name: 'api_results_post',
        methods: ['POST']
    )]
    public function postAction(Request $request): Response
    {
        $format = Utils::getFormat($request);
        $notAuthorized = $this->returnErrorIfNoAuthorized($format);
        if($notAuthorized){
            return $notAuthorized;
        }
        $body = $request->getContent();
        $postData = json_decode((string) $body, true, 512, JSON_THROW_ON_ERROR);
        if (!isset($postData[Result::RESULT_ATTR], $postData[Result::TIME_ATTR])) {
            return Utils::errorMessage(Response::HTTP_BAD_REQUEST, 'The result field or the time field are not passed', $format);
        }
        $userForbidden = $this->checkUserPostForbidden($postData,$format);
        if($userForbidden){
            return $userForbidden;
        }
        $userIdentifier = $this->getUserIdentifierFromPostOrDefault($postData);
        $user = $this->entityManager->getRepository(User::class)->findOneBy([User::EMAIL_ATTR=>$userIdentifier]);
        if(!$user){
            return $this->createNotFoundResponse($format,"User $userIdentifier not found in db");
        }
        $result = new Result();
        try{
            $result->setResult($postData[Result::RESULT_ATTR])
                    ->setTimeFromString($postData[Result::TIME_ATTR])
                    ->setUser($user);
        }catch (\TypeError){
            return $this->createBadRequestResponse($format);
        }
        $this->resultRepository->insert($result);
        return Utils::apiResponse(
            Response::HTTP_CREATED,
            [ Result::RESULT_ATTR => $result ],
            $format,
            [
                'Location' => $request->getScheme() . '://' . $request->getHttpHost() .
                    self::RUTA_API . '/' . $result->getId(),
            ]
        );
    }

    /**
     * @param Request $request
     * @param int $resultId
     * @return Response
     * @throws \JsonException
     */
    #[Route(
        path:'/{resultId}.{_format}',
        name: 'api_results_get',
        requirements: ['resultId'=>'\d+'],
        defaults: ['resultId'=>0],
        methods: ['GET'],
    )]
    public function getAction(Request $request,int $resultId): Response
    {
        $format = Utils::getFormat($request);
        $notAuthorized = $this->returnErrorIfNoAuthorized($format);
        if($notAuthorized){
            return $notAuthorized;
        }
        $result = $this->returnErrorsOfOperation($format,$resultId);
        if($result instanceof Response){
            return $result;
        }
        $etag = md5(json_encode($result, JSON_THROW_ON_ERROR));
        if (($etags = $request->getETags()) && (in_array($etag, $etags) || in_array('*', $etags))) {
            return new Response(null, Response::HTTP_NOT_MODIFIED); // 304
        }
        return Utils::apiResponse(
            Response::HTTP_OK,
            [ Result::RESULT_ATTR => $result ],
            $format,
            [
                self::HEADER_CACHE_CONTROL => 'private',
                self::HEADER_ETAG => $etag,
            ]
        );
    }
    #[Route(
        path:'/{resultId}.{_format}',
        name: 'api_results_delete',
        requirements: ['resultId'=>'\d+'],
        defaults: ['resultId'=>0],
        methods: ['DELETE'],
    )]
    public function deleteAction(Request $request, int $resultId): Response
    {
        $format = Utils::getFormat($request);
        $notAuthorized = $this->returnErrorIfNoAuthorized($format);
        if($notAuthorized){
            return $notAuthorized;
        }
        $result = $this->returnErrorsOfOperation($format,$resultId);
        if($result instanceof Response){
            return $result;
        }
        $this->resultRepository->remove($result);
        return Utils::apiResponse(Response::HTTP_NO_CONTENT);
    }

    /**
     * @param Request $request
     * @param int $resultId
     * @return Response
     * @throws \JsonException
     */
    #[Route(
        path:'/{resultId}.{_format}',
        name: 'api_results_put',
        requirements: ['resultId'=>'\d+'],
        defaults: ['resultId'=>0],
        methods: ['PUT'],
    )]
    public function putAction(Request $request, int $resultId): Response
    {
        $format = Utils::getFormat($request);
        $notAuthorized = $this->returnErrorIfNoAuthorized($format);
        if($notAuthorized){
            return $notAuthorized;
        }
        $result = $this->returnErrorsOfOperation($format,$resultId);
        if($result instanceof Response){
            return $result;
        }
        $body = (string) $request->getContent();
        $postData = json_decode($body, true);
        if(!isset($postData[Result::RESULT_ATTR],$postData[Result::TIME_ATTR])){
            return $this->createBadRequestResponse($format,'The result field or the time field are not passed');
        }
        $user = null;
        if(isset($postData[Result::USER_ATTR])){
            $user = $this->entityManager->getRepository(User::class)->findOneBy([User::EMAIL_ATTR=>$postData[Result::USER_ATTR]]);
            if(!$user){
                return $this->createNotFoundResponse($format,'User with email:'.$postData[Result::USER_ATTR].' not found');
            }
        }
        $etag = md5(json_encode($result, JSON_THROW_ON_ERROR));
        if (!$request->headers->has('If-Match') || $etag != $request->headers->get('If-Match')) {
            return Utils::errorMessage(
                Response::HTTP_PRECONDITION_FAILED,
                'PRECONDITION FAILED: one or more conditions given evaluated to false',
                $format
            ); // 412
        }
        $postData[Result::USER_ATTR] = $user;
        $result->updateResultFromPostData($postData);
        $this->entityManager->flush();
        return Utils::apiResponse(
            209,                        // 209 - Content Returned
            [ Result::RESULT_ATTR => $result ],
            $format,
            [
                self::HEADER_ETAG=>$etag
            ]
        );
    }
    #[Route(
        path:'/{resultId}.{_format}',
        name: 'api_results_options',
        requirements: ['resultId'=>'\d+'],
        defaults: ['resultId'=>0],
        methods: ['OPTIONS'],
    )]
    public function optionsAction(int|null $resultId): Response
    {
        $methods = $resultId && $resultId !== 0
            ? [ Request::METHOD_GET, Request::METHOD_PUT, Request::METHOD_DELETE ]
            : [Request::METHOD_GET,Request::METHOD_POST];
        $methods[] = Request::METHOD_OPTIONS;

        return new Response(
            null,
            Response::HTTP_NO_CONTENT,
            [
                self::HEADER_ALLOW => implode(',', $methods),
                self::HEADER_CACHE_CONTROL => 'public, inmutable'
            ]
        );
    }
    private function returnErrorIfNoAuthorized(string $format): Response|false{
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return Utils::errorMessage( // 401
                Response::HTTP_UNAUTHORIZED,
                '`Unauthorized`: Invalid credentials.',
                $format
            );
        }
        return false;
    }
    private function createNotFoundResponse($format, $message = 'Results not found'): Response{
        return Utils::errorMessage(
            Response::HTTP_NOT_FOUND,
            $message,
            $format
        );
    }
    private function createBadRequestResponse($format, $message = 'Some request field doesn\' have the correct format'): Response{
        return Utils::errorMessage(
            Response::HTTP_BAD_REQUEST,
            $message,
            $format
        );
    }
    private function returnErrorsOfOperation(string $format, int $resultId):Response|Result{
        $result = $this->resultRepository->findOneBy(['id'=>$resultId]);
        if(!$result){
            return $this->createNotFoundResponse($format,"Result with id #$resultId not found");
        }
        $userIdentifier = $this->getUser()->getUserIdentifier();
        if(!$this->isGranted(self::ROLE_ADMIN) && $userIdentifier != $result->getUser()->getUserIdentifier()){
            return $this->createForbiddenResponse($format);
        }
        return $result;
    }
    private function checkUserPostForbidden(array $postData, string $format):Response|false{
        $userIdentifier = $this->getUserIdentifierFromPostOrDefault($postData);
        $currentUser = $this->getUser()->getUserIdentifier();
        if(!$this->isGranted(self::ROLE_ADMIN) && $userIdentifier != $currentUser){
            return $this->createForbiddenResponse($format);
        }
        return false;
    }
    private function createForbiddenResponse(string $format):Response{
        return Utils::errorMessage(
            Response::HTTP_FORBIDDEN,
            'Operation forbidden for the current user',
            $format
        );
    }
    private function getUserIdentifierFromPostOrDefault(array $postData):string{
        $userIdentifier = $this->getUser()->getUserIdentifier();
        if(isset($postData[Result::USER_ATTR])){
            $userIdentifier = $postData[Result::USER_ATTR];
        }
        return $userIdentifier;
    }

}