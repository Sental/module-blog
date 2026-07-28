<?php

declare(strict_types=1);

namespace MageOS\Blog\Controller\Adminhtml\Author;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Model\Author;

class InlineEdit extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'MageOS_Blog::author';

    public function __construct(
        Context $context,
        private readonly AuthorRepositoryInterface $repository
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

        foreach ($items as $authorId => $changes) {
            try {
                $author = $this->repository->getById((int) $authorId);
                if ($author instanceof Author) {
                    foreach ((array) $changes as $key => $value) {
                        $author->setData((string) $key, $value);
                    }
                }
                $this->repository->save($author);
            } catch (\Throwable $e) {
                $error = true;
                $messages[] = (string) __('[Author ID: %1] %2', $authorId, $e->getMessage());
            }
        }

        return $result->setData([
            'messages' => $messages,
            'error' => $error,
        ]);
    }
}
