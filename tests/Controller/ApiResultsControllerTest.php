<?php

namespace App\Tests\Controller;

use App\Entity\Result;
use App\Entity\User;
use App\Tests\Controller\BaseTestCase;
use Generator;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertTrue;

class ApiResultsControllerTest extends BaseTestCase
{
    private const RUTA_API = '/api/v1/results';
    private const RESULTS_ATTR = "results";
    /**
     * @var array<string,string>
     */
    private array $roleUserHeaders;
    /**
     * @var array<string,string>
     */
    private array $roleAdminHeaders;
    protected function setUp(): void
    {
        $this->roleUserHeaders = self::getTokenHeaders(self::$role_user[User::EMAIL_ATTR],self::$role_user[User::PASSWD_ATTR]);
        $this->roleAdminHeaders = self::getTokenHeaders(self::$role_admin[User::EMAIL_ATTR],self::$role_admin[User::PASSWD_ATTR]);
    }
    public function testOptionsResultAction204NoContent(): void
    {
        // OPTIONS /api/v1/results
        self::$client->request(
            Request::METHOD_OPTIONS,
            self::RUTA_API
        );
        $response = self::$client->getResponse();

        self::assertSame(
            Response::HTTP_NO_CONTENT,
            $response->getStatusCode()
        );
        self::assertNotEmpty($response->headers->get('Allow'));

        // OPTIONS /api/v1/results/{id}
        self::$client->request(
            Request::METHOD_OPTIONS,
            self::RUTA_API . '/' . self::$faker->numberBetween(1, 100)
        );
        $response = self::$client->getResponse();
        self::assertSame(
            Response::HTTP_NO_CONTENT,
            $response->getStatusCode()
        );
        self::assertNotEmpty($response->headers->get('Allow'));
        $options = 'GET,PUT,DELETE,POST,OPTIONS';
        self::assertEquals($options,$response->headers->get('Allow'));
    }
    /**
     * Test GET    /results 401 UNAUTHORIZED
     * Test POST   /results 401 UNAUTHORIZED
     * Test GET    /results/{resultId} 401 UNAUTHORIZED
     * Test PUT    /results/{resultId} 401 UNAUTHORIZED
     * Test DELETE /results/{resultId} 401 UNAUTHORIZED
     *
     * @param string $method
     * @param string $uri
     * @dataProvider providerRoutes401
     * @return void
     */
    public function testResultStatus401Unauthorized(string $method, string $uri): void
    {
        self::$client->request(
            $method,
            $uri,
            [],
            [],
            [ 'HTTP_ACCEPT' => 'application/json' ]
        );
        $this->checkResponseErrorMessage(
            self::$client->getResponse(),
            Response::HTTP_UNAUTHORIZED
        );
    }

    /**
     * Test POST   /results 403 FORBIDDEN
     * Test GET    /results/{resultId} 403 FORBIDDEN
     * Test PUT    /results/{resultId} 403 FORBIDDEN
     * Test DELETE /results/{resultId} 403 FORBIDDEN
     *
     * @param string $method
     * @param string $uri
     * @dataProvider providerRoutes403
     * @return void
     */
    public function testResultStatus403Forbidden(string $method, string $uri): void{
        $pdata = [
            Result::RESULT_ATTR=>1,
            Result::USER_ATTR=>self::$role_admin[User::EMAIL_ATTR],
            Result::TIME_ATTR=>'2023-12-12 10:10:10'
        ];
        self::$client->request(
            $method,
            $uri,
            [],
            [],
            $this->roleUserHeaders,
            strval(json_encode($pdata))
        );
        $response = self::$client->getResponse();
        self::checkResponseErrorMessage($response,403);
    }
    /**
     * Test POST   /results 400 BAD_REQUEST
     * Test PUT    /results/{resultId} 400 BAD_REQUEST
     *
     * @param string $method
     * @param string $uri
     * @dataProvider providerRoutes400
     * @return void
     */
    public function testResultStatus400BadRequest(string $method, string $uri): void{
        $pdata = [
            Result::RESULT_ATTR=>1,
            Result::USER_ATTR=>self::$role_admin[User::EMAIL_ATTR]
        ];
        self::$client->request(
            $method,
            $uri,
            [],
            [],
            $this->roleUserHeaders,
            strval(json_encode($pdata))
        );
        $response = self::$client->getResponse();
        self::checkResponseErrorMessage($response,400);
    }

    /**
     * Test POST   /results 400 BAD_REQUEST time
     *
     * @return void
     */
    public function testResultStatus400PostTimeBadRequest(): void{
        $pdata = [
            Result::RESULT_ATTR=>1,
            Result::USER_ATTR=>self::$role_admin[User::EMAIL_ATTR],
            Result::TIME_ATTR=>'asdfl'
        ];
        self::$client->request(
            Request::METHOD_POST,
            self::RUTA_API,
            [],
            [],
            $this->roleAdminHeaders,
            strval(json_encode($pdata))
        );
        $response = self::$client->getResponse();
        self::checkResponseErrorMessage($response,400);
    }

    /**
     * Test GET   /results/{resultId} 404 NOT_FOUND
     * Test PUT    /results/{resultId} 404 NOT_FOUND
     * Test DELETE    /results/{resultId} 404 NOT_FOUND
     *
     * @param string $method
     * @param string $uri
     * @dataProvider providerRoutes404
     * @return void
     */
    public function testResultStatus404NotFound(string $method, string $uri): void{
        $pdata = [
            Result::RESULT_ATTR=>1,
            Result::USER_ATTR=>self::$role_admin[User::EMAIL_ATTR]
        ];
        self::$client->request(
            $method,
            $uri,
            [],
            [],
            $this->roleUserHeaders,
            strval(json_encode($pdata))
        );
        $response = self::$client->getResponse();
        self::checkResponseErrorMessage($response,404);
    }

    /**
     *
     * Test POST /result 404 User NOT_FOUND
     * Test PUT /result/{resultId} 404 User NOT_FOUND
     *
     * @param string $method
     * @param string $uri
     * @return void
     * @dataProvider providerRoutes404UserNotFound
     */
    public function testResultStatus404UserNotFoundPut(string $method, string $uri): void{
        $pdata = [
            Result::RESULT_ATTR=>1,
            Result::USER_ATTR=>'userNotExists',
            Result::TIME_ATTR=>'2023-12-12 10:10:10'
        ];
        self::$client->request(
            $method,
            $uri,
            [],
            [],
            $this->roleAdminHeaders,
            strval(json_encode($pdata))
        );
        $response = self::$client->getResponse();
        self::checkResponseErrorMessage($response,404);
    }

    /**
     * Test GET /results 404 NOT_FOUND
     * @return void
     */
    public function testResultStatus404NotFoundCGet():void{
        $token = self::getTokenHeaders(self::$role_user_aux[User::EMAIL_ATTR],self::$role_user_aux[User::PASSWD_ATTR]);
        self::$client->request(
            Request::METHOD_GET,
            self::RUTA_API,
            [],
            [],
            $token
        );
        $response = self::$client->getResponse();
        self::checkResponseErrorMessage($response,404);
    }

    /**
     * Test CGET /results 200 OK RoleUser
     *
     * @return string Etag header
     */
    public function testResultsStatus200OkCGet_roleUser():string{
        self::$client->request(
            Request::METHOD_GET,
            self::RUTA_API,
            [],
            [],
            $this->roleUserHeaders
        );
        $response = self::$client->getResponse();
        self::assertTrue($response->isSuccessful());
        self::assertNotEmpty($response->getContent());
        $results = json_decode($response->getContent(),true);
        self::assertNotEmpty($results);
        self::assertTrue(isset($results[self::RESULTS_ATTR]));
        self::assertCount(1,$results[self::RESULTS_ATTR]);
        return (string) $response->getEtag();
    }

    /**
     * @param string $etag Etag received from other test
     * @return void
     * @depends testResultsStatus200OkCGet_roleUser
     */
    public function testResultsStatus304NotModified(string $etag):void{
        $headers = array_merge($this->roleUserHeaders,['HTTP_If-None-Match'=>[$etag]]);
        self::$client->request(
            Request::METHOD_GET,
            self::RUTA_API,
            [],
            [],
            $headers
        );
        $response = self::$client->getResponse();
        self::assertSame(Response::HTTP_NOT_MODIFIED, $response->getStatusCode());
    }

    /**
     * Test POST /results 201 CREATED
     * @return array
     * @depends testResultsStatus200OkCGet_roleAdmin
     */
    public function testResultsPost201Created():array{
        $arr = [Result::RESULT_ATTR=>1,Result::USER_ATTR=>self::$role_user_aux[User::EMAIL_ATTR],Result::TIME_ATTR=>'2023-12-12 10:10:10'];
        $json = json_encode($arr);
        self::$client->request(
            Request::METHOD_POST,
            self::RUTA_API,
            [],
            [],
            $this->roleAdminHeaders,
            strval($json)
        );
        $response = self::$client->getResponse();
        self::assertSame(Response::HTTP_CREATED,$response->getStatusCode());
        assertTrue($response->isSuccessful());
        self::assertJson(strval($response->getContent()));
        $result = json_decode(strval($response->getContent()),true)[Result::RESULT_ATTR];
        self::assertNotEmpty($result['id']);
        self::assertSame($arr[Result::RESULT_ATTR],$result[Result::RESULT_ATTR]);
        self::assertNotEmpty($result[Result::USER_ATTR]);
        self::assertNotEmpty($result[Result::TIME_ATTR]);
        return $result;
    }

    /**
     * Test GET /results/{resultId} 200 OK
     *
     * @param array<int,string,mixed,\DateTime> $result
     * @return array<string,int> Etag and resultId
     * @depends testResultsPost201Created
     */
    public function testResultsGet200Ok(array $result):array{
        $id = $result['id'];
        self::$client->request(
            Request::METHOD_GET,
            self::RUTA_API.'/'.$id,
            [],
            [],
            $this->roleAdminHeaders
        );
        $response = self::$client->getResponse();
        self::assertSame(Response::HTTP_OK,$response->getStatusCode());
        self::assertTrue($response->isSuccessful());
        self::assertJson(strval($response->getContent()));
        $result = json_decode(strval($response->getContent()),true);
        self::assertNotEmpty($result);
        return ['Etag'=>(string) $response->getEtag(),'id'=>$id];
    }

    /**
     * Test GET /results/{resultId} 304 NOT_MODIFIED
     * @param array<string,int> $etagAndId Etag and resultId array
     * @depends testResultsGet200Ok
     */
    public function testResultsGet304NotModified(array $etagAndId): void{
        $etag = $etagAndId['Etag'];
        $id = $etagAndId['id'];
        $headers = array_merge($this->roleAdminHeaders,['HTTP_If-None-Match'=>[$etag]]);
        self::$client->request(
            Request::METHOD_GET,
            self::RUTA_API."/$id",
            [],
            [],
            $headers
        );
        $response = self::$client->getResponse();
        self::assertSame(Response::HTTP_NOT_MODIFIED, $response->getStatusCode());
    }

    /**
     * Test PUT /results/{resultId} 209 CONTENT_UPDATED
     * @param array<string,int> $etagAndId
     * @return void
     * @depends testResultsGet200Ok
     * @depends testResultsGet304NotModified
     */
    public function testResultsPut209ContentUpdated(array $etagAndId):void{
        $postData = [
            Result::RESULT_ATTR=>100,
            Result::TIME_ATTR=>'2023-12-12 10:12:12'
        ];
        $id = $etagAndId['id'];
        $etag = $etagAndId['Etag'];
        self::$client->request(
            Request::METHOD_PUT,
            self::RUTA_API."/$id",
            [],
            [],
            array_merge(
                $this->roleAdminHeaders,
                ['HTTP_If-Match'=>$etag]
            ),
            strval(json_encode($postData))
        );
        $response = self::$client->getResponse();
        self::assertSame(209,$response->getStatusCode());
        $body = $response->getContent();
        self::assertJson(strval($body));
        $result = json_decode(strval($body),true);
        self::assertSame($id,$result[Result::RESULT_ATTR]['id']);
    }

    /**
     * Test PUT /results/{resultId} 412 HTTP_PRECONDITION_FAILED
     * @return void
     */
    public function testResultsPut412PreconditionFailed():void{
        $headers = array_merge($this->roleAdminHeaders,['HTTP_If-Match'=>'']);
        $postData = [
            Result::RESULT_ATTR=>100,
            Result::TIME_ATTR=>'2023-12-12 10:12:12'
        ];
        self::$client->request(
            Request::METHOD_PUT,
            self::RUTA_API.'/1',
            [],
            [],
            $headers,
            strval(json_encode($postData))
        );
        $response = self::$client->getResponse();
        self::assertSame(Response::HTTP_PRECONDITION_FAILED,$response->getStatusCode());
    }


    /**
     * Test CGET /results 200 OK RoleAdmin
     *
     * @return void
     */
    public function testResultsStatus200OkCGet_roleAdmin():void{
        self::$client->request(
            Request::METHOD_GET,
            self::RUTA_API,
            [],
            [],
            $this->roleAdminHeaders
        );
        $response = self::$client->getResponse();
        self::assertTrue($response->isSuccessful());
        self::assertNotEmpty($response->getContent());
        $results = json_decode($response->getContent(),true);
        self::assertNotEmpty($results);
        self::assertTrue(isset($results[self::RESULTS_ATTR]));
        self::assertCount(2,$results[self::RESULTS_ATTR]);
    }

    /**
     * @return void
     * @depends testResultsGet200Ok
     * @depends testResultsStatus200OkCGet_roleAdmin
     * @depends testResultStatus403Forbidden
     */
    public function testResultsDelete204NoContent(): void{
        self::$client->request(
            Request::METHOD_DELETE,
            self::RUTA_API.'/1',
            [],
            [],
            $this->roleAdminHeaders
        );
        $response = self::$client->getResponse();
        self::assertSame(Response::HTTP_NO_CONTENT,$response->getStatusCode());
    }

    /**
     * Route provider (expected status: 401 UNAUTHORIZED)
     *
     * @return Generator name => [ method, url ]
     */
    #[ArrayShape([
        'cgetAction401' => "array",
        'getAction401' => "array",
        'postAction401' => "array",
        'putAction401' => "array",
        'deleteAction401' => "array"
    ])]
    public function providerRoutes401(): Generator
    {
        yield 'cgetAction401'   => [ Request::METHOD_GET,    self::RUTA_API ];
        yield 'getAction401'    => [ Request::METHOD_GET,    self::RUTA_API . '/1' ];
        yield 'postAction401'   => [ Request::METHOD_POST,   self::RUTA_API ];
        yield 'putAction401'    => [ Request::METHOD_PUT,    self::RUTA_API . '/1' ];
        yield 'deleteAction401' => [ Request::METHOD_DELETE, self::RUTA_API . '/1' ];
    }
    /**
     * Route provider (expected status: 403 FORBIDDEN)
     *
     * @return Generator name => [ method, url ]
     */
    #[ArrayShape([
        'getAction403' => "array",
        'postAction403' => "array",
        'putAction403' => "array",
        'deleteAction403' => "array"
    ])]
    public function providerRoutes403(): Generator
    {
        yield 'getAction403'    => [ Request::METHOD_GET,    self::RUTA_API . '/1' ];
        yield 'postAction403'   => [ Request::METHOD_POST,   self::RUTA_API ];
        yield 'putAction403'    => [ Request::METHOD_PUT,    self::RUTA_API . '/1' ];
        yield 'deleteAction403' => [ Request::METHOD_DELETE, self::RUTA_API . '/1' ];
    }
    /**
     * Route provider (expected status: 400 BAD_REQUEST)
     *
     * @return Generator name => [ method, url ]
     */
    #[ArrayShape([
        'postAction400' => "array",
        'putAction400' => "array",
    ])]
    public function providerRoutes400(): Generator
    {
        yield 'postAction400'   => [ Request::METHOD_POST,   self::RUTA_API ];
        yield 'putAction400'    => [ Request::METHOD_PUT,    self::RUTA_API . '/2' ];
    }
    /**
     * Route provider (expected status: 404 NOT_FOUND)
     *
     * @return Generator name => [ method, url ]
     */
    #[ArrayShape([
        'getAction404' => "array",
        'putAction404' => "array",
        'deleteAction404' => "array",
    ])]
    public function providerRoutes404(): Generator
    {
        yield 'getAction404'   => [ Request::METHOD_GET,   self::RUTA_API . '/4' ];
        yield 'putAction404'    => [ Request::METHOD_PUT,    self::RUTA_API . '/4' ];
        yield 'deleteAction404'    => [ Request::METHOD_DELETE,    self::RUTA_API . '/4' ];
    }
    /**
     * Route provider (expected status: 404 User NOT_FOUND)
     *
     * @return Generator name => [ method, url ]
     */
    #[ArrayShape([
        'postAction404' => "array",
        'putAction404' => "array",
    ])]
    public function providerRoutes404UserNotFound(): Generator
    {
        yield 'postAction404'   => [ Request::METHOD_POST,   self::RUTA_API ];
        yield 'putAction404'    => [ Request::METHOD_PUT,    self::RUTA_API . '/2' ];
    }
}