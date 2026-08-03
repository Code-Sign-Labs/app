<?php

namespace App\Controller\App;

use App\Controller\CoreAbstractController;
use App\Entity\LicenseKey;
use App\Entity\Product;
use App\Enum\LicenseKeyStatusEnum;
use App\Enum\ProductStatusEnum;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Framework\Http\Objects\Request;
use Framework\Http\Objects\Response;
use Framework\Http\Validators\CSRFValidator;
use Framework\Http\ViewEngine\ViewEngineInterface;
use Throwable;

class ProductController extends CoreAbstractController
{
    protected CSRFValidator $CSRFValidator;
    public function __construct(ViewEngineInterface $viewEngine, EntityManager $entityManager)
    {
        $this->CSRFValidator = new CSRFValidator();
        parent::__construct($viewEngine, $entityManager);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function list(Request $request): Response
    {
        $user = $request->getAttributes()['user'];
        $error = $this->getFlash($request, "products.list.error");

        $productsRepository = $this->entityManager->getRepository(Product::class);
        $products = $productsRepository->findAll();

        return $this->render("app/product/list.twig", [
            'user' => $user,
            'error' => $error,
            'products' => $products
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function indexCreate(Request $request): Response
    {
        $user = $request->getAttributes()['user'];
        $error = $this->getFlash($request, "products.create.error");

        return $this->render("app/product/create.twig", [
            'user' => $user,
            'error' => $error
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function create(Request $request): Response
    {
        if(!$this->CSRFValidator->handle($request)) {
            return $this->redirect("/app/products-new");
        }

        $user = $request->getAttributes()['user'];

        $name = $request->getBody('name');
        $description = $request->getBody('description');
        $version = $request->getBody('version');
        $slug = $request->getBody('slug');

        if(!$name || !$description || !$version || !$slug) {
            $this->setFlash($request, "products.create.error", "You must fill all the fields.");
            return $this->redirect("/app/products-new");
        }

        $product = new Product();
        $product->setName($name)
            ->setDescription($description)
            ->setVersion($version)
            ->setSlug($slug)
            ->setStatus(ProductStatusEnum::active);

        try {
            $this->entityManager->persist($product);
            $this->entityManager->flush();

            $this->auditLogService->log("products.create.success", [
                'user_agent' => $request->getHeader('User-Agent'),
                'ip' => $request->getUserIp()
            ], "Product with id {$product->getId()} has been created.", $user, product: $product);
            return $this->redirect("/app/products");
        } catch (Throwable $e) {
            $this->auditLogService->log("products.create.failure", [
                'user_agent' => $request->getHeader('User-Agent'),
                'ip' => $request->getUserIp()
            ], "Product with name {$product->getName()} could not be created.", $user);

            $this->setFlash($request, "products.create.error", "Something went wrong: " . $e->getMessage());
            return $this->redirect("/app/products-new");
        }
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function indexEdit(Request $request): Response
    {
        $user = $request->getAttributes()['user'];
        $error = $this->getFlash($request, "products.edit.error");
        $productSlug = $request->getParams()['productSlug'];

        $productsRepository = $this->entityManager->getRepository(Product::class);
        $product = $productsRepository->findOneBy([
            'slug' => $productSlug
        ]);

        if(!$product) {
            return $this->redirect("/app/products");
        }

        return $this->render("app/product/edit.twig", [
            'user' => $user,
            'error' => $error,
            'product' => $product
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function edit(Request $request): Response
    {
        $productSlug = $request->getParams()['productSlug'];

        if(!$this->CSRFValidator->handle($request)) {
            $this->setFlash($request, "products.edit.error", "Invalid CSRF token.");
            return $this->redirect("/app/products/{$productSlug}/edit");
        }

        $user = $request->getAttributes()['user'];

        $name = trim($request->getBody('name'));
        $description = trim($request->getBody('description'));
        $version = trim($request->getBody('version'));
        $slug = trim($request->getBody('slug'));

        if(!$name || !$description || !$version || !$slug) {
            $this->setFlash($request, "products.edit.error", "You must fill all the fields.");
            return $this->redirect("/app/products/{$productSlug}/edit");
        }

        $productsRepository = $this->entityManager->getRepository(Product::class);
        $product = $productsRepository->findOneBy([
            'slug' => $productSlug
        ]);

        if(!$product) {
            return $this->redirect("/app/products");
        }

        $existingProduct = $productsRepository->findOneBy([
            'slug' => $slug
        ]);

        if($existingProduct && $existingProduct->getId() !== $product->getId()) {
            $this->setFlash($request, "products.edit.error", "A product with this slug already exists.");
            return $this->redirect("/app/products/{$productSlug}/edit");
        }

        try {
            $product->setName($name)
                ->setDescription($description)
                ->setVersion($version)
                ->setSlug($slug)
                ->setUpdatedAt(new \DateTime());

            $this->entityManager->flush();

            $this->auditLogService->log("products.edit.success", [
                'user_agent' => $request->getHeader('User-Agent'),
                'ip' => $request->getUserIp()
            ], "Product with id {$product->getId()} has been updated.", $user, product: $product);

            return $this->redirect("/app/products/{$slug}");
        } catch (Throwable $e) {
            $this->auditLogService->log("products.edit.failure", [
                'user_agent' => $request->getHeader('User-Agent'),
                'ip' => $request->getUserIp()
            ], "Product with name {$product->getName()} could not be updated.", $user);

            $this->setFlash($request, "products.edit.error", "Something went wrong: " . $e->getMessage());
            return $this->redirect("/app/products/{$productSlug}/edit");
        }
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function details(Request $request): Response
    {
        $productSlug = $request->getParams()['productSlug'];
        $user = $request->getAttributes()['user'];

        $productsRepository = $this->entityManager->getRepository(Product::class);
        $product = $productsRepository->findOneBy([
            'slug' => $productSlug
        ]);
        if(!$product) return $this->redirect("/app/products");

        $activationsCount = 0;
        /**
         * @var LicenseKey $license
         */
        foreach($product->getLicenses() as $license) {
            $activationsCount += $license->getActivations()->count();
        }

        $licenses = $product->getLicenses()->toArray();
        usort($licenses, function(LicenseKey $a, LicenseKey $b) {
            return $a->getCreatedAt() <=> $b->getCreatedAt();
        });
        $licenses = array_reverse(array_slice($licenses, -5));

        return $this->render("app/product/details.twig", [
            'user' => $user,
            'product' => $product,
            'active_licenses' => $product->getLicenses()->filter(function(LicenseKey $license) {
                return $license->getStatus() === LicenseKeyStatusEnum::active;
            })->count(),
            'activations_count' => $activationsCount,
            'expired_licenses' => $product->getLicenses()->filter(function(LicenseKey $license) {
                return $license->getStatus() === LicenseKeyStatusEnum::expired;
            })->count(),
            'suspended_licenses' => $product->getLicenses()->filter(function(LicenseKey $license) {
                return $license->getStatus() === LicenseKeyStatusEnum::suspended;
            })->count(),
            'licenses' => $licenses,
            'logs' => $this->auditLogService->getProductLogs($product),
        ]);
    }
}