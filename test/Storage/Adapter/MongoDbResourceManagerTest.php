<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Cache\Storage\Adapter;

use Zend\Cache\Storage\Adapter\MongoDbResourceManager;

/**
 * @group      Zend_Cache
 * @covers Zend\Cache\Storage\Adapter\MongoDbResourceManager
 */
class MongoDbResourceManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $object;

    public function setUp()
    {
        if (getenv('TESTS_ZEND_CACHE_MONGODB_ENABLED') != 'true') {
            $this->markTestSkipped('Enable TESTS_ZEND_CACHE_MONGODB_ENABLED to run this test');
        }

        if (! extension_loaded('mongo') || ! class_exists('\Mongo') || ! class_exists('\MongoClient')) {
            // Allow tests to run if Mongo extension is loaded, or we have a polyfill in place
            $this->markTestSkipped("Mongo extension is not loaded");
        }

        $this->object = new MongoDbResourceManager();
    }

    public function testSetResourceAlreadyCreated()
    {
        $this->assertAttributeEmpty('resources', $this->object);

        $id = 'foo';

        $clientClass = (version_compare(phpversion('mongo'), '1.3.0', '<')) ? '\Mongo' : '\MongoClient';
        $client = new $clientClass(getenv('TESTS_ZEND_CACHE_MONGODB_CONNECTSTRING'));
        $resource = $client->selectCollection(
            getenv('TESTS_ZEND_CACHE_MONGODB_DATABASE'),
            getenv('TESTS_ZEND_CACHE_MONGODB_COLLECTION')
        );

        $this->object->setResource($id, $resource);

        $this->assertSame($resource, $this->object->getResource($id));
    }

    public function testSetResourceArray()
    {
        $this->assertAttributeEmpty('resources', $this->object);

        $id     = 'foo';
        $server = 'mongodb://test:1234';

        $this->object->setResource($id, ['server' => $server]);

        $this->assertSame($server, $this->object->getServer($id));
    }

    public function testSetResourceThrowsException()
    {
        $id = 'foo';
        $resource = new \stdClass();

        $this->setExpectedException('Zend\Cache\Exception\InvalidArgumentException');
        $this->object->setResource($id, $resource);
    }

    public function testHasResourceEmpty()
    {
        $id = 'foo';

        $this->assertFalse($this->object->hasResource($id));
    }

    public function testHasResourceSet()
    {
        $id = 'foo';

        $this->object->setResource($id, ['foo' => 'bar']);

        $this->assertTrue($this->object->hasResource($id));
    }

    public function testGetResourceNotSet()
    {
        $id = 'foo';

        $this->assertFalse($this->object->hasResource($id));

        $this->setExpectedException('Zend\Cache\Exception\RuntimeException');
        $this->object->getResource($id);
    }

    public function testGetResourceInitialized()
    {
        $id = 'foo';

        $clientClass = (version_compare(phpversion('mongo'), '1.3.0', '<')) ? '\Mongo' : '\MongoClient';
        $client = new $clientClass(getenv('TESTS_ZEND_CACHE_MONGODB_CONNECTSTRING'));
        $resource = $client->selectCollection(
            getenv('TESTS_ZEND_CACHE_MONGODB_DATABASE'),
            getenv('TESTS_ZEND_CACHE_MONGODB_COLLECTION')
        );

        $this->object->setResource($id, $resource);

        $this->assertSame($resource, $this->object->getResource($id));
    }

    public function testGetResourceNewResource()
    {
        $id                = 'foo';
        $server            = getenv('TESTS_ZEND_CACHE_MONGODB_CONNECTSTRING');
        $connectionOptions = ['connectTimeoutMS' => 5];
        $database          = getenv('TESTS_ZEND_CACHE_MONGODB_DATABASE');
        $collection        = getenv('TESTS_ZEND_CACHE_MONGODB_COLLECTION');

        $this->object->setServer($id, $server);
        $this->object->setConnectionOptions($id, $connectionOptions);
        $this->object->setDatabase($id, $database);
        $this->object->setCollection($id, $collection);

        $this->assertInstanceOf('\MongoCollection', $this->object->getResource($id));
    }

    public function testGetResourceUnknownServerThrowsException()
    {
        $id                = 'foo';
        $server            = 'mongodb://unknown.unknown';
        $connectionOptions = ['connectTimeoutMS' => 5];
        $database          = getenv('TESTS_ZEND_CACHE_MONGODB_DATABASE');
        $collection        = getenv('TESTS_ZEND_CACHE_MONGODB_COLLECTION');

        $this->object->setServer($id, $server);
        $this->object->setConnectionOptions($id, $connectionOptions);
        $this->object->setDatabase($id, $database);
        $this->object->setCollection($id, $collection);

        $this->setExpectedException('Zend\Cache\Exception\RuntimeException');
        $this->object->getResource($id);
    }
}
