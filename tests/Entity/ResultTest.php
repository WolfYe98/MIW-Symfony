<?php

namespace App\Tests\Entity;

use App\Entity\Result;
use App\Entity\User;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertEquals;

class ResultTest extends TestCase
{
    private static \DateTime $datetime;
    private Result $result;
    private User $user;
    protected function setUp(): void
    {
        self::$datetime = new \DateTime('now');
        $this->user = new User('abc','abc');
        $this->result = new Result(1,$this->user,self::$datetime);
    }

    public function testUpdateResultFromPostData()
    {
        $timeStr = '2023-12-12 10:10:10';
        $newUser = new User('abc2','abc2');
        $postData = [Result::RESULT_ATTR=>1,Result::TIME_ATTR => $timeStr,Result::USER_ATTR=>$newUser];
        $this->result->updateResultFromPostData($postData);
        self::assertEquals(1,$this->result->getResult());
        self::assertEquals($newUser,$this->result->getUser());
        self::assertEquals(\DateTime::createFromFormat('Y-m-d H:i:s',$timeStr),$this->result->getTime());
    }

    public function testGetId()
    {
        assertEquals(0,$this->result->getId());
    }

    public function testGetResult()
    {
        assertEquals(1,$this->result->getResult());
    }

    public function testJsonSerialize()
    {
        assertEquals('{"Id":0,"user":{"Id":0,"email":"abc","roles":["ROLE_USER"]},"time":{"date":"'.self::$datetime->format('Y-m-d H:i:s.u').'","timezone_type":3,"timezone":"Europe\/Berlin"}}',json_encode($this->result));
    }

    public function testGetTime()
    {
        assertEquals(self::$datetime,$this->result->getTime());
    }

    public function testSetId()
    {
        assertEquals(0,$this->result->getId());
        $this->result->setId(1);
        assertEquals(1,$this->result->getId());
    }

    public function testSetResult()
    {
        assertEquals(1,$this->result->getResult());
        $this->result->setResult(2);
        assertEquals(2,$this->result->getResult());
    }

    public function testSetTime()
    {
        assertEquals(self::$datetime,$this->result->getTime());
        $newTime = new \DateTime('now');
        $this->result->setTime($newTime);
        assertEquals($newTime,$this->result->getTime());
    }

    public function testGetUser()
    {
        assertEquals($this->user,$this->result->getUser());
    }

    public function testSetTimeFromString()
    {
        assertEquals(self::$datetime,$this->result->getTime());
        $timeStr = '2023-12-12 10:10:10';
        $time = \DateTime::createFromFormat('Y-m-d H:i:s',$timeStr);
        $this->result->setTimeFromString($timeStr);
        assertEquals($time,$this->result->getTime());
    }

    public function testSetUser()
    {
        assertEquals($this->user,$this->result->getUser());
        $newUser = new User('222','222');
        $this->result->setUser($newUser);
        assertEquals($newUser,$this->result->getUser());
    }
}
