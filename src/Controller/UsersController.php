<?php


namespace App\Controller;


use App\Entity\Users;
use App\Errors\CustomAssert;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Put;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use FOS\RestBundle\Controller\Annotations\View;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
     *
     * @param UsersRepository $usersRepository
     * @param Request $request
     * @return Users[]
     */
    public function All(UsersRepository $usersRepository, Request $request)
    {
        $limit = 3;
        $offset = $request->query->get('offset', 0);

        return $usersRepository->findBy(['client_id' => $this->getUser()->getName()], [], $limit, $offset);

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
     * )
     *
     * @param UsersRepository $usersRepository
     * @param int $id
     * @return Users[]|Response
     */
    public function One(UsersRepository $usersRepository, int $id)
    {
        $user = $usersRepository->findBy(['id' => $id]);

        if ($user == null)
        {
            throw new HttpException(404, "User not found.");
        }

        return $user;
    }

    /**
     * @Delete(
     *     path = "/users/{id}",
     *     name = "delete_user"
     * )
     * @View(
     *     statusCode = 204
     *     )
     *
     * @param EntityManagerInterface $entityManager
     * @param UsersRepository $usersRepository
     * @param int $id
     * @return Response
     */
    public function Delete(EntityManagerInterface $entityManager, UsersRepository $usersRepository, int $id): Response
    {
        $user = $usersRepository->findOneBy(['id' => $id, 'client_id'=> $this->getUser()->getName()]);

        if ($user == null)
        {
            return New Response(null, Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($user);
        $entityManager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
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
     * @throws \Exception
     */
    public function Create(EntityManagerInterface $entityManager, Users $users, ConstraintViolationList $violations, UsersRepository $usersRepository)
    {
        if (count($violations)) {
            return $this->view($violations, Response::HTTP_BAD_REQUEST);
        }

        $errors = [];
        if ($usersRepository->count(['email' => $users->getEmail(), 'client_id'=> $this->getUser()->getName()]) > 0)
        {
            $errors[] = new CustomAssert('email', "This email already exist");
        }

        if (count($errors))
        {
            return $this->view($errors, Response::HTTP_BAD_REQUEST);
        }

        $created_at = new \DateTime('now');

        $users->setCreatedAt($created_at);
        $users->setClientId($this->getUser()->getName());

        $entityManager->persist($users);
        $entityManager->flush();

        return $users;
    }

    /**
     * @Put(
     *     path = "/users/{id}",
     *     name = "update_user"
     * )
     * @View(
     *     statusCode = 200
     *     )
     * @ParamConverter("users", converter="fos_rest.request_body")
     *
     * @param EntityManagerInterface $entityManager
     * @param UsersRepository $usersRepository
     * @param int $id
     * @param Users $users
     * @param ConstraintViolationList $violations
     * @return \FOS\RestBundle\View\View
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function Update(EntityManagerInterface $entityManager, UsersRepository $usersRepository, int $id, Users $users, ConstraintViolationList $violations) : \FOS\RestBundle\View\View
    {
        $user =$usersRepository->findOneBy(['id' => $id, 'client_id'=> $this->getUser()->getName()]);

        if ($user == null)
        {
            return $this->view(null, Response::HTTP_NOT_FOUND);
        }

        if (count($violations)) {
            return $this->view($violations, Response::HTTP_BAD_REQUEST);
        }

        $errors = [];

        if ($usersRepository->CountByEMail($users->getEmail(), $this->getUser()->getName(), $id) > 0)
        {
            $errors[] = new CustomAssert('email', "This email already exist");
        }

        if (count($errors))
        {
            return $this->view($errors, Response::HTTP_BAD_REQUEST);
        }

        $user->setName($users->getName());
        $user->setEmail($users->getEmail());

        $updated_at = new \DateTime('now');

        $user->setUpdatedAt($updated_at);

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->view($user, Response::HTTP_OK);
    }

}