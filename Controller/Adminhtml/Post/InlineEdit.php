<?php

declare(strict_types=1);

namespace MageOS\Blog\Controller\Adminhtml\Post;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Api\UrlKeyGeneratorInterface;
use MageOS\Blog\Model\Post;
use MageOS\Blog\Model\UrlKeyGenerator\UrlKeyResolver;

class InlineEdit extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'MageOS_Blog::post';

    public function __construct(
        Context $context,
        private readonly PostRepositoryInterface $repository,
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

        foreach ($items as $postId => $changes) {
            try {
                $post = $this->repository->getById((int) $postId);
                $changes = (array) $changes;
                if ($post instanceof Post) {
                    foreach ($changes as $key => $value) {
                        if ($key === 'url_key') {
                            continue;
                        }
                        $post->setData((string) $key, $value);
                    }
                }
                // Blanking the url_key cell keeps the current slug rather than storing '', and
                // an edited title never rewrites the slug behind the editor's back.
                if (\array_key_exists('url_key', $changes)) {
                    $post->setUrlKey($this->urlKeyResolver->resolve(
                        (string) $changes['url_key'],
                        $post->getTitle(),
                        UrlKeyGeneratorInterface::ENTITY_POST,
                        $post->getUrlKey()
                    ));
                }
                $this->repository->save($post);
            } catch (\Throwable $e) {
                $error = true;
                $messages[] = (string) __('[Post ID: %1] %2', $postId, $e->getMessage());
            }
        }

        return $result->setData([
            'messages' => $messages,
            'error' => $error,
        ]);
    }
}
