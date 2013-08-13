<?php

class CM_QueueTest extends CMTest_TestCase {

	public function tearDown() {
		CM_Cache_Redis::flush();
	}

	public function testConstructor() {
		try {
			$queue = new CM_Queue('');
			$this->fail('No error with empty key');
		} catch (CM_Exception_Invalid $e) {
			$this->assertTrue(true);
		}

		$queue = new CM_Queue('foo');
		$this->assertSame('foo', $queue->getKey());
	}

	public function testPushPop() {
		$queue1 = new CM_Queue('foo');
		$queue2 = new CM_Queue('bar');

		$queue1->push(12);
		$this->assertSame(12, $queue1->pop());
		$this->assertSame(false, $queue1->pop());
		$this->assertSame(false, $queue2->pop());

		$queue2->push(1);
		$queue2->push('two');
		$queue2->push(array(3 => 'three'));
		$this->assertSame(1, $queue2->pop());
		$this->assertSame('two', $queue2->pop());
		$this->assertSame(array(3 => 'three'), $queue2->pop());
		$this->assertSame(false, $queue2->pop());
		$this->assertSame(false, $queue1->pop());
	}

	public function testPushPopDelayed() {
		$queue = new CM_Queue('foo');
		$timestamp = time();
		$queue->push('bla', $timestamp);

		$this->assertSame('bla', $queue->pop($timestamp));
		$this->assertFalse($queue->pop($timestamp));

		$timeStamp1 = time();
		$timeStamp2 = time() + 10;
		$timeStamp3 = time() + 20;
		$queue->push(1, $timeStamp1);
		$queue->push('two', $timeStamp2);
		$queue->push(array(3 => 'three'), $timeStamp3);
		$this->assertSame(1, $queue->pop($timeStamp1));
		$this->assertFalse($queue->pop($timeStamp1));

		$this->assertSame('two', $queue->pop($timeStamp2));
		$this->assertFalse($queue->pop($timeStamp2));

		$this->assertSame(array(3 => 'three'), $queue->pop($timeStamp3));
		$this->assertFalse($queue->pop($timeStamp3));
	}

	public function testPopDelayedCount() {
		$queue = new CM_Queue('foo');
		$timestamp = time();
		$queue->push('bla', $timestamp);
		$queue->push('foo', $timestamp + 1);
		$queue->push('bar', $timestamp + 2);

		$result = $queue->pop($timestamp + 1, 2);
		$this->assertSame(array('bla', 'foo'), $result);
	}
}
