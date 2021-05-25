<?php

namespace App\Controller;

use App\Errors\Error;
use App\Repository\ProductsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\View;


class ProductsController extends AbstractController
{
    /**
     * @Get(
     *     path = "/products",
     *     name = "products_all"
     * )
     * @View(
     *     statusCode = 200
     * )
     *
     * @param ProductsRepository $productsRepository
     * @param Request $request
     * @return \App\Entity\Products[]
     */
    public function All(ProductsRepository $productsRepository, Request $request)
    {
        $limit = 3;
        $offset = $request->query->get('offset', 0);

        return $productsRepository->findBy([],[], $limit, $offset);
    }

    /**
     * @Get(
     *     path = "/products/{id}",
     *     name = "products_one",
     *     requirements = {"id"="\d+"}
     * )
     * @View(
     *     statusCode = 200
     *     )
     * @param ProductsRepository $productsRepository
     * @param int $id
     * @return \App\Entity\Products|Response
     */
    public function One(ProductsRepository $productsRepository, int $id)
    {
        $product = $productsRepository->findOneBy(['id' => $id]);

        if ($product == null)
        {
            $error = new Error('Product not found', 404);
            return $this->view($error, 404);
        }

        return $product;

    }
}
