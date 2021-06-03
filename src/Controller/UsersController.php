<?php


namespace App\Controller;


use App\Entity\Users;
use App\Errors\CustomAssert;
use App\Errors\Error;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Put;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\FilesystemTagAwareAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use FOS\RestBundle\Controller\Annotations\View;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\Cache\ItemInterface;

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
     * @param LoggerInterface $logger
     * @param ItemInterface $item
     * @return Users[]
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function All(UsersRepository $usersRepository, Request $request, LoggerInterface $logger)
    {
        $start = microtime(true);
        $limit      = 3;
        $cache      = new FilesystemTagAwareAdapter();
        $customer   = $this->getUser();
        $offset     = $request->query->get('offset', 0);

        $users_cached = $cache->getItem(sprintf("users_%s_%d", $customer->getUsername(), $offset));
        $users_cached->tag('users');

        if (!$users_cached->isHit()) {
            $users = $usersRepository->findBy(['client_id' => $customer->getName()], [], $limit, $offset);
            $end = microtime(true);
            $users_cached->set($users);
            $users_cached->expiresAfter(24 * 3600);
            $cache->save($users_cached);
            $logger->info("users list from database, time (".$end - $start.")");
        }
        else {
            $users = $users_cached->get();
            $end = microtime(true);
            $logger->info("users list from cache,time (".$end - $start.")");
        }

        return $users;

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
     * @param LoggerInterface $logger
     * @param int $id
     * @param ItemInterface $item
     * @return \FOS\RestBundle\View\View
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\Cache\CacheException
     */
    public function One(UsersRepository $usersRepository, LoggerInterface $logger, int $id)
    {
        $cache = new FilesystemTagAwareAdapter();

        $customer = $this->getUser();

        $user_cached = $cache->getItem(sprintf("user_%s_%d", $customer->getUsername(), $id));
        $user_cached->tag('users');
        if (!$user_cached->isHit()) {
            $user = $usersRepository->findBy(['id' => $id, 'client_id'=> $customer->getUsername()]);
            $user_cached->set($user);
            $user_cached->expiresAfter(24 * 3600);
            $cache->save($user_cached);
            $logger->info("user $id from database");
        }
        else {
            $user = $user_cached->get();
            $logger->info("user $id from cache");
        }

        if ($user == null)
        {
            $error = new Error('User not found', 404);
            return $this->view($error, 404);
        }

        return $this->view($user);
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
     * @return \FOS\RestBundle\View\View
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function Delete(EntityManagerInterface $entityManager, UsersRepository $usersRepository, int $id): \FOS\RestBundle\View\View
    {
        $user = $usersRepository->findOneBy(['id' => $id, 'client_id'=> $this->getUser()->getName()]);

        if ($user == null)
        {
            $error = new Error('User not found', 404);
            return $this->view($error, 404);
        }

        $entityManager->remove($user);
        $entityManager->flush();

        $cache = new FilesystemTagAwareAdapter();
        $cache->invalidateTags(['users']);

        return $this->view(null, 204);
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
     * @throws \Psr\Cache\InvalidArgumentException
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

        $cache = new FilesystemTagAwareAdapter();
        $cache->invalidateTags(['users']);

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
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function Update(EntityManagerInterface $entityManager, UsersRepository $usersRepository, int $id, Users $users, ConstraintViolationList $violations) : \FOS\RestBundle\View\View
    {
        $user =$usersRepository->findOneBy(['id' => $id, 'client_id'=> $this->getUser()->getName()]);

        if ($user == null)
        {
            $error = new Error('User not found', 404);
            return $this->view($error, 404);
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

        $cache = new FilesystemTagAwareAdapter();
        $cache->invalidateTags(['users']);

        return $this->view($user, Response::HTTP_OK);
    }

}