<?php
namespace Twinsen\AmazonMwsRepricing;

use Aws\CloudFront\Exception\Exception;
use Aws\Sqs\SqsClient;
use Twinsen\AmazonMwsRepricing\Models\AwsSqsConfigInterface;
use Twinsen\AmazonMwsRepricing\Models\PriceChangeMessage;


/**
 * Class MwsClient
 * @package Twinsen\AmazonRepricer\AmazonMwsRepricing
 */
class AwsSqsService
{
    /**
     * @var AwsSqsConfigInterface
     */
    public $config;
    /**
     * @var SqsClient
     */
    public $service;

    public function connect(AwsSqsConfigInterface $config)
    {
        $this->service = SqsClient::factory(array(
            'credentials' => array(
                'key' => $config->getAccessKey(),
                'secret' => $config->getSecretKey(),

            ),
            'region' => $config->getRegion(),
        ));


        /*

        //var_dump();
        var_dump($response);


        echo $queueUrl;


        */
    }

    public function getQueues()
    {
        $response = $this->service->listQueues(array());

        return $response->get('QueueUrls');
    }

    public function addQueue($queueName)
    {
        $result = $this->service->createQueue(array('QueueName' => $queueName));
        $queueUrl = $result->get('QueueUrl');

        return $queueUrl;
    }

    /**
     * @param $queueUrl
     * @param int $maximalMessages
     * @return PriceChangeMessage[]
     */

    public function getPriceUpdates($queueUrl, $maximalMessages = 1)
    {
        $messages = $this->receiveMessages($queueUrl, $maximalMessages);
        $dataProcessor = new \Twinsen\AmazonMwsRepricing\Processors\PriceChangeDataProcessor();
        $items = array();
        foreach ($messages as $message) {
            $items[] = $dataProcessor->processData($message);
        }

        return $items;
    }

    public function receiveMessages($queueUrl, $maximalMessages = 1)
    {
        if ($maximalMessages > 10) {
            throw new Exception('Messagenumber should be not more than 10');
        }
        $result = $this->service->receiveMessage(array(
            'QueueUrl' => $queueUrl,
            'MaxNumberOfMessages' => $maximalMessages
        ));
        $returnMessages = array();
        $resultMessages = $result->getPath('Messages/*/Body');

        if ($resultMessages != null) {
            foreach ($resultMessages as $messageBody) {
                // Do something with the message
                //echo $messageBody;
                $returnMessages[] = $messageBody;
            }
        }

        return $returnMessages;
    }

    public function addAmazonPermission()
    {
        // TODO: Add Support for Adding Amazon Permission
        $this->service->addPermission(array(
            // QueueUrl is required
            'QueueUrl' => 'string',
            // Label is required
            'Label' => 'string',
            // AWSAccountIds is required
            'AWSAccountIds' => array('437568002678'),
            // Actions is required
            'Actions' => array(),
        ));

    }


}