<?php

declare(strict_types=1);

namespace Domain\User;

abstract class User implements \JsonSerializable
{
    private string $userName;
    private string $passWord;

    public function __construct(string $user, string $pass)
    {
        $this->userName = $user;
        $this->passWord = password_hash($pass, PASSWORD_DEFAULT);
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function getPassWord(): string
    {
        return $this->passWord;
    }

    public static function verifyUserPassword(string $submittedPassWord, string $hashedPassword): bool
    {
        return password_verify($submittedPassWord, $hashedPassword);
    }

    public function jsonSerialize(): array
    {
        return array(
            '_user' => $this->userName,
        );
    }

    public function __toString(): string
    {
        return '_user: ' . $this->getUserName() . PHP_EOL;
    }
}

class LoginUser extends User
{
    //TODO
}
