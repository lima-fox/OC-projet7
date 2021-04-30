<?php


namespace App\Controller;


use App\Entity\Users;
use App\Errors\CustomAssert;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use FOS\RestBundle\Controller\Annotations\View;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationList;

class UsersController extends AbstractFOSRestController
{
    /**
     * @Get(
     *     path = "/users",
     *     name = "users_all"
     * )
     * @View(
     *     statusCode = 200
     * )
     */
    public function All(UsersRepository $usersRepository, Request $request)
    {
        $limit = 10;
        $offset = $request->query->get('offset', 0);

        return $usersRepository->findBy(['client_id' => 'client1'], [], $limit, $offset);

    }

    /**
     * @Get(
     *     path = "/users/{id}",
     *     name = "users_one",
     *     requirements = {"id"="\d+"}
     * )
     * @View(
     *     statusCode = 200
     *     )
     */
    public function One(UsersRepository $usersRepository, Request $request, int $id)
    {
        $user = $usersRepository->findBy(['id' => $id]);

        if ($user == null)
        {
            return New Response(null, 404);
        }

        return $user;
    }

    /**
     * @Post(
     *     path = "/users",
     *     name = "create_user"
     * )
     * @View(
     *     statusCode = 201
     *     )
     * @ParamConverter("users", converter="fos_rest.request_body")
     * @param EntityManagerInterface $entityManager
     * @param Users $users
     * @param ConstraintViolationList $violations
     * @param UsersRepository $usersRepository
     * @return Users|\FOS\RestBundle\View\View|Response
     */
    public function Create(EntityManagerInterface $entityManager, Users $users, ConstraintViolationList $violations, UsersRepository $usersRepository)
    {
        if (count($violations)) {
            return $this->view($violations, Response::HTTP_BAD_REQUEST);
        }

        $errors = [];
        if ($usersRepository->count(['email' => $users->getEmail(), 'client_id'=> 'client1']) > 0)
        {
            $errors[] = new CustomAssert('email', "This email already exist");
        }

        if (count($errors))
        {
            return $this->view($errors, Response::HTTP_BAD_REQUEST);
        }

        $created_at = new \DateTime('now');

        $users->setCreatedAt($created_at);
        $users->setClientId('client1');

        $entityManager->persist($users);
        $entityManager->flush();

        return $users;
    }

}