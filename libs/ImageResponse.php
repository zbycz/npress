<?php

class ImageResponse extends Object implements IPresenterResponse
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

  function send(IHttpRequest $httpRequest, IHttpResponse $httpResponse)
  {
    if ($this->mime) {
      $httpResponse->setContentType($this->mime);
    }
    echo $this->source;
  }
}
?>
