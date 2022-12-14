<?php

declare(strict_types=1);

require_once '../lib/User.php';

use Domain\User\LoginUser as UserClassToTest;

use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testUserCreation(): void
    {
        $testEncryptedPassword = "mYNotSoSecretPassword";

        $newUser = new UserClassToTest("dpauley", $testEncryptedPassword);

        $this->assertSame("dpauley", $newUser->getUserName());

        $testPassword = $newUser->getPassWord();

        $this->assertTrue(password_verify($testEncryptedPassword, $testPassword));
    }
}
