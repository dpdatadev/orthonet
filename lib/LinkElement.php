<?php

/*
* Class for constructing HTML link elements
*/

class LinkElement
{
    protected string $link;
    protected string $text;

    public function __construct(string $link, string $text)
    {
        $this->link = $link;
        $this->text = $text;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function displayHTML(): string
    {
        return "<li class='list-group-item'>" . "<a href='" . $this->link . "'>" . $this->text . "</a>" . "</li>";
    }

    public function __toString(): string
    {
        return "::" . $this->getLink() . "::" . $this->getText() . "::";
    }
}
