<?php

namespace Swarrot\SwarrotBundle\Tests\Broker;

use PHPUnit\Framework\TestCase;
use Swarrot\SwarrotBundle\Broker\AmqpLibFactory;

class AmqpLibFactoryTest extends TestCase
{
    protected $factory;

    protected function setUp(): void
    {
        if (!class_exists('PhpAmqpLib\Connection\AMQPStreamConnection')) {
            $this->markTestSkipped('The php-amqplib/php-amqplib package is not available');
        }

        $this->factory = new class() extends AmqpLibFactory {
            public function getConnectionData(string $name): array
            {
                if (!isset($this->connections[$name])) {
                    throw new \LogicException('No connection named '.$name);
                }

                return $this->connections[$name];
            }
        };
    }

    public function testGetPublisherWithUnknownConnection()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown connection "connection". Available: []');

        $this->factory->getMessagePublisher('exchange', 'connection');
    }

    public function testAConnectionCanBeAdded()
    {
        $connectionData = [
            'host' => 'rabbitmq_host',
            'port' => 5672,
            'login' => 'rabbitmq_login',
            'password' => 'rabbitmq_password',
            'vhost' => 'swarrot',
        ];

        $this->factory->addConnection('connection', $connectionData);

        $this->assertSame($connectionData, $this->factory->getConnectionData('connection'));
    }

    public function testAConnectionCanBeAddedUsingAnUrl()
    {
        $url = 'amqp://rabbitmq_login:rabbitmq_password@rabbitmq_host:5672/swarrot';

        $this->factory->addConnection('connection', [
            'url' => $url,
        ]);

        $this->assertEquals([
            'url' => $url,
            'host' => 'rabbitmq_host',
            'port' => 5672,
            'login' => 'rabbitmq_login',
            'password' => 'rabbitmq_password',
            'vhost' => 'swarrot',
        ], $this->factory->getConnectionData('connection'));
    }

    public function testItThrowsAnExceptionIfTheUrlIsInvalid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid connection URL given: "bloup"');

        $this->factory->addConnection('connection', [
            'url' => 'bloup',
        ]);
    }
}
