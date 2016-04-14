<?php
namespace atk4\dsql\tests;

use atk4\dsql\Connection;

/**
 * @coversDefaultClass \atk4\dsql\ConnectionTest
 */
class ConnectionTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Test constructor
     *
     * @covers ::__construct
     */
    public function testInit()
    {
        $c = Connection::connect('sqlite::memory:');
        $this->assertEquals(
            4,
            $c->expr('select (2+2)')->getOne()
        );
    }

    public function testDumper()
    {
        $c = Connection::connect('dumper:sqlite::memory:');

        $result = false;
        $c->callback = function($expr, $time)use(&$result){
            $result = $expr->render();
        };

        $this->assertEquals(
            4,
            $c->expr('select (2+2)')->getOne()
        );

        $this->assertEquals(
            'select (2+2)',
            $result
        );
    }

    public function testCounter()
    {
        $c = Connection::connect('counter:sqlite::memory:');

        $result = false;
        $c->callback = function($a, $b, $c, $d)use(&$result){
            $result = [$a, $b, $c, $d];
        };

        $this->assertEquals(
            4,
            $c->expr('select ([]+[])', [$c->expr('2'), 2])->getOne()
        );

        unset($c);
        $this->assertEquals(
            [0,0,1,1],
            $result
        );
    }

    public function testCounter2()
    {
        $c = Connection::connect('counter:sqlite::memory:');

        $result = false;
        $c->callback = function($a, $b, $c, $d)use(&$result){
            $result = [$a, $b, $c, $d];
        };

        $this->assertEquals(
            4,
            $c->dsql()->field($c->expr('2+2'))->getOne()
        );

        unset($c);
        $this->assertEquals(
            [1,1,1,0],
            // 1 query
            // 1 select
            // 1 result row
            // 0 expressions
            $result
        );
    }

    public function testCounter3()
    {
        $c = Connection::connect('counter:sqlite::memory:');

        $result = false;
        $c->callback = function($a, $b, $c, $d)use(&$result){
            $result = [$a, $b, $c, $d];
        };

        $c->expr('create table test (id int, name varchar(255))')->execute();
        $c->dsql()->table('test')->set('name', 'John')->insert();
        $c->dsql()->table('test')->set('name', 'Peter')->insert();
        $c->dsql()->table('test')->set('name', 'Joshua')->insert();
        $res = $c->dsql()->table('test')->where('name','like','J%')->field('name')->get();

        $this->assertEquals(
            [['name'=>'John'],['name'=>'Joshua']],
            $res
        );

        unset($c);
        $this->assertEquals(
            [4,1,2,1],
            // 4 queries, 3 inserts and select
            // 1 select
            // 2 result row, john, joshua
            // 1 expressions, create
            $result
        );
    }



    public function testException1()
    {
        $this->setExpectedException('PDOException');
        $c = Connection::connect(':');
    }

    public function testException2()
    {
        $this->setExpectedException('atk4\dsql\Exception');
        $c = Connection::connect('');
    }

    public function testException3()
    {
        $this->setExpectedException('atk4\dsql\Exception');
        $c = Connection::connect('');
    }
}
