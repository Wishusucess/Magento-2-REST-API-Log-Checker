<?php
/**
 * Developer: Hemant Singh Magento 2x Developer
 * Category:  Wishusucess_WebApiLog Get Product Image URL Using REST API
 * Website:   http://www.wishusucess.com/
 */
namespace Wishusucess\WebApiLog\Plugin\Rest;

class Api
{
    /** @var \Wishusucess\WebApiLog\Logger\Handler */
    protected $logger;

    /** @var array */
    protected $currentRequest;

    /**
     * Rest constructor.
     * @param \Wishusucess\WebApiLog\Logger\Handler $logger
     */
    public function __construct(\Wishusucess\WebApiLog\Logger\Handler $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Webapi\Controller\Rest $subject
     * @param callable $proceed
     * @param \Magento\Framework\App\RequestInterface $request
     * @return mixed
     */
    public function aroundDispatch(
        \Magento\Webapi\Controller\Rest $subject,
        callable $proceed,
        \Magento\Framework\App\RequestInterface $request
    ) {
        try {
            $this->currentRequest = [
                'is_api' => true,
                'is_auth' => $this->isAuthorizationRequest($request->getPathInfo()),
                'request' => [
                    'method' => $request->getMethod(),
                    'uri' => $request->getRequestUri(),
                    'version' => $request->getVersion(),
                    'headers' => [],
                    'body' => '',
                ],
                'response' => [
                    'headers' => [],
                    'body' => '',
                ],
            ];
            foreach ($request->getHeaders()->toArray() as $key => $value) {
                $this->currentRequest['request']['headers'][$key] = $value;
            }
            $this->currentRequest['request']['body'] = $this->currentRequest['is_auth'] ?
                'Request body is not available for authorization requests.' :
                $request->getContent();
        } catch (\Exception $exception) {
            $this->logger->debug(sprintf(
                'Exception when logging API request: %s (%s::%s)',
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine()
            ));
        }

        return $proceed($request);
    }


    public function afterSendResponse(
        \Magento\Framework\Webapi\Rest\Response $subject,
        $result
    ) {
        try {
            foreach ($subject->getHeaders()->toArray() as $key => $value) {
                $this->currentRequest['response']['headers'][$key] = $value;
            }
            $this->currentRequest['response']['body'] = $this->currentRequest['is_auth'] ?
                'Response body is not available for authorization requests.' :
                $subject->getBody();
            $this->logger->debug('', $this->currentRequest);
        } catch (\Exception $exception) {
            $this->logger->debug('Exception when logging API response: ' . $exception->getMessage());
        }

        return $result;
    }

    /**
     * @param string $path
     * @return bool
     */
    protected function isAuthorizationRequest(string $path) : bool
    {
        return preg_match('/integration\/(admin|customer)\/token/', $path) !== 0;
    }
}
