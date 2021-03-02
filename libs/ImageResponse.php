<?php

use Nette\Application\IResponse;
use Nette\Http\IRequest;
use Nette\Object;

class ImageResponse extends Object implements IResponse
{
  private $source;
  private $mime;

  function __construct($source, $mime = 'image/png')
  {
    $this->source = $source;
    $this->mime = $mime;
  }

  final function getSource()
  {
    return $this->source;
  }

  final function getMime()
  {
    return $this->mime;
  }

  function send(IRequest $httpRequest, Nette\Http\IResponse $httpResponse)
  {
    if ($this->mime) {
      $httpResponse->setContentType($this->mime);
    }
    echo $this->source;
  }
}
