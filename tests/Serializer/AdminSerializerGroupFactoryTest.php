<?php
declare(strict_types=1);

namespace App\Tests\Serializer;

use App\Entity\User;
use App\Serializer\AdminSerializerGroupFactory;
use PHPUnit\Framework\TestCase;

class AdminSerializerGroupFactoryTest extends TestCase
{

    /**
     * @var AdminSerializerGroupFactory
     */
    private $adminSerializerGroupFactory;

    public function validResourceClassProvider(): array {
        return [
            'class constat' => [
                User::class,
                'read',
                'UserAdminRead',
            ],
            'complex string' => [
                '\\App\\Entity\\Project',
                'Write',
                'ProjectAdminWrite',
            ],
            'direct string' => [
                'Article',
                'update',
                'ArticleAdminUpdate',
            ],
        ];
    }


    /**
     * @dataProvider validResourceClassProvider
     *
     * @param string $resourceClass
     * @param string $postfix
     * @param string $expected
     */
    public function testCreateAdminSerializerGroup(string $resourceClass, string $postfix, string $expected) {
        $this->assertSame($expected, $this->adminSerializerGroupFactory->createAdminGroup($resourceClass, $postfix));
    }

    protected function setUp()
    {
        $this->adminSerializerGroupFactory = new AdminSerializerGroupFactory();
    }
}