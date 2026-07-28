<?php

declare(strict_types=1);

namespace MageOS\Blog\Controller\Adminhtml\Tag;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use MageOS\Blog\Api\TagRepositoryInterface;
use MageOS\Blog\Model\Tag;

class InlineEdit extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'MageOS_Blog::tag';

    public function __construct(
        Context $context,
        private readonly TagRepositoryInterface $repository
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $request = $this->getRequest();

        if (!$request instanceof HttpRequest || !$request->isXmlHttpRequest() || !$request->isPost()) {
            return $result->setData([
                'messages' => [(string) __('Invalid request.')],
                'error' => true,
            ]);
        }

        $items = (array) $request->getParam('items', []);
        if ($items === []) {
            return $result->setData([
                'messages' => [(string) __('Please correct the data sent.')],
                'error' => true,
            ]);
        }

        $messages = [];
        $error = false;

        foreach ($items as $tagId => $changes) {
            try {
                $tag = $this->repository->getById((int) $tagId);
                if ($tag instanceof Tag) {
                    foreach ((array) $changes as $key => $value) {
                        $tag->setData((string) $key, $value);
                    }
                }
                $this->repository->save($tag);
            } catch (\Throwable $e) {
                $error = true;
                $messages[] = (string) __('[Tag ID: %1] %2', $tagId, $e->getMessage());
            }
        }

        return $result->setData([
            'messages' => $messages,
            'error' => $error,
        ]);
    }
}
