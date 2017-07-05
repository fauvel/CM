<?php

class CM_Model_StorageAdapter_DatabaseTest extends CMTest_TestCase {

    public static function setupBeforeClass() {
        CM_Db_Db::exec("CREATE TABLE `mock_modelStorageAdapter` (
				`id` INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
				`foo` VARCHAR(32),
				`bar` INT,
				`baz` INT NOT NULL,
				`qux` INT NOT NULL,
				UNIQUE `unq_baz_qux` (`baz`, `qux`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;
		");
    }

    public static function tearDownAfterClass() {
        parent::tearDownAfterClass();
        CM_Db_Db::exec("DROP TABLE `mock_modelStorageAdapter`");
    }

    protected function tearDown() {
        CM_Db_Db::truncate('mock_modelStorageAdapter');
    }

    public function testGetTableName() {
        CM_Config::get()->CM_Model_Abstract->types += [
            1 => 'CMTest_ModelMock_1',
            2 => 'CMTest_ModelMock_2',
        ];

        $adapter = new CM_Model_StorageAdapter_Database();
        $method = CMTest_TH::getProtectedMethod('CM_Model_StorageAdapter_Database', '_getTableName');
        $this->assertSame('cmtest_modelmock_1', $method->invoke($adapter, 1));
        $this->assertSame('custom_table', $method->invoke($adapter, 2));
    }

    public function testLoad() {
        $id1 = CM_Db_Db::insert('mock_modelStorageAdapter', array('foo' => 'foo1', 'bar' => 1, 'baz' => 1, 'qux' => 2));
        $id2 = CM_Db_Db::insert('mock_modelStorageAdapter', array('foo' => 'foo2', 'bar' => 2, 'baz' => 1, 'qux' => 3));
        $type = 99;

        $adapter = $this->getMockBuilder('CM_Model_StorageAdapter_Database')->setMethods(array('_getTableName'))->getMock();
        $adapter->expects($this->any())->method('_getTableName')->will($this->returnValue('mock_modelStorageAdapter'));
        /** @var CM_Model_StorageAdapter_Database $adapter */

        $this->assertSame(array('id' => $id1, 'foo' => 'foo1', 'bar' => '1', 'baz' => '1', 'qux' => '2'), $adapter->load($type, array('id' => $id1)));
        $this->assertSame(
            array('id' => $id1, 'foo' => 'foo1', 'bar' => '1', 'baz' => '1', 'qux' => '2'),
            $adapter->load($type, array('id' => $id1, 'foo' => 'foo1'))
        );
        $this->assertSame(array('id' => $id2, 'foo' => 'foo2', 'bar' => '2', 'baz' => '1', 'qux' => '3'), $adapter->load($type, array('id' => $id2)));
        $this->assertFalse($adapter->load($type, array('id' => '9999')));
        $this->assertFalse($adapter->load($type, array('id' => $id1, 'foo' => '9999')));
    }

    public function testSave() {
        $id1 = CM_Db_Db::insert('mock_modelStorageAdapter', array('foo' => 'foo1', 'bar' => 1, 'baz' => 1, 'qux' => 2));
        $id2 = CM_Db_Db::insert('mock_modelStorageAdapter', array('foo' => 'foo2', 'bar' => 2, 'baz' => 1, 'qux' => 3));
        $type = 99;

        $adapter = $this->getMockBuilder('CM_Model_StorageAdapter_Database')->setMethods(array('_getTableName'))->getMock();
        $adapter->expects($this->any())->method('_getTableName')->will($this->returnValue('mock_modelStorageAdapter'));
        /** @var CM_Model_StorageAdapter_Database $adapter */

        $adapter->save($type, array('id' => $id1), array('foo' => 'hello', 'bar' => 55));
        $this->assertRow('mock_modelStorageAdapter', array('id' => $id1, 'foo' => 'hello', 'bar' => '55'));
        $this->assertRow('mock_modelStorageAdapter', array('id' => $id2, 'foo' => 'foo2', 'bar' => '2'));

        $adapter->save($type, array('id' => $id1, 'foo' => '9999'), array('foo' => 'world', 'bar' => 66));
        $this->assertNotRow('mock_modelStorageAdapter', array('id' => $id1, 'foo' => 'world', 'bar' => '66'));
    }

    public function testCreate() {
        $type = 99;

        $adapter = $this->getMockBuilder('CM_Model_StorageAdapter_Database')->setMethods(array('_getTableName'))->getMock();
        $adapter->expects($this->any())->method('_getTableName')->will($this->returnValue('mock_modelStorageAdapter'));
        /** @var CM_Model_StorageAdapter_Database $adapter */

        $id = $adapter->create($type, array('foo' => 'foo1', 'bar' => 23));
        $this->assertInternalType('array', $id);
        $this->assertCount(1, $id);
        $this->assertArrayHasKey('id', $id);
        $this->assertInternalType('int', $id['id']);
        $this->assertRow('mock_modelStorageAdapter', array('id' => $id['id'], 'foo' => 'foo1', 'bar' => '23'));
    }

    public function testReplace() {
        $type = 99;

        $adapter = $this->getMockBuilder('CM_Model_StorageAdapter_Database')->setMethods(array('_getTableName'))->getMock();
        $adapter->expects($this->any())->method('_getTableName')->will($this->returnValue('mock_modelStorageAdapter'));
        /** @var CM_Model_StorageAdapter_Database $adapter */

        $id1 = $adapter->replace($type, array('foo' => 'foo1', 'bar' => 23, 'baz' => 1, 'qux' => 3));
        $this->assertInternalType('array', $id1);
        $this->assertCount(1, $id1);
        $this->assertArrayHasKey('id', $id1);
        $this->assertInternalType('int', $id1['id']);
        $this->assertRow('mock_modelStorageAdapter', array('id' => $id1['id'], 'foo' => 'foo1', 'bar' => '23', 'baz' => '1', 'qux' => '3'));

        $id2 = $adapter->replace($type, array('foo' => 'foo2', 'bar' => 24, 'baz' => 1, 'qux' => 3));
        $this->assertInternalType('array', $id2);
        $this->assertCount(1, $id2);
        $this->assertArrayHasKey('id', $id2);
        $this->assertInternalType('int', $id2['id']);
        $this->assertNotRow('mock_modelStorageAdapter', array('id' => $id1['id']));
        $this->assertRow('mock_modelStorageAdapter', array('id' => $id2['id'], 'foo' => 'foo2', 'bar' => '24', 'baz' => '1', 'qux' => '3'));
    }

    public function testDelete() {
        $type = 99;

        $adapter = $this->getMockBuilder('CM_Model_StorageAdapter_Database')->setMethods(array('_getTableName'))->getMock();
        $adapter->expects($this->any())->method('_getTableName')->will($this->returnValue('mock_modelStorageAdapter'));
        /** @var CM_Model_StorageAdapter_Database $adapter */

        $id = $adapter->create($type, array('foo' => 'foo1', 'bar' => 23));
        $this->assertRow('mock_modelStorageAdapter', array('id' => $id['id'], 'foo' => 'foo1', 'bar' => '23'));

        $adapter->delete($type, $id);
        $this->assertNotRow('mock_modelStorageAdapter', array('id' => $id['id'], 'foo' => 'foo1', 'bar' => '23'));
    }

    public function testFindByData() {
        $id1 = CM_Db_Db::insert('mock_modelStorageAdapter', array('foo' => 'foo1', 'bar' => 1, 'baz' => 1, 'qux' => 2));
        $id2 = CM_Db_Db::insert('mock_modelStorageAdapter', array('foo' => 'foo2', 'bar' => 2, 'baz' => 1, 'qux' => 3));
        $type = 99;

        $adapter = $this->getMockBuilder('CM_Model_StorageAdapter_Database')->setMethods(array('_getTableName'))->getMock();
        $adapter->expects($this->any())->method('_getTableName')->will($this->returnValue('mock_modelStorageAdapter'));
        /** @var CM_Model_StorageAdapter_Database $adapter */

        $this->assertSame(array('id' => $id1), $adapter->findByData($type, array('foo' => 'foo1')));
        $this->assertSame(array('id' => $id1), $adapter->findByData($type, array('bar' => 1)));
        $this->assertSame(array('id' => $id1), $adapter->findByData($type, array('foo' => 'foo1', 'bar' => 1)));
        $this->assertNull($adapter->findByData($type, array('foo' => 'foo2', 'bar' => 1)));
    }
}

class CMTest_ModelMock_1 extends CM_Model_Abstract {

}

class CMTest_ModelMock_2 extends CM_Model_Abstract {

    public static function getTableName() {
        return 'custom_table';
    }
}
