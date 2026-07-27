<?php

declare(strict_types=1);

namespace MageOS\Blog\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\UrlKeyGeneratorInterface;
use MageOS\Blog\Model\Category;
use MageOS\Blog\Model\UrlKeyGenerator\UrlKeyResolver;

class InlineEdit extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'MageOS_Blog::category';

    public function __construct(
        Context $context,
        private readonly CategoryRepositoryInterface $repository,
        private readonly UrlKeyResolver $urlKeyResolver
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

        foreach ($items as $categoryId => $changes) {
            try {
                $category = $this->repository->getById((int) $categoryId);
                $changes = (array) $changes;
                if ($category instanceof Category) {
                    foreach ($changes as $key => $value) {
                        if ($key === 'url_key') {
                            continue;
                        }
                        $category->setData((string) $key, $value);
                    }
                }
                // Blanking the url_key cell keeps the current slug rather than storing '', and
                // an edited title never rewrites the slug behind the editor's back.
                if (\array_key_exists('url_key', $changes)) {
                    $category->setUrlKey($this->urlKeyResolver->resolve(
                        (string) $changes['url_key'],
                        $category->getTitle(),
                        UrlKeyGeneratorInterface::ENTITY_CATEGORY,
                        $category->getUrlKey()
                    ));
                }
                $this->repository->save($category);
            } catch (\Throwable $e) {
                $error = true;
                $messages[] = (string) __('[Category ID: %1] %2', $categoryId, $e->getMessage());
            }
        }

        return $result->setData([
            'messages' => $messages,
            'error' => $error,
        ]);
    }
}
