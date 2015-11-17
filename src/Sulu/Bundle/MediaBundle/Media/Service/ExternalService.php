<?php

namespace Sulu\Bundle\MediaBundle\Media\Service;

use Guzzle\Http\Client;
use Guzzle\Http\Exception\BadResponseException;

class ExternalService implements ServiceInterface
{
    protected $externalService = [];

    protected $serializer;

    protected $logger;

    protected $client;

    /**
     * @param array
     */
    public function __construct(
        $externalService,
        $serializer,
        $logger
    ) {
        $this->externalService = $externalService;
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->client = new Client();
    }

    /**
     * send HTTP request.
     *
     * @param string $JSONstring
     * @param string $action
     * @param string $HTTPmethod
     */
    private function makeRequest($JSONstring, $action, $HTTPmethod)
    {
        foreach ($this->externalService as $key => $value) {
            try {
                $request = $this->client->$HTTPmethod($value[$action]);
                $request->setBody($JSONstring, 'application/json');
                $res = $request->send();
            } catch (BadResponseException $e) {
                $this->logger->error(
                    sprintf(
                        'External Service Notification send error: %s %s',
                        $e->getResponse()->getStatusCode(),
                        $value[$action]
                    )
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function add(array $media)
    {
        $mediaJson = $this->serializer->serialize($media, 'json');
        $this->makeRequest($mediaJson, 'add', 'post');
    }

    /**
     * {@inheritdoc}
     */
    public function update(array $media)
    {
        $mediaJson = $this->serializer->serialize($media, 'json');
        $this->makeRequest($mediaJson, 'update', 'put');
    }

    /**
     * {@inheritdoc}
     */
    public function delete(array $media)
    {
        $mediaJson = $this->serializer->serialize($media, 'json');
        $this->makeRequest($mediaJson, 'delete', 'delete');
    }
}
