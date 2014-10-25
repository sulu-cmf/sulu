<?php

namespace Sulu\Component\Content;

use Sulu\Component\Content\Mapper\ContentMapperRequest;

class ContentMapperRequestTest extends \PHPUnit_Framework_TestCase
{
    public function testRequest()
    {
        $request = ContentMapperRequest::create('page');

        foreach (array(
            'data' => 'Foobar data',
            'templateKey' => 'template_key',
            'webspaceKey' => 'webspace_key',
            'locale' => 'language_code',
            'userId' => 5,
            'partialUpdate' => true,
            'uuid' => '1234',
            'parentUuid' => '4321',
            'state' => 2,
            'isShadow' => true,
            'shadowBaseLanguage' => 'de'
            ) as $key => $value) 
        {
            $request->{'set' . ucfirst($key)}($value);
            $res = $request->{'get' . ucfirst($key)}();
            $this->assertEquals($value, $res);
        }
    }

}
